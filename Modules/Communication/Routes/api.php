<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Http\Controllers\ChatRoomController;
use Modules\Communication\Http\Controllers\Fcm\FcmTokenController;
use Modules\Communication\Http\Controllers\MessageController;
use Modules\Communication\Http\Controllers\NotificationController;
use Modules\Communication\Http\Controllers\TypingController;

Route::prefix('api/chat')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('rooms', [ChatRoomController::class, 'index'])->name('api.chat.rooms.index');
    Route::post('rooms', [ChatRoomController::class, 'store'])->name('api.chat.rooms.store');
    Route::get('rooms/{roomId}', [ChatRoomController::class, 'show'])->name('api.chat.rooms.show');
    Route::get('rooms/{roomId}/messages', [MessageController::class, 'index'])->name('api.chat.rooms.messages.index');
    Route::post('rooms/{roomId}/messages', [MessageController::class, 'store'])->name('api.chat.rooms.messages.store');
    Route::delete('rooms/{roomId}/messages/{messageId}', [MessageController::class, 'destroy'])->name('api.chat.rooms.messages.destroy');
    Route::post('rooms/{roomId}/typing', [TypingController::class, 'store'])->name('api.chat.rooms.typing')->middleware('throttle:1,1');
});

Route::prefix('api/notifications')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('api.notifications.index')->middleware('throttle:60,1');
    Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread-count')->middleware('throttle:120,1');
    Route::patch('{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.mark-read');
    Route::patch('read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.mark-all-read')->middleware('throttle:10,1');
    Route::delete('{id}', [NotificationController::class, 'destroy'])->name('api.notifications.destroy');
});

Route::prefix('api/fcm')->middleware(['auth:sanctum'])->group(function () {
    Route::post('register', [FcmTokenController::class, 'store'])->name('api.fcm.register');
    Route::delete('revoke', [FcmTokenController::class, 'destroy'])->name('api.fcm.revoke');
});
