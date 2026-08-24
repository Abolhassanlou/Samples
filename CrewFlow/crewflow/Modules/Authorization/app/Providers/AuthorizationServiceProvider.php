<?php

namespace Modules\Authorization\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthorizationServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Authorization';

    protected string $moduleNameLower = 'authorization';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        // Deliberately NOT calling loadMigrationsFrom() — see Authentication
        // module's README for why (nwidart auto-registers the conventional
        // `database/migrations` folder with the central migrator regardless;
        // this module's migrations live in `database/tenant-migrations`
        // instead, picked up only by `php artisan tenants:migrate`).
    }

    public function register(): void
    {
        //
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower
        );
    }

    protected function registerRoutes(): void
    {
        Route::middleware([
            'api',
            \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
        ])
            ->prefix('api')
            ->group(module_path($this->moduleName, 'routes/api.php'));
    }
}
