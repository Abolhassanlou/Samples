<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Api\AuthController;
use Modules\Authentication\Http\Controllers\Api\UserController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Role/permission management for these same users lives in the
// Authorization module (POST/DELETE users/{user}/roles, roles CRUD, etc.)
Route::middleware(['auth:sanctum', 'permission:users.manage'])->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
});
