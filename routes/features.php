<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Admin\FeatureFlagController;

/*
|--------------------------------------------------------------------------
| Feature Toggle Routes
|--------------------------------------------------------------------------
|
| Routes for feature flag management and API endpoints.
| - API routes: For frontend to check enabled features
| - Admin routes: For administrators to manage feature flags
|
*/

// ==================== API Routes (for Frontend) ====================
// These routes are used by the frontend to determine which features to show
Route::prefix('api/features')->name('api.features.')->group(function () {
    // Public endpoints (no auth required for basic feature checks)
    Route::get('available-platforms', [FeatureController::class, 'getAvailablePlatforms'])
        ->name('available-platforms');

    Route::get('matrix', [FeatureController::class, 'getFeatureMatrix'])
        ->name('matrix');

    Route::get('enabled-platforms/{category}', [FeatureController::class, 'getEnabledPlatformsForFeature'])
        ->name('enabled-platforms');

    Route::get('check/{featureKey}', [FeatureController::class, 'checkFeature'])
        ->name('check')
        ->where('featureKey', '.*'); // Allow dots in feature keys
});

// ==================== Admin Routes (for Feature Management) ====================
// These routes are protected and only accessible by administrators
Route::prefix('admin/features')->name('admin.features.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/', [FeatureFlagController::class, 'index'])
        ->name('index');

    // Toggle single feature
    Route::post('toggle', [FeatureFlagController::class, 'toggle'])
        ->name('toggle');

    // Bulk toggle multiple features
    Route::post('bulk-toggle', [FeatureFlagController::class, 'bulkToggle'])
        ->name('bulk-toggle');

    // Apply preset configuration
    Route::post('apply-preset', [FeatureFlagController::class, 'applyPreset'])
        ->name('apply-preset');

    // Create user/org override
    Route::post('override', [FeatureFlagController::class, 'createOverride'])
        ->name('override');
});
