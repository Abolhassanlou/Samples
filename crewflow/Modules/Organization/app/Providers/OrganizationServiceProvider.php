<?php

namespace Modules\Organization\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class OrganizationServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Organization';

    protected string $moduleNameLower = 'organization';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        // Deliberately NOT calling loadMigrationsFrom() — see Authentication module's
        // README for why (nwidart auto-registers the conventional
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
            module_path($this->moduleName, 'config/config.php') => config_path('organization_module.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'), 'organization_module'
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
