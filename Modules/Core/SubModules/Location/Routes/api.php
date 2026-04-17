<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\SubModules\Location\Http\Controllers\CityController;
use Modules\Core\SubModules\Location\Http\Controllers\CountryController;

// Public routes
Route::apiResource('cities', CityController::class)->except(['store', 'update', 'destroy']);
Route::apiResource('countries', CountryController::class)->except(['store', 'update', 'destroy']);

// Protected routes - Dashboard
Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
    Route::apiResource('cities', CityController::class);
    Route::apiResource('countries', CountryController::class);
});
