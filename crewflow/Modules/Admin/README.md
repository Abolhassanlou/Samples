# Admin Module

The **tenant's own** Company Admin back-office — not the platform's Super Admin (`PlatformUser`, currently in Tenancy, slated for extraction to a separate platform project). This is what a Company Admin at a client company uses to manage their own company profile and see reports/activity.

## Why this module is smaller than you might expect

Most of what "admin management" usually means already has a home in another module — this one deliberately does **not** duplicate any of it:

| Capability | Lives in |
|---|---|
| Define roles / permissions | Authorization |
| Define qualifications | Employee |
| Manage branches | Organization |
| Manage company-wide behavior settings | Setting |
| Manage clients / billing | Client / Transaction |

What was actually missing — and what this module adds — is: **this company's own profile/branding**, and a **read-only dashboard/reports view** that aggregates data already owned by other modules. This module introduces no new source of truth; `DashboardController` and `ReportController` just query Shift/Employee/Payment/Transaction and summarize.

## Install

1. Place this folder at `Modules/Admin`, `php artisan module:enable Admin`, `composer dump-autoload`. Install this **last** — it depends on Authentication, Shift, Payment, and Transaction all being present.
2. Migration lives in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## Permission used

`settings.manage` — reused rather than introducing a new permission (viewing the company profile/logo is open to any authenticated company user; editing it, and the whole dashboard/reports section, requires it). Already granted to `Company Admin` by default.

## Endpoints

```
GET  /api/admin/company-profile           any authenticated user
GET  /api/admin/company-profile/logo      any authenticated user
POST /api/admin/company-profile           multipart: { display_name?, address?, phone?, email?, website?, logo? }   [settings.manage]

GET /api/admin/dashboard                                                    [settings.manage]
    -> { workers_count, active_shifts_count, shifts_this_week_count, pending_transactions_total, pending_transactions_count }

GET /api/admin/reports/worker-hours?from=YYYY-MM-DD&to=YYYY-MM-DD           [settings.manage]
    -> per-worker total hours/pay/shifts-completed in that range

GET /api/admin/reports/shift-summary?from=YYYY-MM-DD&to=YYYY-MM-DD          [settings.manage]
    -> shift counts grouped by status in that range
```
