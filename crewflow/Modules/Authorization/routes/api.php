<?php

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Http\Controllers\Api\PermissionController;
use Modules\Authorization\Http\Controllers\Api\RoleController;
use Modules\Authorization\Http\Controllers\Api\UserRoleController;

Route::middleware(['auth:sanctum', 'permission:users.manage'])->group(function () {
    Route::post('users/{user}/roles', [UserRoleController::class, 'store']);
    Route::delete('users/{user}/roles/{role}', [UserRoleController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'permission:roles.manage'])->group(function () {
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{role}', [RoleController::class, 'update']);
    Route::delete('roles/{role}', [RoleController::class, 'destroy']);
    Route::get('permissions', [PermissionController::class, 'index']);
});
