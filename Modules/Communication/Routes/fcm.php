<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Http\Controllers\Fcm\FcmTokenController;

Route::prefix('api/fcm')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/register', [FcmTokenController::class, 'store'])
        ->name('fcm.register');

    Route::delete('/revoke', [FcmTokenController::class, 'destroy'])
        ->name('fcm.revoke');
});
