<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\Api\Admin\CompanyManagementController;
use Modules\Tenancy\Http\Controllers\Api\Admin\PlanController;
use Modules\Tenancy\Http\Controllers\Api\Admin\SubscriptionManagementController;
use Modules\Tenancy\Http\Controllers\Api\CompanyRegistrationController;
use Modules\Tenancy\Http\Controllers\Api\PlatformAuthController;

// Central-only routes: no tenant middleware here on purpose. These must be
// reachable from the central domain(s) (e.g. crewflow.localhost), before
// any specific company's subdomain exists.
Route::post('companies/register', [CompanyRegistrationController::class, 'register']);

Route::prefix('platform')->group(function () {
    Route::post('login', [PlatformAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [PlatformAuthController::class, 'logout']);
        Route::get('me', [PlatformAuthController::class, 'me']);

        // Read access: any platform user with the view permission (Super Admin + Support Agent both have it).
        Route::middleware('permission:platform.companies.view')->group(function () {
            Route::get('companies', [CompanyManagementController::class, 'index']);
            Route::get('companies/{company}', [CompanyManagementController::class, 'show']);
        });

        // Mutating actions: gated per-route by the specific permission needed.
        // The controller methods no longer check this themselves — the route is the single source of truth.
        Route::middleware('permission:platform.companies.manage')->group(function () {
            Route::post('companies/{company}/suspend', [CompanyManagementController::class, 'suspend']);
            Route::post('companies/{company}/unsuspend', [CompanyManagementController::class, 'unsuspend']);
        });

        Route::get('plans', [PlanController::class, 'index']);
        Route::post('plans', [PlanController::class, 'store'])
            ->middleware('permission:platform.plans.manage');

        Route::middleware('permission:platform.subscriptions.manage')->group(function () {
            Route::post('companies/{company}/subscription', [SubscriptionManagementController::class, 'assign']);
            Route::post('subscriptions/{subscription}/extend', [SubscriptionManagementController::class, 'extend']);
            Route::post('subscriptions/{subscription}/expire', [SubscriptionManagementController::class, 'expire']);
        });
    });
});
