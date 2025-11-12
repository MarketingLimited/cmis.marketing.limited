<?php

use App\Http\Controllers\AI\AIDashboardController;
use App\Http\Controllers\Analytics\OverviewController as AnalyticsOverviewController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\Channels\ChannelController;
use App\Http\Controllers\Creative\CreativeAssetController;
use App\Http\Controllers\Creative\OverviewController as CreativeOverviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Offerings\BundleController;
use App\Http\Controllers\Offerings\OverviewController as OfferingsOverviewController;
use App\Http\Controllers\Offerings\ProductController;
use App\Http\Controllers\Offerings\ServiceController;
use App\Http\Controllers\OrgController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
Route::get('/notifications/latest', [DashboardController::class, 'latest'])->name('notifications.latest');

Route::prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/', [CampaignController::class, 'index'])->name('index');
    Route::get('{campaign}', [CampaignController::class, 'show'])
        ->whereNumber('campaign')
        ->name('show');
    Route::get('{campaign}/performance/{range}', [CampaignController::class, 'performanceByRange'])
        ->whereNumber('campaign')
        ->whereIn('range', ['daily', 'weekly', 'monthly', 'yearly'])
        ->name('performance');
});

Route::prefix('orgs')->name('orgs.')->group(function () {
    Route::get('/', [OrgController::class, 'index'])->name('index');
    Route::get('{org}', [OrgController::class, 'show'])
        ->whereNumber('org')
        ->name('show');
    Route::get('{org}/campaigns', [OrgController::class, 'campaigns'])
        ->whereNumber('org')
        ->name('campaigns');
    Route::get('{org}/campaigns/compare', [OrgController::class, 'compareCampaigns'])
        ->whereNumber('org')
        ->name('campaigns.compare');
    Route::post('{org}/campaigns/export/pdf', [OrgController::class, 'exportComparePdf'])
        ->whereNumber('org')
        ->name('campaigns.export.pdf');
    Route::post('{org}/campaigns/export/excel', [OrgController::class, 'exportCompareExcel'])
        ->whereNumber('org')
        ->name('campaigns.export.excel');
    Route::get('{org}/services', [OrgController::class, 'services'])
        ->whereNumber('org')
        ->name('services');
    Route::get('{org}/products', [OrgController::class, 'products'])
        ->whereNumber('org')
        ->name('products');
});

Route::get('/offerings', [OfferingsOverviewController::class, 'index'])->name('offerings.index');
Route::get('/analytics', [AnalyticsOverviewController::class, 'index'])->name('analytics.index');
Route::get('/creative', [CreativeOverviewController::class, 'index'])->name('creative.index');
Route::get('/creative-assets', [CreativeAssetController::class, 'index'])->name('creative-assets.index');
Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
Route::get('/ai', [AIDashboardController::class, 'index'])->name('ai.index');

Route::get('/kpis', [AnalyticsOverviewController::class, 'index'])->name('analytics.kpis');
Route::get('/reports', [AnalyticsOverviewController::class, 'index'])->name('analytics.reports');
Route::get('/metrics', [AnalyticsOverviewController::class, 'index'])->name('analytics.metrics');
Route::get('/products', [ProductController::class, 'index'])->name('offerings.products');
Route::get('/services', [ServiceController::class, 'index'])->name('offerings.services');
Route::get('/bundles', [BundleController::class, 'index'])->name('offerings.bundles');
Route::get('/ads', [CreativeOverviewController::class, 'index'])->name('creative.ads');
Route::get('/templates', [CreativeOverviewController::class, 'index'])->name('creative.templates');
Route::get('/ai/campaigns', [AIDashboardController::class, 'index'])->name('ai.campaigns');
Route::get('/ai/recommendations', [AIDashboardController::class, 'index'])->name('ai.recommendations');
Route::get('/ai/models', [AIDashboardController::class, 'index'])->name('ai.models');

// User Management Routes
Route::prefix('users')->name('users.')->middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('users.index');
    })->name('index');

    Route::get('/{userId}', function ($userId) {
        return view('users.show', ['userId' => $userId]);
    })->name('show');
});
