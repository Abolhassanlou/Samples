# Authorization Module

Dynamic role/permission (RBAC) management: `Role` (+ spatie's `Permission`), and every endpoint a Company Admin uses to define custom user groups and decide which sections/actions each group can access. Tenant-scoped, exactly like Authentication.

Depends on **Authentication** (operates on `Modules\Authentication\Models\User`) and **Core** (base Controller, ApiResponse).

## Install

1. Place this folder at `Modules/Authorization`, `php artisan module:enable Authorization`, `composer dump-autoload`.
2. `config/permission.php`:
   ```php
   'models' => [
       'permission' => Spatie\Permission\Models\Permission::class,
       'role' => Modules\Authorization\Models\Role::class, // custom model, adds is_system
   ],
   ```
   > This package version has no top-level `'defaults' => ['guard' => ...]` key — don't add one. Guard scoping is per-row via `guard_name` (always `'api'` here) and via `User::$guard_name = 'api'` (set in Authentication).

## Migrations (bundled, no manual publish needed)

`database/tenant-migrations/2024_01_01_000002_create_permission_tables.php` is an exact copy of spatie/laravel-permission's own migration (bundled directly here — see the comment at the top of that file for why). `..._000003_add_is_system_to_roles_table.php` adds the `is_system` column used to protect the three seeded roles' names from being changed.

Like every tenant-scoped module, these run only via `php artisan tenants:migrate` (never central `migrate`) — see Authentication's README for the full explanation of the non-conventional `tenant-migrations` folder name.

## Seeding

`AuthorizationDatabaseSeeder` creates the default permissions and three starting roles (`Company Admin`, `Dispatcher`, `Worker`). This is called automatically by Tenancy's `POST /api/companies/register` flow for every new company — you don't need to run it manually.

## Endpoints

```
POST   /api/users/{user}/roles             { role: "Dispatcher" }   [users.manage]
DELETE /api/users/{user}/roles/{role}      refuses to remove the last Company Admin  [users.manage]

GET    /api/roles                          list every role with its permissions  [roles.manage]
POST   /api/roles                          { name, permissions?: [...] }         [roles.manage]
PUT    /api/roles/{role}                   { name?, permissions?: [...] }        [roles.manage]
                                            (system roles' NAME can't change, permissions can)
DELETE /api/roles/{role}                   (system roles can't be deleted)       [roles.manage]

GET    /api/permissions                    list every available permission (for a role-creation UI)  [roles.manage]
```

## Why roles are fully dynamic

The three seeded roles are just a starting point. `is_system = true` on them only prevents accidental deletion/renaming — a Company Admin can freely change what permissions `Dispatcher` or `Worker` have, and can create as many additional custom roles (any name, any combination of permissions) as they want. This is the actual feature MohammadMahdi asked for: "admin can define role groups and say which sections each group can access."
