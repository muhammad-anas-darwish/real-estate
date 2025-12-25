<?php

use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::prefix('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');
});
