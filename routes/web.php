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

// Knowledge Base Routes
Route::prefix('knowledge')->name('knowledge.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\KnowledgeController::class, 'index'])->name('index');
    Route::post('/search', [App\Http\Controllers\KnowledgeController::class, 'search'])->name('search');
    Route::post('/', [App\Http\Controllers\KnowledgeController::class, 'store'])->name('store');
    Route::get('/domains', [App\Http\Controllers\KnowledgeController::class, 'domains'])->name('domains');
    Route::get('/domains/{domain}/categories', [App\Http\Controllers\KnowledgeController::class, 'categories'])->name('categories');
});

// Workflow Routes
Route::prefix('workflows')->name('workflows.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\WorkflowController::class, 'index'])->name('index');
    Route::get('/{flowId}', [App\Http\Controllers\WorkflowController::class, 'show'])->name('show');
    Route::post('/initialize-campaign', [App\Http\Controllers\WorkflowController::class, 'initializeCampaign'])->name('initialize-campaign');
    Route::post('/{flowId}/steps/{stepNumber}/complete', [App\Http\Controllers\WorkflowController::class, 'completeStep'])->name('complete-step');
    Route::post('/{flowId}/steps/{stepNumber}/assign', [App\Http\Controllers\WorkflowController::class, 'assignStep'])->name('assign-step');
    Route::post('/{flowId}/steps/{stepNumber}/comment', [App\Http\Controllers\WorkflowController::class, 'addComment'])->name('add-comment');
});

// Creative Brief Routes
Route::prefix('briefs')->name('briefs.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\CreativeBriefController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\CreativeBriefController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\CreativeBriefController::class, 'store'])->name('store');
    Route::get('/{briefId}', [App\Http\Controllers\CreativeBriefController::class, 'show'])->name('show');
    Route::post('/{briefId}/approve', [App\Http\Controllers\CreativeBriefController::class, 'approve'])->name('approve');
});

// Social Media Management Routes
Route::prefix('social')->name('social.')->middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('social.index');
    })->name('index');
    Route::get('/posts', function () {
        return view('social.index');
    })->name('posts');
});

// Organization Management Extended Routes
Route::prefix('orgs')->name('orgs.')->middleware('auth')->group(function () {
    Route::get('/create', function () {
        return view('orgs.create');
    })->name('create');
    Route::post('/', [OrgController::class, 'store'])->name('store');
    Route::get('/{org}/edit', [OrgController::class, 'edit'])->name('edit');
    Route::put('/{org}', [OrgController::class, 'update'])->name('update');
});

// Product & Service Detail Routes
Route::prefix('products')->name('products.')->middleware('auth')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{productId}', function ($productId) {
        $product = \DB::table('cmis.offerings_full_details')
            ->where('offering_id', $productId)
            ->where('type', 'product')
            ->first();
        return view('products.show', ['product' => $product]);
    })->name('show');
});

Route::prefix('services')->name('services.')->middleware('auth')->group(function () {
    Route::get('/', [ServiceController::class, 'index'])->name('index');
    Route::get('/{serviceId}', function ($serviceId) {
        $service = \DB::table('cmis.offerings_full_details')
            ->where('offering_id', $serviceId)
            ->where('type', 'service')
            ->first();
        return view('services.show', ['service' => $service]);
    })->name('show');
});
