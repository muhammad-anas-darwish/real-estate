<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Http\Controllers\ConversationController;
use Modules\Communication\Http\Controllers\MessageController;

Route::middleware(['auth:sanctum'])->prefix('conversations')->group(function () {
    Route::get('/', [ConversationController::class, 'index'])
        ->name('conversations.index');

    Route::get('/{id}', [ConversationController::class, 'show'])
        ->name('conversations.show');

    Route::post('/', [ConversationController::class, 'store'])
        ->name('conversations.store');

    Route::delete('/{id}', [ConversationController::class, 'destroy'])
        ->name('conversations.destroy');

    Route::post('/property/{propertyId}', [ConversationController::class, 'getOrCreateForProperty'])
        ->name('conversations.get-or-create-for-property');

    Route::get('/{conversationId}/messages', [MessageController::class, 'index'])
        ->name('conversations.messages.index');

    Route::get('/{conversationId}/messages/{id}', [MessageController::class, 'show'])
        ->name('conversations.messages.show');

    Route::post('/{conversationId}/messages', [MessageController::class, 'store'])
        ->name('conversations.messages.store');

    Route::post('/{conversationId}/read', [MessageController::class, 'markAsRead'])
        ->name('conversations.messages.mark-as-read');

    Route::delete('/messages/{id}', [MessageController::class, 'destroy'])
        ->name('conversations.messages.destroy');
});