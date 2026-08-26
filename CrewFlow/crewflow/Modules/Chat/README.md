# Chat Module

In-app messaging. **Feature-complete**: direct 1:1 conversations, group conversations, and dispatcher broadcast.

## The three modes

- **Direct** (`type: direct`) — exactly two participants, e.g. a worker messaging a dispatcher/admin. `POST /api/chats/direct`.
- **Group** (`type: group`) — three or more participants, optional `title` (e.g. "Wedding Event Team"). Everyone in the group sees every message and can post. `POST /api/chats/group`.
- **Broadcast** — a dispatcher sends **one** message to **many** recipients at once (`POST /api/chats/broadcast`), but this is **not** a shared thread. Per the original design ("dispatcher broadcast to selected workers"), it fans the message out into ordinary private `direct` conversations — one per recipient (reusing the same get-or-create logic as `startDirect`). Each worker only ever sees their own private reply thread with the sender; workers never see each other's replies to a broadcast. `chats/broadcast` requires `shifts.dispatch` (broadcasting is a dispatching action, reusing that existing permission rather than introducing a new one).

## Access control

Direct/group conversations are deliberately unrestricted for the MVP — any two (or more) authenticated users in the same company can start one, and only conversation participants can read/send within it. Broadcast is the one exception, gated by `shifts.dispatch`.

## Automatic per-Event team chat (no changes to Shift needed)

Mirrors the Notification module's design exactly: `ChatServiceProvider` calls `Assignment::observe(AssignmentObserver::class)`. Shift has no idea this module exists. Whenever a worker is assigned to a Shift that belongs to an Event:

- If that Event doesn't have a team chat yet, one is created automatically — a `group` conversation titled `"{Event title} Team"`, with the dispatcher who made the assignment as the first participant.
- The newly assigned worker is added to it.
- Every subsequent assignment to any Shift under that same Event adds that worker to the same conversation — independent of any admin manually creating a chat.

This conversation is an ordinary `group` conversation in every other respect (anyone in it can post/see messages via the same endpoints below) — it's just created and populated automatically instead of by hand.

## Install

1. Place this folder at `Modules/Chat`, `php artisan module:enable Chat`, `composer dump-autoload`.
2. Depends on **Authentication** (`User`) and **Shift** (`Assignment`, `Shift`, `Event` — for the automatic per-Event chat observer) — install those first.
3. Migrations live in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## Endpoints

```
GET  /api/chats                       every conversation the current user is part of (newest activity first)
POST /api/chats/direct                { user_id }                        get-or-create a direct conversation with that user
POST /api/chats/group                 { user_ids: [...], title? }         start a group conversation (requester + user_ids, 3+ total)
POST /api/chats/broadcast             { user_ids: [...], message }        [shifts.dispatch] fan one message out to many private direct threads
GET  /api/chats/{conversation}/messages
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
-> creates/reuses 3 separate direct conversations, each gets its own copy of the message
Worker 2 replies in their own thread with the dispatcher — workers 3 and 4 never see it.
```
