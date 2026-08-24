# Shift Module

The actual work a company posts, workers express interest in, and a dispatcher assigns. This is the module that answers "can a Company Admin/Dispatcher assign work to a worker" — yes, via `POST /api/shifts/{shift}/assignments`.

## Event grouping and dynamic per-role headcounts (new)

- **`Event`** — groups multiple Shifts under one big occasion sharing a client/location (e.g. a wedding needing separate Shifts for drivers and guards). Fully optional — a Shift doesn't need an Event.
- **`ShiftRole`** — a dynamic, company-defined catalog of position types (e.g. "Driver", "Coordinator", "Team Lead"). Deliberately **not** a fixed enum — same philosophy as Employee's `Qualification` catalog: every company defines its own roles, nothing is hardcoded.
- **`ShiftPosition`** — says "this Shift needs N of role X", with an optional per-position `hourly_rate` override (e.g. drivers paid differently than guards on the same shift). Fully optional.

**Backward compatibility:** a Shift with zero `ShiftPosition` rows works exactly as before — plain `quantity_needed` headcount, no role distinction, `worker_id`-only assignment. `Shift::isFull()` automatically switches to "every position full" only once a Shift actually has positions. Nothing about existing Shifts/Assignments changes.

## Still deferred (see `project-business-model.md` for the full design)

- `TransportGroup` / driver-vehicle assignment
- Formal `CancellationRequest` flow (24h-notice rule, mandatory reason if urgent) — cancelling after assignment is currently just a direct status update by whoever has `shifts.dispatch`
- Waitlist status when a shift/position fills up
- `CompanySettings.shift_visibility_mode` filtering by worker qualification (currently every authenticated user sees every shift)

## Install

1. Place this folder at `Modules/Shift`, `php artisan module:enable Shift`, `composer dump-autoload`.
2. Depends on **Organization** (`Branch`), **Client** (`Client`), and **Authentication** (`User`) — install those first.
3. Migrations live in `database/tenant-migrations/` — same reasoning as every other tenant-scoped module (see Authentication's README).

## Permissions used (already seeded by Authorization)

- `shifts.create` — create/edit shifts, events, shift roles, and positions
- `shifts.dispatch` — view who's interested, assign workers, see `client_billing_rate`
- Viewing (shifts, events, roles) and expressing/withdrawing interest require no special permission beyond being an authenticated company user.

## Endpoints

```
GET    /api/events
GET    /api/events/{event}
GET    /api/events/{event}/shifts                every Shift under this Event
POST   /api/events                          { branch_id, client_id?, title, starts_at, ends_at, ... }   [shifts.create]
PUT    /api/events/{event}                                                                                [shifts.create]

GET    /api/shift-roles
POST   /api/shift-roles                     { name, description? }                                       [shifts.create]
PUT    /api/shift-roles/{shift_role}                                                                       [shifts.create]
DELETE /api/shift-roles/{shift_role}                                                                        [shifts.create]

GET    /api/shifts
GET    /api/shifts/{shift}
POST   /api/shifts                          { event_id?, branch_id, client_id?, title, ..., quantity_needed?, rate_type?, hourly_rate?, starts_at, ends_at }   [shifts.create]
PUT    /api/shifts/{shift}                                                                                                                            [shifts.create]

GET    /api/shifts/{shift}/positions
POST   /api/shifts/{shift}/positions        { shift_role_id?, quantity_needed, hourly_rate? }             [shifts.create]
PUT    /api/shifts/{shift}/positions/{position}                                                             [shifts.create]
DELETE /api/shifts/{shift}/positions/{position}                                                             [shifts.create]

POST   /api/shifts/{shift}/interest         worker expresses interest
DELETE /api/shifts/{shift}/interest         worker withdraws (only while still "pending")

GET    /api/shifts/{shift}/interests        list who's interested                    [shifts.dispatch]
GET    /api/shifts/{shift}/assignments      list who's assigned                       [shifts.dispatch]
POST   /api/shifts/{shift}/assignments      { worker_id, shift_position_id?, transport_amount? }  assign a worker (shift_position_id required only if the shift has positions)  [shifts.dispatch]

POST   /api/assignments/{assignment}/confirm   worker confirms their own assignment
```

## Field visibility note

`client_billing_rate` on `ShiftResource` is only returned to users with `shifts.dispatch` — Workers never see it, matching the project's rule that worker pay rate and client billing rate must never be visible to the same audience.
