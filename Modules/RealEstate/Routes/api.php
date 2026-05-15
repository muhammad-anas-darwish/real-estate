<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Http\Controllers\AdAnalyticsController;
use Modules\RealEstate\Http\Controllers\AdController;
use Modules\RealEstate\Http\Controllers\AdDisplayController;
use Modules\RealEstate\Http\Controllers\AdGroupController;
use Modules\RealEstate\Http\Controllers\AdTrackingController;
use Modules\RealEstate\Http\Controllers\PropertyController;

// Public ad display routes
Route::prefix('api/ads/display')->group(function () {
    Route::get('standalone', [AdDisplayController::class, 'displayStandalone'])
        ->name('api.ads.display-standalone');
    Route::get('/{groupId}', [AdDisplayController::class, 'display'])
        ->name('api.ads.display');
});

    Route::prefix('api')->group(function () {
    // Public - standalone route without group
    Route::get('ads/display', [AdDisplayController::class, 'displayAll'])
        ->name('api.ads.display-all');

    // Public ad tracking routes
    Route::post('ads/{id}/track/view', [AdTrackingController::class, 'recordView'])
        ->name('api.ads.track.view');
    Route::post('ads/{id}/track/visit', [AdTrackingController::class, 'recordVisit'])
        ->name('api.ads.track.visit');

    // Public property routes
    Route::get('properties/browse', [PropertyController::class, 'indexPublic'])
        ->name('api.properties.public.index');
    Route::get('properties/random', [PropertyController::class, 'random'])
        ->name('api.properties.random');
    Route::get('properties/{id}/details', [PropertyController::class, 'showPublic'])
        ->name('api.properties.public.show');

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Dashboard properties
        Route::get('properties', [PropertyController::class, 'indexDashboard'])
            ->name('api.dashboard.properties.index');
        Route::get('dashboard/properties/statistics', [PropertyController::class, 'statistics'])
            ->name('api.dashboard.properties.statistics');
        Route::get('dashboard/properties/{id}', [PropertyController::class, 'showDashboard'])
            ->name('api.dashboard.properties.show');
        Route::get('my-properties', [PropertyController::class, 'myProperties'])
            ->name('api.my-properties');

        // Property CRUD
        Route::post('properties', [PropertyController::class, 'store'])
            ->name('api.properties.store');
        Route::patch('properties/{id}', [PropertyController::class, 'update'])
            ->name('api.properties.update');
        Route::delete('properties/{id}', [PropertyController::class, 'destroy'])
            ->name('api.properties.destroy');
        Route::patch('properties/{id}/status', [PropertyController::class, 'updateStatus'])
            ->name('api.properties.update-status');
        Route::post('properties/{id}/favorite', [PropertyController::class, 'toggleFavorite'])
            ->name('api.properties.favorite');

        // Ad Groups
        Route::get('ad-groups', [AdGroupController::class, 'index'])
            ->name('api.ad-groups.index');
        Route::post('ad-groups', [AdGroupController::class, 'store'])
            ->name('api.ad-groups.store');
        Route::get('ad-groups/{id}', [AdGroupController::class, 'show'])
            ->name('api.ad-groups.show');
        Route::patch('ad-groups/{id}', [AdGroupController::class, 'update'])
            ->name('api.ad-groups.update');
        Route::delete('ad-groups/{id}', [AdGroupController::class, 'archive'])
            ->name('api.ad-groups.archive');
        Route::post('ad-groups/{id}/restore', [AdGroupController::class, 'restore'])
            ->name('api.ad-groups.restore');
        Route::post('ad-groups/{id}/set-default', [AdGroupController::class, 'setDefault'])
            ->name('api.ad-groups.set-default');
        Route::delete('ad-groups/{id}/default', [AdGroupController::class, 'removeDefault'])
            ->name('api.ad-groups.remove-default');

        // Ads
        Route::get('ads', [AdController::class, 'index'])
            ->name('api.ads.index');
        Route::post('ads', [AdController::class, 'store'])
            ->name('api.ads.store');
        Route::get('ads/{id}', [AdController::class, 'show'])
            ->name('api.ads.show');
        Route::patch('ads/{id}', [AdController::class, 'update'])
            ->name('api.ads.update');
        Route::delete('ads/{id}', [AdController::class, 'archive'])
            ->name('api.ads.archive');
        Route::post('ads/{id}/restore', [AdController::class, 'restore'])
            ->name('api.ads.restore');
        Route::post('ads/{id}/status', [AdController::class, 'setStatus'])
            ->name('api.ads.set-status');
        Route::post('ads/{id}/link-property', [AdController::class, 'linkProperty'])
            ->name('api.ads.link-property');
        Route::delete('ads/{id}/property', [AdController::class, 'unlinkProperty'])
            ->name('api.ads.unlink-property');

        // Ad Analytics
        Route::get('analytics/dashboard', [AdAnalyticsController::class, 'dashboard'])
            ->name('api.analytics.dashboard');
        Route::get('analytics/groups/{groupId}', [AdAnalyticsController::class, 'groupAnalytics'])
            ->name('api.analytics.groups');
        Route::get('analytics/ads/{adId}', [AdAnalyticsController::class, 'adAnalytics'])
            ->name('api.analytics.ads');
        Route::get('analytics/export', [AdAnalyticsController::class, 'export'])
            ->name('api.analytics.export');
    });
});
