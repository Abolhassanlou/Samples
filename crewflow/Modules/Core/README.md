# Core Module

Shared foundation of the project: `User` and the dynamic role/permission (RBAC) system, at the tenant level. Per the project's dependency map, this module is **layer zero** — it depends on nothing else, and every other module depends on it.

## 1) Prerequisites — packages

If the Laravel project doesn't have these yet:

```bash
composer require nwidart/laravel-modules
composer require spatie/laravel-permission
composer require laravel/sanctum
```

## 2) Copy the module

Place this entire folder at `Modules/Core` in the root of the Laravel project.

## 3) Enable the module

nwidart/laravel-modules disables newly-added modules by default:

```bash
php artisan module:enable Core
```

## 4) Autoloading — required composer.json change

Since nwidart v11+, module classes are not autoloaded automatically. Add this to the **root** `composer.json`, inside `extra`:

```json
"extra": {
    "laravel": {
        "dont-discover": []
    },
    "merge-plugin": {
        "include": [
            "Modules/*/composer.json"
        ]
    }
}
```

Then run:

```bash
composer dump-autoload
```

## 5) Publish spatie/laravel-permission's migration and config

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-migrations"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-config"
```

Move the generated migration file into this module, with a timestamp that runs **before** `2024_01_01_000003_add_is_system_to_roles_table.php` (since that migration alters the `roles` table this one creates):

```bash
mv database/migrations/*_create_permission_tables.php Modules/Core/database/migrations/2024_01_01_000002_create_permission_tables.php
```

> **Why copy it here instead of the default Laravel path?**
> Because in this project's tenancy architecture (`stancl/tenancy`), migrations for the `users`/`roles`/`permissions` tables must be registered as **tenant migrations** (each company has its own tables) — and tenant-migration discovery is typically configured by folder (`Modules/*/database/migrations`), not the default root `database/migrations` folder.

## 6) Remove Laravel's default users migration

Laravel's own `0001_01_01_000000_create_users_table.php` (in the project root's `database/migrations`) creates the same `users` table this module's migration creates. Since Core now owns that table, delete the default one:

```bash
rm database/migrations/0001_01_01_000000_create_users_table.php
```

Leave `0001_01_01_000001_create_cache_table.php` and `0001_01_01_000002_create_jobs_table.php` untouched — those belong to Laravel's own cache/queue infrastructure and are unrelated to our business model.

## 7) Configure `config/auth.php`

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => Modules\Core\Models\User::class,
    ],
],
```

## 8) Configure `config/permission.php`

```php
'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role' => Modules\Core\Models\Role::class, // this module's custom model (adds is_system)
],

'defaults' => [
    'guard' => 'api', // this project is SPA + Sanctum, not session-based
],
```

## 9) Run migrations and seeders

```bash
php artisan module:migrate Core
php artisan module:seed Core
```

## 10) Quick endpoint test

```
POST /api/auth/register   { name, email, phone, password, password_confirmation }
POST /api/auth/login      { email, password }
GET  /api/auth/me         (requires Bearer Token)
POST /api/auth/logout     (requires Bearer Token)
```

---

## Architectural notes (why it was designed this way)

- **`User` is intentionally lean:** worker-specific fields (`employment_type`, `hourly_rate`, home branch) are NOT here — they belong to a `WorkerProfile` model (one-to-one with `User`) in the **Workforce** module. If those fields lived here, Core would be forced to depend on the Organization module (for `Branch`), which would violate the project's layering.
- **Roles are fully dynamic:** the three seeded roles (`Company Admin`, `Dispatcher`, `Worker`) are just a starting point; `is_system = true` on them only prevents accidental deletion, it doesn't prevent creating other roles.
- **`api` guard:** since the project's decision was SPA + Sanctum (not Inertia), all role/permission checks use the `api` guard everywhere.

## Next step

Next module to build: **Tenancy** — containing `Company`, `Domain`, `CompanySettings`, `Plan`, `Subscription`, `PlanLimit`. Unlike Core, this module lives in the **Central DB** (not inside each tenant's database).
