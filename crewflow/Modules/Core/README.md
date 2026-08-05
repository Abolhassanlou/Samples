# Core Module

Shared foundation of the project: `User` and the dynamic role/permission (RBAC) system, at the tenant level (i.e. every one of these tables exists once **per company**, not once globally). Per the project's dependency map, this module is **layer zero** — it depends on nothing else, and every other module depends on it.

> This module assumes the **Tenancy** module is already installed and configured (`stancl/tenancy` + a custom `Company` tenant model). If you haven't set that up yet, do that first — the notes below assume it's in place.

## 1) Prerequisites — packages

If the Laravel project doesn't have these yet:

```bash
composer require nwidart/laravel-modules
composer require spatie/laravel-permission
composer require laravel/sanctum
composer require stancl/tenancy
```

## 2) Copy the module

Place this entire folder at `Modules/Core` in the root of the Laravel project.

## 3) Enable the module

```bash
php artisan module:enable Core
```

## 4) Autoloading — required composer.json change (only needed once per project)

Since nwidart v11+, module classes are not autoloaded automatically. Add this to the **root** `composer.json`, inside `extra` (skip if already done for another module):

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

## 5) Publish spatie/laravel-permission's config

The `create_permission_tables` migration is now **already bundled** in this module (`database/tenant-migrations/2024_01_01_000002_create_permission_tables.php`) — no need to publish or move it manually anymore. You only need the package's config file:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-config"
```

> If you ever upgrade spatie/laravel-permission to a version with a different migration schema, republish (`--tag="permission-migrations"`) and replace the contents of the bundled file above accordingly.
>
> `2024_01_01_000004_create_personal_access_tokens_table.php` (Sanctum's token table), on the other hand, **is** bundled with this module already — you do not need to republish it.

## 6) How this module's migrations actually run (important — read this)

`CoreServiceProvider` deliberately does **not** call `loadMigrationsFrom()`. If it did, these migrations (`users`, `permissions`, `roles`, `personal_access_tokens`) would run as part of the plain, central `php artisan migrate` — which would be wrong, since every company (tenant) needs its own separate copy of these tables, not one shared central copy.

However, that alone isn't enough: **nwidart/laravel-modules (v11.1+) has a known behavior where it auto-registers every enabled module's conventional `database/migrations` folder with the central migrator, regardless of what the module's own provider does** (see [nWidart/laravel-modules#1951](https://github.com/nWidart/laravel-modules/issues/1951) — this project hit the exact same issue). To work around this, this module's migrations deliberately live in a **non-conventional** folder name, `database/tenant-migrations/`, which nwidart's auto-discovery does not scan.

These migrations are picked up by `php artisan tenants:migrate`, which reads paths directly from `config/tenancy.php`'s `migration_parameters['--path']`:

```php
'--path' => array_merge(
    [database_path('migrations/tenant')],
    glob(base_path('Modules/*/database/tenant-migrations'))
),
```

This means:
- `php artisan migrate` / `migrate:fresh` → only touches the Central database (the `Tenancy` module's own tables, plus Laravel's own cache/queue tables). Core's tables should **never** show up here.
- `php artisan tenants:migrate` → runs Core's migrations (and every other tenant-scoped module's, as long as they also use the `database/tenant-migrations/` folder name) inside **each individual company's database**.

There is no separate "remove Laravel's default users migration" step needed here anymore — the central database never has a `users` table at all in this architecture.

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
```

> Note: this package version does not have a top-level `'defaults' => ['guard' => ...]` key — don't add one. Guard scoping is handled per-row via the `guard_name` column (always `'api'` in this project, set explicitly wherever roles/permissions are created) and via `User::$guard_name = 'api'`.

## 9) Run migrations and seeders

This module has no direct "test tenant" of its own — its tables only get created once at least one Company (tenant) exists. See the **Tenancy** module's `POST /api/companies/register` endpoint, which internally seeds this module's roles/permissions and creates the first Company Admin user automatically.

If you need to seed an already-existing tenant manually, run this while tenancy is initialized for it (e.g. inside `php artisan tinker` after `tenancy()->initialize($company)`, or via `$company->run(fn () => (new \Modules\Core\Database\Seeders\CoreDatabaseSeeder())->run())`).

## 10) Quick endpoint test

These only work once inside a company's context (i.e. hit through that company's subdomain, e.g. `acme2024.crewflow.localhost`, not the central domain):

```
POST /api/auth/register   { name, email, phone, password, password_confirmation }
POST /api/auth/login      { email, password }
GET  /api/auth/me         (requires Bearer Token)
POST /api/auth/logout     (requires Bearer Token)
```

### User & role management (requires `users.manage` / `roles.manage` permission — Company Admin has both by default)

```
GET    /api/users                          list every user in this company
GET    /api/users/{user}                   
POST   /api/users/{user}/roles             { role: "Dispatcher" }   assign a role
DELETE /api/users/{user}/roles/{role}      remove a role (refuses to remove the last Company Admin)

GET    /api/roles                          list every role (with its permissions)
POST   /api/roles                          { name, permissions?: [...] }   create a custom role
PUT    /api/roles/{role}                   { name?, permissions?: [...] }  update a role
                                            (system roles' NAME can't change, but their permissions can)
DELETE /api/roles/{role}                   (system roles can't be deleted)

GET    /api/permissions                    list every available permission (for building a role-creation UI)
```

---

## Architectural notes (why it was designed this way)

- **`User` is intentionally lean:** worker-specific fields (`employment_type`, `hourly_rate`, home branch) are NOT here — they belong to a `WorkerProfile` model (one-to-one with `User`) in the **Workforce** module. If those fields lived here, Core would be forced to depend on the Organization module (for `Branch`), which would violate the project's layering.
- **Roles are fully dynamic:** the three seeded roles (`Company Admin`, `Dispatcher`, `Worker`) are just a starting point; `is_system = true` on them only prevents accidental deletion, it doesn't prevent creating other roles.
- **`api` guard:** since the project's decision was SPA + Sanctum (not Inertia), all role/permission checks use the `api` guard everywhere.
- **Routes require a tenant subdomain:** `CoreServiceProvider` wraps this module's routes in `InitializeTenancyBySubdomain` + `PreventAccessFromCentralDomains`, so they are unreachable from the central domain by design.

## Next step

Next module to build: **Shifts** (formerly named Job, then Duty — see the project's business-model document for that naming history).
