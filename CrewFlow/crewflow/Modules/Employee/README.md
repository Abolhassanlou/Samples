# Employee Module

## The core architectural decision: person ≠ contract

**A worker's contract terms are not a permanent attribute of the person.** Someone might be `Geringfügig` today and sign an entirely different contract a few months later. So contract data is never stored on the same row as personal facts — three separate tables, each with its own reason to exist:

```
users (Authentication)
  └── workers                    personal/legal facts, work authorization
        └── company_workers      the employment RELATIONSHIP (status, branch, employee number)
              └── employment_contracts   full contract HISTORY (many rows per worker over time)
```

## `workers` — personal facts only

`first_name`, `last_name` (a legal-name split, separate from `User.name` which is just a display/login name), `date_of_birth`, `address`/`postal_code`/`city`/`country`, `status` (`pending`/`active`/`inactive`/`blocked` — the person's overall standing), and work authorization (`work_authorization_status`: `pending`/`valid`/`expired`/`not_required`/`rejected`, plus `work_authorization_type` e.g. `"Rot-Weiß-Rot Karte Plus"`, and `work_authorization_expiry_date`).

**No contract fields here at all** — not even `home_branch_id` (that's on `company_workers` — see below).

## `company_workers` — the employment relationship, no `company_id`

Deliberately **no `company_id` column**, even though the concept is "this worker's relationship with the company" — because this database already belongs to exactly one company (`stancl/tenancy`, one physical database per tenant). Adding `company_id` would be meaningless here: it could only ever hold one value in any given tenant's database. (If a worker ever needs to work for a *different* company, that's a separate `User` account in that company's own separate database — same as any other user.)

Holds: `employee_number` (nullable, **manually** entered — deliberately separate from Authentication's auto-assigned `User.personnel_number`, see below), `home_branch_id`, `works_night_shifts` (a declared preference, not inferred from availability — see the migration's docblock), `status` (`invited`/`pending`/`active`/`inactive`/`blocked`), `joined_at`, `left_at`.

### Two different "personnel numbers" — on purpose

| | `User.personnel_number` (Authentication) | `CompanyWorker.employee_number` (here) |
|---|---|---|
| Who assigns it | The system, automatically (`"0001"`, `"0002"`, ...) | The admin, by hand — any format |
| What it's for | Telling two same-named **users** apart (any user, including admins/dispatchers) in a list | The company's own real employee ID/badge number, following whatever convention that company already uses (e.g. `"MA-0048"`) |
| Required? | Always set | Optional, can be blank |

We didn't try to build one configurable-format auto-numbering algorithm to satisfy both — every company might number employees completely differently, so the honest answer is: the admin just types it in.

## `employment_contracts` — full history, one row per contract

`contract_number` (nullable, manual), `contract_type`, `work_time_model`, `is_marginal`, `weekly_hours`, `start_date`, `end_date`, `status`, `termination_date`, `termination_reason`, `notes`.

**`contract_type`** — three values for now (not five; `Lehrvertrag`/`Praktikum` deliberately excluded, add later if actually needed):

| Value | German |
|---|---|
| `employment_contract` | Echter Dienstvertrag |
| `free_service_contract` | Freier Dienstvertrag |
| `work_contract` | Werkvertrag |

**`work_time_model`**:

| Value | German |
|---|---|
| `full_time` | Vollzeit |
| `part_time` | Teilzeit |
| `casual` | Fallweise Beschäftigung |

`casual` matters specifically for event staffing — many workers are only booked on individual specific days, not on an ongoing part-time schedule.

**`is_marginal`** — deliberately its **own boolean**, not a fourth `work_time_model` value. A worker can be *both* `part_time` **and** marginal (`Geringfügig`) at the same time; they're independent axes, not mutually exclusive options in one list.

**No stored `duration_type`.** Whether a contract is `Unbefristet` (permanent) or `Befristet` (fixed-term) is derived from whether `end_date` is null — see `EmploymentContract::isPermanent()`. Storing it as a separate field would risk it disagreeing with the actual dates.

**`status`**: `draft` → `pending_signature` → `active` → (`expired` / `terminated` / `cancelled`). `expired` is meant to be system-determined from `end_date`, not something an admin sets by hand (not yet automated — a manual status change for now).

## Shift assignment eligibility (see the Shift module)

Only a worker with `Worker.status = active`, valid/not-required work authorization, an `active` `CompanyWorker.status`, and at least one currently-active `EmploymentContract` (`EmploymentContract::isCurrentlyActive()`) can actually be assigned to a Shift — enforced in Shift's `AssignmentController`, not here (Shift already depends on Employee for qualification/branch checks, so this is a natural extension of that existing dependency).

## The invite-by-email flow — this is now the primary way a worker gets an account

An admin/dispatcher only ever types an **email address** (`POST /api/workers/invite`, gated by `shifts.dispatch` — both roles can invite, matching how both already work with shifts). Everything else is filled in by the worker themselves:

1. `WorkerInvitationController::store()` creates the `User` (placeholder name = the email's local part, an unusable random password), an empty `Worker`, and a `CompanyWorker` with `status: invited` and a random `invitation_token` (expires in 7 days), then emails a link built from `config('employee.worker_portal_url')` + `?token=...&company=...`.
2. The worker opens that link — `GET /api/invitations/{token}` (public, no auth) lets a frontend show "You've been invited to join {company}" before they commit to anything.
3. `POST /api/invitations/{token}/accept` (public, no auth) — the worker submits their real name, phone, and password. Sets those on their `User`, flips both `Worker.status` and `CompanyWorker.status` to `pending` (an admin still needs to actually set up their contract — see the status lifecycle above), clears the invitation token, and returns a fresh Sanctum token so they're immediately signed in.

**The worker-facing portal that link actually opens doesn't exist yet.** `worker_portal_url` is deliberately configurable (`WORKER_PORTAL_URL` env var) specifically so the invite/email mechanism could be built and tested end-to-end (e.g. with `MAIL_MAILER=log`, checking `storage/logs/laravel.log`) before that portal exists. Update the config once it does.

**Why the link includes `company=` alongside `token=`**: every API call in this project needs to know which tenant's subdomain to hit (`{company-code}.crewflow.localhost/api/...` — see `Modules/Tenancy/README.md` and the admin panel's `buildBaseUrl()`). A bare token alone wouldn't tell a future worker-portal frontend which company's API to call before it's even authenticated — so the company code rides along in the same link, and is also returned from `GET /api/invitations/{token}` as a cross-check.

The older "admin types everything by hand" flow (the frontend's `CreateWorkerView`, hitting `POST /api/auth/register` directly) still works — useful for e.g. importing existing employee data — but is no longer the primary path a new worker is expected to go through.

## Install

1. Place this folder at `Modules/Employee`, `php artisan module:enable Employee`, `composer dump-autoload`.
2. Depends on **Authentication** (`User`) and **Organization** (`Branch`) — install those first.
3. File storage note (documents) and migration-loading note: same reasoning as every tenant-scoped module — see Authentication's README.
4. Set `WORKER_PORTAL_URL` in `.env` once the worker-facing portal exists (defaults to a `localhost:5174` placeholder otherwise).

## Permissions used (already seeded by Authorization)

- `users.manage` — edit a worker's personal record, employment relationship, or contracts (all financial/legal data)
- `qualifications.manage` — grant/revoke qualifications, manage the qualification catalog
- `documents.review` — review uploaded documents
- `shifts.dispatch` — use the `/api/workers` directory, and invite a new worker by email (see Shift's own README for the underlying permission philosophy)

## The qualification-granting rule (important, matches the business-model doc)

A `WorkerQualification` is only ever created two ways: (1) an admin grants it directly (`source: company_granted`), or (2) an admin approves a reviewed document and links it (`source: document_verified`). **Never automatic** — uploading a document alone never grants anything by itself.

## Endpoints

```
GET    /api/users/{user}/worker                                                              (self or users.manage)
PUT    /api/users/{user}/worker            { first_name?, last_name?, date_of_birth?, address?, postal_code?, city?, country?, status?, work_authorization_status?, work_authorization_type?, work_authorization_expiry_date? }   [users.manage]

GET    /api/users/{user}/employment                                                           [users.manage]
PUT    /api/users/{user}/employment        { employee_number?, home_branch_id?, works_night_shifts?, status?, joined_at?, left_at? }   [users.manage]

GET    /api/users/{user}/contracts                                                             [users.manage]
POST   /api/users/{user}/contracts         { contract_type, work_time_model, is_marginal?, weekly_hours?, start_date, end_date?, contract_number?, notes? }   [users.manage]
PUT    /api/users/{user}/contracts/{contract}                                                  [users.manage]

GET    /api/users/{user}/qualifications
POST   /api/users/{user}/qualifications         { qualification_id }                        [qualifications.manage]
DELETE /api/users/{user}/qualifications/{workerQualification}                                [qualifications.manage]

GET    /api/users/{user}/availability
POST   /api/users/{user}/availability           { slots: [{ day_of_week, start_time, end_time }, ...] }  (full replace; self or users.manage)

GET    /api/documents                           a worker's own upload history
POST   /api/documents                           multipart: { document_type: identity_document|residence_permit|work_permit|social_security_card|driving_license|criminal_record|certificate|other, file, document_number?, issued_at?, visa_type?, expires_at? }
GET    /api/documents/{document}/download       (owner or documents.review)

GET    /api/documents/pending                                                                [documents.review]
POST   /api/documents/{document}/review         { decision: approved|rejected, rejection_reason?, qualification_id? }   [documents.review]

GET    /api/workers                             ?search=&qualification_id=&branch_id=&contract_type=&work_time_model=&night_shift=1&eligible=1&day_of_week=&time=   [shifts.dispatch]

POST   /api/workers/invite                      { email }                                    [shifts.dispatch]
GET    /api/invitations/{token}                 (public)
POST   /api/invitations/{token}/accept          { name, phone, password, password_confirmation }   (public)
```

## The `/api/workers` directory (dispatcher-facing, not the same thing as `/api/users`)

Authentication's `GET /api/users` (gated by `users.manage`) is an access-control tool — who exists, what roles do they have. `GET /api/workers` here is a *different* concern: finding the right person to staff a shift. It's gated by `shifts.dispatch` instead, specifically so a **Dispatcher** (not just Company Admin) can use it. Supports filtering by any combination of:

- `search` — matches name, email, personnel number, or employee number
- `qualification_id` — only workers holding that qualification
- `branch_id` — only workers whose home branch matches
- `contract_type` / `work_time_model` — only workers with an *active* contract matching
- `night_shift=1` — only workers who've declared `works_night_shifts`
- `eligible=1` — only workers actually assignable right now (active status, valid work authorization, active employment relationship, active non-expired contract) — the exact same rule Shift's `AssignmentController` enforces
- `day_of_week` (0=Sunday..6=Saturday) + `time` (`HH:MM`) — only workers with an availability slot covering that day and time (both params required together)

Returns each worker's personal record, employment relationship (including home branch name and a summary of their currently-active contract, if any), full qualification list, and full availability list in one call — avoids the N+1 problem of calling the per-user endpoints once per worker.
