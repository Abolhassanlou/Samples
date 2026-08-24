# Shift Module

The actual work a company posts, workers express interest in, and a dispatcher assigns. This is the module that answers "can a Company Admin/Dispatcher assign work to a worker" — yes, via `POST /api/shifts/{shift}/assignments`.

## MVP scope — what's deferred

This is a first pass to get the core loop working end-to-end (post → express interest → assign → confirm). Deliberately **not** included yet (see the project's `project-business-model.md` for the full design, to be implemented in later passes):

- `Event` grouping (multiple Shifts under one big event)
- `ShiftQualification` (qualification-gated visibility) — depends on the not-yet-built Employee module
- `TransportGroup` / driver assignment
- Formal `CancellationRequest` flow (24h-notice rule, mandatory reason if urgent) — cancelling after assignment is currently just a direct status update by whoever has `shifts.dispatch`
- Waitlist status when a shift fills up
- `CompanySettings.shift_visibility_mode` filtering (currently every authenticated user sees every shift)

## Install

1. Place this folder at `Modules/Shift`, `php artisan module:enable Shift`, `composer dump-autoload`.
2. Depends on **Organization** (`Branch`), **Client** (`Client`), and **Authentication** (`User`) — install those first.
3. Migrations live in `database/tenant-migrations/` — same reasoning as every other tenant-scoped module (see Authentication's README).

## Permissions used (already seeded by Authorization)

- `shifts.create` — create/edit shifts
- `shifts.dispatch` — view who's interested, assign workers, see `client_billing_rate`
- Viewing shifts and expressing/withdrawing interest require no special permission beyond being an authenticated company user.

## Endpoints

```
GET    /api/shifts
GET    /api/shifts/{shift}
POST   /api/shifts                          { branch_id, client_id?, title, ..., quantity_needed?, rate_type?, hourly_rate?, starts_at, ends_at }   [shifts.create]
PUT    /api/shifts/{shift}                                                                                                                            [shifts.create]

POST   /api/shifts/{shift}/interest         worker expresses interest
DELETE /api/shifts/{shift}/interest         worker withdraws (only while still "pending")

GET    /api/shifts/{shift}/interests        list who's interested                    [shifts.dispatch]
GET    /api/shifts/{shift}/assignments      list who's assigned                       [shifts.dispatch]
POST   /api/shifts/{shift}/assignments      { worker_id, transport_amount? }  assign a worker  [shifts.dispatch]

POST   /api/assignments/{assignment}/confirm   worker confirms their own assignment
```

## Field visibility note

`client_billing_rate` on `ShiftResource` is only returned to users with `shifts.dispatch` — Workers never see it, matching the project's rule that worker pay rate and client billing rate must never be visible to the same audience.
