# Tenancy Module

Central-level tenant management. Unlike every other module in this project, this one lives in the **Central database**, not inside each company's own database.

## What's inside

- **Company** — this project's tenant model (replaces stancl/tenancy's default `Tenant`). Adds `company_code` (used both as the subdomain label and as a human-friendly reference) and `is_suspended` (manual account suspension, independent of billing).
- **CompanySettings**, **Plan** / **PlanLimit** / **Subscription** — billing/configuration, all Central.
- **PlatformUser** — a user of the platform itself (you, the SaaS owner), completely separate from any company's own staff.
- **Platform roles/permissions** — fully dynamic, reusing Core's exact `Role` model class and Spatie's `Permission` class. See "Why Platform roles reuse Core's Role model" below.
- **`POST /api/companies/register`** — the one truly public, unauthenticated Central endpoint: this is how a brand new company gets onboarded (creates the tenant, its database, migrates it, seeds Core + Organization defaults, creates the first Company Admin).
- **`/api/platform/*`** — the admin panel backend for you: login, list/inspect companies, suspend/unsuspend, manage plans, assign/extend/expire subscriptions.

## Why Platform roles reuse Core's `Role` model

spatie/laravel-permission only supports **one** global Role/Permission model configuration per application (`config('permission.models.role')`). Rather than fighting that with a second parallel model class, Platform roles reuse the *exact same* `Modules\Core\Models\Role` / `Spatie\Permission\Models\Permission` classes tenant users use.

This works because Central and every tenant are just physically separate databases with an **identical** `roles`/`permissions` schema — the same model classes operate correctly against whichever database is "current" at request time (Central for `/api/platform/*` routes, since they never initialize tenancy; a specific tenant's database for that company's own routes). The `guard_name` column (`'central'` for `PlatformUser`, `'api'` for tenant `User`) is what keeps the two worlds' roles from ever mixing, even though they share model classes and — not a coincidence — even share the exact same table *names*, just in different databases.

Practical consequence: this module's Central migrations include a **copy** of Core's `create_permission_tables` and `add_is_system_to_roles_table` migrations, so Central gets its own physical `roles`/`permissions`/etc. tables, separate from (but schema-identical to) every tenant's copy.

## Install

1. Place this folder at `Modules/Tenancy`, enable it, `composer dump-autoload`.
2. In `config/tenancy.php`: `'tenant_model' => \Modules\Tenancy\Models\Company::class` (see earlier setup notes for the full walkthrough — subdomain resolution, `tenant-migrations` folders, etc.)
3. The Central copy of spatie/laravel-permission's `create_permission_tables` migration is **already bundled** here (`database/migrations/2024_02_01_000006b_create_central_permission_tables.php`) — no manual publish/move step needed.
4. `php artisan migrate:fresh` (Central) — should now include `platform_users`, a Central `personal_access_tokens`, `roles`/`permissions`/etc. (Central copy), and the `is_suspended` column on `tenants`.
5. Seed plans and platform roles:
   ```bash
   php artisan db:seed --class="Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder"
   ```
6. Create your own Super Admin account:
   ```bash
   php artisan platform:make-admin
   ```

## Endpoints

```
POST /api/companies/register       { company_name, admin_name, admin_email, admin_password, admin_password_confirmation }

POST /api/platform/login           { email, password }
POST /api/platform/logout
GET  /api/platform/me

GET  /api/platform/companies
GET  /api/platform/companies/{company}
POST /api/platform/companies/{company}/suspend
POST /api/platform/companies/{company}/unsuspend

GET  /api/platform/plans
POST /api/platform/plans           { name, price, billing_cycle?, limits? }   [platform.plans.manage]

POST /api/platform/companies/{company}/subscription   { plan_id, status?, expires_at? }   [platform.subscriptions.manage]
POST /api/platform/subscriptions/{subscription}/extend { expires_at }                      [platform.subscriptions.manage]
POST /api/platform/subscriptions/{subscription}/expire                                     [platform.subscriptions.manage]
```

## Permissions (guard `central`)

| Permission | Super Admin | Support Agent |
|---|---|---|
| `platform.companies.view` | ✅ | ✅ |
| `platform.companies.manage` (suspend/unsuspend) | ✅ | ❌ |
| `platform.plans.manage` | ✅ | ❌ |
| `platform.subscriptions.manage` | ✅ | ❌ |

Like tenant-side roles, these are just a starting seed — create more central roles/permissions any time via the same `Role`/`Permission` model classes with `guard_name = 'central'`.
