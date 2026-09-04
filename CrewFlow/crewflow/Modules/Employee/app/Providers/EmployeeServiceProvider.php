<?php

namespace Modules\Employee\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EmployeeServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Employee';

    protected string $moduleNameLower = 'employee';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->registerViews();
        // Deliberately NOT calling loadMigrationsFrom() — see Authentication
        // module's README for why. Migrations live in `database/tenant-migrations`
        // and are picked up only by `php artisan tenants:migrate`.
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

    protected function registerViews(): void
    {
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);
    }
}
