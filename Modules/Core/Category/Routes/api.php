<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Category\Http\Controllers\CategoryController;

Route::prefix('api')->group(function () {
    // Public routes - view categories
    Route::get('categories/public', [CategoryController::class, 'indexPublic'])
        ->name('categories.public');

    Route::get('categories/public/{id}', [CategoryController::class, 'showPublic'])
        ->name('categories.public.show');

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Categories CRUD
        Route::apiResource('categories', CategoryController::class);
    });
});
