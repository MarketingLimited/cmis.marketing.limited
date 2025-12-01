<?php

use App\Http\Controllers\AI\AIDashboardController;
use App\Http\Controllers\Analytics\OverviewController as AnalyticsOverviewController;
use App\Http\Controllers\Campaigns\CampaignController;
use App\Http\Controllers\Campaigns\CampaignAdSetController;
use App\Http\Controllers\Campaigns\CampaignAdController;
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
use App\Http\Controllers\LanguageController;
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

    // Password Reset Routes
    Route::get('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// ==================== Invitation Routes ====================
Route::prefix('invitations')->name('invitations.')->group(function () {
    Route::get('/accept/{token}', [InvitationController::class, 'show'])->name('show');
    Route::post('/accept/{token}', [InvitationController::class, 'accept'])->name('accept');
    Route::get('/decline/{token}', [InvitationController::class, 'decline'])->name('decline');
});

// ==================== OAuth Callback Routes (Public) ====================
// These routes handle OAuth callbacks from external platforms
// The org_id is encoded in the 'state' parameter
Route::prefix('integrations')->name('integrations.')->group(function () {
    Route::get('/youtube/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackYouTube'])->name('youtube.callback');
    Route::get('/linkedin/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackLinkedIn'])->name('linkedin.callback');
    Route::get('/twitter/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackTwitter'])->name('twitter.callback');
    Route::get('/pinterest/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackPinterest'])->name('pinterest.callback');
    Route::get('/tiktok/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackTikTok'])->name('tiktok.callback');
    Route::get('/tumblr/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackTumblr'])->name('tumblr.callback');
    Route::get('/reddit/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackReddit'])->name('reddit.callback');
    Route::get('/google-business/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackGoogleBusiness'])->name('google-business.callback');
    Route::get('/google/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackGoogle'])->name('google.callback');
    Route::get('/snapchat/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackSnapchat'])->name('snapchat.callback');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ==================== Language Switching ====================
Route::post('/language/{locale}', [LanguageController::class, 'switch'])
    ->name('language.switch')
    ->where('locale', 'ar|en');

// GET method for direct URL language switching (backup/testing)
Route::get('/language/{locale}', [LanguageController::class, 'switch'])
    ->name('language.switch.get')
    ->where('locale', 'ar|en');

// DEBUG: Development-only routes (protected in production)
if (app()->environment('local', 'testing', 'development')) {
    Route::get('/debug-locale', function() {
        return response()->json([
            'app_locale' => app()->getLocale(),
            'session_locale' => session('locale'),
            'user_locale' => auth()->check() ? auth()->user()->locale : 'not authenticated',
            'user_email' => auth()->check() ? auth()->user()->email : 'guest',
            'config_locale' => config('app.locale'),
            'session_id' => session()->getId(),
            'browser_accept_language' => request()->header('Accept-Language'),
        ]);
    });

    Route::get('/test-language', function() {
        return view('language-test');
    });

    Route::get('/locale-diagnostic', function() {
        return view('locale-diagnostic');
    });
}

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

// HI-004: Explicit /home route for post-login redirects
Route::get('/home', function () {
    return redirect()->route('home');
})->name('home.explicit');

// HI-007: Organization create alias (old URL pattern)
Route::get('/organizations/create', function () {
    return redirect()->route('orgs.create');
})->name('organizations.create');

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

        // HI-005: Friendly URL routes for onboarding flow
        // Map to existing step-based system for compatibility
        Route::get('/industry', function () {
            return redirect()->route('onboarding.step', ['step' => 1]); // Profile setup includes industry
        })->name('industry');
        Route::get('/goals', function () {
            return redirect()->route('onboarding.step', ['step' => 3]); // First campaign = goals
        })->name('goals');
        Route::get('/complete', [App\Http\Controllers\UserOnboardingController::class, 'complete'])->name('complete');
    });

    // ==================== Organizations List (No specific org context) ====================
    Route::prefix('orgs')->name('orgs.')->group(function () {
        Route::get('/', [OrgController::class, 'index'])->name('index');
        Route::get('/create', function () { return view('orgs.create'); })->name('create');
        Route::post('/', [OrgController::class, 'store'])->name('store');
    });

    // ==================== Organization Switcher (Web Session Auth) ====================
    Route::get('/user/organizations', [\App\Http\Controllers\Core\OrgSwitcherController::class, 'getUserOrganizations'])->name('user.organizations');
    Route::post('/user/switch-organization', [\App\Http\Controllers\Core\OrgSwitcherController::class, 'switchOrganization'])->name('user.switch-organization');

    // ==================== Organization-Specific Routes ====================
    // All routes under /orgs/{org}/* require org context and validate org access
    Route::prefix('orgs/{org}')->name('orgs.')->whereUuid('org')->middleware(['validate.org.access', 'org.context'])->group(function () {

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

            // Ad Sets (nested under campaigns)
            Route::prefix('{campaign}/ad-sets')->name('ad-sets.')->group(function () {
                Route::get('/', [CampaignAdSetController::class, 'index'])->name('index');
                Route::get('/create', [CampaignAdSetController::class, 'create'])->name('create');
                Route::post('/', [CampaignAdSetController::class, 'store'])->name('store');
                Route::get('/{adSet}', [CampaignAdSetController::class, 'show'])->name('show');
                Route::get('/{adSet}/edit', [CampaignAdSetController::class, 'edit'])->name('edit');
                Route::put('/{adSet}', [CampaignAdSetController::class, 'update'])->name('update');
                Route::delete('/{adSet}', [CampaignAdSetController::class, 'destroy'])->name('destroy');
                Route::post('/{adSet}/duplicate', [CampaignAdSetController::class, 'duplicate'])->name('duplicate');
                Route::patch('/{adSet}/status', [CampaignAdSetController::class, 'updateStatus'])->name('status');

                // Ads (nested under ad sets)
                Route::prefix('{adSet}/ads')->name('ads.')->group(function () {
                    Route::get('/', [CampaignAdController::class, 'index'])->name('index');
                    Route::get('/create', [CampaignAdController::class, 'create'])->name('create');
                    Route::post('/', [CampaignAdController::class, 'store'])->name('store');
                    Route::get('/{ad}', [CampaignAdController::class, 'show'])->name('show');
                    Route::get('/{ad}/edit', [CampaignAdController::class, 'edit'])->name('edit');
                    Route::put('/{ad}', [CampaignAdController::class, 'update'])->name('update');
                    Route::delete('/{ad}', [CampaignAdController::class, 'destroy'])->name('destroy');
                    Route::post('/{ad}/duplicate', [CampaignAdController::class, 'duplicate'])->name('duplicate');
                    Route::patch('/{ad}/status', [CampaignAdController::class, 'updateStatus'])->name('status');
                    Route::get('/{ad}/preview', [CampaignAdController::class, 'preview'])->name('preview');
                });
            });

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
        Route::post('/products', [OrgController::class, 'storeProduct'])->name('products.store');
        Route::put('/products/{product}', [OrgController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [OrgController::class, 'destroyProduct'])->name('products.destroy');

        // ==================== Audiences (Unified Multi-Platform) ====================
        Route::prefix('audiences')->name('audiences.')->group(function () {
            Route::get('/', [App\Http\Controllers\Web\AudienceWebController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Web\AudienceWebController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Web\AudienceWebController::class, 'store'])->name('store');
            Route::get('/builder', [App\Http\Controllers\Web\AudienceWebController::class, 'builder'])->name('builder');
            Route::get('/{audience}', [App\Http\Controllers\Web\AudienceWebController::class, 'show'])->name('show');
            Route::get('/{audience}/edit', [App\Http\Controllers\Web\AudienceWebController::class, 'edit'])->name('edit');
            Route::put('/{audience}', [App\Http\Controllers\Web\AudienceWebController::class, 'update'])->name('update');
            Route::delete('/{audience}', [App\Http\Controllers\Web\AudienceWebController::class, 'destroy'])->name('destroy');
            Route::post('/{audience}/sync/{platform}', [App\Http\Controllers\Web\AudienceWebController::class, 'syncToPlatform'])->name('sync');
        });

        // ==================== Keywords (Google Ads) ====================
        Route::prefix('keywords')->name('keywords.')->group(function () {
            Route::get('/', [App\Http\Controllers\Web\KeywordWebController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Web\KeywordWebController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Web\KeywordWebController::class, 'store'])->name('store');
            Route::get('/planner', [App\Http\Controllers\Web\KeywordWebController::class, 'planner'])->name('planner');
            Route::get('/negative', [App\Http\Controllers\Web\KeywordWebController::class, 'negative'])->name('negative');
            Route::get('/groups', [App\Http\Controllers\Web\KeywordWebController::class, 'groups'])->name('groups');
            Route::get('/{keyword}', [App\Http\Controllers\Web\KeywordWebController::class, 'show'])->name('show');
            Route::get('/{keyword}/edit', [App\Http\Controllers\Web\KeywordWebController::class, 'edit'])->name('edit');
            Route::put('/{keyword}', [App\Http\Controllers\Web\KeywordWebController::class, 'update'])->name('update');
            Route::delete('/{keyword}', [App\Http\Controllers\Web\KeywordWebController::class, 'destroy'])->name('destroy');
        });

        // ==================== Catalogs (Multi-Platform Product Feeds) ====================
        Route::prefix('catalogs')->name('catalogs.')->group(function () {
            Route::get('/', [App\Http\Controllers\Web\CatalogWebController::class, 'index'])->name('index');
            Route::get('/import', [App\Http\Controllers\Web\CatalogWebController::class, 'import'])->name('import');
            Route::post('/import', [App\Http\Controllers\Web\CatalogWebController::class, 'processImport'])->name('import.process');
            Route::get('/{catalog}', [App\Http\Controllers\Web\CatalogWebController::class, 'show'])->name('show');
            Route::post('/{catalog}/sync', [App\Http\Controllers\Web\CatalogWebController::class, 'sync'])->name('sync');
        });

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
            // HI-009: Fixed to use correct controller methods for reports and metrics
            Route::get('/reports', [AnalyticsOverviewController::class, 'reports'])->name('reports');
            Route::get('/metrics', [AnalyticsOverviewController::class, 'metrics'])->name('metrics');
            // Platform Insights - Social, Ads, GA, GSC
            Route::get('/platform-insights', [EnterpriseAnalyticsController::class, 'platformInsights'])->name('platform-insights');
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
                Route::get('/{briefId}/edit', [App\Http\Controllers\CreativeBriefController::class, 'edit'])->name('edit');
                Route::put('/{briefId}', [App\Http\Controllers\CreativeBriefController::class, 'update'])->name('update');
                Route::delete('/{briefId}', [App\Http\Controllers\CreativeBriefController::class, 'destroy'])->name('destroy');
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

        // ==================== Predictive Analytics ====================
        Route::prefix('predictive')->name('predictive.')->group(function () {
            Route::get('/', [App\Http\Controllers\Intelligence\PredictiveController::class, 'index'])->name('index');
        });

        // ==================== A/B Testing & Experiments ====================
        Route::prefix('experiments')->name('experiments.')->group(function () {
            Route::get('/', [App\Http\Controllers\Testing\ExperimentsController::class, 'index'])->name('index');
        });

        // ==================== Optimization Engine ====================
        Route::prefix('optimization')->name('optimization.')->group(function () {
            Route::get('/', [App\Http\Controllers\Optimization\OptimizationDashboardController::class, 'index'])->name('index');
        });

        // ==================== Automation ====================
        Route::prefix('automation')->name('automation.')->group(function () {
            Route::get('/', [App\Http\Controllers\Automation\AutomationDashboardController::class, 'index'])->name('index');
        });

        // ==================== System: Alerts ====================
        Route::prefix('alerts')->name('alerts.')->group(function () {
            Route::get('/', [App\Http\Controllers\System\AlertsController::class, 'index'])->name('index');
        });

        // ==================== System: Data Exports ====================
        Route::prefix('exports')->name('exports.')->group(function () {
            Route::get('/', [App\Http\Controllers\System\ExportsController::class, 'index'])->name('index');
        });

        // ==================== System: Dashboard Builder ====================
        Route::prefix('dashboard-builder')->name('dashboard-builder.')->group(function () {
            Route::get('/', [App\Http\Controllers\System\DashboardBuilderController::class, 'index'])->name('index');
        });

        // ==================== System: Feature Flags ====================
        Route::prefix('feature-flags')->name('feature-flags.')->group(function () {
            Route::get('/', [App\Http\Controllers\System\FeatureFlagsController::class, 'index'])->name('index');
        });

        // ==================== Knowledge Base ====================
        Route::prefix('knowledge')->name('knowledge.')->group(function () {
            Route::get('/', [App\Http\Controllers\KnowledgeController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\KnowledgeController::class, 'create'])->name('create');
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

        // ==================== Influencer Marketing ====================
        Route::prefix('influencer')->name('influencer.')->group(function () {
            Route::get('/', [App\Http\Controllers\Influencer\InfluencerController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Influencer\InfluencerController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Influencer\InfluencerController::class, 'store'])->name('store');
            Route::get('/{influencer}', [App\Http\Controllers\Influencer\InfluencerController::class, 'show'])->name('show');
            Route::get('/{influencer}/edit', [App\Http\Controllers\Influencer\InfluencerController::class, 'edit'])->name('edit');
            Route::put('/{influencer}', [App\Http\Controllers\Influencer\InfluencerController::class, 'update'])->name('update');
            Route::delete('/{influencer}', [App\Http\Controllers\Influencer\InfluencerController::class, 'destroy'])->name('destroy');
        });

        // ==================== Campaign Orchestration ====================
        Route::prefix('orchestration')->name('orchestration.')->group(function () {
            Route::get('/', [App\Http\Controllers\Orchestration\OrchestrationController::class, 'index'])->name('index');
        });

        // ==================== Social Listening ====================
        Route::prefix('listening')->name('listening.')->group(function () {
            Route::get('/', [App\Http\Controllers\Listening\SocialListeningController::class, 'index'])->name('index');
        });

        // ==================== Social Media ====================
        Route::prefix('social')->name('social.')->group(function () {
            Route::get('/', function ($org) {
                $currentOrg = \App\Models\Core\Org::findOrFail($org);
                return view('social.index', compact('currentOrg'));
            })->name('index');
            Route::get('/posts', function ($org) {
                $currentOrg = \App\Models\Core\Org::findOrFail($org);
                return view('social.posts', compact('currentOrg'));
            })->name('posts');
            Route::get('/scheduler', function ($org) {
                $currentOrg = \App\Models\Core\Org::findOrFail($org);
                return view('social.scheduler', compact('currentOrg'));
            })->name('scheduler');
            Route::get('/inbox', function ($org) {
                $currentOrg = \App\Models\Core\Org::findOrFail($org);
                return view('social.inbox', compact('currentOrg'));
            })->name('inbox');

            // Post actions (uses session auth from web middleware)
            Route::delete('/posts/{post}', [App\Http\Controllers\Social\SocialPostController::class, 'destroy'])->name('posts.destroy');
            Route::put('/posts/{post}', [App\Http\Controllers\Social\SocialPostController::class, 'update'])->name('posts.update');
            Route::delete('/posts-failed', [App\Http\Controllers\Social\SocialPostController::class, 'destroyAllFailed'])->name('posts.destroy-all-failed');

            // Queue settings
            Route::get('/queue-settings', [App\Http\Controllers\Social\SocialPostController::class, 'getQueueSettings'])->name('queue-settings.index');
            Route::post('/queue-settings', [App\Http\Controllers\Social\SocialPostController::class, 'saveQueueSettings'])->name('queue-settings.save');
            Route::get('/queue-slot/{integrationId}', [App\Http\Controllers\Social\SocialPostController::class, 'getNextQueueSlot'])->name('queue-settings.next-slot');

            // Post types
            Route::get('/post-types', [App\Http\Controllers\Social\SocialPostController::class, 'getPostTypes'])->name('post-types.index');

            // ==================== Publishing Modal API (JSON) ====================
            Route::prefix('publish-modal')->name('publish-modal.')->group(function () {
                Route::get('/profile-groups', [App\Http\Controllers\API\PublishingModalController::class, 'getProfileGroupsWithProfiles'])->name('profile-groups');
                Route::get('/brand-voices', [App\Http\Controllers\API\PublishingModalController::class, 'getBrandVoices'])->name('brand-voices');
                Route::post('/validate-safety', [App\Http\Controllers\API\PublishingModalController::class, 'validateBrandSafety'])->name('validate-safety');
                Route::post('/create', [App\Http\Controllers\API\PublishingModalController::class, 'createPost'])->name('create');
                Route::post('/save-draft', [App\Http\Controllers\API\PublishingModalController::class, 'saveDraft'])->name('save-draft');
                Route::get('/best-times', [App\Http\Controllers\API\PublishingModalController::class, 'getBestTimes'])->name('best-times');
                Route::get('/character-limits', [App\Http\Controllers\API\PublishingModalController::class, 'getCharacterLimits'])->name('character-limits');
                Route::post('/status', [App\Http\Controllers\API\PublishingModalController::class, 'getPostsStatus'])->name('status');
                // Timezone API - Get timezone from profile group inheritance chain (session auth)
                Route::post('/timezone', [App\Http\Controllers\Social\SocialPostController::class, 'getTimezone'])->name('timezone');

                // Redirect GET requests on POST-only endpoints to social page
                Route::get('/create', fn($org) => redirect()->route('orgs.social.index', $org))->name('create.redirect');
                Route::get('/save-draft', fn($org) => redirect()->route('orgs.social.index', $org))->name('save-draft.redirect');
                Route::get('/validate-safety', fn($org) => redirect()->route('orgs.social.index', $org))->name('validate-safety.redirect');
            });

            // ==================== Social Post API (JSON - Session Auth) ====================
            Route::get('/accounts', [App\Http\Controllers\Social\SocialPostController::class, 'getConnectedAccounts'])->name('accounts');
            Route::get('/posts-json', [App\Http\Controllers\Social\SocialPostController::class, 'index'])->name('posts.json');
            Route::get('/collaborators/suggestions', [App\Http\Controllers\Social\SocialPostController::class, 'getCollaboratorSuggestions'])->name('collaborators.suggestions');
            Route::post('/collaborators', [App\Http\Controllers\Social\SocialPostController::class, 'storeCollaborator'])->name('collaborators.store');
            Route::post('/posts/{post}/publish', [App\Http\Controllers\Social\SocialPostController::class, 'publish'])->name('posts.publish');
            Route::post('/instagram/validate-username', [App\Http\Controllers\Social\SocialPostController::class, 'validateInstagramUsername'])->name('instagram.validate-username');
            Route::get('/trending-hashtags/{platform}', [App\Http\Controllers\Social\SocialPostController::class, 'getTrendingHashtags'])->name('trending-hashtags');
            Route::post('/shorten-link', [App\Http\Controllers\Social\LinkShortenerController::class, 'shorten'])->name('shorten-link');
            Route::get('/locations/search', [App\Http\Controllers\Social\LocationController::class, 'search'])->name('locations.search');
            Route::get('/posts-scheduled', [App\Http\Controllers\Social\SocialPostController::class, 'getScheduledPosts'])->name('posts.scheduled');
            Route::post('/posts/{post}/reschedule', [App\Http\Controllers\Social\SocialPostController::class, 'reschedule'])->name('posts.reschedule');
            Route::get('/media-library', [App\Http\Controllers\Social\MediaLibraryController::class, 'index'])->name('media-library');
            Route::post('/media/upload', [App\Http\Controllers\Social\MediaLibraryController::class, 'upload'])->name('media.upload');

            // AI Content Transformation
            Route::post('/ai/transform-content', [App\Http\Controllers\API\AIAssistantController::class, 'transformSocialContent'])->name('ai.transform-content');

            // ==================== Historical Content & Brand Knowledge ====================
            Route::prefix('history')->name('history.')->group(function () {
                // Web Views
                Route::get('/', function () { return view('social.history.index'); })->name('index');
                Route::get('/analytics', function () { return view('social.history.analytics'); })->name('analytics');
                Route::get('/knowledge-base', function () { return view('social.history.knowledge-base'); })->name('knowledge-base');

                // API Endpoints (JSON responses)
                Route::prefix('api')->name('api.')->group(function () {
                    // Historical posts
                    Route::get('/posts', [App\Http\Controllers\Social\HistoricalContentController::class, 'index'])->name('posts.index');
                    Route::get('/posts/{id}', [App\Http\Controllers\Social\HistoricalContentController::class, 'show'])->name('posts.show');
                    Route::post('/import', [App\Http\Controllers\Social\HistoricalContentController::class, 'import'])->name('import');
                    Route::post('/posts/{id}/analyze', [App\Http\Controllers\Social\HistoricalContentController::class, 'analyze'])->name('posts.analyze');
                    Route::post('/batch-analyze', [App\Http\Controllers\Social\HistoricalContentController::class, 'batchAnalyze'])->name('batch-analyze');
                    Route::get('/progress', [App\Http\Controllers\Social\HistoricalContentController::class, 'getProgress'])->name('progress');

                    // Knowledge base operations
                    Route::post('/kb/add', [App\Http\Controllers\Social\HistoricalContentController::class, 'addToKnowledgeBase'])->name('kb.add');
                    Route::post('/kb/remove', [App\Http\Controllers\Social\HistoricalContentController::class, 'removeFromKnowledgeBase'])->name('kb.remove');
                    Route::post('/kb/build', [App\Http\Controllers\Social\HistoricalContentController::class, 'buildKnowledgeBase'])->name('kb.build');
                    Route::get('/kb/summary', [App\Http\Controllers\Social\HistoricalContentController::class, 'getKBSummary'])->name('kb.summary');
                    Route::post('/kb/query', [App\Http\Controllers\Social\HistoricalContentController::class, 'queryKB'])->name('kb.query');
                    Route::post('/kb/recommendations', [App\Http\Controllers\Social\HistoricalContentController::class, 'getRecommendations'])->name('kb.recommendations');
                    Route::get('/kb/export', [App\Http\Controllers\Social\HistoricalContentController::class, 'exportKB'])->name('kb.export');

                    // Brand DNA & configuration
                    Route::get('/brand-dna', [App\Http\Controllers\Social\HistoricalContentController::class, 'getBrandDNA'])->name('brand-dna');
                    Route::get('/kb/config', [App\Http\Controllers\Social\HistoricalContentController::class, 'getKBConfig'])->name('kb.config.get');
                    Route::put('/kb/config', [App\Http\Controllers\Social\HistoricalContentController::class, 'updateKBConfig'])->name('kb.config.update');

                    // KB-Enhanced Content Generation
                    Route::post('/kb-content/generate-post', [App\Http\Controllers\Social\KBContentGenerationController::class, 'generatePost'])->name('kb-content.generate-post');
                    Route::post('/kb-content/generate-ad-copy', [App\Http\Controllers\Social\KBContentGenerationController::class, 'generateAdCopy'])->name('kb-content.generate-ad-copy');
                    Route::post('/kb-content/generate-variations', [App\Http\Controllers\Social\KBContentGenerationController::class, 'generateVariations'])->name('kb-content.generate-variations');
                    Route::get('/kb-content/suggestions', [App\Http\Controllers\Social\KBContentGenerationController::class, 'getSuggestions'])->name('kb-content.suggestions');
                    Route::post('/kb-content/analyze-fit', [App\Http\Controllers\Social\KBContentGenerationController::class, 'analyzeContentFit'])->name('kb-content.analyze-fit');

                    // Boost & Campaign Integration
                    Route::post('/posts/{id}/boost', [App\Http\Controllers\Social\HistoricalContentController::class, 'boostPost'])->name('posts.boost');
                    Route::post('/posts/{id}/add-to-campaign', [App\Http\Controllers\Social\HistoricalContentController::class, 'addToCampaign'])->name('posts.add-to-campaign');
                    Route::post('/posts/{id}/create-campaign', [App\Http\Controllers\Social\HistoricalContentController::class, 'createCampaignFromPost'])->name('posts.create-campaign');
                    Route::get('/posts/{id}/available-boost-rules', [App\Http\Controllers\Social\HistoricalContentController::class, 'getAvailableBoostRules'])->name('posts.available-boost-rules');
                });
            });
        });

        // ==================== Unified Inbox / Comments ====================
        Route::prefix('inbox')->name('inbox.')->group(function () {
            Route::get('/', [UnifiedInboxController::class, 'index'])->name('index');
            Route::get('/comments', [UnifiedCommentsController::class, 'index'])->name('comments');
            Route::post('/comments/{comment_id}/reply', [UnifiedCommentsController::class, 'reply'])->name('comments.reply');
        });

        // ==================== Settings (Org-specific) ====================
        Route::prefix('settings')->name('settings.')->group(function () {
            // Redirect /settings to user settings page
            Route::get('/', function ($org) {
                return redirect()->route('orgs.settings.user', $org);
            })->name('index');

            // User Settings Page (Profile, Notifications, Security)
            Route::get('/user', [App\Http\Controllers\Settings\SettingsController::class, 'userSettings'])->name('user');

            // Organization Settings Page (General, Team, API, Billing)
            Route::get('/organization', [App\Http\Controllers\Settings\SettingsController::class, 'organizationSettings'])->name('organization');

            // User Settings Actions
            Route::put('/profile', [App\Http\Controllers\Settings\SettingsController::class, 'updateProfile'])->name('profile.update');
            Route::put('/notifications', [App\Http\Controllers\Settings\SettingsController::class, 'updateNotifications'])->name('notifications.update');
            Route::put('/password', [App\Http\Controllers\Settings\SettingsController::class, 'updatePassword'])->name('password.update');
            Route::delete('/sessions/{session}', [App\Http\Controllers\Settings\SettingsController::class, 'destroySession'])->name('sessions.destroy');

            // Organization Settings Actions
            Route::put('/organization/update', [App\Http\Controllers\Settings\SettingsController::class, 'updateOrganization'])->name('organization.update');
            Route::post('/api-tokens', [App\Http\Controllers\Settings\SettingsController::class, 'storeApiToken'])->name('api-tokens.store');
            Route::delete('/api-tokens/{token}', [App\Http\Controllers\Settings\SettingsController::class, 'destroyApiToken'])->name('api-tokens.destroy');
            Route::post('/team/invite', [App\Http\Controllers\Settings\SettingsController::class, 'inviteTeamMember'])->name('team.invite');
            Route::delete('/team/{user}', [App\Http\Controllers\Settings\SettingsController::class, 'removeTeamMember'])->name('team.remove');

            // HI-010: Platform Settings Pages (platform-specific configuration)
            Route::prefix('platforms')->name('platforms.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'index'])->name('index');
                Route::get('/meta', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'meta'])->name('meta');
                Route::get('/google', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'google'])->name('google');
                Route::get('/tiktok', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'tiktok'])->name('tiktok');
                Route::get('/linkedin', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'linkedin'])->name('linkedin');
                Route::get('/twitter', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'twitter'])->name('twitter');
                Route::get('/snapchat', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'snapchat'])->name('snapchat');

                // Platform settings update actions
                Route::put('/meta', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'updateMeta'])->name('meta.update');
                Route::put('/google', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'updateGoogle'])->name('google.update');
                Route::put('/tiktok', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'updateTikTok'])->name('tiktok.update');
                Route::put('/linkedin', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'updateLinkedIn'])->name('linkedin.update');
                Route::put('/twitter', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'updateTwitter'])->name('twitter.update');
                Route::put('/snapchat', [App\Http\Controllers\Settings\PlatformSettingsController::class, 'updateSnapchat'])->name('snapchat.update');
            });

            // Platform Connections (Meta, Google, TikTok, etc.)
            Route::prefix('platform-connections')->name('platform-connections.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'index'])->name('index');

                // API Routes
                Route::prefix('api')->name('api.')->group(function () {
                    Route::get('/list', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'listIntegrations'])->name('list');
                });

                // Meta System User Token Management
                Route::get('/meta/add', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'createMetaToken'])->name('meta.create');
                Route::post('/meta', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeMetaToken'])->name('meta.store');
                Route::get('/meta/{connection}/edit', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'editMetaToken'])->name('meta.edit');
                Route::put('/meta/{connection}', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'updateMetaToken'])->name('meta.update');
                Route::post('/meta/{connection}/refresh-accounts', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'refreshAdAccounts'])->name('meta.refresh-accounts');

                // Meta Asset Selection (Pages, Instagram, Ad Accounts, Pixels, Catalogs)
                Route::get('/meta/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectMetaAssets'])->name('meta.assets');
                Route::post('/meta/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeMetaAssets'])->name('meta.assets.store');

                // Google Service Account / OAuth Token Management
                Route::get('/google/add', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'createGoogleToken'])->name('google.create');
                Route::post('/google', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeGoogleToken'])->name('google.store');
                Route::get('/google/{connection}/edit', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'editGoogleToken'])->name('google.edit');
                Route::put('/google/{connection}', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'updateGoogleToken'])->name('google.update');

                // Meta OAuth (Facebook Login)
                Route::get('/meta/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeMeta'])->name('meta.authorize');
                Route::get('/meta/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackMeta'])->name('meta.callback');

                // YouTube OAuth
                Route::get('/youtube/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeYouTube'])->name('youtube.authorize');
                Route::get('/youtube/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackYouTube'])->name('youtube.callback');

                // LinkedIn OAuth
                Route::get('/linkedin/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeLinkedIn'])->name('linkedin.authorize');
                Route::get('/linkedin/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackLinkedIn'])->name('linkedin.callback');

                // Twitter/X OAuth
                Route::get('/twitter/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeTwitter'])->name('twitter.authorize');
                Route::get('/twitter/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackTwitter'])->name('twitter.callback');

                // Pinterest OAuth
                Route::get('/pinterest/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizePinterest'])->name('pinterest.authorize');
                Route::get('/pinterest/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackPinterest'])->name('pinterest.callback');

                // TikTok OAuth
                Route::get('/tiktok/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeTikTok'])->name('tiktok.authorize');
                Route::get('/tiktok/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackTikTok'])->name('tiktok.callback');

                // Reddit OAuth
                Route::get('/reddit/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeReddit'])->name('reddit.authorize');
                Route::get('/reddit/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackReddit'])->name('reddit.callback');

                // Tumblr OAuth
                Route::get('/tumblr/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeTumblr'])->name('tumblr.authorize');
                Route::get('/tumblr/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackTumblr'])->name('tumblr.callback');

                // Google Business Profile OAuth
                Route::get('/google-business/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeGoogleBusiness'])->name('google-business.authorize');
                Route::get('/google-business/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackGoogleBusiness'])->name('google-business.callback');

                // Google OAuth (unified for all Google services)
                Route::get('/google/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeGoogle'])->name('google.authorize');

                // Snapchat OAuth
                Route::get('/snapchat/authorize', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'authorizeSnapchat'])->name('snapchat.authorize');
                Route::get('/snapchat/callback', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'callbackSnapchat'])->name('snapchat.callback');

                // LinkedIn Assets
                Route::get('/linkedin/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectLinkedInAssets'])->name('linkedin.assets');
                Route::post('/linkedin/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeLinkedInAssets'])->name('linkedin.assets.store');

                // Twitter/X Assets
                Route::get('/twitter/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectTwitterAssets'])->name('twitter.assets');
                Route::post('/twitter/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeTwitterAssets'])->name('twitter.assets.store');

                // TikTok Assets
                Route::get('/tiktok/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectTikTokAssets'])->name('tiktok.assets');
                Route::post('/tiktok/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeTikTokAssets'])->name('tiktok.assets.store');

                // Snapchat Assets
                Route::get('/snapchat/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectSnapchatAssets'])->name('snapchat.assets');
                Route::post('/snapchat/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeSnapchatAssets'])->name('snapchat.assets.store');

                // Pinterest Assets
                Route::get('/pinterest/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectPinterestAssets'])->name('pinterest.assets');
                Route::post('/pinterest/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storePinterestAssets'])->name('pinterest.assets.store');

                // YouTube Assets
                Route::get('/youtube/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectYouTubeAssets'])->name('youtube.assets');
                Route::post('/youtube/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeYouTubeAssets'])->name('youtube.assets.store');

                // Google Assets (Business Profile & Ads)
                Route::get('/google/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectGoogleAssets'])->name('google.assets');
                Route::post('/google/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeGoogleAssets'])->name('google.assets.store');

                // Reddit Assets
                Route::get('/reddit/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'selectRedditAssets'])->name('reddit.assets');
                Route::post('/reddit/{connection}/assets', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'storeRedditAssets'])->name('reddit.assets.store');

                // Generic Connection Actions
                Route::post('/{connection}/test', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'testConnection'])->name('test');
                Route::delete('/{connection}', [App\Http\Controllers\Settings\PlatformConnectionsController::class, 'destroy'])->name('destroy');
            });

            // ==================== Profile Management (VistaSocial-like) ====================
            Route::prefix('profiles')->name('profiles.')->group(function () {
                // List and stats
                Route::get('/', [App\Http\Controllers\Settings\ProfileManagementController::class, 'index'])->name('index');
                Route::get('/stats', [App\Http\Controllers\Settings\ProfileManagementController::class, 'stats'])->name('stats');

                // Single profile
                Route::get('/{integration_id}', [App\Http\Controllers\Settings\ProfileManagementController::class, 'show'])->name('show');
                Route::patch('/{integration_id}', [App\Http\Controllers\Settings\ProfileManagementController::class, 'update'])->name('update');
                Route::delete('/{integration_id}', [App\Http\Controllers\Settings\ProfileManagementController::class, 'destroy'])->name('destroy');

                // Profile actions
                Route::post('/{integration_id}/avatar', [App\Http\Controllers\Settings\ProfileManagementController::class, 'updateAvatar'])->name('avatar.update');
                Route::post('/{integration_id}/toggle', [App\Http\Controllers\Settings\ProfileManagementController::class, 'toggleEnabled'])->name('toggle');
                Route::post('/{integration_id}/refresh', [App\Http\Controllers\Settings\ProfileManagementController::class, 'refreshConnection'])->name('refresh');

                // Profile group assignment
                Route::post('/{integration_id}/groups', [App\Http\Controllers\Settings\ProfileManagementController::class, 'assignGroup'])->name('groups.assign');
                Route::delete('/{integration_id}/groups', [App\Http\Controllers\Settings\ProfileManagementController::class, 'removeFromGroup'])->name('groups.remove');

                // Queue settings
                Route::get('/{integration_id}/queue', [App\Http\Controllers\Settings\ProfileManagementController::class, 'getQueueSettings'])->name('queue.show');
                Route::patch('/{integration_id}/queue', [App\Http\Controllers\Settings\ProfileManagementController::class, 'updateQueueSettings'])->name('queue.update');

                // Boost rules
                Route::get('/{integration_id}/boosts', [App\Http\Controllers\Settings\ProfileManagementController::class, 'getBoostRules'])->name('boosts.index');
                Route::post('/{integration_id}/boosts', [App\Http\Controllers\Settings\ProfileManagementController::class, 'createBoostRule'])->name('boosts.store');
                Route::patch('/{integration_id}/boosts/{boost_id}', [App\Http\Controllers\Settings\ProfileManagementController::class, 'updateBoostRule'])->name('boosts.update');
                Route::delete('/{integration_id}/boosts/{boost_id}', [App\Http\Controllers\Settings\ProfileManagementController::class, 'deleteBoostRule'])->name('boosts.destroy');
                Route::post('/{integration_id}/boosts/{boost_id}/toggle', [App\Http\Controllers\Settings\ProfileManagementController::class, 'toggleBoostRule'])->name('boosts.toggle');

                // Boost helper endpoints (ad accounts, audiences, budget validation)
                Route::get('/{integration_id}/ad-accounts', [App\Http\Controllers\Settings\ProfileManagementController::class, 'getAdAccounts'])->name('ad-accounts');
                Route::get('/{integration_id}/audiences', [App\Http\Controllers\Settings\ProfileManagementController::class, 'getAudiences'])->name('audiences');
                Route::post('/{integration_id}/validate-budget', [App\Http\Controllers\Settings\ProfileManagementController::class, 'validateBudget'])->name('validate-budget');
            });

            // ==================== Queue Slot Labels (Organization-wide) ====================
            Route::prefix('queue-labels')->name('queue-labels.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\QueueSlotLabelController::class, 'index'])->name('index');
                Route::post('/', [App\Http\Controllers\Settings\QueueSlotLabelController::class, 'store'])->name('store');
                Route::patch('/{label_id}', [App\Http\Controllers\Settings\QueueSlotLabelController::class, 'update'])->name('update');
                Route::delete('/{label_id}', [App\Http\Controllers\Settings\QueueSlotLabelController::class, 'destroy'])->name('destroy');
                Route::get('/presets', [App\Http\Controllers\Settings\QueueSlotLabelController::class, 'presets'])->name('presets');
                Route::post('/reorder', [App\Http\Controllers\Settings\QueueSlotLabelController::class, 'reorder'])->name('reorder');
            });

            // ==================== Profile Groups (Publishing Management) ====================
            Route::prefix('profile-groups')->name('profile-groups.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'store'])->name('store');
                Route::get('/{group}', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'show'])->name('show');
                Route::get('/{group}/edit', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'edit'])->name('edit');
                Route::put('/{group}', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'update'])->name('update');
                Route::delete('/{group}', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'destroy'])->name('destroy');

                // Profile Group Members
                Route::get('/{group}/members', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'members'])->name('members');
                Route::post('/{group}/members', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'addMember'])->name('members.add');
                Route::put('/{group}/members/{member}', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'updateMember'])->name('members.update');
                Route::delete('/{group}/members/{member}', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'removeMember'])->name('members.remove');

                // Profile Group Social Profiles
                Route::get('/{group}/profiles', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'profiles'])->name('profiles');
                Route::post('/{group}/profiles', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'attachProfile'])->name('profiles.attach');
                Route::delete('/{group}/profiles/{profile}', [App\Http\Controllers\Settings\ProfileGroupSettingsController::class, 'detachProfile'])->name('profiles.detach');
            });

            // ==================== Brand Voices ====================
            Route::prefix('brand-voices')->name('brand-voices.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\BrandVoiceSettingsController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Settings\BrandVoiceSettingsController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Settings\BrandVoiceSettingsController::class, 'store'])->name('store');
                Route::get('/{voice}', [App\Http\Controllers\Settings\BrandVoiceSettingsController::class, 'show'])->name('show');
                Route::get('/{voice}/edit', [App\Http\Controllers\Settings\BrandVoiceSettingsController::class, 'edit'])->name('edit');
                Route::put('/{voice}', [App\Http\Controllers\Settings\BrandVoiceSettingsController::class, 'update'])->name('update');
                Route::delete('/{voice}', [App\Http\Controllers\Settings\BrandVoiceSettingsController::class, 'destroy'])->name('destroy');
                Route::post('/{voice}/duplicate', [App\Http\Controllers\Settings\BrandVoiceSettingsController::class, 'duplicate'])->name('duplicate');
            });

            // ==================== Brand Safety Policies ====================
            Route::prefix('brand-safety')->name('brand-safety.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\BrandSafetySettingsController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Settings\BrandSafetySettingsController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Settings\BrandSafetySettingsController::class, 'store'])->name('store');
                Route::get('/{policy}', [App\Http\Controllers\Settings\BrandSafetySettingsController::class, 'show'])->name('show');
                Route::get('/{policy}/edit', [App\Http\Controllers\Settings\BrandSafetySettingsController::class, 'edit'])->name('edit');
                Route::put('/{policy}', [App\Http\Controllers\Settings\BrandSafetySettingsController::class, 'update'])->name('update');
                Route::delete('/{policy}', [App\Http\Controllers\Settings\BrandSafetySettingsController::class, 'destroy'])->name('destroy');
                Route::post('/{policy}/validate', [App\Http\Controllers\Settings\BrandSafetySettingsController::class, 'validateContent'])->name('validate');
            });

            // ==================== Approval Workflows ====================
            Route::prefix('approval-workflows')->name('approval-workflows.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\ApprovalWorkflowSettingsController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Settings\ApprovalWorkflowSettingsController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Settings\ApprovalWorkflowSettingsController::class, 'store'])->name('store');
                Route::get('/{workflow}', [App\Http\Controllers\Settings\ApprovalWorkflowSettingsController::class, 'show'])->name('show');
                Route::get('/{workflow}/edit', [App\Http\Controllers\Settings\ApprovalWorkflowSettingsController::class, 'edit'])->name('edit');
                Route::put('/{workflow}', [App\Http\Controllers\Settings\ApprovalWorkflowSettingsController::class, 'update'])->name('update');
                Route::delete('/{workflow}', [App\Http\Controllers\Settings\ApprovalWorkflowSettingsController::class, 'destroy'])->name('destroy');
                Route::post('/{workflow}/toggle', [App\Http\Controllers\Settings\ApprovalWorkflowSettingsController::class, 'toggle'])->name('toggle');
            });

            // ==================== Boost Rules ====================
            Route::prefix('boost-rules')->name('boost-rules.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\BoostRuleSettingsController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Settings\BoostRuleSettingsController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Settings\BoostRuleSettingsController::class, 'store'])->name('store');
                Route::get('/{rule}', [App\Http\Controllers\Settings\BoostRuleSettingsController::class, 'show'])->name('show');
                Route::get('/{rule}/edit', [App\Http\Controllers\Settings\BoostRuleSettingsController::class, 'edit'])->name('edit');
                Route::put('/{rule}', [App\Http\Controllers\Settings\BoostRuleSettingsController::class, 'update'])->name('update');
                Route::delete('/{rule}', [App\Http\Controllers\Settings\BoostRuleSettingsController::class, 'destroy'])->name('destroy');
                Route::post('/{rule}/toggle', [App\Http\Controllers\Settings\BoostRuleSettingsController::class, 'toggle'])->name('toggle');
            });

            // ==================== Ad Accounts ====================
            Route::prefix('ad-accounts')->name('ad-accounts.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'store'])->name('store');
                Route::get('/{account}', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'show'])->name('show');
                Route::get('/{account}/edit', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'edit'])->name('edit');
                Route::put('/{account}', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'update'])->name('update');
                Route::delete('/{account}', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'destroy'])->name('destroy');
                Route::post('/{account}/sync', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'sync'])->name('sync');
                Route::post('/{connection}/import', [App\Http\Controllers\Settings\AdAccountSettingsController::class, 'import'])->name('import');
            });
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
        Route::get('/', function () {
            // Redirect to user's active org settings
            $user = auth()->user();
            $orgId = $user->active_org_id ?? $user->current_org_id ?? $user->org_id;

            if ($orgId) {
                return redirect()->route('orgs.settings.user', ['org' => $orgId]);
            }

            // If no org, redirect to org selection
            return redirect()->route('orgs.index');
        })->name('index');
        Route::get('/profile', [App\Http\Controllers\Settings\SettingsController::class, 'profile'])->name('profile');
        Route::get('/notifications', [App\Http\Controllers\Settings\SettingsController::class, 'notifications'])->name('notifications');
        Route::get('/security', [App\Http\Controllers\Settings\SettingsController::class, 'security'])->name('security');
        // Note: Integrations moved to org-specific settings at /orgs/{org}/settings/integrations
    });

    // ==================== Subscription ====================
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', [App\Http\Controllers\SubscriptionController::class, 'plans'])->name('index');
        Route::get('/plans', [App\Http\Controllers\SubscriptionController::class, 'plans'])->name('plans');
        Route::get('/status', [App\Http\Controllers\SubscriptionController::class, 'status'])->name('status');
        Route::get('/manage', [App\Http\Controllers\SubscriptionController::class, 'status'])->name('manage');
        Route::get('/upgrade', [App\Http\Controllers\SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::post('/upgrade', [App\Http\Controllers\SubscriptionController::class, 'processUpgrade'])->name('upgrade.process');
        Route::get('/payment', [App\Http\Controllers\SubscriptionController::class, 'payment'])->name('payment');
        Route::post('/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
    });

    // HI-008: Subscriptions alias routes (plural form)
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', fn() => redirect()->route('subscription.plans'))->name('index');
        Route::get('/manage', fn() => redirect()->route('subscription.manage'))->name('manage');
        Route::get('/payment', fn() => redirect()->route('subscription.payment'))->name('payment');
    });

    // ==================== Profile ====================
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile');

    // HI-006: Profile edit route - redirects to org settings user page
    Route::get('/profile/edit', function () {
        $user = auth()->user();
        $orgId = $user->active_org_id ?? $user->current_org_id ?? $user->org_id;

        if ($orgId) {
            return redirect()->route('orgs.settings.user', ['org' => $orgId]);
        }

        // Fallback to profile page if no org
        return redirect()->route('profile');
    })->name('profile.edit');

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
