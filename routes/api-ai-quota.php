<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AiQuotaController;

/*
|--------------------------------------------------------------------------
| AI Quota & Usage Management Routes
|--------------------------------------------------------------------------
|
| Routes for managing AI usage quotas, tracking, and cost control.
| Part of Phase 1B weakness remediation (2025-11-21).
|
| All routes require authentication and respect RLS policies.
|
*/

Route::middleware(['auth:sanctum', 'rls.context'])->prefix('ai')->name('ai.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Quota Status & Management
    |--------------------------------------------------------------------------
    */

    // Get all quota status for current user
    Route::get('/quota', [AiQuotaController::class, 'index'])
        ->name('quota.index');

    // Get quota status for specific service
    Route::get('/quota/{service}', [AiQuotaController::class, 'show'])
        ->name('quota.show')
        ->where('service', 'gpt|embeddings|image_gen');

    // Check if quota is available (pre-flight check)
    Route::post('/check-quota', [AiQuotaController::class, 'checkQuota'])
        ->name('quota.check');

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking & History
    |--------------------------------------------------------------------------
    */

    // Get usage history
    Route::get('/usage', [AiQuotaController::class, 'usage'])
        ->name('usage.index');

    // Get usage statistics (aggregated)
    Route::get('/stats', [AiQuotaController::class, 'stats'])
        ->name('stats');

    /*
    |--------------------------------------------------------------------------
    | Recommendations & Insights
    |--------------------------------------------------------------------------
    */

    // Get quota and upgrade recommendations
    Route::get('/recommendations', [AiQuotaController::class, 'recommendations'])
        ->name('recommendations');

});
