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
        Route::patch('properties/{id}/status', [PropertyController::class, 'updateStatus'])
            ->name('properties.update-status');

        // Property statistics
        Route::post('properties/{id}/toggle-favorite', [PropertyController::class, 'toggleFavorite'])
            ->name('properties.toggle-favorite');
    });
});
