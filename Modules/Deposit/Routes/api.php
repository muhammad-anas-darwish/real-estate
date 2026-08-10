<?php

use Illuminate\Support\Facades\Route;
use Modules\Deposit\Http\Controllers\DepositController;

Route::prefix('api/dashboard')->middleware(['auth:sanctum'])->group(function () {
    // Deposits
    Route::get('deposits', [DepositController::class, 'index'])
        ->name('api.dashboard.deposits.index');
    Route::post('deposits', [DepositController::class, 'store'])
        ->name('api.dashboard.deposits.store');
    Route::get('deposits/{id}', [DepositController::class, 'show'])
        ->name('api.dashboard.deposits.show');
    Route::patch('deposits/{id}', [DepositController::class, 'update'])
        ->name('api.dashboard.deposits.update');
    Route::delete('deposits/{id}', [DepositController::class, 'destroy'])
        ->name('api.dashboard.deposits.destroy');
    Route::post('deposits/{id}/pay', [DepositController::class, 'pay'])
        ->name('api.dashboard.deposits.pay');
    Route::post('deposits/{id}/release', [DepositController::class, 'release'])
        ->name('api.dashboard.deposits.release');
    Route::post('deposits/{id}/refund', [DepositController::class, 'refund'])
        ->name('api.dashboard.deposits.refund');
    Route::post('deposits/{id}/cancel', [DepositController::class, 'cancel'])
        ->name('api.dashboard.deposits.cancel');

    // My deposits / My sales
    Route::get('my-deposits', [DepositController::class, 'myDeposits'])
        ->name('api.dashboard.my-deposits');
    Route::get('my-sales', [DepositController::class, 'mySales'])
        ->name('api.dashboard.my-sales');
});
