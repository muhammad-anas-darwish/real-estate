<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Http\Controllers\ChatRoomController;
use Modules\Communication\Http\Controllers\MessageController;
use Modules\Communication\Http\Controllers\TypingController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('chat/rooms', [ChatRoomController::class, 'index'])
        ->name('chat.rooms.index');

    Route::post('chat/rooms', [ChatRoomController::class, 'store'])
        ->name('chat.rooms.store');

    Route::get('chat/rooms/{roomId}', [ChatRoomController::class, 'show'])
        ->name('chat.rooms.show');

    Route::get('chat/rooms/{roomId}/messages', [MessageController::class, 'index'])
        ->name('chat.rooms.messages.index');

    Route::post('chat/rooms/{roomId}/messages', [MessageController::class, 'store'])
        ->name('chat.rooms.messages.store');

    Route::delete('chat/rooms/{roomId}/messages/{messageId}', [MessageController::class, 'destroy'])
        ->name('chat.rooms.messages.destroy');

    Route::post('chat/rooms/{roomId}/typing', [TypingController::class, 'store'])
        ->name('chat.rooms.typing');
});