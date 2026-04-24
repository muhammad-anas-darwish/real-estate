<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Http\Controllers\NotificationController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unread-count')
        ->middleware('throttle:120,1');

    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.mark-read');

    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read')
        ->middleware('throttle:10,1');

    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])
        ->name('notifications.destroy');
});