<?php

namespace Modules\Transaction\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Payment\Models\WorkLog;
use Modules\Transaction\Console\Commands\GenerateRecurringInvoices;
use Modules\Transaction\Observers\WorkLogObserver;

class TransactionServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Transaction';

    protected string $moduleNameLower = 'transaction';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->registerObservers();
        $this->registerScheduledBilling();
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
     * Registers both the artisan command and its daily schedule
     * programmatically — same approach as Notification's reminder
     * command, so no edits to the project's own routes/console.php.
     */
    protected function registerScheduledBilling(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            GenerateRecurringInvoices::class,
        ]);

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command(GenerateRecurringInvoices::class)->daily();
        });
    }

    /**
     * Payment has no idea this module exists — same "observe from the
     * outside" pattern the Notification module uses on Shift/Assignment.
     */
    protected function registerObservers(): void
    {
        WorkLog::observe(WorkLogObserver::class);
    }
}
