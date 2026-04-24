<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Http\Controllers\PropertyController;

Route::prefix('api')->group(function () {
    // Public routes - view only approved/sold properties
    Route::get('properties', [PropertyController::class, 'indexPublic'])
        ->name('properties.index');

    Route::get('properties/random', [PropertyController::class, 'random'])
        ->name('properties.random');

    Route::get('properties/{id}', [PropertyController::class, 'showPublic'])
        ->name('properties.show');

    // Protected routes - Dashboard
    Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
        // Property statistics
        Route::get('properties/statistics', [PropertyController::class, 'statistics'])
            ->name('dashboard.properties.statistics');

        // Dashboard properties (all statuses)
        Route::get('properties', [PropertyController::class, 'indexDashboard'])
            ->name('dashboard.properties.index');

        Route::get('properties/{id}', [PropertyController::class, 'showDashboard'])
            ->name('dashboard.properties.show');

        // Properties CRUD
        Route::apiResource('properties', PropertyController::class)->except(['index', 'show']);

        // Property status management
        Route::patch('properties/{id}/status', [PropertyController::class, 'updateStatus'])
            ->name('dashboard.properties.update-status');

        // Property favorite
        Route::post('properties/{id}/toggle-favorite', [PropertyController::class, 'toggleFavorite'])
            ->name('dashboard.properties.toggle-favorite');
    });
});
