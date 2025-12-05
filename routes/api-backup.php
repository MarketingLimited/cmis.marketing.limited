<?php

use App\Http\Controllers\Api\V1\BackupApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backup & Restore API Routes
|--------------------------------------------------------------------------
|
| API endpoints for the Organization Backup & Restore application.
| These routes require API authentication and appropriate permissions.
|
| Base URL: /api/v1/backup
|
*/

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum'])->group(function () {

    Route::prefix('backup')->name('backup.')->group(function () {
        // List & Usage
        Route::get('/list', [BackupApiController::class, 'list'])->name('list');
        Route::get('/usage', [BackupApiController::class, 'usage'])->name('usage');

        // Create backup
        Route::post('/create', [BackupApiController::class, 'create'])->name('create');

        // View & Download
        Route::get('/{id}', [BackupApiController::class, 'show'])->name('show');
        Route::get('/{id}/download', [BackupApiController::class, 'download'])->name('download');

        // Delete
        Route::delete('/{id}', [BackupApiController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('restore')->name('restore.')->group(function () {
        // Analyze
        Route::post('/analyze', [BackupApiController::class, 'analyzeRestore'])->name('analyze');

        // Start restore
        Route::post('/start', [BackupApiController::class, 'startRestore'])->name('start');

        // Status & Rollback
        Route::get('/{id}/status', [BackupApiController::class, 'restoreStatus'])->name('status');
        Route::post('/{id}/rollback', [BackupApiController::class, 'rollback'])->name('rollback');
    });

    Route::prefix('schedule')->name('schedule.')->group(function () {
        // List schedules
        Route::get('/', [BackupApiController::class, 'scheduleList'])->name('list');

        // Create/Update schedule
        Route::put('/', [BackupApiController::class, 'scheduleUpdate'])->name('update');

        // Trigger scheduled backup
        Route::post('/trigger', [BackupApiController::class, 'scheduleTrigger'])->name('trigger');
    });
});
