# Payment Module

Worker payroll: logging actual hours/pay when an Assignment is completed (`WorkLog`), completion proof (`CompletionProof` — uploaded document or digital signature), and post-completion ratings (`WorkerRating`, bidirectional: client or internal).

## Install

1. Place this folder at `Modules/Payment`, `php artisan module:enable Payment`, `composer dump-autoload`.
2. Depends on **Shift** (`Assignment`), **Authentication** (`User`), and **Setting** (`CompanySettings`, for overtime warning thresholds) — install those first.
3. Migrations live in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## The completion flow

`POST /api/assignments/{assignment}/complete` is the core action. Either the worker themselves or someone with `shifts.dispatch` may call it, but only on a `confirmed` assignment. It:

1. Computes `hours_worked` (from the request, or defaults to the Shift's own `starts_at`/`ends_at` span).
2. Computes `base_amount` from the Shift's `rate_type`/`hourly_rate`/`fixed_amount` **at that moment** — copied into `WorkLog`, not recalculated later, so a future rate change on the Shift never silently changes what a worker was paid for past work.
3. Records `CompletionProof` — either an uploaded document or a digital signature string, matching `proof_type`.
4. Flips the `Assignment`'s own `status` to `completed`.
5. Returns non-blocking overtime warnings (rolling 7-day window vs. `CompanySettings.warning_hour_threshold` / `warning_income_threshold`) — per the project's rule, this **never** prevents completion, it only flags it.

## Endpoints

```
POST /api/assignments/{assignment}/complete   { hours_worked?, proof_type: uploaded_document|digital_signature, file? (if document), signature_data? (if digital) }
POST /api/assignments/{assignment}/rate        { rated_by_type: client|internal, score: 1-5, comment? }

GET  /api/users/{user}/work-logs               a worker's own pay history (self, or users.manage)
```
