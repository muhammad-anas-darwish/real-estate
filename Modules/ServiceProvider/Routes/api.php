<?php

use Illuminate\Support\Facades\Route;
use Modules\ServiceProvider\Http\Controllers\AdminServiceRequestController;
use Modules\ServiceProvider\Http\Controllers\ClientServiceRequestController;
use Modules\ServiceProvider\Http\Controllers\ProviderServiceRequestController;
use Modules\ServiceProvider\Http\Controllers\ServiceProviderController;

Route::prefix('api/public')->group(function () {
    Route::get('service-providers', [ServiceProviderController::class, 'indexPublic'])
        ->name('api.public.service-providers.index');
    Route::get('service-providers/{id}', [ServiceProviderController::class, 'showPublic'])
        ->name('api.public.service-providers.show');
});

Route::prefix('api/service-provider')->middleware(['auth:sanctum'])->group(function () {
    Route::post('register', [ServiceProviderController::class, 'register'])
        ->name('api.service-provider.register');
    Route::get('profile', [ServiceProviderController::class, 'myProfile'])
        ->name('api.service-provider.profile');
    Route::put('profile', [ServiceProviderController::class, 'updateProfile'])
        ->name('api.service-provider.profile.update');
    Route::post('availability', [ServiceProviderController::class, 'toggleAvailability'])
        ->name('api.service-provider.availability');

    // Service Requests — Provider perspective
    Route::get('service-requests', [ProviderServiceRequestController::class, 'index'])
        ->name('api.service-provider.requests.index');
    Route::get('service-requests/{id}', [ProviderServiceRequestController::class, 'show'])
        ->name('api.service-provider.requests.show');
    Route::post('service-requests/{id}/accept', [ProviderServiceRequestController::class, 'accept'])
        ->name('api.service-provider.requests.accept');
    Route::post('service-requests/{id}/reject', [ProviderServiceRequestController::class, 'reject'])
        ->name('api.service-provider.requests.reject');
    Route::post('service-requests/{id}/start', [ProviderServiceRequestController::class, 'startProgress'])
        ->name('api.service-provider.requests.start');
    Route::post('service-requests/{id}/complete', [ProviderServiceRequestController::class, 'complete'])
        ->name('api.service-provider.requests.complete');
    Route::post('service-request-tasks/{taskId}/complete', [ProviderServiceRequestController::class, 'completeTask'])
        ->name('api.service-provider.tasks.complete');
});

Route::prefix('api/service-requests')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [ClientServiceRequestController::class, 'index'])
        ->name('api.service-requests.index');
    Route::post('/', [ClientServiceRequestController::class, 'store'])
        ->name('api.service-requests.store');
    Route::get('{id}', [ClientServiceRequestController::class, 'show'])
        ->name('api.service-requests.show');
    Route::post('{id}/cancel', [ClientServiceRequestController::class, 'cancel'])
        ->name('api.service-requests.cancel');
});

Route::prefix('api/admin/service-providers')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [ServiceProviderController::class, 'adminIndex'])
        ->name('api.admin.service-providers.index');
    Route::post('{id}/verify', [ServiceProviderController::class, 'verify'])
        ->name('api.admin.service-providers.verify');
    Route::post('{id}/unverify', [ServiceProviderController::class, 'unverify'])
        ->name('api.admin.service-providers.unverify');
});

Route::prefix('api/admin/service-requests')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [AdminServiceRequestController::class, 'index'])
        ->name('api.admin.service-requests.index');
    Route::get('{id}', [AdminServiceRequestController::class, 'show'])
        ->name('api.admin.service-requests.show');
});
