<?php

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Core';

    protected string $moduleNameLower = 'core';

    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        // Note: deliberately NOT calling loadMigrationsFrom() here. This
        // module's migrations must run ONLY as tenant migrations (via
        // `php artisan tenants:migrate`, which reads paths directly from
        // config/tenancy.php), never as part of the default central
        // `php artisan migrate`. Only the Tenancy module's own provider
        // should call loadMigrationsFrom(), since its tables genuinely
        // belong in the Central database.
    }

    /**
     * Register the service provider.
     */
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
        // Wrapped in the tenancy middleware so these routes are only reachable
        // through a company's subdomain (e.g. acme2024.crewflow.localhost),
        // never on the central domain. InitializeTenancyBySubdomain switches
        // the DB connection to that company's tenant database before the
        // request reaches any Core controller.
        Route::middleware([
            'api',
            \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
        ])
            ->prefix('api')
            ->group(module_path($this->moduleName, 'routes/api.php'));
    }
}
