# Shift Module

The actual work a company posts, workers express interest in, and a dispatcher assigns. This is the module that answers "can a Company Admin/Dispatcher assign work to a worker" — yes, via `POST /api/shifts/{shift}/assignments`. **This module is now feature-complete** against the original design (see `project-business-model.md`).

## Event grouping and dynamic per-role headcounts

- **`Event`** — groups multiple Shifts under one big occasion sharing a client/location (e.g. a wedding needing separate Shifts for drivers and guards). Fully optional.
- **`ShiftRole`** — a dynamic, company-defined catalog of position types (e.g. "Driver", "Coordinator"). Deliberately **not** a fixed enum — same philosophy as Employee's `Qualification` catalog.
- **`ShiftPosition`** — "this Shift needs N of role X", with an optional per-position `hourly_rate` override.

**Backward compatibility:** a Shift with zero `ShiftPosition` rows works exactly as before — plain `quantity_needed` headcount, no role distinction. `Shift::isFull()` automatically switches to "every position full" only once a Shift actually has positions.

## Waitlisting

`ShiftInterestController::store()` no longer rejects interest when a shift/position is already full — it records the interest as `waitlisted` instead of `pending`. A dispatcher can still see waitlisted workers (`GET /api/shifts/{shift}/interests`) and manually assign one if a spot opens (e.g. after an approved cancellation).

## Formal cancellation (24-hour rule)

`CancellationRequest` — a worker requests cancellation of their own assignment (`POST /api/assignments/{assignment}/cancellation-request`); it does **not** cancel immediately. `is_urgent` is computed automatically (true if less than 24 hours remain before the shift starts), and a `reason` becomes mandatory in that case. A dispatcher must review and approve/reject (`shifts.dispatch`). Approving flips the Assignment to `cancelled` and automatically reopens the slot — `Shift`/`ShiftPosition::isFull()` only count `confirmed` assignments, so nothing else needs to happen except correcting the Shift's own status label if it had been `filled`.

## Transport (per-vehicle, per-Event)

`TransportGroup` — one vehicle for an Event, with a designated driver (that driver's own `Assignment`, typically to a "Driver"-role `ShiftPosition`) and a list of other workers' `Assignment`s riding along (`transport_group_passengers` pivot). Scoped under `/api/events/{event}/transport-groups`, gated by `shifts.dispatch` (assigning transport is a dispatching concern).

## Install

1. Place this folder at `Modules/Shift`, `php artisan module:enable Shift`, `composer dump-autoload`.
2. Depends on **Organization** (`Branch`), **Client** (`Client`), **Authentication** (`User`), and **Employee** (`WorkerProfile`, `WorkerQualification`, `Qualification` — for shift visibility) — install those first.
3. Migrations live in `database/tenant-migrations/` — same reasoning as every other tenant-scoped module (see Authentication's README).

## Permissions used (already seeded by Authorization)

- `shifts.create` — create/edit shifts, events, shift roles, and positions
- `shifts.dispatch` — view who's interested/waitlisted, assign workers, manage transport groups, process cancellation requests, see `client_billing_rate`
- Viewing (shifts, events, roles) and expressing/withdrawing interest require no special permission beyond being an authenticated company user.

## Endpoints

```
GET    /api/events
GET    /api/events/{event}
GET    /api/events/{event}/shifts
POST   /api/events                          { branch_id, client_id?, title, starts_at, ends_at, ... }   [shifts.create]
PUT    /api/events/{event}                                                                                [shifts.create]

GET    /api/shift-roles
POST   /api/shift-roles                     { name, description? }                                       [shifts.create]
PUT    /api/shift-roles/{shift_role}                                                                       [shifts.create]
DELETE /api/shift-roles/{shift_role}                                                                        [shifts.create]

GET    /api/shifts
GET    /api/shifts/{shift}
POST   /api/shifts                          { event_id?, branch_id, client_id?, title, ..., quantity_needed?, starts_at, ends_at }   [shifts.create]
PUT    /api/shifts/{shift}                                                                                                            [shifts.create]

GET    /api/shifts/{shift}/positions
POST   /api/shifts/{shift}/positions        { shift_role_id?, quantity_needed, hourly_rate? }             [shifts.create]
PUT    /api/shifts/{shift}/positions/{position}                                                             [shifts.create]
DELETE /api/shifts/{shift}/positions/{position}                                                             [shifts.create]

POST   /api/shifts/{shift}/interest         { shift_position_id? }  worker expresses interest (waitlisted automatically if full)
DELETE /api/shifts/{shift}/interest         worker withdraws (pending or waitlisted)

GET    /api/shifts/{shift}/interests        pending + waitlisted, for the dispatcher            [shifts.dispatch]
GET    /api/shifts/{shift}/assignments                                                            [shifts.dispatch]
POST   /api/shifts/{shift}/assignments      { worker_id, shift_position_id?, transport_amount? }  [shifts.dispatch]

POST   /api/assignments/{assignment}/confirm                worker confirms their own assignment
POST   /api/assignments/{assignment}/cancellation-request   { reason? }  worker requests cancellation (reason required if <24h before start)

GET    /api/cancellation-requests                                pending queue                    [shifts.dispatch]
POST   /api/cancellation-requests/{cancellationRequest}/approve                                    [shifts.dispatch]
POST   /api/cancellation-requests/{cancellationRequest}/reject                                     [shifts.dispatch]

GET    /api/events/{event}/transport-groups
POST   /api/events/{event}/transport-groups      { driver_assignment_id, vehicle_description?, notes?, passenger_assignment_ids? }   [shifts.dispatch]
PUT    /api/events/{event}/transport-groups/{transportGroup}                                                                          [shifts.dispatch]
DELETE /api/events/{event}/transport-groups/{transportGroup}                                                                          [shifts.dispatch]
```

## Field visibility note

`client_billing_rate` on `ShiftResource` is only returned to users with `shifts.dispatch` — Workers never see it, matching the project's rule that worker pay rate and client billing rate must never be visible to the same audience.

## Shift visibility (qualification + branch access)

A Dispatcher/Admin (`shifts.dispatch`) always sees every Shift, unfiltered — full visibility is required to manage. A plain Worker only sees a Shift if **both** hold (see `Services/ShiftVisibility.php`):

1. **Access** — either:
   - the Shift's own `branch_id` is the worker's home branch (`WorkerProfile.home_branch_id`, from Employee), **or**
   - the worker has been explicitly activated for that Shift's Event via `EventWorkerAccess`.
2. **Qualification** — the worker holds *every* qualification the Shift requires (`ShiftQualification`, referencing Employee's `Qualification` catalog), **unless the Shift itself has `qualification_override: true`** — a deliberate escape hatch for staffing shortages (e.g. an unpopular night shift nobody with the right qualification wants to take). When set, this skips the qualification check entirely for that one Shift; access (branch/event) is still required either way. A Shift with no requirements at all is visible to anyone who passes the access check regardless.

Set it when creating/editing a shift: `POST /api/shifts { ..., qualification_override: true }` (`shifts.create`, same as any other shift field).

Failing either check **hides** the shift entirely (404 on direct access, absent from the list) — it is never shown disabled/greyed out, per this project's explicit design choice. This applies to `GET /api/shifts`, `GET /api/shifts/{shift}`, and `POST /api/shifts/{shift}/interest` alike.

### Cross-branch access is two steps, on purpose

By default, only a Shift's own branch can see it. To let workers from **another** branch in on an Event:

1. Someone with `shifts.dispatch` grants that whole branch visibility: `POST /api/events/{event}/branch-access { branch_id }`. This alone does **not** show anything to any worker yet.
2. That branch's own admin/dispatcher then activates specific workers, one at a time: `POST /api/events/{event}/worker-access { worker_id }`. This is rejected with a 422 if the worker's home branch hasn't been granted access in step 1 first.

### Endpoints (visibility-related)

```
GET    /api/shifts/{shift}/qualifications
POST   /api/shifts/{shift}/qualifications           { qualification_id }                [shifts.create]
DELETE /api/shifts/{shift}/qualifications/{qualification}                                [shifts.create]

GET    /api/events/{event}/branch-access
POST   /api/events/{event}/branch-access            { branch_id }                        [shifts.dispatch]
DELETE /api/events/{event}/branch-access/{branchAccess}                                   [shifts.dispatch]

GET    /api/events/{event}/worker-access
POST   /api/events/{event}/worker-access            { worker_id }                        [shifts.dispatch]
DELETE /api/events/{event}/worker-access/{workerAccess}                                   [shifts.dispatch]
```

## Still open

- Waitlisted workers aren't auto-promoted to `pending` when a slot reopens; a dispatcher currently has to notice and assign manually.
- `EventWorkerAccessController` checks that the worker's *branch* was granted access, but doesn't separately verify the acting dispatcher actually belongs to that branch (Organization's `UserBranch`) — anyone with `shifts.dispatch` company-wide can activate any worker today. Tightening this to branch-scoped dispatchers is a possible future refinement.
