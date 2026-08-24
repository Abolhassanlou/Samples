# Platform Project

The platform's own admin panel (you, as the SaaS owner) — completely independent from `crewflow`. See `crewflow`'s `project-business-model.md` (section 7) and `Modules/Tenancy/README.md` for the full architecture discussion this project implements.

**Golden rule: this project never writes directly to crewflow's Central database.** It reads Company/Domain/Plan/Subscription data directly (read-only connection) for simple viewing, but every mutation (suspend a company, change a subscription, create a plan) goes through `CrewflowApiClient`, which calls crewflow's `/api/internal/*` API instead.

## 1) Bootstrap (you've already done this if you're reading this after running the commands)

```bash
cd ~/projects/Samples
composer create-project laravel/laravel platform
cd platform
composer require laravel/sanctum spatie/laravel-permission
php artisan install:api
```

## 2) Extract this code into your fresh install

Unzip this archive's `app/`, `database/`, `routes/` folders directly into `~/projects/Samples/platform/` — they'll merge with (not replace) the framework's own files. `routes/api.php` **will** be replaced (the fresh install's version is nearly empty anyway).

## 3) Publish spatie/laravel-permission's migration + config

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

## 4) `config/auth.php` — point the default user model at PlatformUser

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\PlatformUser::class,
    ],
],
```

## 5) `bootstrap/app.php` — register spatie's permission middleware

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

## 6) `config/database.php` — add the read-only 'central' connection

Add a new entry inside the `'connections'` array (alongside the default `'mysql'` one):

```php
'central' => [
    'driver' => 'mysql',
    'host' => env('CENTRAL_DB_HOST', '127.0.0.1'),
    'port' => env('CENTRAL_DB_PORT', '3306'),
    'database' => env('CENTRAL_DB_DATABASE', 'crewflow'),
    'username' => env('CENTRAL_DB_USERNAME'),
    'password' => env('CENTRAL_DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
],
```

**Create a dedicated, genuinely read-only MySQL user for this** — don't reuse crewflow's own `crewflow` DB user here, since that one has full write access:

```sql
CREATE USER 'platform_readonly'@'localhost' IDENTIFIED BY 'choose-a-real-password';
GRANT SELECT ON crewflow.tenants TO 'platform_readonly'@'localhost';
GRANT SELECT ON crewflow.domains TO 'platform_readonly'@'localhost';
GRANT SELECT ON crewflow.plans TO 'platform_readonly'@'localhost';
GRANT SELECT ON crewflow.plan_limits TO 'platform_readonly'@'localhost';
GRANT SELECT ON crewflow.subscriptions TO 'platform_readonly'@'localhost';
FLUSH PRIVILEGES;
```

This way, even a bug in this project's own code (e.g. someone accidentally calling `->save()` on a `Central` model) fails at the database level instead of silently corrupting crewflow's data.

## 7) `config/services.php` — add the crewflow API client config

```php
'crewflow' => [
    'base_url' => env('CREWFLOW_BASE_URL', 'http://127.0.0.1:8000'),
    'api_key' => env('CREWFLOW_PLATFORM_API_KEY'),
],
```

## 8) `.env` additions

```
CENTRAL_DB_HOST=127.0.0.1
CENTRAL_DB_PORT=3306
CENTRAL_DB_DATABASE=crewflow
CENTRAL_DB_USERNAME=platform_readonly
CENTRAL_DB_PASSWORD=choose-a-real-password

CREWFLOW_BASE_URL=http://127.0.0.1:8000
CREWFLOW_PLATFORM_API_KEY=<the exact same value as crewflow's PLATFORM_SERVICE_API_KEY>
```

The two `*_API_KEY` values (this project's `CREWFLOW_PLATFORM_API_KEY` and crewflow's own `PLATFORM_SERVICE_API_KEY`) **must match exactly** — see crewflow's `Modules/Tenancy/README.md` for how to generate/rotate it.

## 9) Migrate, seed, create your first admin

This project's own database (the default `mysql` connection — a **new, separate** database, e.g. `platform`, not `crewflow` itself) needs to exist first:

```bash
mysql -u root -e "CREATE DATABASE platform;"
```

Then, matching that DB's credentials in `.env`'s default `DB_*` variables:

```bash
php artisan migrate
php artisan db:seed --class=PlatformDatabaseSeeder
php artisan platform:make-admin
```

## 10) Run it (on a different port than crewflow)

```bash
php artisan serve --port=8001
```

## Endpoints

```
POST /api/login    { email, password }
POST /api/logout
GET  /api/me

GET  /api/companies                                   [companies.view]
GET  /api/companies/{company}                          [companies.view]
POST /api/companies/{company}/suspend                  [companies.manage]
POST /api/companies/{company}/unsuspend                [companies.manage]

GET  /api/plans
POST /api/plans                                        [plans.manage]

POST /api/companies/{company}/subscription              [subscriptions.manage]
POST /api/subscriptions/{subscription}/extend           [subscriptions.manage]
POST /api/subscriptions/{subscription}/expire           [subscriptions.manage]

GET    /api/users                                       [users.manage]
POST   /api/users/{user}/roles                          [users.manage]
DELETE /api/users/{user}/roles/{role}                   [users.manage]

GET    /api/roles                                       [roles.manage]
POST   /api/roles                                       [roles.manage]
PUT    /api/roles/{role}                                [roles.manage]
DELETE /api/roles/{role}                                [roles.manage]
GET    /api/permissions                                 [roles.manage]
```

## Why crewflow's `PLATFORM_SERVICE_API_KEY` check doesn't care which PlatformUser you are

That's entirely by design — see `CrewflowApiClient`'s docblock. crewflow only verifies "is this a legitimate call from the Platform service" (the static API key). **Which human is allowed to trigger which action is decided entirely in this project**, via the `permission:` middleware on each route above, before `CrewflowApiClient` is ever called.
