<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
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

Route::view('/offerings', 'offerings.index')->name('offerings.index');
Route::view('/analytics', 'analytics.index')->name('analytics.index');
Route::view('/creative', 'creative.index')->name('creative.index');
Route::view('/creative-assets', 'creative-assets.index')->name('creative-assets.index');
Route::view('/channels', 'channels.index')->name('channels.index');
Route::view('/ai', 'ai.index')->name('ai.index');

Route::view('/kpis', 'analytics.index')->name('analytics.kpis');
Route::view('/reports', 'analytics.index')->name('analytics.reports');
Route::view('/metrics', 'analytics.index')->name('analytics.metrics');
Route::view('/products', 'offerings.index')->name('offerings.products');
Route::view('/services', 'offerings.index')->name('offerings.services');
Route::view('/bundles', 'offerings.index')->name('offerings.bundles');
Route::view('/ads', 'creative.index')->name('creative.ads');
Route::view('/templates', 'creative.index')->name('creative.templates');
Route::view('/ai/campaigns', 'ai.index')->name('ai.campaigns');
Route::view('/ai/recommendations', 'ai.index')->name('ai.recommendations');
Route::view('/ai/models', 'ai.index')->name('ai.models');
