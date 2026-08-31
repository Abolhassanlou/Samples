# Authentication Module

Identity management for the tenant-facing product: the `User` model, registration/login/logout, and the basic user directory (`GET /api/users`). Every table here exists once **per company** (tenant-scoped), not once globally.

**Role/permission management (who can access what) is a separate concern — see the Authorization module.** This module only answers "who are you"; Authorization answers "what can you do".

> Assumes **Core** (base Controller + ApiResponse trait), **Tenancy** (Company/subdomain resolution), and **Authorization** (Role model + permission seeding) are already installed. Auth registration assigns a "Worker" role that Authorization's seeder creates — install Authorization before testing registration end-to-end.

## Install

1. Place this folder at `Modules/Authentication`, `php artisan module:enable Authentication`, `composer dump-autoload`.
2. Publish spatie/laravel-permission's config (the migration itself is bundled in the **Authorization** module, not here):
   ```bash
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-config"
   ```
3. `config/auth.php`:
   ```php
   'providers' => [
       'users' => [
           'driver' => 'eloquent',
           'model' => Modules\Authentication\Models\User::class,
       ],
   ],
   ```

## How this module's migrations run (same reasoning as every tenant-scoped module)

`AuthenticationServiceProvider` deliberately does not call `loadMigrationsFrom()`. Migrations live in `database/tenant-migrations/` (a non-conventional folder name, to dodge a known nwidart/laravel-modules auto-registration behavior — see [nWidart/laravel-modules#1951](https://github.com/nWidart/laravel-modules/issues/1951)) and are picked up only by `php artisan tenants:migrate`, via the glob already configured in `config/tenancy.php`.

- `php artisan migrate` / `migrate:fresh` → Central only. This module's tables should never show up here.
- `php artisan tenants:migrate` → runs inside each company's own database.

## Endpoints

```
POST /api/auth/register   { name, email, phone, password, password_confirmation }
POST /api/auth/login      { email, password }
GET  /api/auth/me         (Bearer token)
POST /api/auth/logout     (Bearer token)

GET  /api/users           list every user in this company        [users.manage]
GET  /api/users/{user}                                            [users.manage]
```

Role assignment (`POST/DELETE /api/users/{user}/roles`) and everything role/permission-related lives in the **Authorization** module's routes, not here.

## Architectural notes

**`personnel_number`**: an auto-assigned, sequential (per company) identifier like `"0007"`, set automatically the moment a `User` is created (see `User::booted()` — a `creating` event, so both `AuthController::register()` and Tenancy's `CompanyRegistrationController` get it for free, no extra code needed in either). It exists purely so an admin can tell apart two workers who happen to share a name — it is **not** a login credential and never needs to be typed in by anyone.

- **`User` is intentionally lean:** worker-specific fields (`employment_type`, `hourly_rate`, home branch) belong to a `WorkerProfile` model in the **Employee** module, not here — keeps this module from needing to depend on Organization.
- **`api` guard:** this project is SPA + Sanctum, so all permission checks use the `api` guard.
- **Routes require a tenant subdomain:** wrapped in `InitializeTenancyBySubdomain` + `PreventAccessFromCentralDomains`.
