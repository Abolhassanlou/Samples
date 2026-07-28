<?php

namespace Modules\Tenancy\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Note: this class is intentionally named TenancyModuleServiceProvider,
 * NOT TenancyServiceProvider, to avoid clashing with the root
 * App\Providers\TenancyServiceProvider that stancl/tenancy's own
 * `tenancy:install` command generates. That provider stays untouched
 * and keeps handling stancl's events/middleware priority; this one only
 * registers this module's own routes/migrations/config.
 */
class TenancyModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Tenancy';

    protected string $moduleNameLower = 'tenancy';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
    }

    public function register(): void
    {
        //
    }

    protected function registerConfig(): void
    {
        // Deliberately published under 'tenancy_module', NOT 'tenancy' — the
        // 'tenancy' config key already belongs to stancl/tenancy's own
        // config/tenancy.php file, and reusing it here would risk clashing
        // with that file depending on provider load order.
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path('tenancy_module.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'), 'tenancy_module'
        );
    }

    protected function registerRoutes(): void
    {
        // These are CENTRAL routes (company registration, plan listing, etc.),
        // deliberately registered WITHOUT any tenant-identification middleware.
        Route::middleware('api')
            ->prefix('api')
            ->group(module_path($this->moduleName, 'routes/api.php'));
    }
}
