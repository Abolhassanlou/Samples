# Transaction Module

The company's own billing/invoicing with **its** clients (the `Client` module) — money a `Client` owes this company. **Not** to be confused with the platform's billing of the company itself (`Plan`/`Subscription` in Tenancy, slated for extraction to a separate platform project per an earlier decision — see `project-business-model.md`, section 7).

## Install

1. Place this folder at `Modules/Transaction`, `php artisan module:enable Transaction`, `composer dump-autoload`.
2. Depends on **Client** (`Client`), **Shift** (`Shift`), **Authentication** (`User`), **Payment** (`WorkLog` — for the auto-billing observer), and **Tenancy** (`Company` — the recurring-billing command iterates every company) — install those first.
3. Migration lives in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## Recurring billing (fixed periodic invoices for standing clients)

For a client with a fixed/standing arrangement (a retainer, a fixed periodic fee) rather than billing per completed shift, set up a `RecurringBillingProfile`:

```
POST /api/recurring-billing-profiles   { client_id, amount, cycle: weekly|monthly, next_billing_date }
```

`transaction:generate-recurring-invoices` runs **daily** (registered programmatically in `TransactionServiceProvider`, same approach as Notification's reminder command — no edits to the project's own `routes/console.php` needed) and, for every company, creates a new `pending` Transaction for every active profile whose `next_billing_date` has arrived, then advances that date to the next cycle.

A client can have both a `RecurringBillingProfile` **and** get automatically billed per-shift (the `WorkLogObserver` below) at the same time, if that's how their contract actually works — the two mechanisms don't interfere with each other.

**For local testing**, run the command directly instead of waiting for a real cron:
```bash
php artisan transaction:generate-recurring-invoices
```
(Same production cron requirement as Notification's scheduled reminders — see that module's README for the exact crontab line.)

## Automatic per-shift billing (no changes to Payment needed)

Mirrors the Notification module's design exactly: `TransactionServiceProvider` calls `WorkLog::observe(WorkLogObserver::class)`. Payment has no idea this module exists. Whenever a `WorkLog` is created (i.e. an Assignment is completed — see the Payment module), if that Shift has both a `client_id` and a `client_billing_rate` set, a `pending` Transaction is created automatically:

```
amount = shift.client_billing_rate × work_log.hours_worked
```

A dispatcher/admin can also create a Transaction manually (`POST /api/transactions`) for anything not tied to one specific completed shift.

## Permission used

`clients.manage` — reused rather than introducing a new permission, since this is client-related financial data and the same people who manage clients are the natural owners of billing them. Already exists in Authorization's seeded permission list, granted to `Company Admin` by default.

## Endpoints

```
GET  /api/transactions
POST /api/transactions                        { client_id, shift_id?, amount, description?, due_at? }
POST /api/transactions/{transaction}/mark-paid

GET    /api/recurring-billing-profiles
POST   /api/recurring-billing-profiles         { client_id, amount, cycle: weekly|monthly, next_billing_date }
PUT    /api/recurring-billing-profiles/{recurringBillingProfile}
DELETE /api/recurring-billing-profiles/{recurringBillingProfile}
```
