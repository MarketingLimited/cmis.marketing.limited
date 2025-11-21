<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Core\{OrgController, UserController, OrgMarketController};
use App\Http\Controllers\Creative\CreativeAssetController;
use App\Http\Controllers\Creative\ContentPlanController;
use App\Http\Controllers\Channels\ChannelController;
use App\Http\Controllers\Social\SocialSchedulerController;
use App\Http\Controllers\Integration\IntegrationController;
use App\Http\Controllers\AI\AIGenerationController;
use App\Http\Controllers\Analytics\KpiController;
use App\Http\Controllers\API\CMISEmbeddingController;
use App\Http\Controllers\API\SemanticSearchController;
use App\Http\Controllers\UnifiedInboxController;
use App\Http\Controllers\AdCampaignController;
use App\Http\Controllers\UnifiedCommentsController;
use App\Http\Controllers\API\PlatformIntegrationController;
use App\Http\Controllers\API\SyncController;
use App\Http\Controllers\API\ContentPublishingController;
use App\Http\Controllers\API\WebhookController;
use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\AdCampaignController as APIAdCampaignController;

/*
|--------------------------------------------------------------------------
| API Routes - CMIS Marketing System
|--------------------------------------------------------------------------
|
| هيكل الـ API الجديد يدعم:
| - Multi-tenancy عبر org_id
| - Database Context لكل طلب
| - RLS (Row Level Security)
| - Sanctum Authentication
|
*/

/*
|--------------------------------------------------------------------------
| Webhooks (Public - لاستقبال التحديثات من المنصات)
| مع التحقق من التوقيع (Signature Verification)
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->name('webhooks.')
    ->middleware('throttle:webhooks')
    ->group(function () {
        // Meta webhooks - GET for verification, POST for events
        Route::get('/meta', [WebhookController::class, 'handleMetaWebhook'])->name('meta.verify');
        Route::post('/meta', [WebhookController::class, 'handleMetaWebhook'])
            ->middleware('verify.webhook:meta')
            ->name('meta');

        Route::get('/whatsapp', [WebhookController::class, 'handleWhatsAppWebhook'])->name('whatsapp.verify');
        Route::post('/whatsapp', [WebhookController::class, 'handleWhatsAppWebhook'])
            ->middleware('verify.webhook:meta')
            ->name('whatsapp');

        // Other platform webhooks with signature verification
        Route::post('/google', [WebhookController::class, 'handleGoogleWebhook'])
            ->middleware('verify.webhook:google')
            ->name('google');

        Route::post('/tiktok', [WebhookController::class, 'handleTikTokWebhook'])
            ->middleware('verify.webhook:tiktok')
            ->name('tiktok');

        Route::post('/twitter', [WebhookController::class, 'handleTwitterWebhook'])
            ->middleware('verify.webhook:twitter')
            ->name('twitter');

        Route::post('/linkedin', [WebhookController::class, 'handleLinkedInWebhook'])
            ->middleware('verify.webhook:linkedin')
            ->name('linkedin');

        Route::post('/snapchat', [WebhookController::class, 'handleSnapchatWebhook'])
            ->middleware('verify.webhook:snapchat')
            ->name('snapchat');
    });

/*
|--------------------------------------------------------------------------
| مسارات المصادقة (Authentication) - بدون org_id
| مع Rate Limiting للحماية من Brute Force
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // التسجيل وتسجيل الدخول (عام - بدون مصادقة) - مع rate limiting
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('auth.login');

    // OAuth callbacks
    Route::get('/oauth/{provider}/callback', [AuthController::class, 'oauthCallback'])->name('auth.oauth.callback');

    // مسارات محمية (تحتاج مصادقة)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout.all');

        // Profile & Avatar
        Route::put('/profile/avatar', [App\Http\Controllers\ProfileController::class, 'avatar'])->name('auth.profile.avatar');
        Route::get('/activity', [AuthController::class, 'activity'])->name('auth.activity');

        // Settings
        Route::get('/settings', [App\Http\Controllers\Settings\SettingsController::class, 'index'])->name('auth.settings');
        Route::put('/settings', [App\Http\Controllers\Settings\SettingsController::class, 'updateProfile'])->name('auth.settings.update');
        Route::put('/password', [App\Http\Controllers\Settings\SettingsController::class, 'updatePassword'])->name('auth.password.update');

        // Notifications
        Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('auth.notifications');
        Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('auth.notifications.read');
        Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('auth.notifications.read-all');
        Route::delete('/notifications/{id}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('auth.notifications.delete');
    });
});

/*
|--------------------------------------------------------------------------
| مسارات المستخدم (User Level) - بدون org_id
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // قائمة الشركات للمستخدم
    Route::get('/user/orgs', [OrgController::class, 'listUserOrgs'])->name('user.orgs');

    // إنشاء شركة جديدة
    Route::post('/orgs', [OrgController::class, 'store'])->name('orgs.store');

    // Organization Switcher (NEW: P1 - Multi-Org UI)
    Route::get('/user/organizations', [\App\Http\Controllers\Core\OrgSwitcherController::class, 'getUserOrganizations'])
        ->name('user.organizations');
    Route::post('/user/switch-organization', [\App\Http\Controllers\Core\OrgSwitcherController::class, 'switchOrganization'])
        ->name('user.switch-organization');
    Route::get('/user/active-organization', [\App\Http\Controllers\Core\OrgSwitcherController::class, 'getActiveOrganization'])
        ->name('user.active-organization');
});

/*
|--------------------------------------------------------------------------
| مسارات الشركة (Organization Level) - كل شيء تحت org_id
|--------------------------------------------------------------------------
| جميع المسارات هنا تتطلب:
| 1. auth:sanctum - مصادقة المستخدم
| 2. validate.org.access - التحقق من صلاحية الوصول للشركة
| 3. set.db.context - ضبط سياق قاعدة البيانات
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'validate.org.access', 'set.db.context'])
    ->prefix('orgs/{org_id}')
    ->name('org.')
    ->group(function () {

    /*
    |----------------------------------------------------------------------
    | الشركة (Organization Management)
    |----------------------------------------------------------------------
    */
    Route::get('/', [OrgController::class, 'show'])->name('show');
    Route::put('/', [OrgController::class, 'update'])->name('update');
    Route::delete('/', [OrgController::class, 'destroy'])->name('destroy');
    Route::get('/statistics', [OrgController::class, 'statistics'])->name('statistics');

    /*
    |----------------------------------------------------------------------
    | إدارة المستخدمين (User Management)
    |----------------------------------------------------------------------
    */
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/invite', [UserController::class, 'inviteUser'])->name('invite');
        Route::get('/{user_id}', [UserController::class, 'show'])->name('show');
        Route::put('/{user_id}/role', [UserController::class, 'updateRole'])->name('updateRole');
        Route::post('/{user_id}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
        Route::delete('/{user_id}', [UserController::class, 'remove'])->name('remove');

        // User Activities & Permissions
        Route::get('/{user_id}/activities', [UserController::class, 'activities'])->name('activities');
        Route::get('/{user_id}/permissions', [UserController::class, 'permissions'])->name('permissions');
    });

    /*
    |----------------------------------------------------------------------
    | Markets (Organization Markets Management)
    |----------------------------------------------------------------------
    */
    Route::prefix('markets')->name('markets.')->group(function () {
        Route::get('/', [OrgMarketController::class, 'index'])->name('index');
        Route::post('/', [OrgMarketController::class, 'store'])->name('store');
        Route::get('/available', [OrgMarketController::class, 'available'])->name('available');
        Route::get('/stats', [OrgMarketController::class, 'stats'])->name('stats');
        Route::get('/{market_id}', [OrgMarketController::class, 'show'])->name('show');
        Route::put('/{market_id}', [OrgMarketController::class, 'update'])->name('update');
        Route::delete('/{market_id}', [OrgMarketController::class, 'destroy'])->name('destroy');
        Route::post('/{market_id}/roi', [OrgMarketController::class, 'calculateRoi'])->name('roi');
    });

    /*
    |----------------------------------------------------------------------
    | CMIS AI & Embeddings (الموجود حالياً)
    |----------------------------------------------------------------------
    */
    Route::prefix('cmis')->name('cmis.')->group(function () {
        Route::post('/search', [CMISEmbeddingController::class, 'search'])->name('search');
        Route::post('/knowledge/{id}/process', [CMISEmbeddingController::class, 'processKnowledge'])->name('knowledge.process');
        Route::get('/knowledge/{id}/similar', [CMISEmbeddingController::class, 'findSimilar'])->name('knowledge.similar');
        Route::get('/status', [CMISEmbeddingController::class, 'status'])->name('status');
    });

    // Semantic Search
    Route::post('/semantic-search', [SemanticSearchController::class, 'search'])->name('semantic.search');

    /*
    |----------------------------------------------------------------------
    | الحملات (Campaigns) - See full definition below in Ad Campaign Management section
    |----------------------------------------------------------------------
    */
    // Route moved to line 734 for better organization with ad campaigns

    /*
    |----------------------------------------------------------------------
    | المحتوى الإبداعي (Creative Assets)
    |----------------------------------------------------------------------
    */
    Route::prefix('creative')->name('creative.')->group(function () {
        Route::apiResource('assets', CreativeAssetController::class)->parameters([
            'assets' => 'asset_id'
        ]);

        // Content Plans
        Route::apiResource('content-plans', ContentPlanController::class)->parameters([
            'content-plans' => 'plan_id'
        ]);
        Route::post('content-plans/{plan_id}/generate', [ContentPlanController::class, 'generateContent'])->name('content-plans.generate');
        Route::post('content-plans/{plan_id}/approve', [ContentPlanController::class, 'approve'])->name('content-plans.approve');
        Route::post('content-plans/{plan_id}/reject', [ContentPlanController::class, 'reject'])->name('content-plans.reject');
        Route::post('content-plans/{plan_id}/publish', [ContentPlanController::class, 'publish'])->name('content-plans.publish');
        Route::get('content-plans-stats', [ContentPlanController::class, 'stats'])->name('content-plans.stats');
    });

    /*
    |----------------------------------------------------------------------
    | القنوات (Social Channels)
    |----------------------------------------------------------------------
    */
    Route::apiResource('channels', ChannelController::class)->parameters([
        'channels' => 'channel_id'
    ]);

    /*
    |----------------------------------------------------------------------
    | جدولة المنشورات الاجتماعية (Social Scheduler)
    |----------------------------------------------------------------------
    */
    Route::prefix('social')->name('social.')->group(function () {
        // Dashboard & Overview
        Route::get('/dashboard', [SocialSchedulerController::class, 'dashboard'])->name('dashboard');

        // Posts Management
        Route::prefix('posts')->name('posts.')->group(function () {
            // List posts by status
            Route::get('/scheduled', [SocialSchedulerController::class, 'scheduled'])->name('scheduled');
            Route::get('/published', [SocialSchedulerController::class, 'published'])->name('published');
            Route::get('/drafts', [SocialSchedulerController::class, 'drafts'])->name('drafts');

            // CRUD operations
            Route::post('/schedule', [SocialSchedulerController::class, 'schedule'])->name('schedule');
            Route::get('/{post_id}', [SocialSchedulerController::class, 'show'])->name('show');
            Route::put('/{post_id}', [SocialSchedulerController::class, 'update'])->name('update');
            Route::delete('/{post_id}', [SocialSchedulerController::class, 'destroy'])->name('destroy');

            // Actions
            Route::post('/{post_id}/publish-now', [SocialSchedulerController::class, 'publishNow'])->name('publish-now');
            Route::post('/{post_id}/reschedule', [SocialSchedulerController::class, 'reschedule'])->name('reschedule');
        });
    });

    /*
    |----------------------------------------------------------------------
    | قوائم النشر (Publishing Queues) - Sprint 2.1
    |----------------------------------------------------------------------
    */
    Route::prefix('queues')->name('queues.')->group(function () {
        // Queue Configuration
        Route::get('/{social_account_id}', [App\Http\Controllers\PublishingQueueController::class, 'show'])->name('show');
        Route::post('/', [App\Http\Controllers\PublishingQueueController::class, 'store'])->name('store');
        Route::put('/{social_account_id}', [App\Http\Controllers\PublishingQueueController::class, 'update'])->name('update');

        // Queue Information
        Route::get('/{social_account_id}/next-slot', [App\Http\Controllers\PublishingQueueController::class, 'nextSlot'])->name('next-slot');
        Route::get('/{social_account_id}/statistics', [App\Http\Controllers\PublishingQueueController::class, 'statistics'])->name('statistics');

        // Queued Posts Management
        Route::get('/{social_account_id}/posts', [App\Http\Controllers\PublishingQueueController::class, 'queuedPosts'])->name('posts');
        Route::post('/{social_account_id}/schedule', [App\Http\Controllers\PublishingQueueController::class, 'schedulePost'])->name('schedule');
        Route::delete('/posts/{post_id}', [App\Http\Controllers\PublishingQueueController::class, 'removePost'])->name('posts.remove');
    });

    /*
    |----------------------------------------------------------------------
    | إنشاء المنشورات الجماعي (Bulk Post Composer) - Sprint 2.2
    |----------------------------------------------------------------------
    */
    Route::prefix('bulk-posts')->name('bulk-posts.')->group(function () {
        // Create Bulk Posts
        Route::post('/create', [App\Http\Controllers\BulkPostController::class, 'createBulk'])->name('create');
        Route::post('/import-csv', [App\Http\Controllers\BulkPostController::class, 'importCSV'])->name('import-csv');

        // Bulk Operations
        Route::put('/update', [App\Http\Controllers\BulkPostController::class, 'bulkUpdate'])->name('update');
        Route::delete('/delete', [App\Http\Controllers\BulkPostController::class, 'bulkDelete'])->name('delete');

        // Template Suggestions
        Route::get('/suggestions', [App\Http\Controllers\BulkPostController::class, 'getTemplateSuggestions'])->name('suggestions');
    });

    /*
    |----------------------------------------------------------------------
    | تحليل أفضل أوقات النشر (Best Time Analyzer) - Sprint 2.3
    |----------------------------------------------------------------------
    */
    Route::prefix('best-times')->name('best-times.')->group(function () {
        Route::get('/{social_account_id}', [App\Http\Controllers\BestTimeController::class, 'analyze'])->name('analyze');
        Route::get('/{social_account_id}/recommendations', [App\Http\Controllers\BestTimeController::class, 'recommendations'])->name('recommendations');
        Route::get('/{social_account_id}/compare', [App\Http\Controllers\BestTimeController::class, 'compare'])->name('compare');
        Route::get('/{social_account_id}/audience-activity', [App\Http\Controllers\BestTimeController::class, 'audienceActivity'])->name('audience-activity');
    });

    /*
    |----------------------------------------------------------------------
    | سير عمل الموافقات (Approval Workflow) - Sprint 2.4
    |----------------------------------------------------------------------
    */
    Route::prefix('approvals')->name('approvals.')->group(function () {
        // Request Approval
        Route::post('/request', [App\Http\Controllers\ApprovalController::class, 'requestApproval'])->name('request');

        // Approve/Reject
        Route::post('/{approval_id}/approve', [App\Http\Controllers\ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{approval_id}/reject', [App\Http\Controllers\ApprovalController::class, 'reject'])->name('reject');
        Route::post('/{approval_id}/reassign', [App\Http\Controllers\ApprovalController::class, 'reassign'])->name('reassign');

        // Get Approvals
        Route::get('/pending', [App\Http\Controllers\ApprovalController::class, 'pending'])->name('pending');
        Route::get('/post/{post_id}/history', [App\Http\Controllers\ApprovalController::class, 'history'])->name('history');
        Route::get('/statistics', [App\Http\Controllers\ApprovalController::class, 'statistics'])->name('statistics');
    });

    /*
    |----------------------------------------------------------------------
    | لوحة التحليلات (Analytics Dashboard) - Sprint 3.1
    |----------------------------------------------------------------------
    */
    Route::prefix('analytics/dashboard')->name('analytics.dashboard.')->group(function () {
        // Organization Overview
        Route::get('/overview', [App\Http\Controllers\AnalyticsDashboardController::class, 'orgOverview'])->name('overview');
        Route::get('/snapshot', [App\Http\Controllers\AnalyticsDashboardController::class, 'snapshot'])->name('snapshot');

        // Account Analytics
        Route::get('/account/{social_account_id}', [App\Http\Controllers\AnalyticsDashboardController::class, 'accountDashboard'])->name('account');
        Route::get('/account/{social_account_id}/content', [App\Http\Controllers\AnalyticsDashboardController::class, 'contentPerformance'])->name('account.content');
        Route::get('/account/{social_account_id}/trends', [App\Http\Controllers\AnalyticsDashboardController::class, 'trends'])->name('account.trends');

        // Platform Comparison
        Route::get('/platforms', [App\Http\Controllers\AnalyticsDashboardController::class, 'platformComparison'])->name('platforms');
    });

    /*
    |----------------------------------------------------------------------
    | تحليل أداء المحتوى (Content Performance Analysis) - Sprint 3.2
    |----------------------------------------------------------------------
    */
    Route::prefix('content/analytics')->name('content.analytics.')->group(function () {
        // Post-Level Analytics
        Route::get('/post/{post_id}', [App\Http\Controllers\ContentAnalyticsController::class, 'postAnalytics'])->name('post');

        // Hashtag Analysis
        Route::get('/hashtags/{social_account_id}', [App\Http\Controllers\ContentAnalyticsController::class, 'hashtagAnalytics'])->name('hashtags');

        // Audience Demographics
        Route::get('/demographics/{social_account_id}', [App\Http\Controllers\ContentAnalyticsController::class, 'audienceDemographics'])->name('demographics');

        // Engagement Patterns
        Route::get('/patterns/{social_account_id}', [App\Http\Controllers\ContentAnalyticsController::class, 'engagementPatterns'])->name('patterns');

        // Content Type Performance
        Route::get('/content-types/{social_account_id}', [App\Http\Controllers\ContentAnalyticsController::class, 'contentTypePerformance'])->name('content-types');

        // Top Performing Posts
        Route::get('/top-posts/{social_account_id}', [App\Http\Controllers\ContentAnalyticsController::class, 'topPosts'])->name('top-posts');

        // Comprehensive Analysis
        Route::get('/comprehensive/{social_account_id}', [App\Http\Controllers\ContentAnalyticsController::class, 'comprehensiveAnalysis'])->name('comprehensive');
    });

    /*
    |----------------------------------------------------------------------
    | رؤى الذكاء الاصطناعي (AI Insights) - Sprint 3.3
    |----------------------------------------------------------------------
    */
    Route::prefix('ai/insights')->name('ai.insights.')->group(function () {
        // Comprehensive Insights
        Route::get('/{social_account_id}', [App\Http\Controllers\AIInsightsController::class, 'accountInsights'])->name('account');
        Route::get('/{social_account_id}/summary', [App\Http\Controllers\AIInsightsController::class, 'insightsSummary'])->name('summary');

        // Content Recommendations
        Route::get('/{social_account_id}/recommendations', [App\Http\Controllers\AIInsightsController::class, 'contentRecommendations'])->name('recommendations');

        // Anomaly Detection
        Route::get('/{social_account_id}/anomalies', [App\Http\Controllers\AIInsightsController::class, 'anomalyDetection'])->name('anomalies');

        // Predictions
        Route::get('/{social_account_id}/predictions', [App\Http\Controllers\AIInsightsController::class, 'predictions'])->name('predictions');

        // Observations
        Route::get('/{social_account_id}/observations', [App\Http\Controllers\AIInsightsController::class, 'observations'])->name('observations');

        // Optimization Opportunities
        Route::get('/{social_account_id}/opportunities', [App\Http\Controllers\AIInsightsController::class, 'optimizationOpportunities'])->name('opportunities');

        // Competitive Intelligence
        Route::get('/{social_account_id}/competitive', [App\Http\Controllers\AIInsightsController::class, 'competitiveInsights'])->name('competitive');
    });

    /*
    |----------------------------------------------------------------------
    | تقارير PDF (PDF Reports) - Sprint 3.4
    |----------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->group(function () {
        // Report Types
        Route::get('/types', [App\Http\Controllers\ReportsController::class, 'getReportTypes'])->name('types');

        // Generate Reports
        Route::post('/performance', [App\Http\Controllers\ReportsController::class, 'generatePerformanceReport'])->name('performance');
        Route::post('/ai-insights', [App\Http\Controllers\ReportsController::class, 'generateAIInsightsReport'])->name('ai-insights');
        Route::post('/organization', [App\Http\Controllers\ReportsController::class, 'generateOrgReport'])->name('organization');
        Route::post('/content-analysis', [App\Http\Controllers\ReportsController::class, 'generateContentAnalysisReport'])->name('content-analysis');

        // Scheduled Reports
        Route::post('/schedule', [App\Http\Controllers\ReportsController::class, 'scheduleReport'])->name('schedule');
        Route::get('/schedules', [App\Http\Controllers\ReportsController::class, 'getScheduledReports'])->name('schedules');
        Route::delete('/schedule/{schedule_id}', [App\Http\Controllers\ReportsController::class, 'cancelScheduledReport'])->name('schedule.cancel');
    });

    /*
    |----------------------------------------------------------------------
    | إدارة الحملات الإعلانية (Ad Campaign Management) - Sprint 4.1
    |----------------------------------------------------------------------
    */
    Route::prefix('ad-campaigns')->name('ad-campaigns.')->group(function () {
        // Campaign CRUD
        Route::get('/', [App\Http\Controllers\AdCampaignController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\AdCampaignController::class, 'create'])->name('create');
        Route::get('/{campaign_id}', [App\Http\Controllers\AdCampaignController::class, 'show'])->name('show');
        Route::put('/{campaign_id}', [App\Http\Controllers\AdCampaignController::class, 'update'])->name('update');
        Route::delete('/{campaign_id}', [App\Http\Controllers\AdCampaignController::class, 'destroy'])->name('destroy');

        // Campaign Actions
        Route::patch('/{campaign_id}/status', [App\Http\Controllers\AdCampaignController::class, 'updateStatus'])->name('status');
        Route::post('/{campaign_id}/duplicate', [App\Http\Controllers\AdCampaignController::class, 'duplicate'])->name('duplicate');

        // Bulk Operations
        Route::patch('/bulk/status', [App\Http\Controllers\AdCampaignController::class, 'bulkUpdateStatus'])->name('bulk.status');

        // Statistics
        Route::get('/statistics/summary', [App\Http\Controllers\AdCampaignController::class, 'statistics'])->name('statistics');
    });

    /*
    |----------------------------------------------------------------------
    | منشئ الإعلانات الإبداعية (Ad Creative Builder) - Sprint 4.2
    |----------------------------------------------------------------------
    */
    Route::prefix('ad-creatives')->name('ad-creatives.')->group(function () {
        Route::get('/', [App\Http\Controllers\AdCreativeController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\AdCreativeController::class, 'create'])->name('create');
        Route::get('/templates', [App\Http\Controllers\AdCreativeController::class, 'templates'])->name('templates');
        Route::post('/ai-generate', [App\Http\Controllers\AdCreativeController::class, 'generateAI'])->name('ai-generate');
        Route::get('/{creative_id}', [App\Http\Controllers\AdCreativeController::class, 'show'])->name('show');
        Route::put('/{creative_id}', [App\Http\Controllers\AdCreativeController::class, 'update'])->name('update');
        Route::delete('/{creative_id}', [App\Http\Controllers\AdCreativeController::class, 'destroy'])->name('destroy');
        Route::post('/{creative_id}/variations', [App\Http\Controllers\AdCreativeController::class, 'createVariations'])->name('variations');
    });

    /*
    |----------------------------------------------------------------------
    | الاستهداف والجماهير (Targeting & Audiences) - Sprint 4.3
    |----------------------------------------------------------------------
    */
    Route::prefix('audiences')->name('audiences.')->group(function () {
        // Audience CRUD
        Route::get('/', [App\Http\Controllers\AudienceController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\AudienceController::class, 'create'])->name('create');
        Route::get('/{audience_id}', [App\Http\Controllers\AudienceController::class, 'show'])->name('show');
        Route::put('/{audience_id}', [App\Http\Controllers\AudienceController::class, 'update'])->name('update');
        Route::delete('/{audience_id}', [App\Http\Controllers\AudienceController::class, 'destroy'])->name('destroy');

        // Lookalike Audiences
        Route::post('/{source_audience_id}/lookalike', [App\Http\Controllers\AudienceController::class, 'createLookalike'])->name('lookalike');

        // Targeting Tools
        Route::post('/estimate-size', [App\Http\Controllers\AudienceController::class, 'estimateSize'])->name('estimate-size');
        Route::get('/targeting-suggestions', [App\Http\Controllers\AudienceController::class, 'targetingSuggestions'])->name('targeting-suggestions');
    });

    /*
    |----------------------------------------------------------------------
    | إدارة الميزانية والمزايدة (Budget & Bidding) - Sprint 4.4
    |----------------------------------------------------------------------
    */
    Route::prefix('budget')->name('budget.')->group(function () {
        // Campaign Budget Management
        Route::put('/campaign/{campaign_id}', [App\Http\Controllers\BudgetController::class, 'updateCampaignBudget'])->name('campaign.update');
        Route::put('/campaign/{campaign_id}/bid-strategy', [App\Http\Controllers\BudgetController::class, 'updateBidStrategy'])->name('bid-strategy');

        // Tracking & ROI
        Route::get('/campaign/{campaign_id}/tracking', [App\Http\Controllers\BudgetController::class, 'getSpendTracking'])->name('tracking');
        Route::get('/campaign/{campaign_id}/roi', [App\Http\Controllers\BudgetController::class, 'calculateROI'])->name('roi');
        Route::get('/campaign/{campaign_id}/recommendations', [App\Http\Controllers\BudgetController::class, 'getBudgetRecommendations'])->name('recommendations');

        // Optimization
        Route::post('/optimize', [App\Http\Controllers\BudgetController::class, 'optimizeBudgetAllocation'])->name('optimize');
    });

    /*
    |----------------------------------------------------------------------
    | تحليلات الحملات الإعلانية (Campaign Analytics) - Sprint 4.5
    |----------------------------------------------------------------------
    */
    Route::prefix('campaign-analytics')->name('campaign-analytics.')->group(function () {
        // Campaign Analytics
        Route::get('/{campaign_id}', [App\Http\Controllers\CampaignAnalyticsController::class, 'getCampaignAnalytics'])->name('show');
        Route::post('/compare', [App\Http\Controllers\CampaignAnalyticsController::class, 'compareCampaigns'])->name('compare');

        // Funnel & Attribution
        Route::get('/{campaign_id}/funnel', [App\Http\Controllers\CampaignAnalyticsController::class, 'getFunnelAnalytics'])->name('funnel');
        Route::get('/{campaign_id}/attribution', [App\Http\Controllers\CampaignAnalyticsController::class, 'getAttributionAnalysis'])->name('attribution');

        // Breakdowns
        Route::get('/{campaign_id}/ad-sets', [App\Http\Controllers\CampaignAnalyticsController::class, 'getAdSetBreakdown'])->name('ad-sets');
        Route::get('/{campaign_id}/creatives', [App\Http\Controllers\CampaignAnalyticsController::class, 'getCreativeBreakdown'])->name('creatives');
    });

    /*
    |----------------------------------------------------------------------
    | اختبار A/B للحملات (A/B Testing) - Sprint 4.6
    |----------------------------------------------------------------------
    */
    Route::prefix('ab-tests')->name('ab-tests.')->group(function () {
        // Test Management
        Route::get('/', [App\Http\Controllers\ABTestingController::class, 'listTests'])->name('index');
        Route::post('/', [App\Http\Controllers\ABTestingController::class, 'createTest'])->name('create');
        Route::delete('/{test_id}', [App\Http\Controllers\ABTestingController::class, 'deleteTest'])->name('delete');

        // Variations
        Route::post('/{test_id}/variations', [App\Http\Controllers\ABTestingController::class, 'addVariation'])->name('add-variation');

        // Test Control
        Route::post('/{test_id}/start', [App\Http\Controllers\ABTestingController::class, 'startTest'])->name('start');
        Route::post('/{test_id}/stop', [App\Http\Controllers\ABTestingController::class, 'stopTest'])->name('stop');
        Route::post('/{test_id}/extend', [App\Http\Controllers\ABTestingController::class, 'extendTest'])->name('extend');

        // Results & Winner Selection
        Route::get('/{test_id}/results', [App\Http\Controllers\ABTestingController::class, 'getResults'])->name('results');
        Route::post('/{test_id}/select-winner', [App\Http\Controllers\ABTestingController::class, 'selectWinner'])->name('select-winner');
    });

    /*
    |----------------------------------------------------------------------
    | إدارة الفريق (Team Management) - Sprint 5.1
    |----------------------------------------------------------------------
    */
    Route::prefix('team')->name('team.')->group(function () {
        // Team Members
        Route::get('/members', [App\Http\Controllers\TeamController::class, 'listMembers'])->name('members.index');
        Route::delete('/members/{user_id}', [App\Http\Controllers\TeamController::class, 'removeMember'])->name('members.remove');
        Route::put('/members/{user_id}/role', [App\Http\Controllers\TeamController::class, 'updateRole'])->name('members.update-role');
        Route::put('/members/{user_id}/accounts', [App\Http\Controllers\TeamController::class, 'assignToAccounts'])->name('members.assign-accounts');

        // Invitations
        Route::post('/invite', [App\Http\Controllers\TeamController::class, 'invite'])->name('invite');
        Route::get('/invitations', [App\Http\Controllers\TeamController::class, 'listInvitations'])->name('invitations.index');
        Route::delete('/invitations/{invitation_id}', [App\Http\Controllers\TeamController::class, 'cancelInvitation'])->name('invitations.cancel');
        Route::post('/invitations/{token}/accept', [App\Http\Controllers\TeamController::class, 'acceptInvitation'])->name('invitations.accept');

        // Roles & Permissions
        Route::get('/roles', [App\Http\Controllers\TeamController::class, 'getAllRoles'])->name('roles.index');
        Route::get('/roles/{role}/permissions', [App\Http\Controllers\TeamController::class, 'getRolePermissions'])->name('roles.permissions');
    });

    /*
    |----------------------------------------------------------------------
    | التعليقات والتعاون (Comments & Collaboration) - Sprint 5.3
    |----------------------------------------------------------------------
    */
    Route::prefix('comments')->name('comments.')->group(function () {
        // Comments
        Route::get('/', [App\Http\Controllers\CommentController::class, 'list'])->name('index');
        Route::post('/', [App\Http\Controllers\CommentController::class, 'create'])->name('create');
        Route::put('/{comment_id}', [App\Http\Controllers\CommentController::class, 'update'])->name('update');
        Route::delete('/{comment_id}', [App\Http\Controllers\CommentController::class, 'delete'])->name('delete');

        // Replies
        Route::post('/{comment_id}/reply', [App\Http\Controllers\CommentController::class, 'reply'])->name('reply');

        // Reactions
        Route::post('/{comment_id}/reactions', [App\Http\Controllers\CommentController::class, 'addReaction'])->name('reactions.add');
        Route::delete('/{comment_id}/reactions', [App\Http\Controllers\CommentController::class, 'removeReaction'])->name('reactions.remove');
    });

    // Activity Feed (organization-wide)
    Route::get('/activity', [App\Http\Controllers\CommentController::class, 'getActivityFeed'])->name('activity.feed');

    /*
    |----------------------------------------------------------------------
    | مكتبة المحتوى المشترك (Shared Content Library) - Sprint 5.4
    |----------------------------------------------------------------------
    */
    Route::prefix('content-library')->name('content-library.')->group(function () {
        // Assets
        Route::get('/', [App\Http\Controllers\ContentLibraryController::class, 'list'])->name('index');
        Route::post('/upload', [App\Http\Controllers\ContentLibraryController::class, 'upload'])->name('upload');
        Route::get('/search', [App\Http\Controllers\ContentLibraryController::class, 'search'])->name('search');
        Route::get('/{asset_id}', [App\Http\Controllers\ContentLibraryController::class, 'show'])->name('show');
        Route::put('/{asset_id}', [App\Http\Controllers\ContentLibraryController::class, 'update'])->name('update');
        Route::delete('/{asset_id}', [App\Http\Controllers\ContentLibraryController::class, 'delete'])->name('delete');
        Route::post('/{asset_id}/track-usage', [App\Http\Controllers\ContentLibraryController::class, 'trackUsage'])->name('track-usage');

        // Folders
        Route::get('/folders/list', [App\Http\Controllers\ContentLibraryController::class, 'listFolders'])->name('folders.list');
        Route::post('/folders', [App\Http\Controllers\ContentLibraryController::class, 'createFolder'])->name('folders.create');
    });

    /*
    |----------------------------------------------------------------------
    | تحسين الأداء (Performance Optimization) - Sprint 6.1
    |----------------------------------------------------------------------
    */
    Route::prefix('performance')->name('performance.')->group(function () {
        // Metrics
        Route::get('/metrics', [App\Http\Controllers\PerformanceController::class, 'getMetrics'])->name('metrics');
        Route::get('/slow-queries', [App\Http\Controllers\PerformanceController::class, 'getSlowQueries'])->name('slow-queries');

        // Cache Management
        Route::post('/cache/clear', [App\Http\Controllers\PerformanceController::class, 'clearCache'])->name('cache.clear');
        Route::post('/cache/warmup', [App\Http\Controllers\PerformanceController::class, 'warmupCache'])->name('cache.warmup');

        // Database Optimization
        Route::post('/optimize-database', [App\Http\Controllers\PerformanceController::class, 'optimizeDatabase'])->name('optimize-database');
    });

    /*
    |----------------------------------------------------------------------
    | الأتمتة الذكية (AI-Powered Automation) - Sprint 6.2
    | Rate Limited: 10 requests per minute per user
    |----------------------------------------------------------------------
    */
    Route::prefix('ai')->name('ai.')->middleware('throttle.ai')->group(function () {
        // Optimal Posting Times
        Route::get('/optimal-times/{account_id}', [App\Http\Controllers\AIAutomationController::class, 'getOptimalPostingTimes'])->name('optimal-times');
        Route::post('/auto-schedule/{account_id}', [App\Http\Controllers\AIAutomationController::class, 'autoSchedulePost'])->name('auto-schedule');

        // Content Generation
        Route::post('/hashtags', [App\Http\Controllers\AIAutomationController::class, 'generateHashtags'])->name('hashtags');
        Route::post('/captions', [App\Http\Controllers\AIAutomationController::class, 'generateCaptions'])->name('captions');

        // Budget Optimization
        Route::post('/optimize-budget/{ad_account_id}', [App\Http\Controllers\AIAutomationController::class, 'optimizeBudget'])->name('optimize-budget');

        // Automation Rules
        Route::get('/automation-rules', [App\Http\Controllers\AIAutomationController::class, 'getAutomationRules'])->name('automation-rules.index');
        Route::post('/automation-rules', [App\Http\Controllers\AIAutomationController::class, 'createAutomationRule'])->name('automation-rules.create');
    });

    /*
    |----------------------------------------------------------------------
    | الجدولة المتقدمة (Advanced Scheduling) - Sprint 6.3
    |----------------------------------------------------------------------
    */
    Route::prefix('scheduling')->name('scheduling.')->group(function () {
        // Recurring Templates
        Route::post('/recurring-templates', [App\Http\Controllers\AdvancedSchedulingController::class, 'createRecurringTemplate'])->name('recurring-templates.create');
        Route::post('/recurring-templates/{template_id}/generate', [App\Http\Controllers\AdvancedSchedulingController::class, 'generateRecurringPosts'])->name('recurring-templates.generate');

        // Queue Management
        Route::get('/queue/{account_id}', [App\Http\Controllers\AdvancedSchedulingController::class, 'getSchedulingQueue'])->name('queue');

        // Post Recycling
        Route::post('/recycle/{post_id}', [App\Http\Controllers\AdvancedSchedulingController::class, 'recyclePost'])->name('recycle');

        // Conflict Resolution
        Route::post('/resolve-conflicts/{account_id}', [App\Http\Controllers\AdvancedSchedulingController::class, 'resolveConflicts'])->name('resolve-conflicts');

        // Bulk Operations
        Route::post('/bulk-reschedule', [App\Http\Controllers\AdvancedSchedulingController::class, 'bulkReschedule'])->name('bulk-reschedule');
    });

    /*
    |----------------------------------------------------------------------
    | مركز التكاملات (Integration Hub) - Sprint 6.4 - FINAL SPRINT!
    |----------------------------------------------------------------------
    */
    Route::prefix('integrations')->name('integrations.')->group(function () {
        // Available Integrations
        Route::get('/available', [App\Http\Controllers\IntegrationHubController::class, 'getAvailableIntegrations'])->name('available');

        // Integration Management
        Route::post('/', [App\Http\Controllers\IntegrationHubController::class, 'createIntegration'])->name('create');
        Route::post('/{integration_id}/test', [App\Http\Controllers\IntegrationHubController::class, 'testIntegration'])->name('test');

        // Logs
        Route::get('/logs', [App\Http\Controllers\IntegrationHubController::class, 'getIntegrationLogs'])->name('logs');
    });

    // Webhooks
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::post('/', [App\Http\Controllers\IntegrationHubController::class, 'createWebhook'])->name('create');
    });

    // API Keys
    Route::prefix('api-keys')->name('api-keys.')->group(function () {
        Route::get('/', [App\Http\Controllers\IntegrationHubController::class, 'listAPIKeys'])->name('index');
        Route::post('/', [App\Http\Controllers\IntegrationHubController::class, 'generateAPIKey'])->name('generate');
        Route::delete('/{api_key_id}', [App\Http\Controllers\IntegrationHubController::class, 'revokeAPIKey'])->name('revoke');
    });

    /*
    |----------------------------------------------------------------------
    | صندوق الوارد الموحد (Unified Inbox)
    |----------------------------------------------------------------------
    */
    Route::prefix('inbox')->name('inbox.')->group(function () {
        // Messages List
        Route::get('/', [UnifiedInboxController::class, 'index'])->name('index');

        // Conversation Thread
        Route::get('/conversation/{conversation_id}', [UnifiedInboxController::class, 'conversation'])->name('conversation');

        // Reply to Message
        Route::post('/{message_id}/reply', [UnifiedInboxController::class, 'reply'])->name('reply');

        // Message Actions
        Route::post('/mark-as-read', [UnifiedInboxController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/{message_id}/assign', [UnifiedInboxController::class, 'assign'])->name('assign');
        Route::post('/{message_id}/note', [UnifiedInboxController::class, 'addNote'])->name('add-note');

        // Saved Replies
        Route::get('/saved-replies', [UnifiedInboxController::class, 'savedReplies'])->name('saved-replies');
        Route::post('/saved-replies', [UnifiedInboxController::class, 'createSavedReply'])->name('saved-replies.create');

        // Statistics
        Route::get('/statistics', [UnifiedInboxController::class, 'statistics'])->name('statistics');
    });

    /*
    |----------------------------------------------------------------------
    | إدارة التعليقات الموحدة (Unified Comments)
    |----------------------------------------------------------------------
    */
    Route::prefix('comments')->name('comments.')->group(function () {
        // Comments List
        Route::get('/', [UnifiedCommentsController::class, 'index'])->name('index');

        // Comment Actions
        Route::post('/{comment_id}/reply', [UnifiedCommentsController::class, 'reply'])->name('reply');
        Route::post('/{comment_id}/hide', [UnifiedCommentsController::class, 'hide'])->name('hide');
        Route::delete('/{comment_id}', [UnifiedCommentsController::class, 'delete'])->name('delete');
        Route::post('/{comment_id}/like', [UnifiedCommentsController::class, 'like'])->name('like');

        // Bulk Actions
        Route::post('/bulk-action', [UnifiedCommentsController::class, 'bulkAction'])->name('bulk-action');

        // Statistics
        Route::get('/statistics', [UnifiedCommentsController::class, 'statistics'])->name('statistics');
    });

    /*
    |----------------------------------------------------------------------
    | نشر المحتوى (Content Publishing & Scheduling)
    |----------------------------------------------------------------------
    */
    Route::prefix('publishing')->name('publishing.')->group(function () {
        // Publish Now
        Route::post('/publish-now', [ContentPublishingController::class, 'publishNow'])->name('now');

        // Schedule Posts
        Route::post('/schedule', [ContentPublishingController::class, 'schedulePost'])->name('schedule');
        Route::get('/scheduled', [ContentPublishingController::class, 'getScheduledPosts'])->name('scheduled');
        Route::put('/scheduled/{schedule_id}', [ContentPublishingController::class, 'updateScheduledPost'])->name('scheduled.update');
        Route::delete('/scheduled/{schedule_id}', [ContentPublishingController::class, 'cancelScheduledPost'])->name('scheduled.cancel');

        // Publishing History
        Route::get('/history', [ContentPublishingController::class, 'getPublishingHistory'])->name('history');
    });

    /*
    |----------------------------------------------------------------------
    | إدارة الحملات الإعلانية (Ad Campaign Management) - COMPLETE
    |----------------------------------------------------------------------
    */
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        // Campaign CRUD
        Route::get('/', [APIAdCampaignController::class, 'getCampaigns'])->name('index');
        Route::post('/', [APIAdCampaignController::class, 'createCampaign'])->name('create');
        Route::get('/{campaign_id}', [APIAdCampaignController::class, 'getCampaign'])->name('show');
        Route::put('/{campaign_id}', [APIAdCampaignController::class, 'updateCampaign'])->name('update');
        Route::delete('/{campaign_id}', [APIAdCampaignController::class, 'deleteCampaign'])->name('delete');

        // Campaign Status Management
        Route::post('/{campaign_id}/pause', [APIAdCampaignController::class, 'pauseCampaign'])->name('pause');
        Route::post('/{campaign_id}/activate', [APIAdCampaignController::class, 'activateCampaign'])->name('activate');

        // Campaign Metrics & Performance
        Route::get('/{campaign_id}/metrics', [APIAdCampaignController::class, 'getCampaignMetrics'])->name('metrics');

        // Platform Objectives
        Route::get('/objectives/{platform}', [APIAdCampaignController::class, 'getCampaignObjectives'])->name('objectives');
    });

    /*
    |----------------------------------------------------------------------
    | التكاملات (Platform Integrations) - COMPLETE
    |----------------------------------------------------------------------
    */
    Route::prefix('integrations')->name('integrations.')->group(function () {
        // Platform Management
        Route::get('/platforms', [PlatformIntegrationController::class, 'getAvailablePlatforms'])->name('platforms');
        Route::get('/', [PlatformIntegrationController::class, 'getConnectedPlatforms'])->name('index');
        Route::get('/{integration_id}', [PlatformIntegrationController::class, 'getIntegration'])->name('show');

        // OAuth Connection Flow (Meta, Google, TikTok, etc.)
        Route::get('/{platform}/auth-url', [PlatformIntegrationController::class, 'getAuthUrl'])->name('auth-url');
        Route::post('/{platform}/callback', [PlatformIntegrationController::class, 'handleCallback'])->name('callback');

        // Direct Connect (WooCommerce, WordPress, WhatsApp - no OAuth)
        Route::post('/{platform}/connect', [PlatformIntegrationController::class, 'connect'])->name('connect');

        // Disconnect & Token Management
        Route::delete('/{integration_id}', [PlatformIntegrationController::class, 'disconnect'])->name('disconnect');
        Route::post('/{integration_id}/refresh-token', [PlatformIntegrationController::class, 'refreshToken'])->name('refresh-token');
        Route::post('/{integration_id}/test', [PlatformIntegrationController::class, 'testConnection'])->name('test');
    });

    /*
    |----------------------------------------------------------------------
    | Meta Posts (Facebook & Instagram Organic Posts) - Phase 4
    |----------------------------------------------------------------------
    | Fetch existing organic posts and create ad campaigns from them
    */
    Route::prefix('meta-posts')->name('meta-posts.')->group(function () {
        // Fetch all organic posts from connected accounts
        Route::get('/', [App\Http\Controllers\Platform\MetaPostsController::class, 'index'])->name('index');

        // Get specific post details
        Route::get('/{post_id}', [App\Http\Controllers\Platform\MetaPostsController::class, 'show'])->name('show');

        // Refresh posts cache
        Route::post('/refresh', [App\Http\Controllers\Platform\MetaPostsController::class, 'refresh'])->name('refresh');

        // Boost post (create ad campaign from existing post)
        Route::post('/boost', [App\Http\Controllers\Platform\MetaPostsController::class, 'boostPost'])->name('boost');

        // Get top performing posts for boost suggestions
        Route::get('/top-performing', [App\Http\Controllers\Platform\MetaPostsController::class, 'topPerforming'])->name('top-performing');
    });

    /*
    |----------------------------------------------------------------------
    | المزامنة (Data Sync)
    |----------------------------------------------------------------------
    */
    Route::prefix('sync')->name('sync.')->group(function () {
        // Trigger Manual Sync
        Route::post('/all', [SyncController::class, 'syncAll'])->name('all');
        Route::post('/{integration_id}', [SyncController::class, 'syncIntegration'])->name('integration');
        Route::post('/{integration_id}/posts', [SyncController::class, 'syncPosts'])->name('posts');
        Route::post('/{integration_id}/comments', [SyncController::class, 'syncComments'])->name('comments');
        Route::post('/{integration_id}/messages', [SyncController::class, 'syncMessages'])->name('messages');
        Route::post('/{integration_id}/campaigns', [SyncController::class, 'syncCampaigns'])->name('campaigns');

        // Sync Status & History
        Route::get('/{integration_id}/status', [SyncController::class, 'getSyncStatus'])->name('status');
        Route::get('/history', [SyncController::class, 'getSyncHistory'])->name('history');
    });

    /*
    |----------------------------------------------------------------------
    | الذكاء الاصطناعي (AI & Content Generation)
    | Rate Limited: 10 requests per minute per user
    |----------------------------------------------------------------------
    */
    Route::prefix('ai')->name('ai.')->middleware('throttle.ai')->group(function () {
        // Dashboard & Stats
        Route::get('/dashboard', [AIGenerationController::class, 'dashboard'])->name('dashboard');

        // Content Generation
        Route::post('/generate', [AIGenerationController::class, 'generate'])->name('generate');
        Route::get('/history', [AIGenerationController::class, 'history'])->name('history');

        // Semantic Search (pgvector)
        Route::post('/semantic-search', [AIGenerationController::class, 'semanticSearch'])->name('semantic-search');

        // Recommendations
        Route::get('/recommendations', [AIGenerationController::class, 'recommendations'])->name('recommendations');

        // Knowledge Base
        Route::get('/knowledge', [AIGenerationController::class, 'knowledge'])->name('knowledge');
        Route::post('/knowledge/process', [AIGenerationController::class, 'processKnowledge'])->name('knowledge.process');
    });

    /*
    |----------------------------------------------------------------------
    | التحليلات (Analytics & Reporting) - COMPLETE
    |----------------------------------------------------------------------
    */
    Route::prefix('analytics')->name('analytics.')->group(function () {
        // Overview & Summary
        Route::get('/overview', [AnalyticsController::class, 'getOverview'])->name('overview');

        // Platform Analytics
        Route::get('/platform/{integration_id}', [AnalyticsController::class, 'getPlatformAnalytics'])->name('platform');

        // Post & Content Performance
        Route::get('/posts', [AnalyticsController::class, 'getPostPerformance'])->name('posts');

        // Campaign Performance
        Route::get('/campaigns', [AnalyticsController::class, 'getCampaignPerformance'])->name('campaigns');
        Route::get('/campaigns/{campaign_id}', [AnalyticsController::class, 'getCampaignAnalytics'])->name('campaigns.show');

        // Engagement Analytics
        Route::get('/engagement', [AnalyticsController::class, 'getEngagementAnalytics'])->name('engagement');

        // Export Reports
        Route::post('/export', [AnalyticsController::class, 'exportReport'])->name('export');

        // Legacy KPI Routes (backward compatible)
        Route::get('/kpis', [KpiController::class, 'index'])->name('kpis');
        Route::get('/summary', [KpiController::class, 'summary'])->name('summary');
        Route::get('/trends', [KpiController::class, 'trends'])->name('trends');
    });

    /*
    |----------------------------------------------------------------------
    | قاعدة المعرفة (Knowledge Base)
    |----------------------------------------------------------------------
    */
    Route::prefix('knowledge')->name('knowledge.')->group(function () {
        // List & Search
        Route::get('/', [App\Http\Controllers\KnowledgeController::class, 'index'])->name('index');
        Route::post('/search', [App\Http\Controllers\KnowledgeController::class, 'search'])->name('search');

        // CRUD Operations
        Route::post('/', [App\Http\Controllers\KnowledgeController::class, 'store'])->name('store');
        Route::get('/{knowledge_id}', [App\Http\Controllers\KnowledgeController::class, 'show'])->name('show');
        Route::put('/{knowledge_id}', [App\Http\Controllers\KnowledgeController::class, 'update'])->name('update');
        Route::delete('/{knowledge_id}', [App\Http\Controllers\KnowledgeController::class, 'destroy'])->name('destroy');

        // Categories & Domains
        Route::get('/domains', [App\Http\Controllers\KnowledgeController::class, 'domains'])->name('domains');
        Route::get('/domains/{domain}/categories', [App\Http\Controllers\KnowledgeController::class, 'categories'])->name('categories');

        // Semantic Search Advanced
        Route::post('/semantic-search', [App\Http\Controllers\KnowledgeController::class, 'semanticSearch'])->name('semantic-search');
    });

    /*
    |----------------------------------------------------------------------
    | سير العمل (Workflows)
    |----------------------------------------------------------------------
    */
    Route::prefix('workflows')->name('workflows.')->group(function () {
        // List & Overview
        Route::get('/', [App\Http\Controllers\WorkflowController::class, 'index'])->name('index');
        Route::get('/{flow_id}', [App\Http\Controllers\WorkflowController::class, 'show'])->name('show');

        // Initialize & Manage
        Route::post('/initialize-campaign', [App\Http\Controllers\WorkflowController::class, 'initializeCampaignWorkflow'])->name('initialize.campaign');
        Route::post('/', [App\Http\Controllers\WorkflowController::class, 'store'])->name('store');
        Route::delete('/{flow_id}', [App\Http\Controllers\WorkflowController::class, 'destroy'])->name('destroy');

        // Steps Management
        Route::get('/{flow_id}/steps', [App\Http\Controllers\WorkflowController::class, 'steps'])->name('steps');
        Route::post('/{flow_id}/steps/{step_number}/start', [App\Http\Controllers\WorkflowController::class, 'startStep'])->name('steps.start');
        Route::post('/{flow_id}/steps/{step_number}/complete', [App\Http\Controllers\WorkflowController::class, 'completeStep'])->name('steps.complete');
        Route::post('/{flow_id}/steps/{step_number}/skip', [App\Http\Controllers\WorkflowController::class, 'skipStep'])->name('steps.skip');

        // Actions
        Route::post('/{flow_id}/cancel', [App\Http\Controllers\WorkflowController::class, 'cancel'])->name('cancel');
        Route::get('/{flow_id}/progress', [App\Http\Controllers\WorkflowController::class, 'progress'])->name('progress');
    });

    /*
    |----------------------------------------------------------------------
    | البريفات الإبداعية (Creative Briefs)
    |----------------------------------------------------------------------
    */
    Route::prefix('briefs')->name('briefs.')->group(function () {
        // List & Search
        Route::get('/', [App\Http\Controllers\CreativeBriefController::class, 'index'])->name('index');
        Route::get('/{brief_id}', [App\Http\Controllers\CreativeBriefController::class, 'show'])->name('show');

        // CRUD Operations
        Route::post('/', [App\Http\Controllers\CreativeBriefController::class, 'store'])->name('store');
        Route::put('/{brief_id}', [App\Http\Controllers\CreativeBriefController::class, 'update'])->name('update');
        Route::delete('/{brief_id}', [App\Http\Controllers\CreativeBriefController::class, 'destroy'])->name('destroy');

        // Brief Actions
        Route::post('/{brief_id}/approve', [App\Http\Controllers\CreativeBriefController::class, 'approve'])->name('approve');
        Route::post('/{brief_id}/reject', [App\Http\Controllers\CreativeBriefController::class, 'reject'])->name('reject');
        Route::post('/{brief_id}/generate-summary', [App\Http\Controllers\CreativeBriefController::class, 'generateSummary'])->name('generate-summary');

        // Validation
        Route::post('/validate', [App\Http\Controllers\CreativeBriefController::class, 'validateStructure'])->name('validate');
    });

    /*
    |----------------------------------------------------------------------
    | المحتوى (Content Management)
    |----------------------------------------------------------------------
    */
    Route::prefix('content')->name('content.')->group(function () {
        // List & Search
        Route::get('/', [App\Http\Controllers\Content\ContentController::class, 'index'])->name('index');
        Route::get('/{content_id}', [App\Http\Controllers\Content\ContentController::class, 'show'])->name('show');

        // CRUD Operations
        Route::post('/', [App\Http\Controllers\Content\ContentController::class, 'store'])->name('store');
        Route::put('/{content_id}', [App\Http\Controllers\Content\ContentController::class, 'update'])->name('update');
        Route::delete('/{content_id}', [App\Http\Controllers\Content\ContentController::class, 'destroy'])->name('destroy');

        // Content Actions
        Route::post('/{content_id}/publish', [App\Http\Controllers\Content\ContentController::class, 'publish'])->name('publish');
        Route::post('/{content_id}/unpublish', [App\Http\Controllers\Content\ContentController::class, 'unpublish'])->name('unpublish');
        Route::get('/{content_id}/versions', [App\Http\Controllers\Content\ContentController::class, 'versions'])->name('versions');
    });

    /*
    |----------------------------------------------------------------------
    | المنتجات والخدمات (Products & Services)
    |----------------------------------------------------------------------
    */
    Route::prefix('offerings')->name('offerings.')->group(function () {
        // Products
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [App\Http\Controllers\Product\ProductController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Product\ProductController::class, 'store'])->name('store');
            Route::get('/{offering_id}', [App\Http\Controllers\Product\ProductController::class, 'show'])->name('show');
            Route::put('/{offering_id}', [App\Http\Controllers\Product\ProductController::class, 'update'])->name('update');
            Route::delete('/{offering_id}', [App\Http\Controllers\Product\ProductController::class, 'destroy'])->name('destroy');
        });

        // Services
        Route::prefix('services')->name('services.')->group(function () {
            Route::get('/', [App\Http\Controllers\Service\ServiceController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Service\ServiceController::class, 'store'])->name('store');
            Route::get('/{offering_id}', [App\Http\Controllers\Service\ServiceController::class, 'show'])->name('show');
            Route::put('/{offering_id}', [App\Http\Controllers\Service\ServiceController::class, 'update'])->name('update');
            Route::delete('/{offering_id}', [App\Http\Controllers\Service\ServiceController::class, 'destroy'])->name('destroy');
        });

        // Bundles
        Route::prefix('bundles')->name('bundles.')->group(function () {
            Route::get('/', [App\Http\Controllers\Bundle\BundleController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Bundle\BundleController::class, 'store'])->name('store');
            Route::get('/{bundle_id}', [App\Http\Controllers\Bundle\BundleController::class, 'show'])->name('show');
            Route::put('/{bundle_id}', [App\Http\Controllers\Bundle\BundleController::class, 'update'])->name('update');
            Route::delete('/{bundle_id}', [App\Http\Controllers\Bundle\BundleController::class, 'destroy'])->name('destroy');
        });
    });

    /*
    |----------------------------------------------------------------------
    | لوحة التحكم (Dashboard)
    |----------------------------------------------------------------------
    */
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/overview', [App\Http\Controllers\DashboardController::class, 'overview'])->name('overview');
        Route::get('/stats', [App\Http\Controllers\DashboardController::class, 'stats'])->name('stats');
        Route::get('/recent-activity', [App\Http\Controllers\DashboardController::class, 'recentActivity'])->name('recent-activity');
        Route::get('/charts/campaigns-performance', [App\Http\Controllers\DashboardController::class, 'campaignsPerformance'])->name('charts.campaigns');
        Route::get('/charts/engagement', [App\Http\Controllers\DashboardController::class, 'engagement'])->name('charts.engagement');
    });

    /*
    |----------------------------------------------------------------------
    | نظام التدقيق والتقارير (Audit & Reporting System)
    |----------------------------------------------------------------------
    */
    Route::prefix('audit')->name('audit.')->group(function () {
        // Dashboard & Overview
        Route::get('/dashboard', [App\Http\Controllers\API\AuditController::class, 'dashboard'])->name('dashboard');

        // Reports
        Route::get('/realtime-status', [App\Http\Controllers\API\AuditController::class, 'realtimeStatus'])->name('realtime-status');
        Route::get('/daily-summary', [App\Http\Controllers\API\AuditController::class, 'dailySummary'])->name('daily-summary');
        Route::get('/weekly-performance', [App\Http\Controllers\API\AuditController::class, 'weeklyPerformance'])->name('weekly-performance');
        Route::get('/audit-summary', [App\Http\Controllers\API\AuditController::class, 'auditSummary'])->name('audit-summary');

        // Activity Log
        Route::get('/activity-log', [App\Http\Controllers\API\AuditController::class, 'activityLog'])->name('activity-log');
        Route::post('/log-event', [App\Http\Controllers\API\AuditController::class, 'logEvent'])->name('log-event');

        // Alerts
        Route::get('/check-alerts', [App\Http\Controllers\API\AuditController::class, 'checkAlerts'])->name('check-alerts');

        // Export
        Route::post('/export-report', [App\Http\Controllers\API\AuditController::class, 'exportReport'])->name('export-report');
    });
});

    /*
    |----------------------------------------------------------------------
    | Unified Dashboard & Sync Management (من Phase 2)
    |----------------------------------------------------------------------
    */
    Route::prefix('orgs/{org}')->name('orgs.')->group(function () {
        // Unified Dashboard
        Route::get('/dashboard', [App\Http\Controllers\API\DashboardController::class, 'index'])->name('dashboard.index');
        Route::post('/dashboard/refresh', [App\Http\Controllers\API\DashboardController::class, 'refresh'])->name('dashboard.refresh');

        // Sync Management
        Route::prefix('sync')->name('sync.')->group(function () {
            Route::get('/status', [App\Http\Controllers\API\SyncStatusController::class, 'orgStatus'])->name('status');
            Route::post('/trigger', [App\Http\Controllers\API\SyncStatusController::class, 'triggerOrgSync'])->name('trigger');
            Route::get('/statistics', [App\Http\Controllers\API\SyncStatusController::class, 'statistics'])->name('statistics');

            // Integration-specific sync
            Route::get('/integrations/{integration}/status', [App\Http\Controllers\API\SyncStatusController::class, 'integrationStatus'])->name('integration.status');
            Route::post('/integrations/{integration}/trigger', [App\Http\Controllers\API\SyncStatusController::class, 'triggerIntegrationSync'])->name('integration.trigger');
        });

        // Unified Campaign API (من Phase 3)
        Route::prefix('unified-campaigns')->name('unified-campaigns.')->group(function () {
            Route::get('/', [App\Http\Controllers\API\UnifiedCampaignController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\API\UnifiedCampaignController::class, 'store'])->name('store');
            Route::get('/{campaign}', [App\Http\Controllers\API\UnifiedCampaignController::class, 'show'])->name('show');
        });

        // Cache Management (من Phase 4)
        Route::prefix('cache')->name('cache.')->group(function () {
            Route::delete('/clear', [App\Http\Controllers\API\CacheController::class, 'clearOrg'])->name('clear');
            Route::delete('/dashboard', [App\Http\Controllers\API\CacheController::class, 'clearDashboard'])->name('clear-dashboard');
            Route::delete('/campaigns', [App\Http\Controllers\API\CacheController::class, 'clearCampaigns'])->name('clear-campaigns');
            Route::post('/warm', [App\Http\Controllers\API\CacheController::class, 'warmCache'])->name('warm');
        });

        // AI Optimization (من Phase 5)
        Route::prefix('ai')->name('ai.')->group(function () {
            Route::get('/campaigns/analyze', [App\Http\Controllers\API\AIOptimizationController::class, 'analyzeAllCampaigns'])->name('analyze-all');
            Route::get('/campaigns/{campaign}/analyze', [App\Http\Controllers\API\AIOptimizationController::class, 'analyzeCampaign'])->name('analyze-campaign');
        });

        // Predictive Analytics (من Phase 5B)
        Route::prefix('predictive')->name('predictive.')->group(function () {
            Route::get('/forecast', [App\Http\Controllers\API\PredictiveAnalyticsController::class, 'forecastOrganization'])->name('forecast-org');
            Route::get('/campaigns/{campaign}/forecast', [App\Http\Controllers\API\PredictiveAnalyticsController::class, 'forecastCampaign'])->name('forecast-campaign');
            Route::post('/campaigns/{campaign}/scenarios', [App\Http\Controllers\API\PredictiveAnalyticsController::class, 'compareScenarios'])->name('compare-scenarios');
            Route::get('/campaigns/{campaign}/trends', [App\Http\Controllers\API\PredictiveAnalyticsController::class, 'analyzeTrends'])->name('analyze-trends');
        });

        // Knowledge Learning System (من Phase 5C)
        Route::prefix('knowledge')->name('knowledge.')->group(function () {
            Route::get('/learn', [App\Http\Controllers\API\KnowledgeLearningController::class, 'learnFromHistory'])->name('learn');
            Route::post('/campaigns/{campaign}/decision-support', [App\Http\Controllers\API\KnowledgeLearningController::class, 'getDecisionSupport'])->name('decision-support');
            Route::get('/best-practices', [App\Http\Controllers\API\KnowledgeLearningController::class, 'getBestPractices'])->name('best-practices');
            Route::get('/insights', [App\Http\Controllers\API\KnowledgeLearningController::class, 'getInsights'])->name('insights');
            Route::get('/failure-patterns', [App\Http\Controllers\API\KnowledgeLearningController::class, 'getFailurePatterns'])->name('failure-patterns');
        });
    });

// Cache Statistics (Global)
Route::get('/cache/stats', [App\Http\Controllers\API\CacheController::class, 'stats'])->name('cache.stats');

/*
|--------------------------------------------------------------------------
| Dashboard Convenience Routes (Auto-resolve user's active org)
| These routes provide access to dashboard endpoints without requiring org_id
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', function(\Illuminate\Http\Request $request) {
        return app(App\Http\Controllers\DashboardController::class)->overview($request);
    });
    Route::get('/overview', [App\Http\Controllers\DashboardController::class, 'overview'])->name('overview');
    Route::get('/stats', [App\Http\Controllers\DashboardController::class, 'stats'])->name('stats');
    Route::get('/recent-activity', [App\Http\Controllers\DashboardController::class, 'recentActivity'])->name('recent-activity');
    Route::get('/campaigns-summary', [App\Http\Controllers\DashboardController::class, 'campaignsSummary'])->name('campaigns-summary');
    Route::get('/analytics-overview', [App\Http\Controllers\DashboardController::class, 'analyticsOverview'])->name('analytics-overview');
    Route::get('/upcoming-posts', [App\Http\Controllers\DashboardController::class, 'upcomingPosts'])->name('upcoming-posts');
    Route::get('/top-campaigns', [App\Http\Controllers\DashboardController::class, 'topCampaigns'])->name('top-campaigns');
    Route::get('/budget-summary', [App\Http\Controllers\DashboardController::class, 'budgetSummary'])->name('budget-summary');
    Route::get('/charts/campaigns-performance', [App\Http\Controllers\DashboardController::class, 'campaignsPerformance'])->name('charts.campaigns');
    Route::get('/charts/engagement', [App\Http\Controllers\DashboardController::class, 'engagement'])->name('charts.engagement');
});

/*
|--------------------------------------------------------------------------
| Campaign Convenience Routes (Auto-resolve user's active org)
| These routes provide access to campaign endpoints without requiring org_id
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/', [App\Http\Controllers\Campaigns\CampaignController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\Campaigns\CampaignController::class, 'store'])->name('store');
    Route::get('/{campaign_id}', [App\Http\Controllers\Campaigns\CampaignController::class, 'show'])->name('show');
    Route::put('/{campaign_id}', [App\Http\Controllers\Campaigns\CampaignController::class, 'update'])->name('update');
    Route::delete('/{campaign_id}', [App\Http\Controllers\Campaigns\CampaignController::class, 'destroy'])->name('destroy');
    Route::post('/{campaign_id}/duplicate', [App\Http\Controllers\Campaigns\CampaignController::class, 'duplicate'])->name('duplicate');
    Route::get('/{campaign_id}/analytics', [App\Http\Controllers\Campaigns\CampaignController::class, 'analytics'])->name('analytics');

    // Campaign Performance Dashboard (P2 Option 3)
    Route::get('/{campaign_id}/performance-metrics', [App\Http\Controllers\Campaigns\CampaignController::class, 'performanceMetrics'])->name('performance-metrics');
    Route::post('/compare', [App\Http\Controllers\Campaigns\CampaignController::class, 'compareCampaigns'])->name('compare');
    Route::get('/{campaign_id}/performance-trends', [App\Http\Controllers\Campaigns\CampaignController::class, 'performanceTrends'])->name('performance-trends');
    Route::get('/top-performing', [App\Http\Controllers\Campaigns\CampaignController::class, 'topPerforming'])->name('top-performing');
});

/*
|--------------------------------------------------------------------------
| Content Convenience Routes (Auto-resolve user's active org)
| These routes provide access to content endpoints without requiring org_id
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('content')->name('content.')->group(function () {
    Route::get('/', [App\Http\Controllers\Content\ContentController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\Content\ContentController::class, 'store'])->name('store');
    Route::get('/{content_id}', [App\Http\Controllers\Content\ContentController::class, 'show'])->name('show');
    Route::put('/{content_id}', [App\Http\Controllers\Content\ContentController::class, 'update'])->name('update');
    Route::delete('/{content_id}', [App\Http\Controllers\Content\ContentController::class, 'destroy'])->name('destroy');
    Route::post('/{content_id}/schedule', [App\Http\Controllers\Content\ContentController::class, 'schedule'])->name('schedule');
});

/*
|--------------------------------------------------------------------------
| Integration Convenience Routes (Auto-resolve user's active org)
| These routes provide access to integration endpoints without requiring org_id
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('integrations')->name('integrations.')->group(function () {
    Route::get('/', [App\Http\Controllers\IntegrationController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\IntegrationController::class, 'store'])->name('store');
    Route::get('/{id}', [App\Http\Controllers\IntegrationController::class, 'show'])->name('show');
    Route::put('/{id}', [App\Http\Controllers\IntegrationController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\IntegrationController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/refresh', [App\Http\Controllers\IntegrationController::class, 'refresh'])->name('refresh');
    Route::get('/{id}/status', [App\Http\Controllers\IntegrationController::class, 'status'])->name('status');
});

/*
|--------------------------------------------------------------------------
| Asset Convenience Routes (Auto-resolve user's active org)
| These routes provide access to asset endpoints without requiring org_id
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('assets')->name('assets.')->group(function () {
    Route::get('/', [App\Http\Controllers\AssetController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\AssetController::class, 'store'])->name('store');
    Route::get('/{id}', [App\Http\Controllers\AssetController::class, 'show'])->name('show');
    Route::put('/{id}', [App\Http\Controllers\AssetController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\AssetController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/download', [App\Http\Controllers\AssetController::class, 'download'])->name('download');
});

/*
|--------------------------------------------------------------------------
| Lead Convenience Routes (Auto-resolve user's active org)
| These routes provide access to lead endpoints without requiring org_id
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('leads')->name('leads.')->group(function () {
    Route::get('/', [App\Http\Controllers\LeadController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\LeadController::class, 'store'])->name('store');
    Route::get('/{id}', [App\Http\Controllers\LeadController::class, 'show'])->name('show');
    Route::put('/{id}', [App\Http\Controllers\LeadController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\LeadController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/score', [App\Http\Controllers\LeadController::class, 'score'])->name('score');
    Route::put('/{id}/status', [App\Http\Controllers\LeadController::class, 'updateStatus'])->name('update-status');
});

/*
|--------------------------------------------------------------------------
| Analytics Convenience Routes (Auto-resolve user's active org)
| These routes provide access to analytics endpoints without requiring org_id
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/overview', [App\Http\Controllers\API\AnalyticsController::class, 'getOverview'])->name('overview');
    Route::get('/campaigns', [App\Http\Controllers\API\AnalyticsController::class, 'getCampaignPerformance'])->name('campaigns');
    Route::get('/campaigns/{campaignId}', [App\Http\Controllers\API\AnalyticsController::class, 'getCampaignAnalytics'])->name('campaigns.show');
    Route::get('/platforms', [App\Http\Controllers\API\AnalyticsController::class, 'getPlatformPerformance'])->name('platforms');
    Route::get('/content', [App\Http\Controllers\API\AnalyticsController::class, 'getContentPerformance'])->name('content');
    Route::get('/social', [App\Http\Controllers\API\AnalyticsController::class, 'getSocialAnalytics'])->name('social');
    Route::get('/trends', [App\Http\Controllers\API\AnalyticsController::class, 'getTrends'])->name('trends');
    Route::get('/engagement', [App\Http\Controllers\API\AnalyticsController::class, 'getEngagementAnalytics'])->name('engagement');
    Route::get('/compare', [App\Http\Controllers\API\AnalyticsController::class, 'compareCampaigns'])->name('compare');
    Route::get('/funnel/{campaign_id}', [App\Http\Controllers\API\AnalyticsController::class, 'getFunnelAnalytics'])->name('funnel');
    Route::get('/demographics', [App\Http\Controllers\API\AnalyticsController::class, 'getAudienceDemographics'])->name('demographics');
    Route::post('/export', [App\Http\Controllers\API\AnalyticsController::class, 'exportReport'])->name('export');
});

/*
|--------------------------------------------------------------------------
| AI Assistant API - Content Generation & Analysis (Global)
| Rate Limited: 10 requests per minute per user
|--------------------------------------------------------------------------
*/
Route::prefix('ai')->middleware(['auth:sanctum'])->group(function () {
    // Content Suggestions & Generation
    Route::post('/generate-suggestions', [App\Http\Controllers\API\AIAssistantController::class, 'generateSuggestions']);
    Route::post('/generate-brief', [App\Http\Controllers\API\AIAssistantController::class, 'generateBrief']);
    Route::post('/generate-visual', [App\Http\Controllers\API\AIAssistantController::class, 'generateVisual']);
    Route::post('/generate-variations', [App\Http\Controllers\API\AIAssistantController::class, 'generateVariations']);
    Route::post('/generate-calendar', [App\Http\Controllers\API\AIAssistantController::class, 'generateCalendar']);
    Route::post('/generate-meta', [App\Http\Controllers\API\AIAssistantController::class, 'generateMeta']);

    // Content Analysis
    Route::post('/extract-keywords', [App\Http\Controllers\API\AIAssistantController::class, 'extractKeywords']);
    Route::post('/analyze-sentiment', [App\Http\Controllers\API\AIAssistantController::class, 'analyzeSentiment']);
    Route::post('/categorize', [App\Http\Controllers\API\AIAssistantController::class, 'categorize']);

    // Social Media Tools
    Route::post('/generate-hashtags', [App\Http\Controllers\API\AIAssistantController::class, 'generateHashtags']);

    // Translation
    Route::post('/translate', [App\Http\Controllers\API\AIAssistantController::class, 'translate']);

    // Improvements & Suggestions
    Route::post('/suggest-improvements', [App\Http\Controllers\API\AIAssistantController::class, 'suggestImprovements']);
});

/*
|--------------------------------------------------------------------------
| OAuth Callbacks (Public - No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::get('/integrations/{platform}/callback', [IntegrationController::class, 'callback'])
    ->name('integrations.callback');

/*
|--------------------------------------------------------------------------
| مسارات عامة بدون مصادقة (للتطوير والاختبار)
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
})->name('health');

Route::get('/ping', function () {
    return response()->json(['pong' => true]);
})->name('ping');

/*
|--------------------------------------------------------------------------
| GPT Interface Routes - ChatGPT Integration
|--------------------------------------------------------------------------
| These routes provide GPT-optimized endpoints for ChatGPT integration
| All endpoints require authentication and follow OpenAPI 3.1 spec
*/
use App\Http\Controllers\GPT\GPTController;

Route::prefix('gpt')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Context
    Route::get('/context', [GPTController::class, 'getContext'])->name('gpt.context');

    // Campaigns
    Route::get('/campaigns', [GPTController::class, 'listCampaigns'])->name('gpt.campaigns.index');
    Route::post('/campaigns', [GPTController::class, 'createCampaign'])->name('gpt.campaigns.create');
    Route::get('/campaigns/{campaignId}', [GPTController::class, 'getCampaign'])->name('gpt.campaigns.show');
    Route::put('/campaigns/{campaignId}', [GPTController::class, 'updateCampaign'])->name('gpt.campaigns.update');
    Route::get('/campaigns/{campaignId}/analytics', [GPTController::class, 'getCampaignAnalytics'])->name('gpt.campaigns.analytics');

    // Content Plans
    Route::get('/content-plans', [GPTController::class, 'listContentPlans'])->name('gpt.content-plans.index');
    Route::post('/content-plans', [GPTController::class, 'createContentPlan'])->name('gpt.content-plans.create');
    Route::post('/content-plans/{contentPlanId}/generate', [GPTController::class, 'generateContent'])->name('gpt.content-plans.generate');

    // Knowledge Base
    Route::post('/knowledge/search', [GPTController::class, 'searchKnowledge'])->name('gpt.knowledge.search');
    Route::post('/knowledge', [GPTController::class, 'addKnowledge'])->name('gpt.knowledge.add');

    // AI Insights
    Route::post('/ai/insights', [GPTController::class, 'getAIInsights'])->name('gpt.ai.insights');

    // Conversational Context
    Route::get('/conversation/session', [GPTController::class, 'conversationSession'])->name('gpt.conversation.session');
    Route::post('/conversation/message', [GPTController::class, 'conversationMessage'])->name('gpt.conversation.message');
    Route::get('/conversation/{sessionId}/history', [GPTController::class, 'conversationHistory'])->name('gpt.conversation.history');
    Route::delete('/conversation/{sessionId}/clear', [GPTController::class, 'conversationClear'])->name('gpt.conversation.clear');
    Route::get('/conversation/{sessionId}/stats', [GPTController::class, 'conversationStats'])->name('gpt.conversation.stats');

    // Bulk Operations
    Route::post('/bulk-operation', [GPTController::class, 'bulkOperation'])->name('gpt.bulk-operation');

    // Smart Search
    Route::post('/search', [GPTController::class, 'smartSearch'])->name('gpt.search');
});

/*
|--------------------------------------------------------------------------
| AI Content Generation Routes - Phase 1B & Phase 3 Implementation
|--------------------------------------------------------------------------
| Handles AI-powered content generation with quota enforcement and rate limiting
| Phase 3: Added Gemini 3 (image/text) and Veo 3.1 (video) generation
| Requires authentication and organization context
*/
Route::prefix('ai')->middleware(['auth:sanctum', 'rls.context'])->group(function () {
    // Text Content Generation with quota and rate limiting
    Route::post('/generate', [App\Http\Controllers\Api\AiContentController::class, 'generate'])
        ->middleware(['check.ai.quota:gpt,1', 'ai.rate.limit:gpt'])
        ->name('ai.generate');

    // Batch Content Generation
    Route::post('/generate-batch', [App\Http\Controllers\Api\AiContentController::class, 'generateBatch'])
        ->middleware(['check.ai.quota:gpt,5', 'ai.rate.limit:gpt'])
        ->name('ai.generate-batch');

    // Ad Design Generation (Gemini 3 - Images)
    Route::post('/generate-ad-design', [App\Http\Controllers\Api\AiContentController::class, 'generateAdDesign'])
        ->middleware(['check.ai.quota:image,3', 'ai.rate.limit:gemini'])
        ->name('ai.generate-ad-design');

    // Ad Copy Generation (Gemini 3 - Text)
    Route::post('/generate-ad-copy', [App\Http\Controllers\Api\AiContentController::class, 'generateAdCopy'])
        ->middleware(['check.ai.quota:gpt,1', 'ai.rate.limit:gemini'])
        ->name('ai.generate-ad-copy');

    // Video Ad Generation (Veo 3.1 - Async Job)
    Route::post('/generate-video', [App\Http\Controllers\Api\AiContentController::class, 'generateVideo'])
        ->middleware(['check.ai.quota:video,1', 'ai.rate.limit:veo'])
        ->name('ai.generate-video');

    // Check Video Generation Status
    Route::get('/video-status/{media}', [App\Http\Controllers\Api\AiContentController::class, 'videoStatus'])
        ->name('ai.video-status');

    // AI Usage Statistics
    Route::get('/stats', [App\Http\Controllers\Api\AiContentController::class, 'stats'])
        ->name('ai.stats');
});
