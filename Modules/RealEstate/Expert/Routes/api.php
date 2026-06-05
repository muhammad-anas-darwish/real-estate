<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Expert\Http\Controllers\ExpertRelationshipController;
use Modules\RealEstate\Expert\Http\Controllers\ExpertRequestController;

Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
    Route::get('expert-requests', [ExpertRequestController::class, 'index'])
        ->name('expert-requests.index');

    Route::post('expert-requests', [ExpertRequestController::class, 'store'])
        ->name('expert-requests.store');

    Route::get('expert-requests/{id}', [ExpertRequestController::class, 'show'])
        ->name('expert-requests.show');

    Route::put('expert-requests/{id}/cancel', [ExpertRequestController::class, 'cancel'])
        ->name('expert-requests.cancel');

    Route::get('expert-relationships', [ExpertRelationshipController::class, 'index'])
        ->name('expert-relationships.index');

    Route::get('expert-relationships/{id}', [ExpertRelationshipController::class, 'show'])
        ->name('expert-relationships.show');

    Route::put('expert-relationships/{id}/cancel', [ExpertRelationshipController::class, 'cancel'])
        ->name('expert-relationships.cancel');

    Route::put('expert-relationships/{id}/complete', [ExpertRelationshipController::class, 'complete'])
        ->name('expert-relationships.complete');
});
