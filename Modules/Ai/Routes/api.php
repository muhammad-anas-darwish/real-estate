<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\DescriptionAssistantController;
use Modules\Ai\Http\Controllers\SmartSearchController;

Route::prefix('api')->group(function () {
    Route::middleware('throttle:ai-search')->group(function () {
        Route::post('ai/search', [SmartSearchController::class, 'search']);
    });

    Route::middleware(['auth:sanctum', 'throttle:ai-description'])->group(function () {
        Route::post('ai/description/generate', [DescriptionAssistantController::class, 'generate']);
        Route::post('ai/description/improve', [DescriptionAssistantController::class, 'improve']);
        Route::post('ai/description/suggest-title', [DescriptionAssistantController::class, 'suggestTitle']);
        Route::post('ai/description/suggest-features', [DescriptionAssistantController::class, 'suggestFeatures']);
    });
});
