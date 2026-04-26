<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Http\Controllers\ChatRoomController;
use Modules\Communication\Http\Controllers\MessageController;
use Modules\Communication\Http\Controllers\TypingController;
use Modules\Communication\Http\Controllers\NotificationController;
use Modules\Communication\Http\Controllers\Fcm\FcmTokenController;

Route::prefix('api/v1/chat')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('rooms', [ChatRoomController::class, 'index'])->name('api.v1.chat.rooms.index');
    Route::post('rooms', [ChatRoomController::class, 'store'])->name('api.v1.chat.rooms.store');
    Route::get('rooms/{roomId}', [ChatRoomController::class, 'show'])->name('api.v1.chat.rooms.show');
    Route::get('rooms/{roomId}/messages', [MessageController::class, 'index'])->name('api.v1.chat.rooms.messages.index');
    Route::post('rooms/{roomId}/messages', [MessageController::class, 'store'])->name('api.v1.chat.rooms.messages.store');
    Route::delete('rooms/{roomId}/messages/{messageId}', [MessageController::class, 'destroy'])->name('api.v1.chat.rooms.messages.destroy');
    Route::post('rooms/{roomId}/typing', [TypingController::class, 'store'])->name('api.v1.chat.rooms.typing')->middleware('throttle:1,1');
});

Route::prefix('api/v1/notifications')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('api.v1.notifications.index')->middleware('throttle:60,1');
    Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('api.v1.notifications.unread-count')->middleware('throttle:120,1');
    Route::patch('{id}/read', [NotificationController::class, 'markAsRead'])->name('api.v1.notifications.mark-read');
    Route::patch('read-all', [NotificationController::class, 'markAllAsRead'])->name('api.v1.notifications.mark-all-read')->middleware('throttle:10,1');
    Route::delete('{id}', [NotificationController::class, 'destroy'])->name('api.v1.notifications.destroy');
});

Route::prefix('api/v1/fcm')->middleware(['auth:sanctum'])->group(function () {
    Route::post('register', [FcmTokenController::class, 'store'])->name('api.v1.fcm.register');
    Route::delete('revoke', [FcmTokenController::class, 'destroy'])->name('api.v1.fcm.revoke');
});