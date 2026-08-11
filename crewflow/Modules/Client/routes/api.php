<?php

use Illuminate\Support\Facades\Route;
use Modules\Client\Http\Controllers\Api\ClientController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('clients', [ClientController::class, 'index']);

    Route::middleware('permission:clients.manage')->group(function () {
        Route::post('clients', [ClientController::class, 'store']);
        Route::put('clients/{client}', [ClientController::class, 'update']);
        Route::delete('clients/{client}', [ClientController::class, 'destroy']);
    });
});
