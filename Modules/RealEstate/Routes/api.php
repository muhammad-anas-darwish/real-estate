<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\PublisherController;
use Modules\RealEstate\Http\Controllers\AdAnalyticsController;
use Modules\RealEstate\Http\Controllers\AdController;
use Modules\RealEstate\Http\Controllers\AdDisplayController;
use Modules\RealEstate\Http\Controllers\AdGroupController;
use Modules\RealEstate\Http\Controllers\AdTrackingController;
use Modules\RealEstate\Http\Controllers\PropertyController;
use Modules\RealEstate\Http\Controllers\ReviewController;
use Modules\RealEstate\Http\Controllers\SponsoredAdController;

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
});

Route::prefix('api')->group(function () {
    // Public - standalone route without group
    Route::get('ads/display', [AdDisplayController::class, 'displayAll'])
        ->name('api.ads.display-all');

    // Ad tracking
    Route::post('ads/{id}/track/view', [AdTrackingController::class, 'recordView'])
        ->name('api.ads.track.view');
    Route::post('ads/{id}/track/visit', [AdTrackingController::class, 'recordVisit'])
        ->name('api.ads.track.visit');

    // Properties
    // Public sponsored ad routes
    Route::get('sponsored-ads/pricing', [SponsoredAdController::class, 'pricing'])
        ->name('api.sponsored-ads.pricing');
    Route::get('sponsored-ads/active', [SponsoredAdController::class, 'active'])
        ->name('api.sponsored-ads.active');

    // Public property routes
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

    // Public publisher routes
    Route::get('publishers/offices', [PublisherController::class, 'listOffices'])
        ->name('api.publishers.offices.index');
    Route::get('publishers/offices/{id}', [PublisherController::class, 'showOffice'])
        ->name('api.publishers.offices.show');

    // Public review routes
    Route::get('offices/{officeId}/reviews', [ReviewController::class, 'listForOffice'])
        ->name('api.offices.reviews.index');

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
        // Ad Analytics
        Route::get('analytics/dashboard', [AdAnalyticsController::class, 'dashboard'])
            ->name('api.analytics.dashboard');
        Route::get('analytics/groups/{groupId}', [AdAnalyticsController::class, 'groupAnalytics'])
            ->name('api.analytics.groups');
        Route::get('analytics/ads/{adId}', [AdAnalyticsController::class, 'adAnalytics'])
            ->name('api.analytics.ads');
        Route::get('analytics/export', [AdAnalyticsController::class, 'export'])
            ->name('api.analytics.export');

        // Sponsored Ads (user-facing)
        Route::get('sponsored-ads', [SponsoredAdController::class, 'index'])
            ->name('api.sponsored-ads.index');
        Route::post('sponsored-ads', [SponsoredAdController::class, 'store'])
            ->name('api.sponsored-ads.store');
        Route::get('sponsored-ads/{id}', [SponsoredAdController::class, 'show'])
            ->name('api.sponsored-ads.show');
        Route::post('sponsored-ads/{id}/cancel', [SponsoredAdController::class, 'cancel'])
            ->name('api.sponsored-ads.cancel');
    });
});
