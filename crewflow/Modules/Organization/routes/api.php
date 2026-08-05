<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\BranchController;
use Modules\Organization\Http\Controllers\Api\ClientController;
use Modules\Organization\Http\Controllers\Api\UserBranchController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('branches', BranchController::class)->except(['show']);
    Route::apiResource('clients', ClientController::class)->except(['show']);

    Route::get('users/{user}/branch-restrictions', [UserBranchController::class, 'index']);
    Route::post('users/{user}/branch-restrictions', [UserBranchController::class, 'store']);
    Route::delete('users/{user}/branch-restrictions/{branch}', [UserBranchController::class, 'destroy']);
});
