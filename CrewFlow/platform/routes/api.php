<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::middleware('permission:companies.view')->group(function () {
        Route::get('companies', [CompanyController::class, 'index']);
        Route::get('companies/{company}', [CompanyController::class, 'show']);
    });

    Route::middleware('permission:companies.manage')->group(function () {
        Route::post('companies/{company}/suspend', [CompanyController::class, 'suspend']);
        Route::post('companies/{company}/unsuspend', [CompanyController::class, 'unsuspend']);
    });

    Route::get('plans', [PlanController::class, 'index']);
    Route::post('plans', [PlanController::class, 'store'])->middleware('permission:plans.manage');

    Route::middleware('permission:subscriptions.manage')->group(function () {
        Route::post('companies/{company}/subscription', [SubscriptionController::class, 'assign']);
        Route::post('subscriptions/{subscription}/extend', [SubscriptionController::class, 'extend']);
        Route::post('subscriptions/{subscription}/expire', [SubscriptionController::class, 'expire']);
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::post('users/{user}/roles', [UserController::class, 'assignRole']);
        Route::delete('users/{user}/roles/{role}', [UserController::class, 'removeRole']);
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('roles', [RoleController::class, 'index']);
        Route::post('roles', [RoleController::class, 'store']);
        Route::put('roles/{role}', [RoleController::class, 'update']);
        Route::delete('roles/{role}', [RoleController::class, 'destroy']);
        Route::get('permissions', [RoleController::class, 'permissions']);
    });
});
