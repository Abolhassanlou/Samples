# Organization Module

Branches for each company. Tenant-scoped, exactly like Authentication (lives inside each company's own database, not Central).

**`Client` (the company's own customers) has moved to its own module** — see `Modules/Client`. This module now only owns `Branch` and the dispatcher branch-restriction pivot.

## What's inside

- **Branch** — a company's physical location(s). Every company starts with exactly one, `is_main = true`, named "Main" (created automatically by the Tenancy module's company-registration flow). Can be renamed, added to, or deactivated later — a company never has to think about branches until it wants more than one.
- **UserBranch** (pivot, `user_branch` table) — optionally restricts a dispatcher to a subset of branches. A user with zero rows here has unrestricted access to every branch (see `Modules\Organization\Services\BranchAccessService`).

## Install

1. Place this folder at `Modules/Organization`.
2. `php artisan module:enable Organization`
3. `composer dump-autoload`
4. Migrations live in `database/tenant-migrations/` (not the conventional `database/migrations/`) — see Authentication's README for the full explanation. They run via `php artisan tenants:migrate`, never the central `php artisan migrate`.

## Permission used

- `branches.manage` — create/update/deactivate branches, manage a dispatcher's branch restrictions

Already exists in Authorization's seeded permission list, granted to `Company Admin` by default.

**Authorization is enforced entirely at the route level**, via spatie/laravel-permission's `permission:` middleware (see `routes/api.php`) — not inside controllers, and not via Policies. This requires the middleware alias to be registered once, in the project's `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

## Endpoints

```
GET    /api/branches
POST   /api/branches                 { name, city?, is_main? }
PUT    /api/branches/{branch}
DELETE /api/branches/{branch}        (deactivates, refuses if it's the only active branch)

GET    /api/users/{user}/branch-restrictions
POST   /api/users/{user}/branch-restrictions      { branch_id }
DELETE /api/users/{user}/branch-restrictions/{branch}
```

## Dependency on this module from Tenancy (important, read this)

`Modules/Tenancy/app/Http/Controllers/Api/CompanyRegistrationController.php` calls this module's `OrganizationDatabaseSeeder` directly (to create the default "Main" branch when a company is registered). This is a deliberate, narrow exception to the project's normal dependency direction — see that controller's docblock for why.

## Next step

Next module to build: **Client** (already split out, see `Modules/Client`) is done. After that: **Employee** (`WorkerProfile`, `WorkerAvailability`, `WorkerDocument`).
