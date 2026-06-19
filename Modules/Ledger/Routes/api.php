<?php

use Illuminate\Support\Facades\Route;
use Modules\Ledger\Http\Controllers\LedgerController;

Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
    Route::get('ledger/balance', [LedgerController::class, 'balance'])
        ->name('api.ledger.balance');
    Route::get('ledger/statement', [LedgerController::class, 'statement'])
        ->name('api.ledger.statement');
});
