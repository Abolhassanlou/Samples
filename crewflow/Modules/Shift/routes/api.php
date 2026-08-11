<?php

use Illuminate\Support\Facades\Route;
use Modules\Shift\Http\Controllers\Api\AssignmentController;
use Modules\Shift\Http\Controllers\Api\ShiftController;
use Modules\Shift\Http\Controllers\Api\ShiftInterestController;

Route::middleware('auth:sanctum')->group(function () {
    // Viewing shifts: any authenticated company user.
    Route::get('shifts', [ShiftController::class, 'index']);
    Route::get('shifts/{shift}', [ShiftController::class, 'show']);

    // Creating/editing shifts: dispatcher or admin.
    Route::middleware('permission:shifts.create')->group(function () {
        Route::post('shifts', [ShiftController::class, 'store']);
        Route::put('shifts/{shift}', [ShiftController::class, 'update']);
    });

    // Expressing/withdrawing interest: any worker, no special permission.
    Route::post('shifts/{shift}/interest', [ShiftInterestController::class, 'store']);
    Route::delete('shifts/{shift}/interest', [ShiftInterestController::class, 'destroy']);

    // Dispatching: viewing interested workers + assigning requires shifts.dispatch.
    Route::middleware('permission:shifts.dispatch')->group(function () {
        Route::get('shifts/{shift}/interests', [ShiftInterestController::class, 'index']);
        Route::get('shifts/{shift}/assignments', [AssignmentController::class, 'index']);
        Route::post('shifts/{shift}/assignments', [AssignmentController::class, 'store']);
    });

    // A worker confirming their own assignment — no special permission,
    // the controller checks the assignment belongs to the requester.
    Route::post('assignments/{assignment}/confirm', [AssignmentController::class, 'confirm']);
});
