<?php

namespace Modules\Chat\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Chat\Observers\AssignmentObserver;
use Modules\Shift\Models\Assignment;

class ChatServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Chat';

    protected string $moduleNameLower = 'chat';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->registerObservers();
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
     * Shift has no idea this module exists — same "observe from the
     * outside" pattern the Notification module uses on Shift/Assignment,
     * and Transaction uses on Payment's WorkLog.
     */
    protected function registerObservers(): void
    {
        Assignment::observe(AssignmentObserver::class);
    }
}
