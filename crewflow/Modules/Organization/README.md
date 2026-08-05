# Organization Module

Branches and clients for each company. Tenant-scoped, exactly like **Core** (lives inside each company's own database, not Central).

## What's inside

- **Branch** — a company's physical location(s). Every company starts with exactly one, `is_main = true`, named "Main" (created automatically by the Tenancy module's company-registration flow). Can be renamed, added to, or deactivated later — a company never has to think about branches until it wants more than one.
- **Client** — the end customer this company performs work for (a building needing guards, a family needing a tutor). Unrelated to `Company` (the tenant itself), which lives in the Tenancy module.
- **UserBranch** (pivot, `user_branch` table) — optionally restricts a dispatcher to a subset of branches. A user with zero rows here has unrestricted access to every branch (see `Modules\Organization\Services\BranchAccessService`).

## Install

1. Place this folder at `Modules/Organization`.
2. `php artisan module:enable Organization`
3. `composer dump-autoload`
4. Migrations live in `database/tenant-migrations/` (not the conventional `database/migrations/`) — same reasoning as Core, see Core's README. They run via `php artisan tenants:migrate`, never the central `php artisan migrate`.

## Permissions used

- `branches.manage` — create/update/deactivate branches, manage a dispatcher's branch restrictions
- `clients.manage` — create/update/delete clients

Both already exist in Core's seeded permission list and are granted to `Company Admin` by default.

## Endpoints

```
GET    /api/branches
POST   /api/branches                 { name, city?, is_main? }
PUT    /api/branches/{branch}
DELETE /api/branches/{branch}        (deactivates, refuses if it's the only active branch)

GET    /api/clients
POST   /api/clients                  { name, type?, default_contact_name?, default_contact_phone?, default_address? }
PUT    /api/clients/{client}
DELETE /api/clients/{client}

GET    /api/users/{user}/branch-restrictions
POST   /api/users/{user}/branch-restrictions      { branch_id }
DELETE /api/users/{user}/branch-restrictions/{branch}
```

## Dependency on this module from Tenancy (important, read this)

`Modules/Tenancy/app/Http/Controllers/Api/CompanyRegistrationController.php` calls this module's `OrganizationDatabaseSeeder` directly (to create the default "Main" branch when a company is registered). This is a deliberate, narrow exception to the project's normal dependency direction — see that controller's docblock for why.

## Next step

Next module to build: **Workforce** (`WorkerProfile`, `WorkerAvailability`, `WorkerDocument`, `WorkerBranchAccess`).
