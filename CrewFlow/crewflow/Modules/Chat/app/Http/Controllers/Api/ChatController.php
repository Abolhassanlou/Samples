<?php

namespace Modules\Chat\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Chat\Http\Resources\ChatBroadcastDetailResource;
use Modules\Chat\Http\Resources\ChatBroadcastResource;
use Modules\Chat\Http\Resources\ChatConversationResource;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Models\ChatBroadcast;
use Modules\Chat\Models\ChatBroadcastRecipient;
use Modules\Chat\Models\ChatConversation;
use Modules\Core\Traits\ApiResponse;

class ChatController extends Controller
{
    use ApiResponse;

    /**
     * Every conversation the current user is part of, newest activity
     * first — no special permission, just needing to be a participant.
     *
     * `?inbox_only=1` switches to the admin-facing "inbox" view instead
     * — see indexInbox() below for what that changes and why.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $query = ChatConversation::whereHas('participants', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        });

        if ($request->boolean('inbox_only')) {
            return $this->indexInbox($request, $query);
        }

        $conversations = $query->with(['participants', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->get();

        return $this->success(ChatConversationResource::collection($conversations));
    }

    /**
     * The admin's "who's written to me" view — deliberately different
     * from index()'s plain/default behavior above (which worker-portal
     * still uses unchanged): a company-wide broadcast or announcement
     * often isn't answered as a reply to that specific message at all —
     * a worker might just use the same thread to message the admin
     * about something unrelated later. From the admin's side, what
     * matters is "has this worker ever written to me, and what did
     * they last say" — not the admin's own last message, and not
     * whatever the requester (the admin) happened to send most
     * recently, which would otherwise bump a conversation to the top
     * and preview the admin's OWN words back at them. Sending a
     * broadcast to 50 workers no longer floods this list with 50
     * conversations previewing the admin's own announcement — only
     * ones a worker has actually written back in surface here at all,
     * previewed and sorted by that reply, however old it is relative
     * to anything sent since.
     *
     * Conversations with nothing from the other side yet don't
     * disappear from the system — they're exactly what "Broadcast
     * history" (broadcastsIndex()/broadcastShow()) is for instead.
     */
    private function indexInbox(Request $request, $query)
    {
        $userId = $request->user()->id;

        $conversations = $query->whereHas('messages', fn ($m) => $m->where('sender_id', '!=', $userId))
            ->with('participants')
            ->get();

        $conversations->each(function ($conversation) use ($userId) {
            $lastFromOther = $conversation->messages()
                ->where('sender_id', '!=', $userId)
                ->latest('created_at')
                ->first();

            $conversation->setRelation('latestMessage', $lastFromOther ? collect([$lastFromOther]) : collect());
            $conversation->inbox_sort_key = $lastFromOther?->created_at;
        });

        $conversations = $conversations->sortByDesc('inbox_sort_key')->values();

        return $this->success(ChatConversationResource::collection($conversations));
    }

    /**
     * Get-or-create a direct (1:1) conversation between the requester and
     * another user. Deliberately unrestricted (any two users in the same
     * company can start one) — this is an internal team chat, not
     * something that needs fine-grained permission gating for the MVP.
     */
    public function startDirect(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', 'different:'.$request->user()->id],
        ]);

        $conversation = $this->getOrCreateDirect($request->user()->id, $data['user_id']);

        return $this->success(new ChatConversationResource($conversation->load('participants')));
    }

    /**
     * Create a group conversation with three or more total participants
     * (the requester + everyone in user_ids) and an optional title. Any
     * participant can post and everyone sees every message — unlike
     * broadcast(), which fans a message out into separate private threads.
     */
    public function startGroup(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:2'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $conversation = ChatConversation::create([
            'type' => 'group',
            'title' => $data['title'] ?? null,
        ]);

        $conversation->participants()->attach(array_unique([$request->user()->id, ...$data['user_ids']]));

        return $this->success(new ChatConversationResource($conversation->load('participants')), 'Group conversation started', 201);
    }

    /**
     * A dispatcher sends one message to many recipients at once. This is
     * NOT a shared thread — per the original design ("dispatcher
     * broadcast to selected workers"), each recipient only ever sees a
     * private reply thread with the sender, exactly like an ordinary
     * direct conversation (reusing getOrCreateDirect()'s get-or-create
     * logic for each recipient) — workers never see each other's
     * replies.
     *
     * Also records a ChatBroadcast + one ChatBroadcastRecipient per
     * recipient — see that migration's docblock for why: without this,
     * there'd be no way to later answer "who did I send THIS particular
     * announcement to, and who's replied since" separately from
     * whatever else has been said in each of those private threads
     * before or after.
     */
    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id', 'different:'.$request->user()->id],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $broadcast = ChatBroadcast::create([
            'sender_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        $results = [];

        foreach (array_unique($data['user_ids']) as $recipientId) {
            $conversation = $this->getOrCreateDirect($request->user()->id, $recipientId);

            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'broadcast_id' => $broadcast->id,
                'message' => $data['message'],
            ]);

            $conversation->touch();

            ChatBroadcastRecipient::create([
                'broadcast_id' => $broadcast->id,
                'user_id' => $recipientId,
                'conversation_id' => $conversation->id,
            ]);

            $results[] = ['conversation_id' => $conversation->id, 'recipient_id' => $recipientId, 'message_id' => $message->id];
        }

        return $this->success([
            'broadcast_id' => $broadcast->id,
            'recipients' => $results,
        ], 'Broadcast sent to '.count($results).' recipient(s)', 201);
    }

    /**
     * Every past broadcast, newest first — powers the admin "Broadcast
     * history" view. shifts.dispatch, same permission as sending one —
     * company-wide visibility, not just the current user's own sent
     * broadcasts, so one dispatcher can see what another already
     * announced before sending something similar.
     */
    public function broadcastsIndex(Request $request)
    {
        $broadcasts = ChatBroadcast::with('sender')
            ->withCount('recipients')
            ->orderByDesc('created_at')
            ->get();

        return $this->success(ChatBroadcastResource::collection($broadcasts));
    }

    /**
     * One broadcast's full recipient list, each flagged with whether
     * they've replied since — computed live (a message from that
     * recipient, in their conversation, timestamped after the broadcast
     * itself), not a stored flag that could drift out of sync with the
     * conversation's actual messages.
     */
    public function broadcastShow(Request $request, ChatBroadcast $broadcast)
    {
        $recipients = $broadcast->recipients()->with('user')->get();

        $recipients->each(function ($recipient) use ($broadcast) {
            $recipient->has_replied = ChatMessage::where('conversation_id', $recipient->conversation_id)
                ->where('sender_id', $recipient->user_id)
                ->where('created_at', '>', $broadcast->created_at)
                ->exists();
        });

        return $this->success(new ChatBroadcastDetailResource($broadcast->setRelation('recipients', $recipients)));
    }

    /**
     * By default, a broadcast-origin message (broadcast_id set) older
     * than 30 days is left out — a rolling window, not a calendar-month
     * cutoff, so it's always "the last 30 days" regardless of when in
     * the month someone looks. An ordinary message either side typed
     * directly (broadcast_id null) is NEVER filtered, however old —
     * only announcements accumulate enough volume over time to be worth
     * hiding by default. Pass `?include_archived=1` to see the full
     * history including older announcements (nothing is ever deleted by
     * this — it's a display filter, fully reversible per-request).
     */
    public function messages(Request $request, ChatConversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $query = $conversation->messages()->with('sender');

        if (! $request->boolean('include_archived')) {
            $query->where(function ($q) {
                $q->whereNull('broadcast_id')
                    ->orWhere('created_at', '>=', now()->subDays(30));
            });
        }

        $messages = $query->orderBy('created_at')->get();

        return $this->success(ChatMessageResource::collection($messages));
    }

    public function sendMessage(Request $request, ChatConversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        $conversation->touch();

        return $this->success(new ChatMessageResource($message->load('sender')), 'Message sent', 201);
    }

    private function authorizeParticipant(Request $request, ChatConversation $conversation): void
    {
        $isParticipant = $conversation->participants()->where('users.id', $request->user()->id)->exists();

        abort_unless($isParticipant, 403, 'You are not part of this conversation.');
    }

    /**
     * Shared by startDirect() and broadcast() (each recipient's private
     * copy) — get-or-create so replying to an old broadcast and
     * starting a fresh direct conversation both land in the same
     * ongoing thread instead of forking a new one each time.
     */
    private function getOrCreateDirect(int $userIdA, int $userIdB): ChatConversation
    {
        $existing = ChatConversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('users.id', $userIdA))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $userIdB))
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = ChatConversation::create(['type' => 'direct']);
        $conversation->participants()->attach([$userIdA, $userIdB]);

        return $conversation;
    }
}
