<?php

namespace Modules\Chat\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Chat\Http\Resources\ChatConversationResource;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Models\ChatConversation;
use Modules\Core\Traits\ApiResponse;

class ChatController extends Controller
{
    use ApiResponse;

    /**
     * Every conversation the current user is part of, newest activity
     * first — no special permission, just needing to be a participant.
     */
    public function index(Request $request)
    {
        $conversations = ChatConversation::whereHas('participants', function ($query) use ($request) {
            $query->where('users.id', $request->user()->id);
        })
            ->with(['participants', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->get();

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

        $existing = ChatConversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('users.id', $request->user()->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $data['user_id']))
            ->first();

        if ($existing) {
            return $this->success(new ChatConversationResource($existing->load('participants')));
        }

        $conversation = ChatConversation::create(['type' => 'direct']);
        $conversation->participants()->attach([$request->user()->id, $data['user_id']]);

        return $this->success(new ChatConversationResource($conversation->load('participants')), 'Conversation started', 201);
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
     * direct conversation (reusing startDirect()'s get-or-create logic
     * for each recipient) — workers never see each other's replies.
     */
    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id', 'different:'.$request->user()->id],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $results = [];

        foreach (array_unique($data['user_ids']) as $recipientId) {
            $conversation = ChatConversation::where('type', 'direct')
                ->whereHas('participants', fn ($q) => $q->where('users.id', $request->user()->id))
                ->whereHas('participants', fn ($q) => $q->where('users.id', $recipientId))
                ->first();

            if (! $conversation) {
                $conversation = ChatConversation::create(['type' => 'direct']);
                $conversation->participants()->attach([$request->user()->id, $recipientId]);
            }

            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'message' => $data['message'],
            ]);

            $conversation->touch();

            $results[] = ['conversation_id' => $conversation->id, 'recipient_id' => $recipientId, 'message_id' => $message->id];
        }

        return $this->success($results, 'Broadcast sent to '.count($results).' recipient(s)', 201);
    }

    public function messages(Request $request, ChatConversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $messages = $conversation->messages()->with('sender')->orderBy('created_at')->get();

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
}
