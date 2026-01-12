<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Http\Controllers\PropertyController;

Route::prefix('api')->group(function () {
    // Public routes - view approved properties
    Route::get('properties/public', [PropertyController::class, 'index'])
        ->name('properties.public');

    Route::get('properties/public/{id}', [PropertyController::class, 'show'])
        ->name('properties.public.show');

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Properties CRUD
        Route::apiResource('properties', PropertyController::class);

        // Property status management
        Route::post('properties/{id}/approve', [PropertyController::class, 'approve'])
            ->name('properties.approve');

        Route::post('properties/{id}/reject', [PropertyController::class, 'reject'])
            ->name('properties.reject');

        Route::post('properties/{id}/mark-sold', [PropertyController::class, 'markAsSold'])
            ->name('properties.mark-sold');
    });
});
