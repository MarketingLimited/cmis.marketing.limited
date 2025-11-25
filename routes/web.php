<?php

use App\Http\Controllers\AI\AIDashboardController;
use App\Http\Controllers\Analytics\OverviewController as AnalyticsOverviewController;
use App\Http\Controllers\Campaigns\CampaignController;
use App\Http\Controllers\Creative\CreativeAssetController;
use App\Http\Controllers\Creative\OverviewController as CreativeOverviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnterpriseAnalyticsController;
use App\Http\Controllers\Offerings\BundleController;
use App\Http\Controllers\Offerings\OverviewController as OfferingsOverviewController;
use App\Http\Controllers\Offerings\ProductController;
use App\Http\Controllers\Offerings\ServiceController;
use App\Http\Controllers\OrgController;
use App\Http\Controllers\Web\ChannelController as WebChannelController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\UnifiedInboxController;
use App\Http\Controllers\UnifiedCommentsController;
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

// Public Routes - Home page redirects appropriately
Route::get('/', function () {
    if (auth()->check()) {
        // If user has active org, go to that org's dashboard; otherwise, choose org
        $user = auth()->user();
        $orgId = $user->active_org_id ?? $user->current_org_id ?? $user->org_id;

        if ($orgId) {
            return redirect()->route('orgs.dashboard.index', ['org' => $orgId]);
        }
        return redirect()->route('orgs.index');
    }
    return redirect()->route('login');
})->name('home');

// Protected Routes - Require Authentication
Route::middleware(['auth'])->group(function () {

    // ==================== User Onboarding (No org context needed) ====================
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

    // ==================== Organizations List (No specific org context) ====================
    Route::prefix('orgs')->name('orgs.')->group(function () {
        Route::get('/', [OrgController::class, 'index'])->name('index');
        Route::get('/create', function () { return view('orgs.create'); })->name('create');
        Route::post('/', [OrgController::class, 'store'])->name('store');
    });

    // ==================== Organization-Specific Routes ====================
    // All routes under /orgs/{org}/* require org context and validate org access
    Route::prefix('orgs/{org}')->name('orgs.')->whereUuid('org')->middleware(['validate.org.access'])->group(function () {

        // Organization Management
        Route::get('/', [OrgController::class, 'show'])->name('show');
        Route::get('/edit', [OrgController::class, 'edit'])->name('edit');
        Route::put('/', [OrgController::class, 'update'])->name('update');

        // ==================== Dashboard ====================
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('/data', [DashboardController::class, 'data'])->name('data');
        });

        // Notifications (org-specific)
        Route::get('/notifications/latest', [DashboardController::class, 'latest'])->name('notifications.latest');
        Route::post('/notifications/{notificationId}/read', [DashboardController::class, 'markAsRead'])->name('notifications.markAsRead');

        // ==================== Campaigns ====================
        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::get('/', [CampaignController::class, 'index'])->name('index');
            Route::get('/performance-dashboard', function () {
                return view('campaigns.performance-dashboard');
            })->name('performance-dashboard');
            Route::get('/compare', [OrgController::class, 'compareCampaigns'])->name('compare');
            Route::post('/export/pdf', [OrgController::class, 'exportComparePdf'])->name('export.pdf');
            Route::post('/export/excel', [OrgController::class, 'exportCompareExcel'])->name('export.excel');
            Route::get('/create', [CampaignController::class, 'create'])->name('create');
            Route::post('/', [CampaignController::class, 'store'])->name('store');
            Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
            Route::get('/{campaign}/edit', [CampaignController::class, 'edit'])->name('edit');
            Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update');
            Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
            Route::get('/{campaign}/performance/{range}', [CampaignController::class, 'performanceByRange'])
                ->whereIn('range', ['daily', 'weekly', 'monthly', 'yearly'])
                ->name('performance');

            // Campaign Wizard
            Route::prefix('wizard')->name('wizard.')->group(function () {
                Route::get('/create', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'create'])->name('create');
                Route::get('/{session_id}/step/{step}', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'showStep'])->name('step');
                Route::post('/{session_id}/step/{step}', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'updateStep'])->name('update');
                Route::get('/{session_id}/save-draft', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'saveDraft'])->name('save-draft');
                Route::get('/{session_id}/complete', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'complete'])->name('complete');
                Route::get('/{session_id}/cancel', [App\Http\Controllers\Campaign\CampaignWizardController::class, 'cancel'])->name('cancel');
            });
        });

        // ==================== Products & Services (Org-specific) ====================
        Route::get('/services', [OrgController::class, 'services'])->name('services');
        Route::get('/products', [OrgController::class, 'products'])->name('products');

        // ==================== Team Management ====================
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [App\Http\Controllers\Web\TeamWebController::class, 'index'])->name('index');
            Route::post('/invite', [App\Http\Controllers\Web\TeamWebController::class, 'invite'])->name('invite');
        });

        // ==================== Analytics ====================
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [EnterpriseAnalyticsController::class, 'enterprise'])->name('index');
            Route::get('/enterprise', [EnterpriseAnalyticsController::class, 'enterprise'])->name('enterprise');
            Route::get('/realtime', [EnterpriseAnalyticsController::class, 'realtime'])->name('realtime');
            Route::get('/campaigns', [EnterpriseAnalyticsController::class, 'campaigns'])->name('campaigns');
            Route::get('/campaign/{campaign_id}', [EnterpriseAnalyticsController::class, 'campaign'])->name('campaign');
            Route::get('/kpis', [EnterpriseAnalyticsController::class, 'kpis'])->name('kpis');
            Route::get('/kpis/{entity_type}/{entity_id}', [EnterpriseAnalyticsController::class, 'kpis'])->name('kpis.entity');
            Route::get('/legacy', [AnalyticsOverviewController::class, 'index'])->name('legacy');
            Route::get('/reports', [AnalyticsOverviewController::class, 'index'])->name('reports');
            Route::get('/metrics', [AnalyticsOverviewController::class, 'index'])->name('metrics');
        });

        // ==================== Creative ====================
        Route::prefix('creative')->name('creative.')->group(function () {
            Route::get('/', [CreativeOverviewController::class, 'index'])->name('index');
            Route::get('/assets', [CreativeAssetController::class, 'index'])->name('assets.index');
            Route::get('/ads', [CreativeOverviewController::class, 'index'])->name('ads');
            Route::get('/templates', [CreativeOverviewController::class, 'index'])->name('templates');

            // Creative Briefs
            Route::prefix('briefs')->name('briefs.')->group(function () {
                Route::get('/', [App\Http\Controllers\CreativeBriefController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\CreativeBriefController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\CreativeBriefController::class, 'store'])->name('store');
                Route::get('/{briefId}', [App\Http\Controllers\CreativeBriefController::class, 'show'])->name('show');
                Route::post('/{briefId}/approve', [App\Http\Controllers\CreativeBriefController::class, 'approve'])->name('approve');
            });
        });

        // ==================== Channels ====================
        Route::prefix('channels')->name('channels.')->group(function () {
            Route::get('/', [WebChannelController::class, 'index'])->name('index');
            Route::get('/{channelId}', [WebChannelController::class, 'show'])->name('show');
        });

        // ==================== AI ====================
        Route::prefix('ai')->name('ai.')->group(function () {
            Route::get('/', [AIDashboardController::class, 'index'])->name('index');
            Route::get('/campaigns', [AIDashboardController::class, 'index'])->name('campaigns');
            Route::get('/recommendations', [AIDashboardController::class, 'index'])->name('recommendations');
            Route::get('/models', [AIDashboardController::class, 'index'])->name('models');
        });

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

        // ==================== Unified Inbox / Comments ====================
        Route::prefix('inbox')->name('inbox.')->group(function () {
            Route::get('/', [UnifiedInboxController::class, 'index'])->name('index');
            Route::get('/comments', [UnifiedCommentsController::class, 'index'])->name('comments');
            Route::post('/comments/{comment_id}/reply', [UnifiedCommentsController::class, 'reply'])->name('comments.reply');
        });

        // ==================== Settings (Org-specific) ====================
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Settings\SettingsController::class, 'index'])->name('index');
            Route::get('/integrations', [App\Http\Controllers\Settings\SettingsController::class, 'integrations'])->name('integrations');
        });

    }); // End of Organization-Specific Routes

    // ==================== Offerings (Global - not org-specific) ====================
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

    // ==================== User-Level Routes (No org context) ====================
    // These routes are user-centric rather than org-centric

    // ==================== User Management ====================
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', function () { return view('users.index'); })->name('index');
        Route::get('/create', function () { return view('users.create'); })->name('create');
        Route::get('/{userId}', function ($userId) { return view('users.show', ['userId' => $userId]); })->name('show');
        Route::get('/{userId}/edit', function ($userId) { return view('users.edit', ['userId' => $userId]); })->name('edit');
    });

    // ==================== User Settings (User-level, not org-specific) ====================
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\Settings\SettingsController::class, 'index'])->name('index');
        Route::get('/profile', [App\Http\Controllers\Settings\SettingsController::class, 'profile'])->name('profile');
        Route::get('/notifications', [App\Http\Controllers\Settings\SettingsController::class, 'notifications'])->name('notifications');
        Route::get('/security', [App\Http\Controllers\Settings\SettingsController::class, 'security'])->name('security');
        // Note: Integrations moved to org-specific settings at /orgs/{org}/settings/integrations
    });

    // ==================== Subscription ====================
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/plans', [App\Http\Controllers\SubscriptionController::class, 'plans'])->name('plans');
        Route::get('/status', [App\Http\Controllers\SubscriptionController::class, 'status'])->name('status');
        Route::get('/upgrade', [App\Http\Controllers\SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::post('/upgrade', [App\Http\Controllers\SubscriptionController::class, 'processUpgrade'])->name('upgrade.process');
        Route::post('/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
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
