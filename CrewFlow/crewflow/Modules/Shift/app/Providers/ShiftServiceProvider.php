<?php

namespace Modules\Shift\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Employee\Contracts\ReservedTimeProvider;
use Modules\Shift\Services\ReservedTimes;

class ShiftServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Shift';

    protected string $moduleNameLower = 'shift';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        // Deliberately NOT calling loadMigrationsFrom() — see Authentication
        // module's README for why. Migrations live in `database/tenant-migrations`
        // and are picked up only by `php artisan tenants:migrate`.
    }

    public function register(): void
    {
        // Employee's availability editor refuses to drop hours a worker is
        // already booked into, but only knows the ReservedTimeProvider
        // contract — what counts as "booked" (an active Assignment) is
        // Shift's to say. Plain bind(), not bindIf(): Employee registers a
        // do-nothing default with bindIf(), and this must replace it.
        $this->app->bind(ReservedTimeProvider::class, ReservedTimes::class);
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
