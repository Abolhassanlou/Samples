# Tenancy Module

Central-level tenant management. This module lives in the **Central database**, not inside each company's own database.

## Architectural note (important — read this before anything else)

Platform administration (`PlatformUser`, the platform admin panel) used to live in this module. **It has moved out entirely, into a separate `Platform` project**, per a deliberate architecture decision:

- `crewflow` (this project) remains the **sole owner** of `Company`, `Domain`, `Plan`, `Subscription`, and every `stancl/tenancy` operation (creating a tenant database, changing a domain, etc.).
- The separate `Platform` project owns `PlatformUser` and its own independent admin panel entirely.
- `Platform` may connect **read-only** directly to the Central database for simple viewing (companies list, plans, subscriptions) — but **every write** (suspend a company, assign/extend/expire a subscription, create a plan) must go through this module's `/api/internal/*` API instead of writing directly. This is what keeps `stancl/tenancy`'s own logic, this module's validation, and its events from ever being bypassed by a second codebase touching the same tables directly.

Why not just let both projects write to the same DB freely: a migration change in one could break the other, validation/events in `crewflow` get skipped, a Company could end up created without its tenant database, and separating or scaling the two systems later becomes much harder. See `project-business-model.md` (main project) for the full discussion.

## What's inside

- **Company** — this project's tenant model (replaces stancl/tenancy's default `Tenant`). Adds `company_code` (used both as the subdomain label and as a human-friendly reference) and `is_suspended` (manual account suspension, independent of billing).
- **Plan** / **PlanLimit** / **Subscription** — billing/configuration, Central.
- **`POST /api/companies/register`** — the one truly public, unauthenticated endpoint: this is how a brand new company gets onboarded (creates the tenant, its database, migrates it, seeds Core + Organization + Authorization defaults, creates the first Company Admin, starts a default trial subscription).
- **`/api/internal/*`** — the service API the `Platform` project calls for anything sensitive. Secured by `AuthenticatePlatformService` (a static API key), **not** by any per-user session — see that middleware's docblock.

## Install

1. Place this folder at `Modules/Tenancy`, enable it, `composer dump-autoload`.
2. In `config/tenancy.php`: `'tenant_model' => \Modules\Tenancy\Models\Company::class` (see earlier setup notes for the full walkthrough — subdomain resolution, `tenant-migrations` folders, etc.)
3. `php artisan migrate:fresh` (Central) — includes `companies`/`domains` (from stancl/tenancy itself), `plans`, `plan_limits`, `subscriptions`, and the `is_suspended` column on `tenants`.
4. Seed plans:
   ```bash
   php artisan db:seed --class="Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder"
   ```
5. Generate and set a service API key for the `Platform` project to use:
   ```bash
   php artisan tinker --execute="echo Str::random(64);"
   ```
   Add it to `.env`:
   ```
   PLATFORM_SERVICE_API_KEY=<paste the generated value>
   ```
   The `Platform` project needs this exact same value configured on its own side, to send as `Authorization: Bearer <value>` on every call to `/api/internal/*`.

## Endpoints

```
POST /api/companies/register       { company_name, admin_name, admin_email, admin_password, admin_password_confirmation }
```

### Internal service API (requires `Authorization: Bearer <PLATFORM_SERVICE_API_KEY>`)

```
GET  /api/internal/companies
GET  /api/internal/companies/{company}
POST /api/internal/companies/{company}/suspend
POST /api/internal/companies/{company}/unsuspend

GET  /api/internal/plans
POST /api/internal/plans                                       { name, price, billing_cycle?, limits? }

POST /api/internal/companies/{company}/subscription             { plan_id, status?, expires_at? }
POST /api/internal/subscriptions/{subscription}/extend          { expires_at }
POST /api/internal/subscriptions/{subscription}/expire
```

## Rotating the API key

Since this is a single static secret (see `AuthenticatePlatformService`'s docblock for why, and what to upgrade to later if needed), rotate it by generating a new value, updating `.env` on **both** projects, and restarting both. There's no overlap/grace-period support in this first pass — a mismatched key is simply rejected with 401.
