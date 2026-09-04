<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\CompanyWorkerController;
use Modules\Employee\Http\Controllers\Api\EmploymentContractController;
use Modules\Employee\Http\Controllers\Api\QualificationController;
use Modules\Employee\Http\Controllers\Api\WorkerAvailabilityController;
use Modules\Employee\Http\Controllers\Api\WorkerController;
use Modules\Employee\Http\Controllers\Api\WorkerDirectoryController;
use Modules\Employee\Http\Controllers\Api\WorkerDocumentController;
use Modules\Employee\Http\Controllers\Api\WorkerInvitationController;
use Modules\Employee\Http\Controllers\Api\WorkerQualificationController;

// Public — no auth:sanctum, the worker doesn't have credentials yet.
// Same pattern as Authentication's own auth/register + auth/login.
Route::get('invitations/{token}', [WorkerInvitationController::class, 'show']);
Route::post('invitations/{token}/accept', [WorkerInvitationController::class, 'accept']);

Route::middleware('auth:sanctum')->group(function () {
    // Qualification catalog — viewing is open, managing needs permission.
    Route::get('qualifications', [QualificationController::class, 'index']);
    Route::middleware('permission:qualifications.manage')->group(function () {
        Route::post('qualifications', [QualificationController::class, 'store']);
        Route::put('qualifications/{qualification}', [QualificationController::class, 'update']);
        Route::delete('qualifications/{qualification}', [QualificationController::class, 'destroy']);
    });

    // The dispatcher-facing search/filter directory (by qualification,
    // branch, contract terms, availability) — distinct from
    // Authentication's Users listing. shifts.dispatch, not users.manage,
    // so a Dispatcher (not just Company Admin) can find who's free,
    // qualified, and (with ?eligible=1) actually assignable.
    Route::middleware('permission:shifts.dispatch')->group(function () {
        Route::get('workers', [WorkerDirectoryController::class, 'index']);
        Route::post('workers/invite', [WorkerInvitationController::class, 'store']);
    });

    // A worker's own personal record / qualifications / availability.
    Route::get('users/{user}/worker', [WorkerController::class, 'show']);
    Route::get('users/{user}/qualifications', [WorkerQualificationController::class, 'index']);
    Route::get('users/{user}/availability', [WorkerAvailabilityController::class, 'index']);
    Route::post('users/{user}/availability', [WorkerAvailabilityController::class, 'sync']);

    // Editing personal record / employment relationship / contracts /
    // granting-revoking qualifications: admin only.
    Route::middleware('permission:users.manage')->group(function () {
        Route::put('users/{user}/worker', [WorkerController::class, 'update']);

        Route::get('users/{user}/employment', [CompanyWorkerController::class, 'show']);
        Route::put('users/{user}/employment', [CompanyWorkerController::class, 'update']);

        Route::get('users/{user}/contracts', [EmploymentContractController::class, 'index']);
        Route::post('users/{user}/contracts', [EmploymentContractController::class, 'store']);
        Route::put('users/{user}/contracts/{contract}', [EmploymentContractController::class, 'update']);
    });
    Route::middleware('permission:qualifications.manage')->group(function () {
        Route::post('users/{user}/qualifications', [WorkerQualificationController::class, 'store']);
        Route::delete('users/{user}/qualifications/{workerQualification}', [WorkerQualificationController::class, 'destroy']);
    });

    // Documents: a worker manages their own; review requires documents.review.
    Route::get('documents', [WorkerDocumentController::class, 'index']);
    Route::post('documents', [WorkerDocumentController::class, 'store']);
    Route::get('documents/{document}/download', [WorkerDocumentController::class, 'download']);

    Route::middleware('permission:documents.review')->group(function () {
        Route::get('documents/pending', [WorkerDocumentController::class, 'pending']);
        Route::post('documents/{document}/review', [WorkerDocumentController::class, 'review']);
    });
});
