<?php

use App\Http\Controllers\AI\AIDashboardController;
use App\Http\Controllers\Analytics\OverviewController as AnalyticsOverviewController;
use App\Http\Controllers\Campaigns\CampaignController;
use App\Http\Controllers\Creative\CreativeAssetController;
use App\Http\Controllers\Creative\OverviewController as CreativeOverviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Offerings\BundleController;
use App\Http\Controllers\Offerings\OverviewController as OfferingsOverviewController;
use App\Http\Controllers\Offerings\ProductController;
use App\Http\Controllers\Offerings\ServiceController;
use App\Http\Controllers\OrgController;
use App\Http\Controllers\Web\ChannelController as WebChannelController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\InvitationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ==================== Guest Routes (Authentication) ====================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

// ==================== Invitation Routes ====================
Route::prefix('invitations')->name('invitations.')->group(function () {
    Route::get('/accept/{token}', [InvitationController::class, 'show'])->name('show');
    Route::post('/accept/{token}', [InvitationController::class, 'accept'])->name('accept');
    Route::get('/decline/{token}', [InvitationController::class, 'decline'])->name('decline');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Public Routes
Route::get('/', function () {
    return view('welcome');
});

// Protected Routes - Require Authentication
Route::middleware(['auth'])->group(function () {

    // ==================== Dashboard ====================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/notifications/latest', [DashboardController::class, 'latest'])->name('notifications.latest');
    Route::post('/notifications/{notificationId}/read', [DashboardController::class, 'markAsRead'])->name('notifications.markAsRead');

    // ==================== Campaigns ====================
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/performance-dashboard', function () {
            return view('campaigns.performance-dashboard');
        })->name('performance-dashboard');
        Route::get('create', [CampaignController::class, 'create'])->name('create');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::get('{campaign}', [CampaignController::class, 'show'])->name('show');
        Route::get('{campaign}/edit', [CampaignController::class, 'edit'])->name('edit');
        Route::put('{campaign}', [CampaignController::class, 'update'])->name('update');
        Route::delete('{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
        Route::get('{campaign}/performance/{range}', [CampaignController::class, 'performanceByRange'])
            ->whereIn('range', ['daily', 'weekly', 'monthly', 'yearly'])
            ->name('performance');
    });

    // ==================== Campaign Wizard ====================
    Route::prefix('campaigns/wizard')->name('campaign.wizard.')->group(function () {
        Route::get('/create', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'create'])->name('create');
        Route::get('/{session_id}/step/{step}', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'showStep'])->name('step');
        Route::post('/{session_id}/step/{step}', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'updateStep'])->name('update');
        Route::get('/{session_id}/save-draft', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'saveDraft'])->name('save-draft');
        Route::get('/{session_id}/complete', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'complete'])->name('complete');
        Route::get('/{session_id}/cancel', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'cancel'])->name('cancel');
    });

    // ==================== User Onboarding ====================
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [App\Http\Controllers\UserOnboardingController::class, 'index'])->name('index');
        Route::get('/step/{step}', [App\Http\Controllers\UserOnboardingController::class, 'showStep'])->name('step');
        Route::post('/step/{step}/complete', [App\Http\Controllers\UserOnboardingController::class, 'completeStep'])->name('complete-step');
        Route::post('/step/{step}/skip', [App\Http\Controllers\UserOnboardingController::class, 'skipStep'])->name('skip-step');
        Route::post('/reset', [App\Http\Controllers\UserOnboardingController::class, 'reset'])->name('reset');
        Route::post('/dismiss', [App\Http\Controllers\UserOnboardingController::class, 'dismiss'])->name('dismiss');
        Route::get('/progress', [App\Http\Controllers\UserOnboardingController::class, 'getProgress'])->name('progress');
        Route::get('/tips', [App\Http\Controllers\UserOnboardingController::class, 'getTips'])->name('tips');
    });

    // ==================== Organizations ====================
    Route::prefix('orgs')->name('orgs.')->group(function () {
        Route::get('/', [OrgController::class, 'index'])->name('index');
        Route::get('/create', function () { return view('orgs.create'); })->name('create');
        Route::post('/', [OrgController::class, 'store'])->name('store');
        Route::get('/{org}', [OrgController::class, 'show'])->whereUuid('org')->name('show');
        Route::get('/{org}/edit', [OrgController::class, 'edit'])->whereUuid('org')->name('edit');
        Route::put('/{org}', [OrgController::class, 'update'])->whereUuid('org')->name('update');
        Route::get('/{org}/campaigns', [OrgController::class, 'campaigns'])->whereUuid('org')->name('campaigns');
        Route::get('/{org}/campaigns/compare', [OrgController::class, 'compareCampaigns'])->whereUuid('org')->name('campaigns.compare');
        Route::get('/{org}/services', [OrgController::class, 'services'])->whereUuid('org')->name('services');
        Route::get('/{org}/products', [OrgController::class, 'products'])->whereUuid('org')->name('products');
        Route::post('/{org}/campaigns/export/pdf', [OrgController::class, 'exportComparePdf'])->whereUuid('org')->name('campaigns.export.pdf');
        Route::post('/{org}/campaigns/export/excel', [OrgController::class, 'exportCompareExcel'])->whereUuid('org')->name('campaigns.export.excel');
    });

    // ==================== Offerings ====================
    Route::get('/offerings', [OfferingsOverviewController::class, 'index'])->name('offerings.index');
    
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{productId}', function ($productId) {
            $product = \DB::table('cmis.offerings_full_details')->where('offering_id', $productId)->where('type', 'product')->first();
            return view('products.show', ['product' => $product]);
        })->name('show');
    });

    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('/{serviceId}', function ($serviceId) {
            $service = \DB::table('cmis.offerings_full_details')->where('offering_id', $serviceId)->where('type', 'service')->first();
            return view('services.show', ['service' => $service]);
        })->name('show');
    });

    Route::get('/bundles', [BundleController::class, 'index'])->name('offerings.bundles');

    // ==================== Analytics ====================
    Route::get('/analytics', [AnalyticsOverviewController::class, 'index'])->name('analytics.index');
    Route::get('/kpis', [AnalyticsOverviewController::class, 'index'])->name('analytics.kpis');
    Route::get('/reports', [AnalyticsOverviewController::class, 'index'])->name('analytics.reports');
    Route::get('/metrics', [AnalyticsOverviewController::class, 'index'])->name('analytics.metrics');

    // ==================== Creative ====================
    Route::get('/creative', [CreativeOverviewController::class, 'index'])->name('creative.index');
    Route::get('/creative-assets', [CreativeAssetController::class, 'index'])->name('creative-assets.index');
    Route::get('/ads', [CreativeOverviewController::class, 'index'])->name('creative.ads');
    Route::get('/templates', [CreativeOverviewController::class, 'index'])->name('creative.templates');

    Route::prefix('briefs')->name('briefs.')->group(function () {
        Route::get('/', [App\Http\Controllers\CreativeBriefController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\CreativeBriefController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\CreativeBriefController::class, 'store'])->name('store');
        Route::get('/{briefId}', [App\Http\Controllers\CreativeBriefController::class, 'show'])->name('show');
        Route::post('/{briefId}/approve', [App\Http\Controllers\CreativeBriefController::class, 'approve'])->name('approve');
    });

    // ==================== Channels ====================
    Route::prefix('channels')->name('channels.')->group(function () {
        Route::get('/', [WebChannelController::class, 'index'])->name('index');
        Route::get('/{channelId}', [WebChannelController::class, 'show'])->name('show');
    });

    // ==================== AI ====================
    Route::get('/ai', [AIDashboardController::class, 'index'])->name('ai.index');
    Route::get('/ai/campaigns', [AIDashboardController::class, 'index'])->name('ai.campaigns');
    Route::get('/ai/recommendations', [AIDashboardController::class, 'index'])->name('ai.recommendations');
    Route::get('/ai/models', [AIDashboardController::class, 'index'])->name('ai.models');

    // ==================== Knowledge Base ====================
    Route::prefix('knowledge')->name('knowledge.')->group(function () {
        Route::get('/', [App\Http\Controllers\KnowledgeController::class, 'index'])->name('index');
        Route::post('/search', [App\Http\Controllers\KnowledgeController::class, 'search'])->name('search');
        Route::post('/', [App\Http\Controllers\KnowledgeController::class, 'store'])->name('store');
        Route::get('/domains', [App\Http\Controllers\KnowledgeController::class, 'domains'])->name('domains');
        Route::get('/domains/{domain}/categories', [App\Http\Controllers\KnowledgeController::class, 'categories'])->name('categories');
    });

    // ==================== Workflows ====================
    Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/', [App\Http\Controllers\WorkflowController::class, 'index'])->name('index');
        Route::get('/{flowId}', [App\Http\Controllers\WorkflowController::class, 'show'])->name('show');
        Route::post('/initialize-campaign', [App\Http\Controllers\WorkflowController::class, 'initializeCampaign'])->name('initialize-campaign');
        Route::post('/{flowId}/steps/{stepNumber}/complete', [App\Http\Controllers\WorkflowController::class, 'completeStep'])->name('complete-step');
        Route::post('/{flowId}/steps/{stepNumber}/assign', [App\Http\Controllers\WorkflowController::class, 'assignStep'])->name('assign-step');
        Route::post('/{flowId}/steps/{stepNumber}/comment', [App\Http\Controllers\WorkflowController::class, 'addComment'])->name('add-comment');
    });

    // ==================== Social Media ====================
    Route::prefix('social')->name('social.')->group(function () {
        Route::get('/', function () { return view('social.index'); })->name('index');
        Route::get('/posts', function () { return view('social.posts'); })->name('posts');
        Route::get('/scheduler', function () { return view('social.scheduler'); })->name('scheduler');
        Route::get('/inbox', function () { return view('social.inbox'); })->name('inbox');
    });

    // ==================== User Management ====================
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', function () { return view('users.index'); })->name('index');
        Route::get('/create', function () { return view('users.create'); })->name('create');
        Route::get('/{userId}', function ($userId) { return view('users.show', ['userId' => $userId]); })->name('show');
        Route::get('/{userId}/edit', function ($userId) { return view('users.edit', ['userId' => $userId]); })->name('edit');
    });

    // ==================== Settings ====================
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\Settings\SettingsController::class, 'index'])->name('index');
        Route::get('/profile', [App\Http\Controllers\Settings\SettingsController::class, 'profile'])->name('profile');
        Route::get('/notifications', [App\Http\Controllers\Settings\SettingsController::class, 'notifications'])->name('notifications');
        Route::get('/security', [App\Http\Controllers\Settings\SettingsController::class, 'security'])->name('security');
        Route::get('/integrations', [App\Http\Controllers\Settings\SettingsController::class, 'integrations'])->name('integrations');
    });

    // ==================== Profile ====================
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile');

}); // End of Auth Middleware Group

// ==================== API Documentation (Public) ====================
Route::get('/api/documentation', function () {
    return view('api-docs');
})->name('api.documentation');

Route::get('/api/openapi.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/x-yaml',
    ]);
})->name('api.openapi');
