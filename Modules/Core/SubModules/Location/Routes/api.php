<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\SubModules\Location\Http\Controllers\CityController;
use Modules\Core\SubModules\Location\Http\Controllers\CountryController;

Route::prefix('api/location')->group(function () {
    Route::apiResource('cities', CityController::class)->only(['index', 'show']);
    Route::apiResource('countries', CountryController::class)->only(['index', 'show']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('cities', CityController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('countries', CountryController::class)->only(['store', 'update', 'destroy']);
    });
});
