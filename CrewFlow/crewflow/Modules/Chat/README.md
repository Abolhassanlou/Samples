# Chat Module

In-app messaging. **MVP scope: direct 1:1 conversations only** — e.g. a worker messaging a dispatcher/admin, or vice versa. `group` (all workers on one Shift/Event) and `broadcast` (dispatcher to many recipients at once) from the original design are deferred; the schema (`chat_conversations.type`, unlimited participants per conversation) already supports adding them later without a migration change.

## Install

1. Place this folder at `Modules/Chat`, `php artisan module:enable Chat`, `composer dump-autoload`.
2. Depends on **Authentication** (`User`) only.
3. Migrations live in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## Access control

Deliberately unrestricted for the MVP — any two authenticated users in the same company can start a direct conversation with each other, and only conversation participants can read/send within it. This is an internal team chat, not something that needs fine-grained permission gating yet.

## Endpoints

```
GET  /api/chats                       every conversation the current user is part of (newest activity first)
POST /api/chats/direct                { user_id }   get-or-create a direct conversation with that user
GET  /api/chats/{conversation}/messages
POST /api/chats/{conversation}/messages   { message }
```

## Example flow

```
Worker: POST /chats/direct { user_id: <dispatcher's id> }   -> conversation id 1
Worker: POST /chats/1/messages { message: "Can I swap shifts?" }
Dispatcher: GET /chats                                       -> sees conversation 1 with a message preview
Dispatcher: POST /chats/1/messages { message: "Sure, let me check." }
```
