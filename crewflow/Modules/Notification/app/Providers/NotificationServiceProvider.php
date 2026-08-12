<?php

namespace Modules\Notification\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Notification\Console\Commands\SendShiftReminders;
use Modules\Notification\Observers\AssignmentObserver;
use Modules\Notification\Observers\ShiftObserver;
use Modules\Shift\Models\Assignment;
use Modules\Shift\Models\Shift;

class NotificationServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Notification';

    protected string $moduleNameLower = 'notification';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerObservers();
        $this->registerScheduledReminders();
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

    /**
     * Registers both the artisan command and its schedule, programmatically
     * — this way the module is self-contained and doesn't require editing
     * the project's own routes/console.php. Requires a real cron entry on
     * the server running `php artisan schedule:run` every minute in
     * production; for local testing, just run the command directly (see
     * this module's README).
     */
    protected function registerScheduledReminders(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            SendShiftReminders::class,
        ]);

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command(SendShiftReminders::class)->everyFiveMinutes();
        });
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);
    }

    /**
     * This is the whole point of this module: Shift and Assignment (owned
     * by the Shift module) have no idea these observers exist. Shift never
     * depends on Notification — Notification depends on Shift, and reacts
     * to it from the outside.
     */
    protected function registerObservers(): void
    {
        Shift::observe(ShiftObserver::class);
        Assignment::observe(AssignmentObserver::class);
    }
}
