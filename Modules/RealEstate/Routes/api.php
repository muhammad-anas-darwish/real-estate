<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Http\Controllers\PropertyController;

Route::prefix('api')->group(function () {
    // Public routes - view approved properties
    Route::get('properties', [PropertyController::class, 'index'])
        ->name('properties.index');

    Route::get('properties/random', [PropertyController::class, 'random'])
        ->name('properties.random');

    // Property statistics (must be before {id} route)
    Route::get('properties/statistics', [PropertyController::class, 'statistics'])
        ->name('properties.statistics');

    Route::get('properties/{id}', [PropertyController::class, 'show'])
        ->name('properties.show');

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Properties CRUD
        Route::apiResource('properties', PropertyController::class)->except(['index', 'show']);

        // Property status management
        Route::post('properties/{id}/approve', [PropertyController::class, 'approve'])
            ->name('properties.approve');

        Route::post('properties/{id}/reject', [PropertyController::class, 'reject'])
            ->name('properties.reject');

        Route::post('properties/{id}/mark-sold', [PropertyController::class, 'markAsSold'])
            ->name('properties.mark-sold');

        Route::post('properties/{id}/archive', [PropertyController::class, 'archive'])
            ->name('properties.archive');

        Route::post('properties/{id}/restore', [PropertyController::class, 'restore'])
            ->name('properties.restore');

        Route::put('properties/{id}/status', [PropertyController::class, 'updateStatus'])
            ->name('properties.update-status');

        // Property statistics
        Route::post('properties/{id}/toggle-favorite', [PropertyController::class, 'toggleFavorite'])
            ->name('properties.toggle-favorite');
    });
});
