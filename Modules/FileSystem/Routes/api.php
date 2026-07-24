<?php

use Illuminate\Support\Facades\Route;
use Modules\FileSystem\Http\Controllers\FileController;
use Modules\FileSystem\Http\Controllers\FolderController;
use Modules\FileSystem\Http\Controllers\StorageQuotaController;

Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    Route::get('folders', [FolderController::class, 'index'])->name('folders.index');
    Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
    Route::get('folders/{id}', [FolderController::class, 'show'])->name('folders.show');
    Route::put('folders/{id}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{id}', [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::post('folders/{id}/move', [FolderController::class, 'move'])->name('folders.move');
    Route::post('folders/{id}/rename', [FolderController::class, 'rename'])->name('folders.rename');

    Route::get('files/{id}', [FileController::class, 'show'])->name('files.show');
    Route::post('files/text', [FileController::class, 'storeText'])->name('files.text.store');
    Route::put('files/{id}/text', [FileController::class, 'updateText'])->name('files.text.update');
    Route::post('files/image', [FileController::class, 'uploadImage'])->name('files.image.upload');
    Route::delete('files/{id}', [FileController::class, 'destroy'])->name('files.destroy');
    Route::post('files/{id}/move', [FileController::class, 'move'])->name('files.move');
    Route::post('files/{id}/rename', [FileController::class, 'rename'])->name('files.rename');

    Route::get('storage/status', [StorageQuotaController::class, 'status'])->name('storage.status');
    Route::get('storage/packages', [StorageQuotaController::class, 'packages'])->name('storage.packages');
    Route::post('storage/upgrade', [StorageQuotaController::class, 'upgrade'])->name('storage.upgrade');

    Route::get('properties/{propertyId}/files', [FolderController::class, 'propertyFolder'])->name('properties.files');
});
