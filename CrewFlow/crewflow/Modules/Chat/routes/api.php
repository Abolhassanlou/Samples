<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Api\ChatController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('chats', [ChatController::class, 'index']);
    Route::post('chats/direct', [ChatController::class, 'startDirect']);
    Route::get('chats/{conversation}/messages', [ChatController::class, 'messages']);
    Route::post('chats/{conversation}/messages', [ChatController::class, 'sendMessage']);
});
