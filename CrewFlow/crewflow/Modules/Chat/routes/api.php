<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Api\ChatController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('chats', [ChatController::class, 'index']);
    Route::post('chats/direct', [ChatController::class, 'startDirect']);
    Route::post('chats/group', [ChatController::class, 'startGroup']);
    Route::get('chats/{conversation}/messages', [ChatController::class, 'messages']);
    Route::post('chats/{conversation}/messages', [ChatController::class, 'sendMessage']);

    // Broadcasting to many recipients at once, and browsing the history
    // of past broadcasts, are both dispatching actions — reuse
    // shifts.dispatch rather than introducing a new permission.
    Route::middleware('permission:shifts.dispatch')->group(function () {
        Route::post('chats/broadcast', [ChatController::class, 'broadcast']);
        Route::get('chats/broadcasts', [ChatController::class, 'broadcastsIndex']);
        Route::get('chats/broadcasts/{broadcast}', [ChatController::class, 'broadcastShow']);
    });
});
