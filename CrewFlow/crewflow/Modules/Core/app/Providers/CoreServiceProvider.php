<?php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * This module deliberately has no routes, no migrations, no config —
 * it is purely a shared-code kernel (base Controller, ApiResponse trait)
 * that every other module depends on. All domain-specific logic
 * (User, Role, Permission, auth endpoints) lives in the Authentication
 * module instead.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        //
    }
}
