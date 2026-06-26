<?php

use Illuminate\Support\Facades\Route;
use Modules\Crm\Http\Controllers\LeadController;
use Modules\Crm\Http\Controllers\LeadDashboardController;
use Modules\Crm\Http\Controllers\LeadExportController;
use Modules\Crm\Http\Controllers\LeadNoteController;

Route::prefix('api/dashboard/crm')->middleware(['auth:sanctum', 'trader'])->group(function () {
    // Dashboard
    Route::get('dashboard/summary', [LeadDashboardController::class, 'summary'])->name('api.crm.dashboard.summary');
    Route::get('dashboard/today', [LeadDashboardController::class, 'today'])->name('api.crm.dashboard.today');

    // Leads
    Route::get('leads', [LeadController::class, 'index'])->name('api.crm.leads.index');
    Route::post('leads', [LeadController::class, 'store'])->name('api.crm.leads.store');
    Route::get('leads/archived', [LeadController::class, 'archived'])->name('api.crm.leads.archived');
    Route::get('leads/check-duplicate', [LeadController::class, 'checkDuplicate'])->name('api.crm.leads.check-duplicate');
    Route::get('leads/export', [LeadExportController::class, 'csv'])->name('api.crm.leads.export');
    Route::get('leads/{id}', [LeadController::class, 'show'])->name('api.crm.leads.show');
    Route::patch('leads/{id}', [LeadController::class, 'update'])->name('api.crm.leads.update');
    Route::patch('leads/{id}/status', [LeadController::class, 'changeStatus'])->name('api.crm.leads.status');
    Route::post('leads/{id}/archive', [LeadController::class, 'archive'])->name('api.crm.leads.archive');
    Route::post('leads/{id}/restore', [LeadController::class, 'restore'])->name('api.crm.leads.restore');

    // Lead notes
    Route::get('leads/{leadId}/notes', [LeadNoteController::class, 'index'])->name('api.crm.leads.notes.index');
    Route::post('leads/{leadId}/notes', [LeadNoteController::class, 'store'])->name('api.crm.leads.notes.store');
    Route::patch('notes/{noteId}', [LeadNoteController::class, 'update'])->name('api.crm.notes.update');
    Route::delete('notes/{noteId}', [LeadNoteController::class, 'destroy'])->name('api.crm.notes.destroy');
});
