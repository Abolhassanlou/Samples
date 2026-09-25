<?php

namespace Modules\Employee\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Employee\Console\Commands\ExpireContracts;
use Modules\Employee\Console\Commands\ExpireWorkAuthorizations;

class EmployeeServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Employee';

    protected string $moduleNameLower = 'employee';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerCommands();
        $this->registerSchedule();
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

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireWorkAuthorizations::class,
                ExpireContracts::class,
            ]);
        }
    }

    /**
     * Registered here, not in the core app's routes/console.php, so
     * this module stays self-contained — installing/removing it is a
     * single directory swap with no edit to any file outside Modules/
     * Employee needed. `$this->app->booted()` defers this until the
     * Schedule singleton actually exists; running it any earlier in
     * boot() would resolve too soon.
     */
    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command(ExpireWorkAuthorizations::class)->daily();
            $schedule->command(ExpireContracts::class)->daily();
        });
    }
}
