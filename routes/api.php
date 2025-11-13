<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Core\{OrgController, UserController};
use App\Http\Controllers\Campaigns\CampaignController;
use App\Http\Controllers\Creative\CreativeAssetController;
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
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::match(['get', 'post'], '/meta', [WebhookController::class, 'handleMetaWebhook'])->name('meta');
    Route::match(['get', 'post'], '/whatsapp', [WebhookController::class, 'handleWhatsAppWebhook'])->name('whatsapp');
    Route::post('/tiktok', [WebhookController::class, 'handleTikTokWebhook'])->name('tiktok');
    Route::post('/twitter', [WebhookController::class, 'handleTwitterWebhook'])->name('twitter');
});

/*
|--------------------------------------------------------------------------
| مسارات المصادقة (Authentication) - بدون org_id
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // التسجيل وتسجيل الدخول (عام - بدون مصادقة)
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    // OAuth callbacks
    Route::get('/oauth/{provider}/callback', [AuthController::class, 'oauthCallback'])->name('auth.oauth.callback');

    // مسارات محمية (تحتاج مصادقة)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update');
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
    | الحملات (Campaigns)
    |----------------------------------------------------------------------
    */
    Route::apiResource('campaigns', CampaignController::class)->parameters([
        'campaigns' => 'campaign_id'
    ]);

    /*
    |----------------------------------------------------------------------
    | المحتوى الإبداعي (Creative Assets)
    |----------------------------------------------------------------------
    */
    Route::prefix('creative')->name('creative.')->group(function () {
        Route::apiResource('assets', CreativeAssetController::class)->parameters([
            'assets' => 'asset_id'
        ]);
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
    |----------------------------------------------------------------------
    */
    Route::prefix('ai')->name('ai.')->group(function () {
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
