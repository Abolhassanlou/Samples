<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\Api\AssignmentCompletionController;
use Modules\Payment\Http\Controllers\Api\WorkerRatingController;
use Modules\Payment\Http\Controllers\Api\WorkLogController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('assignments/{assignment}/complete', [AssignmentCompletionController::class, 'store']);
    Route::post('assignments/{assignment}/rate', [WorkerRatingController::class, 'store']);

    Route::get('users/{user}/work-logs', [WorkLogController::class, 'index']);
});
