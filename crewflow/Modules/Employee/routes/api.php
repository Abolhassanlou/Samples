<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\QualificationController;
use Modules\Employee\Http\Controllers\Api\WorkerAvailabilityController;
use Modules\Employee\Http\Controllers\Api\WorkerDocumentController;
use Modules\Employee\Http\Controllers\Api\WorkerProfileController;
use Modules\Employee\Http\Controllers\Api\WorkerQualificationController;

Route::middleware('auth:sanctum')->group(function () {
    // Qualification catalog — viewing is open, managing needs permission.
    Route::get('qualifications', [QualificationController::class, 'index']);
    Route::middleware('permission:qualifications.manage')->group(function () {
        Route::post('qualifications', [QualificationController::class, 'store']);
        Route::put('qualifications/{qualification}', [QualificationController::class, 'update']);
        Route::delete('qualifications/{qualification}', [QualificationController::class, 'destroy']);
    });

    // A worker's own profile / qualifications / availability.
    Route::get('users/{user}/profile', [WorkerProfileController::class, 'show']);
    Route::get('users/{user}/qualifications', [WorkerQualificationController::class, 'index']);
    Route::get('users/{user}/availability', [WorkerAvailabilityController::class, 'index']);
    Route::post('users/{user}/availability', [WorkerAvailabilityController::class, 'sync']);

    // Editing profile / granting-revoking qualifications: admin only.
    Route::middleware('permission:users.manage')->group(function () {
        Route::put('users/{user}/profile', [WorkerProfileController::class, 'update']);
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
