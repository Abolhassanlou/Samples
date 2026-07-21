<?php

use App\Http\Controllers\Api\AuthController;
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
});