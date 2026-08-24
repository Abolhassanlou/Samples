<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\Api\Admin\CompanyManagementController;
use Modules\Tenancy\Http\Controllers\Api\Admin\PlanController;
use Modules\Tenancy\Http\Controllers\Api\Admin\SubscriptionManagementController;
use Modules\Tenancy\Http\Controllers\Api\CompanyRegistrationController;
use Modules\Tenancy\Http\Middleware\AuthenticatePlatformService;

// Central-only route: no tenant middleware here on purpose. This must be
// reachable from the central domain(s) (e.g. crewflow.localhost), before
// any specific company's subdomain exists. This is the ONLY endpoint in
// this module meant to be called by an actual company, not the Platform
// service.
Route::post('companies/register', [CompanyRegistrationController::class, 'register']);

/*
 * Everything below is the internal service API the separate Platform
 * project calls for anything sensitive (create a demo, suspend a
 * company, change a subscription). It is NOT meant to be reachable by
 * end users or company staff — only by the Platform service, which
 * authenticates itself with a static API key (AuthenticatePlatformService).
 * The Platform project may also read Company/Domain/Plan/Subscription
 * directly from the Central database for simple viewing, but every
 * WRITE goes through here, so stancl/tenancy's own logic (and this
 * module's validation) is never bypassed.
 */
Route::prefix('internal')->middleware(AuthenticatePlatformService::class)->group(function () {
    Route::get('companies', [CompanyManagementController::class, 'index']);
    Route::get('companies/{company}', [CompanyManagementController::class, 'show']);
    Route::post('companies/{company}/suspend', [CompanyManagementController::class, 'suspend']);
    Route::post('companies/{company}/unsuspend', [CompanyManagementController::class, 'unsuspend']);

    Route::get('plans', [PlanController::class, 'index']);
    Route::post('plans', [PlanController::class, 'store']);

    Route::post('companies/{company}/subscription', [SubscriptionManagementController::class, 'assign']);
    Route::post('subscriptions/{subscription}/extend', [SubscriptionManagementController::class, 'extend']);
    Route::post('subscriptions/{subscription}/expire', [SubscriptionManagementController::class, 'expire']);
});
