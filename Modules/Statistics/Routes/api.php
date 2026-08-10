<?php

use Illuminate\Support\Facades\Route;
use Modules\Statistics\Http\Controllers\AdminDashboardController;
use Modules\Statistics\Http\Controllers\MarketStatsController;
use Modules\Statistics\Http\Controllers\PropertyStatsController;
use Modules\Statistics\Http\Controllers\TraderDashboardController;

// Trader dashboard (auth:sanctum + trader role)
Route::prefix('api/dashboard/trader')->middleware(['auth:sanctum', 'trader'])->group(function () {
    Route::get('summary', [TraderDashboardController::class, 'summary']);
    Route::get('views-trend', [TraderDashboardController::class, 'viewsTrend']);
    Route::get('leads-by-status', [TraderDashboardController::class, 'leadsByStatus']);
    Route::get('properties-by-status', [TraderDashboardController::class, 'propertiesByStatus']);
    Route::get('top-properties', [TraderDashboardController::class, 'topProperties']);
    Route::get('recent-leads', [TraderDashboardController::class, 'recentLeads']);
    Route::get('upcoming-appointments', [TraderDashboardController::class, 'upcomingAppointments']);
    Route::get('expiring-rentals', [TraderDashboardController::class, 'expiringRentals']);
    Route::get('sponsored-ads-summary', [TraderDashboardController::class, 'sponsoredAdsSummary']);
    Route::get('export/properties', [TraderDashboardController::class, 'exportProperties']);
});

// Per-property statistics (auth:sanctum)
Route::prefix('api/dashboard/properties')->middleware(['auth:sanctum'])->group(function () {
    Route::get('{property}/stats', [PropertyStatsController::class, 'show']);
});

// Market statistics (PUBLIC — no auth)
Route::prefix('api/market')->group(function () {
    Route::get('overview', [MarketStatsController::class, 'overview']);
    Route::get('by-city', [MarketStatsController::class, 'byCity']);
    Route::get('by-category', [MarketStatsController::class, 'byCategory']);
    Route::get('by-price-range', [MarketStatsController::class, 'byPriceRange']);
    Route::get('top-viewed', [MarketStatsController::class, 'topViewed']);
    Route::get('top-saved', [MarketStatsController::class, 'topSaved']);
    Route::get('listings-trend', [MarketStatsController::class, 'listingsTrend']);
});

// Admin dashboard (auth:sanctum + permission: admin_statistics.view)
Route::prefix('api/admin/statistics')->middleware(['auth:sanctum', 'permission:admin_statistics.view'])->group(function () {
    Route::get('overview', [AdminDashboardController::class, 'overview']);
    Route::get('properties', [AdminDashboardController::class, 'properties']);
    Route::get('crm', [AdminDashboardController::class, 'crm']);
    Route::get('ads', [AdminDashboardController::class, 'ads']);
    Route::get('subscriptions', [AdminDashboardController::class, 'subscriptions']);
    Route::get('moderation', [AdminDashboardController::class, 'moderation']);
    Route::get('communication', [AdminDashboardController::class, 'communication']);
});
