<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\Api\CompanySettingsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('settings', [CompanySettingsController::class, 'show']);

    Route::middleware('permission:settings.manage')->group(function () {
        Route::put('settings', [CompanySettingsController::class, 'update']);
    });
});
