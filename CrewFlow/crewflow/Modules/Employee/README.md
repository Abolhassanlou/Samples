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

`first_name`, `last_name` (a legal-name split, separate from `User.name` which is just a display/login name), `date_of_birth`, `status` (`pending`/`active`/`inactive`/`blocked` — the person's overall standing), and work authorization (`work_authorization_status`: `pending`/`valid`/`expired`/`not_required`/`rejected`, plus `work_authorization_type` e.g. `"Rot-Weiß-Rot Karte Plus"`, and `work_authorization_expiry_date`).

Plus a fixed baseline of personal-detail fields every company gets for free (not company-configurable — see the "Custom fields" section below for the ones that *are*):

- **Personal**: `gender` (`female`/`male`/`diverse`), `marital_status` (`single`/`married`/`separated`/`widowed`/`registered_partnership`), `nationality`, `native_language`, `social_security_number` (exactly 10 digits when provided — a value format constraint, not a presence requirement, since this endpoint also accepts partial payloads from the Address/Bank forms that never send it at all), `german_language_level` (`none`/`basic`/`conversational`/`fluent`/`native` — kept separate from the list below since it typically matters most for compliance), `languages_spoken` (a JSON array — a simple multi-select of *other* languages, no per-language proficiency, e.g. `["english", "italian"]`).
- **Address**: `street` + `house_number` (deliberately split, not one combined string — matches how these are used separately on official Austrian/German paperwork), `postal_code`, `city`, `country`, `residence_type` (`main`/`secondary` — Hauptwohnsitz/Nebenwohnsitz).
- **Bank details**: `bank_name`, `bank_account_holder_name`, `iban`, `bic`. `bank_account_holder_name` should match the worker's own name in practice, but that's only a note shown on the frontend — not a backend-enforced check, after real names proved to have too much legitimate variation (diacritics, middle names, order, joint accounts) for automated matching to work reliably.

**No contract fields here at all** — not even `home_branch_id` (that's on `company_workers` — see below).

## Which fields are actually required, and where that's enforced

`first_name`, `last_name`, `phone` (on `User`/Authentication, not here — see `PUT /api/auth/me`), `gender`, `date_of_birth`, `nationality`, and `social_security_number` are meant to be mandatory — but nothing in `WorkerRequest`'s validation rules marks them `required`. That's deliberate: `PUT /api/users/{user}/worker` is one shared endpoint that three different frontend forms (Personal details / Address / Bank details) all send *partial* payloads to — the Address form, for instance, never includes `first_name` at all, so a hard `required` rule here would reject it outright. Requiredness is enforced purely on the frontend (the Personal details form specifically, via HTML5 `required`), not the backend. `social_security_number`'s 10-digit format, by contrast, *is* backend-enforced (`regex:/^[0-9]{10}$/`) — that's a value constraint that only applies when the field is actually present, not a presence requirement, so it doesn't conflict with the other two forms' partial payloads.

## `worker_documents` — the fixed personal-document baseline

Every company gets these `document_type` values for free under `category: personal` (see "Two kinds of documents" below): `photo`, `passport`, `identity_document` (an alternative to passport, not both required), `insurance_card_front`, `insurance_card_back`, `bank_card`, `resume`, `work_permit_front`, `work_permit_back`, `driving_license` (optional — only if the worker actually has one), `meldezettel` (Austrian residence registration certificate), `residence_permit`, `criminal_record`. Under `category: work`: `certificate`, `other`. `CustomDocumentType` only ever adds to this list.

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

**`contract_type`** — four values (not five/six; `Lehrvertrag`/`Praktikum` deliberately excluded, add later if actually needed):

| Value | German |
|---|---|
| `employment_contract` | Echter Dienstvertrag |
| `free_service_contract` | Freier Dienstvertrag |
| `work_contract` | Werkvertrag |
| `assignment_notice` | Überlassungsmitteilung |

`assignment_notice` is different in kind from the other three, not just another option in the same list: the other three are the actual employment relationship (signed once, `event_id` typically null, and what `WorkerEligibility` checks for). `assignment_notice` is a lighter, per-placement notification — Austrian staff-leasing law's Überlassungsmitteilung — auto-created (with `event_id` set) whenever a worker is assigned to a shift under an Event with `requires_contract = true` (see Shift's `AssignmentController`). It does **not** replace or bypass the general contract requirement; a worker already needs an active general contract to be assignable at all (unchanged `WorkerEligibility` rule) — this is an *additional* per-event document layered on top. `event_id` doubles as a convenient way to look up/sort every worker who worked a given event, regardless of which contract_type their rows are.

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

## Per-event vs. ongoing contracts, and the worker's own online signature

`EmploymentContract.event_id` (nullable) supports two different needs with one column instead of building separate systems for each: some companies need a fresh `work_contract` per event/project (`event_id` set); others sign one ongoing contract that covers everything (e.g. an ongoing role like teaching — typically `employment_contract`, `event_id` left null). Deliberately **not** a `belongsTo` Eloquent relationship to Shift's `Event` model — Shift already depends on Employee, so a relationship the other way would be circular; it's a plain FK column, and the frontend fetches event details separately by id when needed.

**Auto-created `assignment_notice` contracts**: when a Shift belongs to an Event with `requires_contract = true`, assigning a worker to it (see Shift's `AssignmentController::store()`) automatically creates (find-or-create, one per worker per event) an `EmploymentContract` with `contract_type: assignment_notice`, `event_id` set, and `status: pending_signature` — the worker then sees it on their profile and signs it separately from their general contract. This does **not** bypass or interact with `WorkerEligibility` — a worker still needs an already-active *general* contract (`employment_contract`/`free_service_contract`/`work_contract` with no `event_id`, or one whose own `event_id` doesn't matter to the eligibility check) before they can be assigned to anything at all. The `assignment_notice` is purely an additional per-placement document layered on top, generated *after* assignment, never a precondition for it.

**Signing**: `POST /api/users/{user}/contracts/{contract}/sign` — self-ONLY (never admin, checked inline, not by route permission), only valid when `status = pending_signature`. Sets `status: active` and stamps `signed_at`. For a general contract, this is what makes it start counting toward `WorkerEligibility` (which just looks at `status = active`, unaware of *how* it got there — signing is one path, an admin directly setting `active` is another). For an `assignment_notice`, signing simply records the worker's confirmation for that specific placement — `WorkerEligibility` never looks at these rows at all.

**The actual document**: signing means nothing if there's nothing to read first. `EmploymentContract.file_path` (nullable) holds the real contract document — an admin attaches it via `POST`/`PUT .../contracts` as `multipart/form-data` with a `file` field (same pattern as `WorkerDocumentController::store()`), alongside the other fields. `GET .../contracts/{contract}/download` (self or `users.manage`) retrieves it — a worker needs to actually read the document before they sign, not just click a button with nothing behind it. Not every contract strictly needs a file (a rough `draft`, or a very informal `casual` arrangement, might never get one) — nothing at the database level forces it, but a `pending_signature` contract with no file attached should be treated as a UI mistake, not a valid state to leave a worker in. Auto-created `assignment_notice` rows in particular start with no file at all — an admin/dispatcher should attach the actual notification document shortly after Shift auto-creates the row, before the worker is expected to sign it. Since `PUT` doesn't natively support `multipart/form-data` in most HTTP clients/browsers, updating a contract's file uses Laravel's standard method-spoofing (`POST` with `_method=PUT` in the form data). `file_path` itself is exposed on `EmploymentContractResource` (not just the `has_file` boolean) so a frontend can extract the real file extension when naming a downloaded copy — a hardcoded extension (or none at all) breaks for anything not stored as that assumed type, since `file` accepts `pdf`/`jpg`/`jpeg`/`png` alike.

## `$request->string()` vs `$request->query()` — a bug that spread across five filters

`$request->string('x')` returns a `Stringable` **object**, not a plain PHP string. Using it directly as an Eloquent `where()` value can fail to bind/match correctly — this silently broke five separate filters before this pass: `WorkerDocumentController::types()`'s category filter (found first, via a strict `===` comparison — different symptom, same root cause), then `CustomFieldDefinitionController`/`CustomDocumentTypeController`'s category filters (any newly-created question or custom document type never showed up when filtered), and `WorkerDirectoryController`'s `contract_type`/`work_time_model`/`time` filters (dispatcher search silently returning nothing for those filters). All five now use `$request->query('x')`, which returns the raw string. A full search across every other module found no further occurrences — this was isolated to Employee. `WorkerDirectoryController`'s `search` filter was never affected despite also calling `$request->string()`, since it's used inside string interpolation (`"%{$search}%"`), which calls `Stringable::__toString()` automatically — the bug is specifically about passing the object as a raw binding value, not about calling `->string()` at all.

## Self-access corrections worth knowing about

Two real gaps existed before this pass and are now fixed:

- `WorkerController::show()` had **no permission check at all** despite its own docblock claiming self-or-`users.manage` — any authenticated user could view any other worker's personal record. Now enforced inline.
- `WorkerController::update()` now allows a worker to edit their own personal facts (name, DOB, address, etc. — matching the worker portal's "My info → Personal details" section, meant to be self-filled) — but `status` and all `work_authorization_*` fields are stripped out unless the requester has `users.manage`, so a worker can never self-approve their own work authorization or activate their own account.
- `EmploymentContractController::index()` moved out of the `users.manage`-only route group — a worker needs to see their own contract history (to know what to sign), checked self-or-admin inline instead.

## Shift assignment eligibility (see the Shift module)

Only a worker with `Worker.status = active`, valid/not-required work authorization, an `active` `CompanyWorker.status`, and at least one currently-active `EmploymentContract` (`EmploymentContract::isCurrentlyActive()`) can actually be assigned to a Shift — enforced in Shift's `AssignmentController`, not here (Shift already depends on Employee for qualification/branch checks, so this is a natural extension of that existing dependency).

## The invite-by-email flow — this is now the primary way a worker gets an account

An admin/dispatcher only ever types an **email address** (`POST /api/workers/invite`, gated by `shifts.dispatch` — both roles can invite, matching how both already work with shifts). Everything else is filled in by the worker themselves:

1. `WorkerInvitationController::store()` creates the `User` (placeholder name = the email's local part, an unusable random password), an empty `Worker`, and a `CompanyWorker` with `status: invited` and a random `invitation_token` (expires in 7 days), then emails a link built from `config('employee.worker_portal_url')` + `?token=...&company=...`.
2. The worker opens that link — `GET /api/invitations/{token}` (public, no auth) lets a frontend show "You've been invited to join {company}" before they commit to anything.
3. `POST /api/invitations/{token}/accept` (public, no auth) — the worker sets only a **password**. Flips both `Worker.status` and `CompanyWorker.status` to `pending` (an admin still needs to actually set up their contract — see the status lifecycle above), clears the invitation token, and returns a fresh Sanctum token so they're immediately signed in.

**Why accept only asks for a password**: name and phone used to be collected here too, duplicating what the worker would fill in again moments later on their own Profile (`PersonalDetailsForm.vue` in worker-portal — `first_name`/`last_name` on `Worker`, `phone` on `User` via `PUT /api/auth/me`). `User.name` keeps its email-prefix placeholder from `store()` until they actually fill in Personal details — at that point, `PersonalDetailsForm.vue` updates `User.name` too (derived from first/last name), not just `Worker.first_name`/`last_name`, so the placeholder gets replaced with their real name for good rather than lingering forever.

**Why the link includes `company=` alongside `token=`**: every API call in this project needs to know which tenant's subdomain to hit (`{company-code}.crewflow.localhost/api/...` — see `Modules/Tenancy/README.md` and the admin panel's `buildBaseUrl()`). A bare token alone wouldn't tell the worker-portal frontend which company's API to call before it's even authenticated — so the company code rides along in the same link, and is also returned from `GET /api/invitations/{token}` as a cross-check.

The older "admin types everything by hand" flow (the admin panel's `CreateWorkerView`, hitting `POST /api/auth/register` directly) still works — useful for e.g. importing existing employee data — but is no longer the primary path a new worker is expected to go through.

## Reactivating a worker who left

Inviting the same email twice normally fails outright (`email` is `unique:users`) — there was no way to bring back a worker whose `CompanyWorker.status` had been set to `inactive`/`blocked` without either a raw "already taken" error or losing their entire history to a workaround. Now `WorkerInvitationController::store()` checks for exactly this case *before* running that validation: if the email belongs to a `User` with a `Worker`/`CompanyWorker` whose status is `inactive` or `blocked`, it returns `409` with `errors: { reactivatable: true, user_id, current_status }` instead of a plain `422` — a distinct, structured response the frontend can detect and offer "Reactivate them instead?" on, rather than a dead end (see `InviteWorkerView.vue` in the admin panel).

`POST /api/workers/{user}/reactivate` is the explicit confirmation step — deliberately separate from `store()` auto-reactivating on a bare retry, since that would let a plain re-invite accidentally resurrect someone a different admin deliberately deactivated. It resets `CompanyWorker.status` to `invited` and resends the invitation email (both `store()` and `reactivate()` share this through a private `sendInvitation()` helper) — that's *all* it touches. `Worker` itself, every document, and the full contract history are completely untouched, so a returning worker picks up right where they left off once they set a new password. `Worker.status` isn't touched by `reactivate()` either — `accept()` (entirely unchanged) sets it back to `pending` once they actually complete the new invitation, exactly the same code path as any other accept, reactivation or not.

## Custom fields — why companies need configurable questions, not hardcoded columns

Different companies need different questions on a worker's profile — one asks about a manual-vs-automatic driving license, another doesn't care but wants a different question instead. Hardcoding columns for every possible question doesn't scale and would need a migration every time a company wants something new. Three tables handle this instead, deliberately kept separate because they answer different needs:

- **`CustomFieldDefinition`** — a company-defined question: `category` (`personal_info` or `skill` — which Profile accordion it appears under), `key` (a stable slug, e.g. `"shoe_size"` — renaming the `label` later never orphans existing answers), `label`, `field_type` (`text`/`number`/`boolean`/`select`/`multi_select`/`date` — tells the frontend which input widget to render; `select` is a dropdown, one answer — `multi_select` is a checkbox list, any number of answers, e.g. "which grade levels do you teach?" answered with several at once), `options` (JSON array, used when `field_type` is `select` or `multi_select`), `is_required`, `sort_order`, `is_active` (soft-disable, not delete — keeps existing answers intact if a company stops asking something).
- **`CustomFieldAnswer`** — one worker's answer to one definition. Everything stored as `text` regardless of `field_type` (a boolean becomes `"true"`/`"false"`, a number its string form, a `multi_select` a JSON-encoded array string e.g. `'["middle_school","high_school"]'`) — simpler than a differently-typed column per `field_type`, and the frontend already knows how to parse/render each type from the definition it's answering.
- **`CustomDocumentType`** — deliberately **not** part of the same system. A document "answer" is a file, not a piece of text, so it doesn't fit the definition/answer pattern above — this is just a company-added `{category, key, label}` triple that extends (never replaces) the fixed baseline list already in `WorkerDocumentController`. `WorkerDocumentController::store()`'s validation now accepts either list.

## Two kinds of documents — `personal` vs `work`, and neither one is the contract

Every document type (fixed or custom) has a `category`:

- **`personal`** — general identity documents (photo, passport, ID card, bank card, resume, driving license, proof of address, etc.) — not tied to any specific job. Shown under the worker portal's **My info** accordion.
- **`work`** — job/event-related uploads (e.g. a timesheet, an event-specific certificate) — tied to actual work performed. Shown under the top-level **Documents** section, alongside (but distinct from) employment contracts.

**Employment contracts are a separate concept from either of these** — `EmploymentContract` (see above) isn't a `WorkerDocument` at all; it's its own table with its own lifecycle. A contract that requires the worker's own online signature/confirmation before a shift can be assigned to them is a planned capability, not yet built — see the Shift module's `WorkerEligibility` for the current (admin-set-status-only) eligibility rule this will eventually extend.

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
PUT    /api/users/{user}/worker            { first_name?, last_name?, date_of_birth?, gender?, marital_status?, nationality?, native_language?, social_security_number?, german_language_level?, languages_spoken?, street?, house_number?, postal_code?, city?, country?, residence_type?, bank_name?, bank_account_holder_name?, iban?, bic?, status?, work_authorization_status?, work_authorization_type?, work_authorization_expiry_date? }   (self — status/work_authorization_* fields silently ignored unless users.manage)

GET    /api/users/{user}/employment                                                           [users.manage]
PUT    /api/users/{user}/employment        { employee_number?, home_branch_id?, works_night_shifts?, status?, joined_at?, left_at? }   [users.manage]

GET    /api/users/{user}/contracts                                                             (self or users.manage)
POST   /api/users/{user}/contracts         multipart: { contract_type, work_time_model, is_marginal?, weekly_hours?, event_id?, start_date, end_date?, contract_number?, notes?, file? }   [users.manage]
PUT    /api/users/{user}/contracts/{contract}   multipart (use _method=PUT), same fields          [users.manage]
GET    /api/users/{user}/contracts/{contract}/download                                         (self or users.manage)
POST   /api/users/{user}/contracts/{contract}/sign                                             (self ONLY — never admin; only valid while status=pending_signature)

GET    /api/users/{user}/qualifications
POST   /api/users/{user}/qualifications         { qualification_id }                        [qualifications.manage]
DELETE /api/users/{user}/qualifications/{workerQualification}                                [qualifications.manage]

GET    /api/users/{user}/availability
POST   /api/users/{user}/availability           { slots: [{ day_of_week, start_time, end_time }, ...] }  (full replace; self or users.manage)

GET    /api/documents                           a worker's own upload history
GET    /api/documents/types                     ?category=personal|work   the fixed baseline document types, each with its category (merge with custom-document-types below for the full list)
POST   /api/documents                           multipart: { document_type: see GET /api/documents/types + /api/custom-document-types for the full current list, file, document_number?, issued_at?, visa_type?, expires_at? }
GET    /api/documents/{document}/download       (owner or documents.review)

GET    /api/documents/pending                                                                [documents.review]
GET    /api/users/{user}/documents                    a specific worker's full document list (not just pending) — what an admin uses on that worker's detail page   [documents.review]
POST   /api/documents/{document}/review         { decision: approved|rejected, rejection_reason?, qualification_id? }   [documents.review]

GET    /api/custom-fields                       ?category=personal_info|skill   (active only unless users.manage)
POST   /api/custom-fields                       { category, key, label, field_type, options?, is_required?, sort_order? }   [users.manage]
PUT    /api/custom-fields/{customField}                                                       [users.manage]
DELETE /api/custom-fields/{customField}         permanent — cascades to every worker's answer to it; prefer PUT { is_active: false } unless that's genuinely wanted   [users.manage]

GET    /api/custom-document-types                ?category=personal|work   (active only unless users.manage)
POST   /api/custom-document-types               { category, key, label, sort_order? }        [users.manage]
PUT    /api/custom-document-types/{documentType}                                               [users.manage]
DELETE /api/custom-document-types/{documentType}   permanent, but safe — doesn't touch already-uploaded files/records   [users.manage]

GET    /api/users/{user}/custom-field-answers                                                  (self or users.manage)
POST   /api/users/{user}/custom-field-answers   { answers: [{ custom_field_definition_id, value }, ...] }   (full replace; self or users.manage)

GET    /api/workers                             ?search=&qualification_id=&branch_id=&contract_type=&work_time_model=&night_shift=1&eligible=1&day_of_week=&time=   [shifts.dispatch]

POST   /api/workers/invite                      { email }   returns 409 with errors: { reactivatable: true, user_id, current_status } instead of a plain validation error when the email belongs to an inactive/blocked worker — see "Reactivating a worker who left" below   [shifts.dispatch]
POST   /api/workers/{user}/reactivate                       explicit confirmation after that 409 — resets CompanyWorker to invited and resends the email; everything else about the worker (Worker, documents, contract history) is untouched   [shifts.dispatch]
GET    /api/invitations/{token}                 (public)
POST   /api/invitations/{token}/accept          { password, password_confirmation }   (public — name/phone deliberately not asked here anymore, filled in later from the worker's own Profile instead; see "Why accept only asks for a password" below)
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
