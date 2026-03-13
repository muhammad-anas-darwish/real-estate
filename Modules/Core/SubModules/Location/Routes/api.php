<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\SubModules\Location\Http\Controllers\CityController;
use Modules\Core\SubModules\Location\Http\Controllers\CountryController;

// Protected Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('cities', CityController::class);
    Route::apiResource('countries', CountryController::class);
});
