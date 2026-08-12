# Notification Module

Notifies workers by email when a Shift they're assigned to changes (schedule, location, cancellation), or when they're newly assigned. This is the module that proves the project's "Shift never knows Notification exists" design goal — implemented via Eloquent Observers, not by editing Shift's controllers.

## Why the Entity/table is called "Alarm", not "Notification"

Laravel already has a built-in `notifications` table (used by its own `Notification` facade's database channel). Reusing that name here risks exactly the collision documented in `project-business-model.md`, section 6.8 — same reasoning that turned the old "Job" entity into "Shift". The **module** is still called `Notification` (matches the project's module list); only the underlying model/table is `Alarm`.

## Scheduled reminders (24h and 1h before a shift starts)

`notification:send-shift-reminders` runs every 5 minutes (registered programmatically in `NotificationServiceProvider` — no edits to the project's own `routes/console.php` needed) and, for every company:

- finds every **confirmed** Assignment whose Shift starts in ~24 hours → sends a `reminder_24h` alarm
- finds every **confirmed** Assignment whose Shift starts in ~1 hour → sends a `reminder_1h` alarm

Idempotency (never sending the same reminder twice) is done by checking whether a matching `Alarm` row already exists for that worker+shift+type — deliberately **not** by adding tracking columns to Shift's own `Assignment` table, keeping with this project's rule that Shift never needs to know Notification exists.

**In production**, this requires a real cron entry running Laravel's scheduler:
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

**For local testing**, just run the command directly instead of waiting for a real cron:
```bash
php artisan notification:send-shift-reminders
```
To actually see a reminder fire locally, create/update a test Shift with `starts_at` set to roughly 24 hours (or 1 hour) from right now, confirm the assignment, then run the command above.

## How it actually works (no changes to the Shift module needed)

`NotificationServiceProvider::registerObservers()` calls `Shift::observe(ShiftObserver::class)` and `Assignment::observe(AssignmentObserver::class)` — Laravel's Eloquent observer system. Shift's own code has zero awareness this module exists:

- `Assignment` **created** → `shift_assigned` alarm to that worker
- `Shift` **updated**, `starts_at`/`ends_at` changed, and the shift has confirmed workers → `schedule_changed` alarm to each of them
- `Shift` **updated**, `location_address` changed → `location_changed` alarm
- `Shift` **updated**, `status` becomes `cancelled` → `cancelled` alarm
- Scheduled command, every 5 minutes → `reminder_24h` / `reminder_1h` alarms (see "Scheduled reminders" below)

Each alarm both writes a DB row (so a worker has an in-app notification history, `GET /api/alarms`) **and** sends a real email via `AlarmService::notify()`.

## Local email testing

By default a fresh Laravel `.env` usually has `MAIL_MAILER=log`, meaning "emails" are written to `storage/logs/laravel.log` instead of actually being sent — perfectly fine for testing this module without a real mail server. Check that file after triggering an alarm if you don't see an actual email arrive anywhere.

## Install

1. Place this folder at `Modules/Notification`, `php artisan module:enable Notification`, `composer dump-autoload`.
2. Depends on **Shift** (`Shift`, `Assignment`), **Authentication** (`User`), and **Tenancy** (`Company` — the scheduled reminder command iterates every company) — install those first.
3. Migration lives in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## Endpoints

```
GET  /api/alarms                 a worker's own notification history (newest first)
POST /api/alarms/{alarm}/read    mark one as read (only the owning worker may do this)
```

## MVP scope — what's deferred

- Only the `email` channel is implemented (`channel` column is there for when push/SMS are added later — no code change needed to the `alarms` table itself).
- No per-company opt-out/preferences yet.
- Chat (direct/group/broadcast) — from the original design — was dropped from the current 13-module list entirely; not part of this module.
