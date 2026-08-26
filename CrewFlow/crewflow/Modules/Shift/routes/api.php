<?php

use Illuminate\Support\Facades\Route;
use Modules\Shift\Http\Controllers\Api\AssignmentController;
use Modules\Shift\Http\Controllers\Api\CancellationRequestController;
use Modules\Shift\Http\Controllers\Api\EventBranchAccessController;
use Modules\Shift\Http\Controllers\Api\EventController;
use Modules\Shift\Http\Controllers\Api\EventWorkerAccessController;
use Modules\Shift\Http\Controllers\Api\ShiftController;
use Modules\Shift\Http\Controllers\Api\ShiftInterestController;
use Modules\Shift\Http\Controllers\Api\ShiftPositionController;
use Modules\Shift\Http\Controllers\Api\ShiftQualificationController;
use Modules\Shift\Http\Controllers\Api\ShiftRoleController;
use Modules\Shift\Http\Controllers\Api\TransportGroupController;

Route::middleware('auth:sanctum')->group(function () {
    // Viewing: any authenticated company user.
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::get('events/{event}/shifts', [EventController::class, 'shifts']);

    Route::get('shift-roles', [ShiftRoleController::class, 'index']);

    Route::get('shifts', [ShiftController::class, 'index']);
    Route::get('shifts/{shift}', [ShiftController::class, 'show']);
    Route::get('shifts/{shift}/positions', [ShiftPositionController::class, 'index']);

    // Creating/editing: dispatcher or admin.
    Route::middleware('permission:shifts.create')->group(function () {
        Route::post('events', [EventController::class, 'store']);
        Route::put('events/{event}', [EventController::class, 'update']);

        Route::post('shift-roles', [ShiftRoleController::class, 'store']);
        Route::put('shift-roles/{shift_role}', [ShiftRoleController::class, 'update']);
        Route::delete('shift-roles/{shift_role}', [ShiftRoleController::class, 'destroy']);

        Route::post('shifts', [ShiftController::class, 'store']);
        Route::put('shifts/{shift}', [ShiftController::class, 'update']);

        Route::post('shifts/{shift}/positions', [ShiftPositionController::class, 'store']);
        Route::put('shifts/{shift}/positions/{position}', [ShiftPositionController::class, 'update']);
        Route::delete('shifts/{shift}/positions/{position}', [ShiftPositionController::class, 'destroy']);

        Route::post('shifts/{shift}/qualifications', [ShiftQualificationController::class, 'store']);
        Route::delete('shifts/{shift}/qualifications/{qualification}', [ShiftQualificationController::class, 'destroy']);
    });

    Route::get('shifts/{shift}/qualifications', [ShiftQualificationController::class, 'index']);

    // Expressing/withdrawing interest: any worker, no special permission.
    Route::post('shifts/{shift}/interest', [ShiftInterestController::class, 'store']);
    Route::delete('shifts/{shift}/interest', [ShiftInterestController::class, 'destroy']);

    // Dispatching: viewing interested workers + assigning requires shifts.dispatch.
    Route::middleware('permission:shifts.dispatch')->group(function () {
        Route::get('shifts/{shift}/interests', [ShiftInterestController::class, 'index']);
        Route::get('shifts/{shift}/assignments', [AssignmentController::class, 'index']);
        Route::post('shifts/{shift}/assignments', [AssignmentController::class, 'store']);

        Route::get('events/{event}/transport-groups', [TransportGroupController::class, 'index']);
        Route::post('events/{event}/transport-groups', [TransportGroupController::class, 'store']);
        Route::put('events/{event}/transport-groups/{transportGroup}', [TransportGroupController::class, 'update']);
        Route::delete('events/{event}/transport-groups/{transportGroup}', [TransportGroupController::class, 'destroy']);

        Route::get('cancellation-requests', [CancellationRequestController::class, 'index']);
        Route::post('cancellation-requests/{cancellationRequest}/approve', [CancellationRequestController::class, 'approve']);
        Route::post('cancellation-requests/{cancellationRequest}/reject', [CancellationRequestController::class, 'reject']);

        Route::get('events/{event}/branch-access', [EventBranchAccessController::class, 'index']);
        Route::post('events/{event}/branch-access', [EventBranchAccessController::class, 'store']);
        Route::delete('events/{event}/branch-access/{branchAccess}', [EventBranchAccessController::class, 'destroy']);

        Route::get('events/{event}/worker-access', [EventWorkerAccessController::class, 'index']);
        Route::post('events/{event}/worker-access', [EventWorkerAccessController::class, 'store']);
        Route::delete('events/{event}/worker-access/{workerAccess}', [EventWorkerAccessController::class, 'destroy']);
    });

    // A worker requesting cancellation of their own assignment — no
    // special permission, the controller checks ownership.
    Route::post('assignments/{assignment}/cancellation-request', [CancellationRequestController::class, 'store']);

    // A worker confirming their own assignment — no special permission,
    // the controller checks the assignment belongs to the requester.
    Route::post('assignments/{assignment}/confirm', [AssignmentController::class, 'confirm']);
});
