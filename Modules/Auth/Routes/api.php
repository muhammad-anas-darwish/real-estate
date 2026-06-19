<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Modules\Auth\Http\Controllers\PublisherController;
use Modules\Auth\Http\Controllers\RoleController;
use Modules\Auth\Http\Controllers\UserController;
use Modules\RealEstate\Http\Controllers\ReviewController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('roles/{id}', [RoleController::class, 'show']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{id}', [RoleController::class, 'update']);
    Route::delete('roles/{id}', [RoleController::class, 'destroy']);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions']);

    Route::get('permissions', [RoleController::class, 'permissions']);

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::put('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);

    // Publisher profile routes
    Route::put('publisher/profile', [PublisherController::class, 'updateProfile']);
    Route::put('publisher/contact-preference', [PublisherController::class, 'updateContactPreference']);
    Route::get('publisher/statistics', [PublisherController::class, 'statistics']);
    Route::get('publisher/analytics', [PublisherController::class, 'analytics']);

    // Publisher upgrade request
    Route::post('publisher/upgrade-request', [PublisherController::class, 'submitUpgradeRequest']);
    Route::get('publisher/upgrade-status', [PublisherController::class, 'upgradeStatus']);

    // Reviews
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);

    // Admin: offices & upgrade requests
    Route::post('admin/offices/{id}/verify', [PublisherController::class, 'verify']);
    Route::post('admin/offices/{id}/unverify', [PublisherController::class, 'unverify']);
    Route::get('admin/office-upgrade-requests', [PublisherController::class, 'listUpgradeRequests']);
    Route::post('admin/upgrade-requests/{id}/approve', [PublisherController::class, 'approveUpgrade']);
    Route::post('admin/upgrade-requests/{id}/reject', [PublisherController::class, 'rejectUpgrade']);
});
