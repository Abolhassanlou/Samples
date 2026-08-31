# Employee Module

Worker profile, documents (with mandatory human review), qualifications, and weekly availability. Merges the former Workforce+Documents+Qualifications concepts from the original design.

## Why `WorkerProfile` is a separate model from `User`

Employment-specific fields (`employment_type`, `hourly_rate`, `home_branch_id`) live here, not on Authentication's `User` — this keeps Authentication from needing to depend on Organization (for `Branch`). One-to-one relationship via `user_id`.

## Install

1. Place this folder at `Modules/Employee`, `php artisan module:enable Employee`, `composer dump-autoload`.
2. Depends on **Authentication** (`User`) and **Organization** (`Branch`) — install those first.
3. Migrations live in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## File storage note

Document uploads use `Storage::disk('local')`. Because `FilesystemTenancyBootstrapper` is already enabled in `config/tenancy.php` (set up during the Tenancy module's install), the local disk root is automatically suffixed per tenant — each company's uploaded files land in a separate folder without any extra configuration here.

## Permissions used (already seeded by Authorization)

- `qualifications.manage` — create/edit/delete the qualification catalog, grant/revoke a worker's qualifications
- `documents.review` — see the pending-review queue, approve/reject documents
- `users.manage` — edit a worker's profile (employment_type/hourly_rate — financial data)

## The qualification-granting rule (important, matches the business-model doc)

A qualification is **never** granted automatically just because a document was uploaded. Two paths, both requiring a human decision:
- `company_granted` — an admin directly grants it (e.g. after in-house training) via `POST /users/{user}/qualifications`.
- `document_verified` — an admin reviews an uploaded document and, if approving it, optionally links a qualification in the same request (`POST /documents/{document}/review`).

## Endpoints

```
GET    /api/qualifications
POST   /api/qualifications                      { name, description? }                    [qualifications.manage]
PUT    /api/qualifications/{qualification}                                                  [qualifications.manage]
DELETE /api/qualifications/{qualification}                                                  [qualifications.manage]

GET    /api/users/{user}/profile
PUT    /api/users/{user}/profile                { employment_type?, hourly_rate?, home_branch_id? }   [users.manage]

GET    /api/users/{user}/qualifications
POST   /api/users/{user}/qualifications         { qualification_id }                        [qualifications.manage]
DELETE /api/users/{user}/qualifications/{workerQualification}                                [qualifications.manage]

GET    /api/users/{user}/availability
POST   /api/users/{user}/availability           { slots: [{ day_of_week, start_time, end_time }, ...] }  (full replace; self or users.manage)

GET    /api/documents                           a worker's own upload history
POST   /api/documents                           multipart: { document_type, file, visa_type?, visa_expiry_date? }
GET    /api/documents/{document}/download       (owner or documents.review)

GET    /api/documents/pending                                                                [documents.review]
POST   /api/documents/{document}/review         { decision: approved|rejected, rejection_reason?, qualification_id? }   [documents.review]

GET    /api/workers                             ?search=&qualification_id=&branch_id=&day_of_week=&time=   [shifts.dispatch]
```

## The `/api/workers` directory (dispatcher-facing, not the same thing as `/api/users`)

Authentication's `GET /api/users` (gated by `users.manage`) is an access-control tool — who exists, what roles do they have. `GET /api/workers` here is a *different* concern: finding the right person to staff a shift. It's gated by `shifts.dispatch` instead, specifically so a **Dispatcher** (not just Company Admin) can use it. Supports filtering by any combination of:

- `search` — matches name, email, or personnel number
- `qualification_id` — only workers holding that qualification
- `branch_id` — only workers whose home branch matches
- `day_of_week` (0=Sunday..6=Saturday) + `time` (`HH:MM`) — only workers with an availability slot covering that day and time (both params required together)

Returns each worker's profile, home branch name, full qualification list, and full availability list in one call — avoids the N+1 problem of calling the existing per-user endpoints (`/api/users/{user}/profile`, etc.) once per worker.
