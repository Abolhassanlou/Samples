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
PUT  /api/auth/me         { name?, phone? }   self-service only — no endpoint exists to edit someone ELSE's name/phone
POST /api/auth/forgot-password   { email, redirect_url }   public — always returns the same success message regardless of whether the email exists; redirect_url is the calling frontend's own reset-password page (see "Forgot password" below)
POST /api/auth/reset-password    { email, token, password, password_confirmation }   public
POST /api/auth/logout     (Bearer token)

GET  /api/users           list every user in this company        [users.manage]
GET  /api/users/{user}                                            [users.manage]
```

Role assignment (`POST/DELETE /api/users/{user}/roles`) and everything role/permission-related lives in the **Authorization** module's routes, not here.

## Forgot password

A plain, standard self-service flow — separate from the Employee module's `reactivate()` (that one is for an admin deliberately restoring a departed worker's standing; this one is for anyone, admin/dispatcher/worker alike, who's simply locked out).

1. `POST /api/auth/forgot-password` — the calling frontend sends `{ email, redirect_url }` (`redirect_url` is that frontend's own reset-password page, e.g. `http://localhost:5174/reset-password` for the worker portal or `:5173` for the admin panel — this module has no fixed config for it since it can't know in advance which of the two apps is calling). If the email matches a real user, a random token is generated, stored **hashed** in `password_reset_tokens` (`email` primary key — `updateOrInsert` so requesting again just replaces the pending token, no duplicate-key errors), and emailed via `PasswordResetMail` as `{redirect_url}?token=...&email=...&company={tenant}`. The response is identical either way — this endpoint can never be used to probe which emails are registered.
2. `POST /api/auth/reset-password` — `{ email, token, password, password_confirmation }`. Checks the token against the stored hash (`Hash::check()`, same treatment as a real password — a leaked `password_reset_tokens` dump can't be used to forge links) and that it's under an hour old, then updates the password and deletes the token row (one-time use).

**Bug fixed in this pass**: `AuthenticationServiceProvider` never called `loadViewsFrom()` — this module had no `resources/views` at all until `PasswordResetMail` needed one, so the missing registration went unnoticed. Without it, `->text('authentication::emails.password-reset-plain', ...)` fails at render time (not at boot) with `"No hint path defined for [authentication]."` — easy to miss until the feature is actually exercised, since the route, controller, and mailable class all look completely correct in isolation.

**Why `company=` rides along in the reset link too**: same reasoning as the Employee module's invite links — the reset-password page opens fresh, with no session yet to read the tenant subdomain from otherwise.

**Deliberately no restriction based on `Worker`/`CompanyWorker` status** — Authentication doesn't know about those (Employee depends on Authentication, not the other way around), so even a worker whose `CompanyWorker` is `inactive`/`blocked` can reset their own password here. That's harmless: it only gets them back into a *session*, not back onto shifts — `WorkerEligibility` and everything else downstream still gates on their actual status regardless of whether they can log in.

## Architectural notes

**`personnel_number`**: an auto-assigned, sequential (per company) identifier like `"0007"`, set automatically the moment a `User` is created (see `User::booted()` — a `creating` event, so both `AuthController::register()` and Tenancy's `CompanyRegistrationController` get it for free, no extra code needed in either). It exists purely so an admin can tell apart two workers who happen to share a name — it is **not** a login credential and never needs to be typed in by anyone.

- **`User` is intentionally lean:** worker-specific fields (`employment_type`, `hourly_rate`, home branch) belong to a `WorkerProfile` model in the **Employee** module, not here — keeps this module from needing to depend on Organization.
- **`api` guard:** this project is SPA + Sanctum, so all permission checks use the `api` guard.
- **Routes require a tenant subdomain:** wrapped in `InitializeTenancyBySubdomain` + `PreventAccessFromCentralDomains`.
