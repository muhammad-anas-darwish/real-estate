<?php

use Illuminate\Support\Facades\Route;
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
});

Route::prefix('api/admin/service-providers')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [ServiceProviderController::class, 'adminIndex'])
        ->name('api.admin.service-providers.index');
    Route::post('{id}/verify', [ServiceProviderController::class, 'verify'])
        ->name('api.admin.service-providers.verify');
    Route::post('{id}/unverify', [ServiceProviderController::class, 'unverify'])
        ->name('api.admin.service-providers.unverify');
});
