# Setting Module

Per-company configurable behavior: recurrence mode for weekly classes, how job completion is confirmed, shift visibility for unqualified workers, overtime warning thresholds, GPS check-in requirement.

## Important: moved from Tenancy/Central to here (tenant-scoped)

`CompanySettings` used to live in the Tenancy module's Central database, with a `tenant_id` column (one row per company, in one shared table). It has been moved here — one row **per tenant database** — with no `tenant_id` column at all, since the database connection itself is already scoped to one company. This is part of the broader decision to fully separate platform (Central) concerns from product (tenant) concerns — see `project-business-model.md`, section 7.

If you have an existing Tenancy install with the old `company_settings` table in Central, that table and its model/migration should be removed from the Tenancy module (see the migration guide in the project's root, or just start fresh with `migrate:fresh` if you have no production data yet).

## Install

1. Place this folder at `Modules/Setting`, `php artisan module:enable Setting`, `composer dump-autoload`.
2. Migration lives in `database/tenant-migrations/` (same reasoning as every tenant-scoped module — see Authentication's README).

## Permission used

`settings.manage` — already exists in Authorization's seeded permission list, granted to `Company Admin` by default. Viewing (`GET /api/settings`) only requires being an authenticated company user (a worker's app may need `shift_visibility_mode` to decide how to render its shift list).

## Endpoints

```
GET /api/settings
PUT /api/settings   { default_recurrence_mode?, shift_completion_mode?, shift_visibility_mode?, warning_hour_threshold?, warning_income_threshold?, gps_checkin_required? }   [settings.manage]
```

There is always exactly one settings row per company — `CompanySettings::current()` creates it with sensible defaults on first access if it doesn't exist yet (e.g. for a company that registered before this module existed).
