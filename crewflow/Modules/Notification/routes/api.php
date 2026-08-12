<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\Api\AlarmController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('alarms', [AlarmController::class, 'index']);
    Route::post('alarms/{alarm}/read', [AlarmController::class, 'markRead']);
});
