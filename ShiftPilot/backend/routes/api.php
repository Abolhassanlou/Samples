<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityOverrideController;
use App\Http\Controllers\Api\AvailabilityRuleController;
use App\Http\Controllers\Api\CompanyLocationController;
use App\Http\Controllers\Api\CompanyLocationMembershipController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', function (Request $request) {
                return response()->json([
                    'data' => [
                        'user' => $request->user(),
                    ],
                ]);
            });

            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get(
            '/company-memberships/{companyMembership}/availability-rules',
            [AvailabilityRuleController::class, 'index']
        );

        Route::post(
            '/company-memberships/{companyMembership}/availability-rules',
            [AvailabilityRuleController::class, 'store']
        );

        Route::get(
            '/availability-rules/{availabilityRule}',
            [AvailabilityRuleController::class, 'show']
        );

        Route::put(
            '/availability-rules/{availabilityRule}',
            [AvailabilityRuleController::class, 'update']
        );

        Route::delete(
            '/availability-rules/{availabilityRule}',
            [AvailabilityRuleController::class, 'destroy']
        );

        Route::get(
            '/company-memberships/{companyMembership}/availability-overrides',
            [AvailabilityOverrideController::class, 'index']
        );

        Route::post(
            '/company-memberships/{companyMembership}/availability-overrides',
            [AvailabilityOverrideController::class, 'store']
        );

        Route::get(
            '/availability-overrides/{availabilityOverride}',
            [AvailabilityOverrideController::class, 'show']
        );

        Route::put(
            '/availability-overrides/{availabilityOverride}',
            [AvailabilityOverrideController::class, 'update']
        );

        Route::delete(
            '/availability-overrides/{availabilityOverride}',
            [AvailabilityOverrideController::class, 'destroy']
        );
        Route::get(
            '/companies/{company}/locations',
            [CompanyLocationController::class, 'index']
        );

        Route::post(
            '/companies/{company}/locations',
            [CompanyLocationController::class, 'store']
        );

        Route::get(
            '/company-locations/{companyLocation}',
            [CompanyLocationController::class, 'show']
        );

        Route::put(
            '/company-locations/{companyLocation}',
            [CompanyLocationController::class, 'update']
        );

        Route::delete(
            '/company-locations/{companyLocation}',
            [CompanyLocationController::class, 'destroy']
        );

        Route::post(
            '/company-locations/{companyLocation}/memberships/{companyMembership}',
            [CompanyLocationMembershipController::class, 'store']
        );

        Route::delete(
            '/company-locations/{companyLocation}/memberships/{companyMembership}',
            [CompanyLocationMembershipController::class, 'destroy']
        );
    });
});
