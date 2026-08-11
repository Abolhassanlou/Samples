<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\BranchController;
use Modules\Organization\Http\Controllers\Api\UserBranchController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('branches', [BranchController::class, 'index']);

    Route::middleware('permission:branches.manage')->group(function () {
        Route::post('branches', [BranchController::class, 'store']);
        Route::put('branches/{branch}', [BranchController::class, 'update']);
        Route::delete('branches/{branch}', [BranchController::class, 'destroy']);

        Route::get('users/{user}/branch-restrictions', [UserBranchController::class, 'index']);
        Route::post('users/{user}/branch-restrictions', [UserBranchController::class, 'store']);
        Route::delete('users/{user}/branch-restrictions/{branch}', [UserBranchController::class, 'destroy']);
    });
});
