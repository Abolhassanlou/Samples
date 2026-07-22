<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityOverrideController;
use App\Http\Controllers\Api\AvailabilityRuleController;
use App\Http\Controllers\Api\CompanyLocationController;
use App\Http\Controllers\Api\CompanyLocationMembershipController;
use App\Http\Controllers\Api\EmployeeQualificationController;
use App\Http\Controllers\Api\EmployeeRegionController;
use App\Http\Controllers\Api\QualificationController;
use App\Http\Controllers\Api\RegionController;
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
        Route::get(
            '/companies/{company}/regions',
            [RegionController::class, 'index']
        );

        Route::post(
            '/companies/{company}/regions',
            [RegionController::class, 'store']
        );

        Route::get(
            '/regions/{region}',
            [RegionController::class, 'show']
        );

        Route::put(
            '/regions/{region}',
            [RegionController::class, 'update']
        );

        Route::delete(
            '/regions/{region}',
            [RegionController::class, 'destroy']
        );

        Route::get(
            '/companies/{company}/qualifications',
            [QualificationController::class, 'index']
        );

        Route::post(
            '/companies/{company}/qualifications',
            [QualificationController::class, 'store']
        );

        Route::get(
            '/qualifications/{qualification}',
            [QualificationController::class, 'show']
        );

        Route::put(
            '/qualifications/{qualification}',
            [QualificationController::class, 'update']
        );

        Route::delete(
            '/qualifications/{qualification}',
            [QualificationController::class, 'destroy']
        );

        Route::put(
            '/company-memberships/{companyMembership}/regions/{region}',
            [EmployeeRegionController::class, 'store']
        );

        Route::delete(
            '/company-memberships/{companyMembership}/regions/{region}',
            [EmployeeRegionController::class, 'destroy']
        );

        Route::put(
            '/company-memberships/{companyMembership}/qualifications/{qualification}',
            [EmployeeQualificationController::class, 'store']
        );

        Route::delete(
            '/company-memberships/{companyMembership}/qualifications/{qualification}',
            [EmployeeQualificationController::class, 'destroy']
        );
    });
});
