<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Http\Controllers\AdAnalyticsController;
use Modules\RealEstate\Http\Controllers\AdController;
use Modules\RealEstate\Http\Controllers\AdDisplayController;
use Modules\RealEstate\Http\Controllers\AdGroupController;
use Modules\RealEstate\Http\Controllers\AdTrackingController;
use Modules\RealEstate\Http\Controllers\PropertyController;

// ============================================================
// Public routes (no authentication)
// ============================================================
Route::prefix('api/public')->group(function () {
    // Ads display
    Route::prefix('ads/display')->group(function () {
        Route::get('standalone', [AdDisplayController::class, 'displayStandalone'])
            ->name('api.ads.display-standalone');
        Route::get('/{groupId}', [AdDisplayController::class, 'display'])
            ->name('api.ads.display');
        Route::get('/', [AdDisplayController::class, 'displayAll'])
            ->name('api.ads.display-all');
    });

    // Ad tracking
    Route::post('ads/{id}/track/view', [AdTrackingController::class, 'recordView'])
        ->name('api.ads.track.view');
    Route::post('ads/{id}/track/visit', [AdTrackingController::class, 'recordVisit'])
        ->name('api.ads.track.visit');

    // Properties
    Route::get('properties/browse', [PropertyController::class, 'indexPublic'])
        ->name('api.properties.public.index');
    Route::get('properties/random', [PropertyController::class, 'random'])
        ->name('api.properties.random');
    Route::get('properties/{id}/details', [PropertyController::class, 'showPublic'])
        ->name('api.properties.public.show');
});

// ============================================================
// Dashboard routes (authenticated with Sanctum)
// ============================================================
Route::prefix('api/dashboard')->middleware(['auth:sanctum'])->group(function () {
    // Properties
    Route::get('properties', [PropertyController::class, 'indexDashboard'])
        ->name('api.dashboard.properties.index');
    Route::get('properties/statistics', [PropertyController::class, 'statistics'])
        ->name('api.dashboard.properties.statistics');
    Route::get('properties/{id}', [PropertyController::class, 'showDashboard'])
        ->name('api.dashboard.properties.show');
    Route::post('properties', [PropertyController::class, 'store'])
        ->name('api.dashboard.properties.store');
    Route::patch('properties/{id}', [PropertyController::class, 'update'])
        ->name('api.dashboard.properties.update');
    Route::delete('properties/{id}', [PropertyController::class, 'destroy'])
        ->name('api.dashboard.properties.destroy');
    Route::patch('properties/{id}/status', [PropertyController::class, 'updateStatus'])
        ->name('api.dashboard.properties.update-status');
    Route::post('properties/{id}/favorite', [PropertyController::class, 'toggleFavorite'])
        ->name('api.dashboard.properties.favorite');

    Route::get('my-properties', [PropertyController::class, 'myProperties'])
        ->name('api.dashboard.my-properties');

    // Ad Groups
    Route::get('ad-groups', [AdGroupController::class, 'index'])
        ->name('api.dashboard.ad-groups.index');
    Route::post('ad-groups', [AdGroupController::class, 'store'])
        ->name('api.dashboard.ad-groups.store');
    Route::get('ad-groups/{id}', [AdGroupController::class, 'show'])
        ->name('api.dashboard.ad-groups.show');
    Route::patch('ad-groups/{id}', [AdGroupController::class, 'update'])
        ->name('api.dashboard.ad-groups.update');
    Route::delete('ad-groups/{id}', [AdGroupController::class, 'archive'])
        ->name('api.dashboard.ad-groups.archive');
    Route::post('ad-groups/{id}/restore', [AdGroupController::class, 'restore'])
        ->name('api.dashboard.ad-groups.restore');
    Route::post('ad-groups/{id}/set-default', [AdGroupController::class, 'setDefault'])
        ->name('api.dashboard.ad-groups.set-default');
    Route::delete('ad-groups/{id}/default', [AdGroupController::class, 'removeDefault'])
        ->name('api.dashboard.ad-groups.remove-default');

    // Ads
    Route::get('ads', [AdController::class, 'index'])
        ->name('api.dashboard.ads.index');
    Route::post('ads', [AdController::class, 'store'])
        ->name('api.dashboard.ads.store');
    Route::get('ads/{id}', [AdController::class, 'show'])
        ->name('api.dashboard.ads.show');
    Route::patch('ads/{id}', [AdController::class, 'update'])
        ->name('api.dashboard.ads.update');
    Route::delete('ads/{id}', [AdController::class, 'archive'])
        ->name('api.dashboard.ads.archive');
    Route::post('ads/{id}/restore', [AdController::class, 'restore'])
        ->name('api.dashboard.ads.restore');
    Route::post('ads/{id}/status', [AdController::class, 'setStatus'])
        ->name('api.dashboard.ads.set-status');
    Route::post('ads/{id}/link-property', [AdController::class, 'linkProperty'])
        ->name('api.dashboard.ads.link-property');
    Route::delete('ads/{id}/property', [AdController::class, 'unlinkProperty'])
        ->name('api.dashboard.ads.unlink-property');

    // Ad Analytics
    Route::get('analytics/dashboard', [AdAnalyticsController::class, 'dashboard'])
        ->name('api.dashboard.analytics.dashboard');
    Route::get('analytics/groups/{groupId}', [AdAnalyticsController::class, 'groupAnalytics'])
        ->name('api.dashboard.analytics.groups');
    Route::get('analytics/ads/{adId}', [AdAnalyticsController::class, 'adAnalytics'])
        ->name('api.dashboard.analytics.ads');
    Route::get('analytics/export', [AdAnalyticsController::class, 'export'])
        ->name('api.dashboard.analytics.export');
});
