# Chat Module

In-app messaging. **Feature-complete**: direct 1:1 conversations, group conversations, dispatcher broadcast, and a trackable broadcast history.

## The three conversation modes

- **Direct** (`type: direct`) — exactly two participants, e.g. a worker messaging a dispatcher/admin. `POST /api/chats/direct`.
- **Group** (`type: group`) — three or more participants, optional `title` (e.g. "Wedding Event Team"). Everyone in the group sees every message and can post. `POST /api/chats/group`.
- **Broadcast** — a dispatcher sends **one** message to **many** recipients at once (`POST /api/chats/broadcast`), but this is **not** a shared thread. Per the original design ("dispatcher broadcast to selected workers"), it fans the message out into ordinary private `direct` conversations — one per recipient (get-or-create, so a second broadcast to the same worker lands in their same ongoing thread rather than forking a new one). Each worker only ever sees their own private reply thread with the sender; workers never see each other's replies to a broadcast.

## Access control

Direct/group conversations are deliberately unrestricted for the MVP — any two (or more) authenticated users in the same company can start one, and only conversation participants can read/send within it. Broadcast (sending one, and browsing the history below) is the exception, gated by `shifts.dispatch`.

## Admin inbox mode — `GET /api/chats?inbox_only=1`

A company-wide announcement often isn't answered as a direct reply to that specific message — a worker might use the same ongoing thread to message the admin about something else entirely, whenever they need to. From the admin's side, what actually matters when checking messages is "which workers have written to me, and what did they last say" — not the admin's own most recent word in each thread, and not the plain default index()'s "whatever this conversation's last activity was" (which would otherwise bump a conversation to the top and preview the admin's own outgoing broadcast right back at them the moment they send it, before anyone's even seen it).

`inbox_only=1` changes three things, only for the requesting admin's own view, and only when passed — the plain `GET /api/chats` (what worker-portal always uses, unchanged) still behaves exactly as before:

1. **Filtered** — only conversations where the *other* participant has sent at least one message ever. A conversation still sitting at "I sent a broadcast, nobody's replied yet" doesn't appear here at all.
2. **Previewed** by the other participant's own last message, not the admin's.
3. **Sorted** by that message's timestamp, not the conversation's general `updated_at` — so sending a broadcast to 50 workers never reorders this list on its own; only an actual reply does.

Where "unanswered" broadcasts still are: **Broadcast history** below — that's the view built for "what did I send and who hasn't answered," which the inbox is deliberately not trying to also be.

## 30-day rolling archive for broadcast messages

A worker's direct conversation with the admin accumulates every broadcast ever sent to them on top of their own private back-and-forth — over months, that's a thread with hundreds of old announcements between the handful of messages either person actually typed themselves. `GET /api/chats/{conversation}/messages` filters this by default: a message with `broadcast_id` set (a recipient's copy of a broadcast — see below) older than 30 days (rolling from *now*, not a calendar-month boundary) is left out. A message with `broadcast_id` null — anything either side typed directly, including a reply to an old broadcast — is **never** filtered, however old. Pass `?include_archived=1` to see the full history, broadcasts included; nothing is ever deleted by this, it's purely a display filter, and fully reversible per-request.

`chat_messages.broadcast_id` (nullable, `nullOnDelete` so deleting a `ChatBroadcast` — not currently exposed anywhere, but the constraint is there regardless — doesn't cascade-delete the messages it produced) is what makes a message identifiable as broadcast-origin at all; `ChatController::broadcast()` sets it on each recipient's copy at creation time. `ChatMessageResource` also exposes `is_broadcast` (just `broadcast_id !== null`) so a frontend can label these if it wants to — that flag plays no role in the filter itself, which reads `broadcast_id` directly.

## Broadcast history — separate from the conversations themselves

A broadcast's replies land in the recipient's ordinary `direct` conversation with the sender — the same thread that also carries whatever day-to-day messages came before or after it. That's the right place for the *conversation*, but it means there was no way to answer "who did I send THIS specific announcement to, and who's replied since" without wading through each thread by hand, especially once a company has enough workers that a broadcast's replies are scattered across dozens of otherwise-unrelated conversations.

Two tables solve this without touching how broadcast() actually sends messages:

- **`ChatBroadcast`** (`chat_broadcasts`) — one row per `POST /api/chats/broadcast` call: who sent it, the message, when.
- **`ChatBroadcastRecipient`** (`chat_broadcast_recipients`) — one row per recipient of that broadcast, pointing at which `direct` conversation their copy landed in. No stored "replied" flag — see below.

```
GET /api/chats/broadcasts               every past broadcast, newest first, with a recipient_count   [shifts.dispatch]
GET /api/chats/broadcasts/{broadcast}   that broadcast's full recipient list, each with has_replied   [shifts.dispatch]
```

**`has_replied` is computed live**, not stored: for each recipient, `broadcastShow()` checks whether their conversation has any `ChatMessage` from them timestamped *after* the broadcast's own `created_at`. This is deliberate — a stored flag would need updating every time a reply comes in (and un-updating if a later broadcast to the same person resets the question), while a live check is always correct and costs one query per recipient, which is trivial at the scale a single broadcast reaches. Company-wide visibility, not just the sender's own broadcasts — `shifts.dispatch` is the only gate, so one dispatcher can see what another already sent before sending something similar.

## Automatic per-Event team chat (no changes to Shift needed)

Mirrors the Notification module's design exactly: `ChatServiceProvider` calls `Assignment::observe(AssignmentObserver::class)`. Shift has no idea this module exists. Whenever a worker is assigned to a Shift that belongs to an Event:

- If that Event doesn't have a team chat yet, one is created automatically — a `group` conversation titled `"{Event title} Team"`, with the dispatcher who made the assignment as the first participant.
- The newly assigned worker is added to it.
- Every subsequent assignment to any Shift under that same Event adds that worker to the same conversation — independent of any admin manually creating a chat.

This conversation is an ordinary `group` conversation in every other respect (anyone in it can post/see messages via the same endpoints below) — it's just created and populated automatically instead of by hand.

`chat_conversations.event_id` is a real foreign key to Shift's `events` table (`nullOnDelete` — deleting an Event doesn't take its team chat down with it, the link just clears). "Shift has no idea this module exists" above is about the *application-level* observer pattern, not the schema — Chat does have a hard DB-level dependency on Shift's `events` table existing, which is exactly why Install, below, lists Shift as something to set up first.

## Install

1. Place this folder at `Modules/Chat`, `php artisan module:enable Chat`, `composer dump-autoload`.
2. Depends on **Authentication** (`User`) and **Shift** (`Assignment`, `Shift`, `Event` — for the automatic per-Event chat observer) — install those first.
3. Migrations live in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## Endpoints

```
GET  /api/chats                       every conversation the current user is part of (newest activity first). ?inbox_only=1 switches to the admin-facing inbox view — see "Admin inbox mode" above
POST /api/chats/direct                { user_id }                        get-or-create a direct conversation with that user
POST /api/chats/group                 { user_ids: [...], title? }         start a group conversation (requester + user_ids, 3+ total)
POST /api/chats/broadcast             { user_ids: [...], message }        [shifts.dispatch] fan one message out to many private direct threads; also records it for the history endpoints below
GET  /api/chats/broadcasts            every past broadcast, newest first   [shifts.dispatch]
GET  /api/chats/broadcasts/{broadcast}   one broadcast's recipients + reply status   [shifts.dispatch]
GET  /api/chats/{conversation}/messages   broadcast-origin messages older than 30 days are left out by default — ?include_archived=1 to see everything (see "30-day rolling archive" above)
POST /api/chats/{conversation}/messages   { message }
```

## Example flows

**Direct:**
```
Worker: POST /chats/direct { user_id: <dispatcher's id> }   -> conversation id 1
Worker: POST /chats/1/messages { message: "Can I swap shifts?" }
```

**Group** (e.g. everyone on one Event's staff):
```
Dispatcher: POST /chats/group { user_ids: [2,3,4], title: "Wedding Event Team" }
Any member: POST /chats/{id}/messages { message: "Meet at the venue 30 min early" }
```

**Broadcast** (e.g. a schedule reminder to everyone on a shift):
```
Dispatcher: POST /chats/broadcast { user_ids: [2,3,4], message: "Reminder: 8am start tomorrow" }
-> creates/reuses 3 separate direct conversations, each gets its own copy of the message, plus one ChatBroadcast + 3 ChatBroadcastRecipient rows
Worker 2 replies in their own thread with the dispatcher — workers 3 and 4 never see it.
Dispatcher: GET /chats/broadcasts/{id} -> sees worker 2 marked has_replied: true, 3 and 4 still false
```
