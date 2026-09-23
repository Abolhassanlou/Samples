# Shift Module

The actual work a company posts, workers express interest in, and a dispatcher assigns. This is the module that answers "can a Company Admin/Dispatcher assign work to a worker" — yes, via `POST /api/shifts/{shift}/assignments`. **This module is now feature-complete** against the original design (see `project-business-model.md`).

## Event grouping and dynamic per-role headcounts

- **`Event`** — groups multiple Shifts under one big occasion sharing a client/location (e.g. a wedding needing separate Shifts for drivers and guards). Fully optional.
- **`ShiftRole`** — a dynamic, company-defined catalog of position types (e.g. "Driver", "Coordinator"). Deliberately **not** a fixed enum — same philosophy as Employee's `Qualification` catalog.
- **`ShiftPosition`** — "this Shift needs N of role X", with an optional per-position `hourly_rate` override.

**Backward compatibility:** a Shift with zero `ShiftPosition` rows works exactly as before — plain `quantity_needed` headcount, no role distinction. `Shift::isFull()` automatically switches to "every position full" only once a Shift actually has positions.

## Per-position qualification requirements (not just per-shift)

`ShiftQualification` can scope a requirement to one specific `ShiftPosition` (`shift_position_id` set) instead of the whole Shift — necessary because different roles on the same Shift often need completely independent qualifications. A combined "Math & English" class, for example, is one Shift with two positions: the Math-teacher position requires a Math qualification, the English-teacher position requires an unrelated English one — nobody should need both just to be assignable to either role.

This changes what `ShiftVisibility` actually checks once a Shift has positions: a worker doesn't need to satisfy every position's requirements, only **at least one**. Qualifying for just the Math-teacher role is enough to see the shift at all, even with zero English qualification. Shift-wide requirements (`shift_position_id` null) only apply to a Shift with no positions at all — once positions exist, define requirements per-position instead (see `POST /api/shifts/{shift}/qualifications`, which now accepts an optional `shift_position_id`).

## Waitlisting

`ShiftInterestController::store()` no longer rejects interest when a shift/position is already full — it records the interest as `waitlisted` instead of `pending`. A dispatcher can still see waitlisted workers (`GET /api/shifts/{shift}/interests`) and manually assign one if a spot opens (e.g. after an approved cancellation).

## Formal cancellation (24-hour rule)

`CancellationRequest` — a worker requests cancellation of their own assignment (`POST /api/assignments/{assignment}/cancellation-request`); it does **not** cancel immediately. `is_urgent` is computed automatically (true if less than 24 hours remain before the shift starts), and a `reason` becomes mandatory in that case. A dispatcher must review and approve/reject (`shifts.dispatch`). Approving flips the Assignment to `cancelled` and automatically reopens the slot — `Shift`/`ShiftPosition::isFull()` only count `confirmed` assignments, so nothing else needs to happen except correcting the Shift's own status label if it had been `filled`.

## Transport (per-vehicle, per-Event)

`TransportGroup` — one vehicle for an Event, with a designated driver (that driver's own `Assignment`, typically to a "Driver"-role `ShiftPosition`) and a list of other workers' `Assignment`s riding along (`transport_group_passengers` pivot). Scoped under `/api/events/{event}/transport-groups`, gated by `shifts.dispatch` (assigning transport is a dispatching concern).

## Install

1. Place this folder at `Modules/Shift`, `php artisan module:enable Shift`, `composer dump-autoload`.
2. Depends on **Organization** (`Branch`), **Client** (`Client`), **Authentication** (`User`), and **Employee** (`Worker`, `CompanyWorker`, `EmploymentContract`, `WorkerQualification`, `Qualification` — for shift visibility and assignment eligibility) — install those first.
3. Migrations live in `database/tenant-migrations/` — same reasoning as every other tenant-scoped module (see Authentication's README).

## Assignment eligibility (see `Services/WorkerEligibility.php`)

`AssignmentController::store()` refuses to assign a worker who isn't currently eligible. All of these must hold — see Employee module's README for the full `Worker`/`CompanyWorker`/`EmploymentContract` split this checks across:

1. `Worker.status` is `active`
2. `work_authorization_status` is `valid` or `not_required`
3. their `CompanyWorker.status` is `active`
4. they have at least one `EmploymentContract` that's currently active (`status=active` and not past its `end_date`)

A worker missing any of these gets a clear 422, not a silent failure. This is a hard gate at assignment time — separate from `ShiftVisibility` (which only controls whether a worker *sees* a shift at all).

## Re-confirmation on shift changes

A worker who already confirmed an assignment did so based on a specific time and place — if a dispatcher then edits `starts_at`, `ends_at`, or `location_address` on that shift (`PUT /api/shifts/{shift}`), every `confirmed` assignment on it resets to `pending_worker_confirmation` (`confirmed_at` cleared too), so the worker has to look at the new details and confirm again rather than silently staying "confirmed" for a shift that's moved under them. If the shift had been `filled`, it reverts to `partially_filled` accordingly.

Only timing/location trigger this — editing the description, contact names, rate, or qualification policy doesn't, since none of those affect whether a worker who already said yes can actually still make it. `pending_worker_confirmation` assignments (not yet confirmed at all) and `pending`/`waitlisted` interests aren't touched either way — they haven't committed to anything yet, so they just see the updated details whenever they act.

Date comparison uses `Carbon::parse(...)->equalTo(...)`, not a raw string comparison — the incoming request value and the stored Carbon value's string form don't match byte-for-byte even for the identical moment (different formats), so a naive string check would falsely trigger this reset on every edit, not just ones that actually change the time.

**`Assignment.change_note`**: set alongside the reset, to a plain-language summary of exactly what changed (e.g. `"Time changed from Mon, Sep 15, 8:00 PM–11:00 PM to Mon, Sep 15, 9:00 PM–12:00 AM."`) — built from the shift's OLD values (captured before `$shift->update()` overwrites them) and its new ones. Without this, a worker asked to re-confirm would have no way to tell what actually changed short of comparing the new details against memory. Cleared back to `null` once the worker re-confirms (`AssignmentController::confirm()`), so it never shows stale info. Exposed on `AssignmentResource` — the worker-portal Jobs tab surfaces it prominently on the affected card/detail view.

## `Event.requires_contract` — auto-creating a per-placement Überlassungsmitteilung

When an Event has `requires_contract: true`, successfully assigning a worker to any Shift under it automatically creates (find-or-create — never duplicated per worker per event) an Employee-module `EmploymentContract` with `contract_type: assignment_notice` (Überlassungsmitteilung — Austrian staff-leasing notification law), `event_id` set, and `status: pending_signature`. See `AssignmentController::ensureAssignmentNotice()`.

**This is layered on top of `WorkerEligibility`, not a replacement for it.** The eligibility check above (which requires an already-*active general* contract) still runs first and can still reject the assignment — a worker with zero contracts at all can't get assigned to a `requires_contract` Event any more than any other Shift. Once assigned, the *additional* `assignment_notice` row is what the worker signs to confirm that specific placement — a second, lighter document layered on top of their already-active general contract, not a way around it.

The auto-created row starts with no document file attached (`EmploymentContract.file_path` is null) — an admin/dispatcher should attach the actual notification document (`PUT /api/users/{worker}/contracts/{contract}`, multipart) shortly after, before the worker is expected to read and sign it.

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

GET    /api/shifts                          each shift includes confirmed_workers ([{worker_id, name}]) alongside the existing confirmed_count, so an admin sees who's confirmed at a glance without a click-through per shift
GET    /api/shifts/{shift}
POST   /api/shifts                          { event_id?, branch_id, client_id?, title, ..., quantity_needed?, starts_at, ends_at }   [shifts.create]
PUT    /api/shifts/{shift}                  also accepts { status } now — "Disable" on the admin page just sets status: cancelled through this. Changing starts_at/ends_at/location_address resets any already-`confirmed` assignment back to `pending_worker_confirmation` (see "Re-confirmation on shift changes" below)   [shifts.create]
DELETE /api/shifts/{shift}                  permanent — cascades to positions, qualifications, interests, and assignments tied to it; prefer PUT { status: cancelled } unless that's genuinely wanted   [shifts.create]

GET    /api/shifts/{shift}/positions
POST   /api/shifts/{shift}/positions        { shift_role_id?, quantity_needed, hourly_rate? }             [shifts.create]
PUT    /api/shifts/{shift}/positions/{position}                                                             [shifts.create]
DELETE /api/shifts/{shift}/positions/{position}                                                             [shifts.create]

POST   /api/shifts/{shift}/interest         { shift_position_id? }  worker expresses interest (waitlisted automatically if full)
DELETE /api/shifts/{shift}/interest         worker withdraws (pending or waitlisted)
GET    /api/my-interests                    the current worker's own pending/waitlisted interests across every shift, each with the full shift nested — what the worker-portal Jobs tab shows as "you're waiting on these"
GET    /api/my-assignments                  the current worker's own pending-confirmation/confirmed assignments across every shift, same nested-shift shape — "your upcoming work"

GET    /api/shifts/{shift}/interests        pending + waitlisted, for the dispatcher            [shifts.dispatch]
GET    /api/shifts/{shift}/assignments                                                            [shifts.dispatch]
POST   /api/shifts/{shift}/assignments      { worker_id, shift_position_id?, transport_amount? }  [shifts.dispatch]

POST   /api/assignments/{assignment}/confirm                worker confirms their own assignment
POST   /api/assignments/{assignment}/cancellation-request   { reason? }  worker requests cancellation (reason required if <24h before start)
DELETE /api/assignments/{assignment}                        dispatcher/admin cancels directly — no approval step, immediate. Soft (sets status: cancelled, keeps the row for history), not a real delete. Distinct from the worker-initiated cancellation-request above, which needs separate approval.   [shifts.dispatch]

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
   - the Shift's own `branch_id` is the worker's home branch (`CompanyWorker.home_branch_id`, from Employee), **or**
   - the worker has been explicitly activated for that Shift's Event via `EventWorkerAccess`.
2. **Qualification** — the worker holds *every* qualification required for **at least one** of the Shift's positions (see "Per-position qualification requirements" above) — unless `qualification_policy` says otherwise:

| `qualification_policy` | Who sees it | What a dispatcher sees afterward |
|---|---|---|
| `strict` (default) | Only workers who qualify for at least one position | Nothing extra — everyone who could act already qualifies |
| `override` | Everyone, regardless of qualification | Nothing extra — no warning, ever |
| `warn` | Everyone, regardless of qualification | A `qualification_warning: true` flag on that worker's `ShiftInterestResource`/`AssignmentResource` if they don't actually meet the requirement |

`override` and `warn` are both opt-in escape hatches for staffing shortages (e.g. an unpopular night shift nobody qualified wants) — they only differ in whether a dispatcher gets told about the mismatch afterward. Set the policy when creating/editing a shift: `POST /api/shifts { ..., qualification_policy: warn }` (`shifts.create`, same as any other shift field). A Shift with no requirements at all is visible to anyone who passes the access check regardless of policy.

A third, independent filter applies only to `GET /api/shifts` (not `show`): a Worker only sees shifts with `status: open` or `partially_filled` in this browsable list — `cancelled`/`filled`/`in_progress`/`completed` ones are excluded, even if access+qualification both pass. (Bug fixed in this pass: the filter originally only allowed `open`, which meant a shift vanished from every worker's browsable list the moment even one assignment was confirmed — even with `quantity_needed > 1` still leaving open spots, since confirming an assignment moves a shift from `open` straight to `partially_filled`, not `filled`.) A dispatcher/admin sees every status here too. This is separate from the access/qualification rule above and applies on top of it.

Failing the check under `strict` **hides** the shift entirely (404 on direct access, absent from the list) — it is never shown disabled/greyed out, per this project's explicit design choice. This applies to `GET /api/shifts`, `GET /api/shifts/{shift}`, and `POST /api/shifts/{shift}/interest` alike.

### Cross-branch access is two steps, on purpose

By default, only a Shift's own branch can see it. To let workers from **another** branch in on an Event:

1. Someone with `shifts.dispatch` grants that whole branch visibility: `POST /api/events/{event}/branch-access { branch_id }`. This alone does **not** show anything to any worker yet.
2. That branch's own admin/dispatcher then activates specific workers, one at a time: `POST /api/events/{event}/worker-access { worker_id }`. This is rejected with a 422 if the worker's home branch hasn't been granted access in step 1 first.

### Endpoints (visibility-related)

```
GET    /api/shifts/{shift}/qualifications
POST   /api/shifts/{shift}/qualifications           { qualification_id, shift_position_id? }   [shifts.create]
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
