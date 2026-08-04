<?php

use Illuminate\Support\Facades\Route;
use Modules\Ledger\Http\Controllers\AccountController;
use Modules\Ledger\Http\Controllers\JournalEntryController;
use Modules\Ledger\Http\Controllers\LedgerController;
use Modules\Ledger\Http\Controllers\PayrollController;

Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
    Route::get('ledger/balance', [LedgerController::class, 'balance'])
        ->name('api.ledger.balance');
    Route::get('ledger/statement', [LedgerController::class, 'statement'])
        ->name('api.ledger.statement');

    Route::get('accounts/tree', [AccountController::class, 'tree'])
        ->name('api.accounts.tree');
    Route::apiResource('accounts', AccountController::class);

    Route::post('journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post'])
        ->name('api.journal-entries.post');
    Route::get('trial-balance', [JournalEntryController::class, 'trialBalance'])
        ->name('api.trial-balance');
    Route::apiResource('journal-entries', JournalEntryController::class);

    Route::get('payroll/providers', [PayrollController::class, 'index'])
        ->name('api.payroll.index');
    Route::post('payroll/setup', [PayrollController::class, 'setup'])
        ->name('api.payroll.setup');
    Route::post('payroll/run', [PayrollController::class, 'run'])
        ->name('api.payroll.run');
    Route::get('payroll/payments', [PayrollController::class, 'payments'])
        ->name('api.payroll.payments');
    Route::get('payroll/{payroll}', [PayrollController::class, 'show'])
        ->name('api.payroll.show');
    Route::put('payroll/{payroll}', [PayrollController::class, 'update'])
        ->name('api.payroll.update');
});
