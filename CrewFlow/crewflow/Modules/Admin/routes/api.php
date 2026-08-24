<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Api\CompanyProfileController;
use Modules\Admin\Http\Controllers\Api\DashboardController;
use Modules\Admin\Http\Controllers\Api\ReportController;

Route::middleware('auth:sanctum')->group(function () {
    // Viewing the company's own branding: any authenticated company user
    // (e.g. the worker app's header needs the logo too).
    Route::get('admin/company-profile', [CompanyProfileController::class, 'show']);
    Route::get('admin/company-profile/logo', [CompanyProfileController::class, 'logo']);

    // Everything else here is admin-only.
    Route::middleware('permission:settings.manage')->group(function () {
        Route::post('admin/company-profile', [CompanyProfileController::class, 'update']);

        Route::get('admin/dashboard', [DashboardController::class, 'stats']);
        Route::get('admin/reports/worker-hours', [ReportController::class, 'workerHours']);
        Route::get('admin/reports/shift-summary', [ReportController::class, 'shiftSummary']);
    });
});
