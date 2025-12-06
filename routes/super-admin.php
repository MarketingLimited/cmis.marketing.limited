<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\SuperAdmin\SuperAdminOrgController;
use App\Http\Controllers\SuperAdmin\SuperAdminUserController;
use App\Http\Controllers\SuperAdmin\SuperAdminPlanController;
use App\Http\Controllers\SuperAdmin\SuperAdminSubscriptionController;
use App\Http\Controllers\SuperAdmin\SuperAdminAnalyticsController;
use App\Http\Controllers\SuperAdmin\SuperAdminSystemController;
use App\Http\Controllers\SuperAdmin\SuperAdminAppController;
use App\Http\Controllers\SuperAdmin\SuperAdminIntegrationController;
use App\Http\Controllers\SuperAdmin\SuperAdminAnnouncementController;
use App\Http\Controllers\SuperAdmin\SuperAdminSecurityController;
use App\Http\Controllers\SuperAdmin\SuperAdminBillingController;
use App\Http\Controllers\SuperAdmin\SuperAdminAssetController;
use App\Http\Controllers\SuperAdmin\SuperAdminFeatureFlagController;
use App\Http\Controllers\SuperAdmin\SuperAdminSettingsController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminWebsiteDashboardController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminPageController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminHeroController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminFeatureController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminFeatureCategoryController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminTestimonialController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminCaseStudyController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminFaqController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminFaqCategoryController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminTeamController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminPartnerController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminBlogController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminBlogCategoryController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminNavigationController;
use App\Http\Controllers\SuperAdmin\Website\SuperAdminWebsiteSettingsController;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are protected
| by the 'auth' and 'super.admin' middleware. They provide access to
| platform-wide management features for super administrators.
|
*/

Route::middleware(['auth', 'super.admin'])->prefix('super-admin')->name('super-admin.')->group(function () {

    // =====================================================
    // Dashboard
    // =====================================================
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/api/quick-stats', [SuperAdminController::class, 'quickStats'])->name('quick-stats');

    // =====================================================
    // Organizations Management
    // =====================================================
    Route::prefix('organizations')->name('orgs.')->group(function () {
        Route::get('/', [SuperAdminOrgController::class, 'index'])->name('index');
        Route::get('/{org}', [SuperAdminOrgController::class, 'show'])->name('show');
        Route::post('/{org}/suspend', [SuperAdminOrgController::class, 'suspend'])->name('suspend');
        Route::post('/{org}/block', [SuperAdminOrgController::class, 'block'])->name('block');
        Route::post('/{org}/restore', [SuperAdminOrgController::class, 'restore'])->name('restore');
        Route::post('/{org}/change-plan', [SuperAdminOrgController::class, 'changePlan'])->name('change-plan');
        Route::post('/bulk-action', [SuperAdminOrgController::class, 'bulkAction'])->name('bulk');
    });

    // =====================================================
    // Users Management
    // =====================================================
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [SuperAdminUserController::class, 'index'])->name('index');
        Route::get('/{user}', [SuperAdminUserController::class, 'show'])->name('show');
        Route::post('/{user}/suspend', [SuperAdminUserController::class, 'suspend'])->name('suspend');
        Route::post('/{user}/block', [SuperAdminUserController::class, 'block'])->name('block');
        Route::post('/{user}/restore', [SuperAdminUserController::class, 'restore'])->name('restore');
        Route::post('/{user}/toggle-super-admin', [SuperAdminUserController::class, 'toggleSuperAdmin'])->name('toggle-super-admin');
        Route::post('/{user}/impersonate', [SuperAdminUserController::class, 'impersonate'])->name('impersonate');
        Route::post('/bulk-action', [SuperAdminUserController::class, 'bulkAction'])->name('bulk');
    });

    // Stop impersonating (accessible from anywhere)
    Route::post('/stop-impersonating', [SuperAdminUserController::class, 'stopImpersonating'])->name('stop-impersonating');

    // =====================================================
    // Plans Management
    // =====================================================
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [SuperAdminPlanController::class, 'index'])->name('index');
        Route::get('/create', [SuperAdminPlanController::class, 'create'])->name('create');
        Route::post('/', [SuperAdminPlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [SuperAdminPlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [SuperAdminPlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [SuperAdminPlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [SuperAdminPlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle-active', [SuperAdminPlanController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{plan}/set-default', [SuperAdminPlanController::class, 'setDefault'])->name('set-default');
    });

    // =====================================================
    // Marketplace Apps Management
    // =====================================================
    Route::prefix('apps')->name('apps.')->group(function () {
        Route::get('/', [SuperAdminAppController::class, 'index'])->name('index');
        Route::get('/matrix', [SuperAdminAppController::class, 'matrix'])->name('matrix');
        Route::post('/bulk-assign', [SuperAdminAppController::class, 'bulkAssign'])->name('bulk-assign');
        Route::get('/{app}', [SuperAdminAppController::class, 'show'])->name('show');
        Route::put('/{app}/plan-apps', [SuperAdminAppController::class, 'updatePlanApps'])->name('update-plan-apps');
        Route::post('/{app}/toggle/{plan}', [SuperAdminAppController::class, 'toggleAppForPlan'])->name('toggle');
    });

    // =====================================================
    // Platform Integrations
    // =====================================================
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [SuperAdminIntegrationController::class, 'index'])->name('index');
        Route::get('/health', [SuperAdminIntegrationController::class, 'healthDashboard'])->name('health');
        Route::get('/sync-status', [SuperAdminIntegrationController::class, 'syncStatus'])->name('sync-status');
        Route::get('/rate-limits', [SuperAdminIntegrationController::class, 'rateLimits'])->name('rate-limits');
        Route::get('/{connection}', [SuperAdminIntegrationController::class, 'show'])->name('show');
        Route::post('/{connection}/refresh', [SuperAdminIntegrationController::class, 'forceRefresh'])->name('refresh');
        Route::delete('/{connection}', [SuperAdminIntegrationController::class, 'disconnect'])->name('disconnect');
    });

    // =====================================================
    // Subscriptions Management
    // =====================================================
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SuperAdminSubscriptionController::class, 'index'])->name('index');
        Route::get('/stats', [SuperAdminSubscriptionController::class, 'stats'])->name('stats');
        Route::post('/create', [SuperAdminSubscriptionController::class, 'create'])->name('create');
        Route::get('/{subscription}', [SuperAdminSubscriptionController::class, 'show'])->name('show');
        Route::post('/{subscription}/change-plan', [SuperAdminSubscriptionController::class, 'changePlan'])->name('change-plan');
        Route::post('/{subscription}/cancel', [SuperAdminSubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/reactivate', [SuperAdminSubscriptionController::class, 'reactivate'])->name('reactivate');
        Route::post('/{subscription}/extend-trial', [SuperAdminSubscriptionController::class, 'extendTrial'])->name('extend-trial');
    });

    // =====================================================
    // Billing & Invoices
    // =====================================================
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [SuperAdminBillingController::class, 'index'])->name('index');
        Route::get('/invoices', [SuperAdminBillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/create', [SuperAdminBillingController::class, 'createInvoiceForm'])->name('create');
        Route::post('/invoices', [SuperAdminBillingController::class, 'createInvoice'])->name('store');
        Route::get('/invoices/{invoice}', [SuperAdminBillingController::class, 'showInvoice'])->name('show');
        Route::post('/invoices/{invoice}/mark-paid', [SuperAdminBillingController::class, 'markAsPaid'])->name('mark-paid');
        Route::post('/invoices/{invoice}/reminder', [SuperAdminBillingController::class, 'sendReminder'])->name('reminder');
        Route::post('/invoices/{invoice}/cancel', [SuperAdminBillingController::class, 'cancelInvoice'])->name('cancel');
        Route::get('/payments', [SuperAdminBillingController::class, 'payments'])->name('payments');
        Route::post('/payments/{payment}/refund', [SuperAdminBillingController::class, 'refund'])->name('refund');
        Route::get('/revenue', [SuperAdminBillingController::class, 'revenue'])->name('revenue');
    });

    // =====================================================
    // API Analytics
    // =====================================================
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [SuperAdminAnalyticsController::class, 'index'])->name('index');
        Route::get('/overview', [SuperAdminAnalyticsController::class, 'overview'])->name('overview');
        Route::get('/by-platform', [SuperAdminAnalyticsController::class, 'byPlatform'])->name('by-platform');
        Route::get('/by-org', [SuperAdminAnalyticsController::class, 'byOrg'])->name('by-org');
        Route::get('/by-user', [SuperAdminAnalyticsController::class, 'byUser'])->name('by-user');
        Route::get('/errors', [SuperAdminAnalyticsController::class, 'errors'])->name('errors');
        Route::get('/rate-limits', [SuperAdminAnalyticsController::class, 'rateLimits'])->name('rate-limits');
        Route::get('/endpoints', [SuperAdminAnalyticsController::class, 'endpoints'])->name('endpoints');
        Route::get('/slow-requests', [SuperAdminAnalyticsController::class, 'slowRequests'])->name('slow-requests');
    });

    // =====================================================
    // Announcements Management
    // =====================================================
    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [SuperAdminAnnouncementController::class, 'index'])->name('index');
        Route::get('/create', [SuperAdminAnnouncementController::class, 'create'])->name('create');
        Route::post('/', [SuperAdminAnnouncementController::class, 'store'])->name('store');
        Route::get('/active', [SuperAdminAnnouncementController::class, 'getActiveForUser'])->name('active');
        Route::get('/{announcement}', [SuperAdminAnnouncementController::class, 'show'])->name('show');
        Route::get('/{announcement}/edit', [SuperAdminAnnouncementController::class, 'edit'])->name('edit');
        Route::put('/{announcement}', [SuperAdminAnnouncementController::class, 'update'])->name('update');
        Route::delete('/{announcement}', [SuperAdminAnnouncementController::class, 'destroy'])->name('destroy');
        Route::post('/{announcement}/toggle-active', [SuperAdminAnnouncementController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{announcement}/duplicate', [SuperAdminAnnouncementController::class, 'duplicate'])->name('duplicate');
        Route::post('/{announcement}/dismiss', [SuperAdminAnnouncementController::class, 'dismiss'])->name('dismiss');
    });

    // =====================================================
    // Security Dashboard
    // =====================================================
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [SuperAdminSecurityController::class, 'index'])->name('index');
        Route::get('/audit-logs', [SuperAdminSecurityController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/events', [SuperAdminSecurityController::class, 'events'])->name('events');
        Route::post('/events/{event}/resolve', [SuperAdminSecurityController::class, 'resolveEvent'])->name('resolve-event');
        Route::get('/ip-blacklist', [SuperAdminSecurityController::class, 'ipBlacklist'])->name('ip-blacklist');
        Route::post('/ip-blacklist', [SuperAdminSecurityController::class, 'blockIp'])->name('block-ip');
        Route::delete('/ip-blacklist/{blacklist}/unblock', [SuperAdminSecurityController::class, 'unblockIp'])->name('unblock-ip');
        Route::get('/admin-actions', [SuperAdminSecurityController::class, 'adminActions'])->name('admin-actions');
    });

    // =====================================================
    // Asset Management
    // =====================================================
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/', [SuperAdminAssetController::class, 'index'])->name('index');
        Route::get('/browse', [SuperAdminAssetController::class, 'browse'])->name('browse');
        Route::get('/storage', [SuperAdminAssetController::class, 'storage'])->name('storage');
        Route::get('/cleanup', [SuperAdminAssetController::class, 'cleanup'])->name('cleanup');
        Route::get('/{asset}', [SuperAdminAssetController::class, 'show'])->name('show');
        Route::delete('/{asset}', [SuperAdminAssetController::class, 'destroy'])->name('destroy');
        Route::post('/purge', [SuperAdminAssetController::class, 'purge'])->name('purge');
        Route::post('/bulk-delete-unused', [SuperAdminAssetController::class, 'bulkDeleteUnused'])->name('bulk-delete-unused');
    });

    // =====================================================
    // Feature Flags Management
    // =====================================================
    Route::prefix('feature-flags')->name('feature-flags.')->group(function () {
        Route::get('/', [SuperAdminFeatureFlagController::class, 'index'])->name('index');
        Route::get('/browse', [SuperAdminFeatureFlagController::class, 'browse'])->name('browse');
        Route::get('/create', [SuperAdminFeatureFlagController::class, 'create'])->name('create');
        Route::post('/', [SuperAdminFeatureFlagController::class, 'store'])->name('store');
        Route::get('/{flag}', [SuperAdminFeatureFlagController::class, 'show'])->name('show');
        Route::get('/{flag}/edit', [SuperAdminFeatureFlagController::class, 'edit'])->name('edit');
        Route::put('/{flag}', [SuperAdminFeatureFlagController::class, 'update'])->name('update');
        Route::post('/{flag}/toggle', [SuperAdminFeatureFlagController::class, 'toggle'])->name('toggle');
        Route::delete('/{flag}', [SuperAdminFeatureFlagController::class, 'destroy'])->name('destroy');
        Route::post('/{flag}/override', [SuperAdminFeatureFlagController::class, 'addOverride'])->name('add-override');
        Route::delete('/{flag}/override/{override}', [SuperAdminFeatureFlagController::class, 'removeOverride'])->name('remove-override');
        Route::post('/bulk-toggle', [SuperAdminFeatureFlagController::class, 'bulkToggle'])->name('bulk-toggle');
    });

    // =====================================================
    // Global Settings
    // =====================================================
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SuperAdminSettingsController::class, 'index'])->name('index');
        Route::post('/', [SuperAdminSettingsController::class, 'update'])->name('update');
        Route::post('/reset/{key}', [SuperAdminSettingsController::class, 'reset'])->name('reset');
        Route::get('/export', [SuperAdminSettingsController::class, 'export'])->name('export');
        Route::post('/import', [SuperAdminSettingsController::class, 'import'])->name('import');
    });

    // =====================================================
    // System Management
    // =====================================================
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/health', [SuperAdminSystemController::class, 'health'])->name('health');
        Route::get('/logs', [SuperAdminSystemController::class, 'logs'])->name('logs');
        Route::get('/queues', [SuperAdminSystemController::class, 'queues'])->name('queues');
        Route::post('/queues/retry/{job}', [SuperAdminSystemController::class, 'retryJob'])->name('retry-job');
        Route::post('/queues/flush', [SuperAdminSystemController::class, 'flushFailedJobs'])->name('flush-jobs');
        Route::post('/clear-cache', [SuperAdminSystemController::class, 'clearCache'])->name('clear-cache');
        Route::get('/database-stats', [SuperAdminSystemController::class, 'databaseStats'])->name('database-stats');
        Route::get('/scheduled-tasks', [SuperAdminSystemController::class, 'scheduledTasks'])->name('scheduled-tasks');
        Route::get('/action-logs', [SuperAdminSystemController::class, 'actionLogs'])->name('action-logs');

        // Database Maintenance
        Route::get('/database', [SuperAdminSystemController::class, 'databaseMaintenance'])->name('database');
        Route::get('/database/schema/{schema}', [SuperAdminSystemController::class, 'schemaTables'])->name('schema-tables');
        Route::get('/database/table/{schema}/{table}', [SuperAdminSystemController::class, 'tableDetails'])->name('table-details');
        Route::post('/database/vacuum/{schema}/{table}', [SuperAdminSystemController::class, 'vacuumTable'])->name('vacuum-table');
        Route::post('/database/analyze/{schema}/{table}', [SuperAdminSystemController::class, 'analyzeTable'])->name('analyze-table');
        Route::post('/database/reindex/{schema}/{table}', [SuperAdminSystemController::class, 'reindexTable'])->name('reindex-table');
        Route::get('/migrations', [SuperAdminSystemController::class, 'migrations'])->name('migrations');
        Route::get('/active-queries', [SuperAdminSystemController::class, 'activeQueries'])->name('active-queries');
        Route::post('/cancel-query/{pid}', [SuperAdminSystemController::class, 'cancelQuery'])->name('cancel-query');
        Route::post('/terminate-connection/{pid}', [SuperAdminSystemController::class, 'terminateConnection'])->name('terminate-connection');
    });

    // =====================================================
    // Marketing Website Management (2025-12-07)
    // =====================================================
    Route::prefix('website')->name('website.')->group(function () {
        // Dashboard
        Route::get('/', [SuperAdminWebsiteDashboardController::class, 'index'])->name('dashboard');
        Route::get('/stats', [SuperAdminWebsiteDashboardController::class, 'stats'])->name('stats');

        // CMS Pages
        Route::prefix('pages')->name('pages.')->group(function () {
            Route::get('/', [SuperAdminPageController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminPageController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminPageController::class, 'store'])->name('store');
            Route::get('/{page}', [SuperAdminPageController::class, 'show'])->name('show');
            Route::get('/{page}/edit', [SuperAdminPageController::class, 'edit'])->name('edit');
            Route::put('/{page}', [SuperAdminPageController::class, 'update'])->name('update');
            Route::delete('/{page}', [SuperAdminPageController::class, 'destroy'])->name('destroy');
            Route::post('/{page}/toggle-publish', [SuperAdminPageController::class, 'togglePublish'])->name('toggle-publish');
            Route::post('/reorder', [SuperAdminPageController::class, 'reorder'])->name('reorder');
        });

        // Hero Slides
        Route::prefix('hero')->name('hero.')->group(function () {
            Route::get('/', [SuperAdminHeroController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminHeroController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminHeroController::class, 'store'])->name('store');
            Route::get('/{slide}', [SuperAdminHeroController::class, 'show'])->name('show');
            Route::get('/{slide}/edit', [SuperAdminHeroController::class, 'edit'])->name('edit');
            Route::put('/{slide}', [SuperAdminHeroController::class, 'update'])->name('update');
            Route::delete('/{slide}', [SuperAdminHeroController::class, 'destroy'])->name('destroy');
            Route::post('/{slide}/toggle-active', [SuperAdminHeroController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/reorder', [SuperAdminHeroController::class, 'reorder'])->name('reorder');
        });

        // Feature Categories
        Route::prefix('feature-categories')->name('feature-categories.')->group(function () {
            Route::get('/', [SuperAdminFeatureCategoryController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminFeatureCategoryController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminFeatureCategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [SuperAdminFeatureCategoryController::class, 'show'])->name('show');
            Route::get('/{category}/edit', [SuperAdminFeatureCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [SuperAdminFeatureCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [SuperAdminFeatureCategoryController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [SuperAdminFeatureCategoryController::class, 'reorder'])->name('reorder');
        });

        // Features
        Route::prefix('features')->name('features.')->group(function () {
            Route::get('/', [SuperAdminFeatureController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminFeatureController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminFeatureController::class, 'store'])->name('store');
            Route::get('/{feature}', [SuperAdminFeatureController::class, 'show'])->name('show');
            Route::get('/{feature}/edit', [SuperAdminFeatureController::class, 'edit'])->name('edit');
            Route::put('/{feature}', [SuperAdminFeatureController::class, 'update'])->name('update');
            Route::delete('/{feature}', [SuperAdminFeatureController::class, 'destroy'])->name('destroy');
            Route::post('/{feature}/toggle-active', [SuperAdminFeatureController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{feature}/toggle-featured', [SuperAdminFeatureController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/reorder', [SuperAdminFeatureController::class, 'reorder'])->name('reorder');
        });

        // Testimonials
        Route::prefix('testimonials')->name('testimonials.')->group(function () {
            Route::get('/', [SuperAdminTestimonialController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminTestimonialController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminTestimonialController::class, 'store'])->name('store');
            Route::get('/{testimonial}', [SuperAdminTestimonialController::class, 'show'])->name('show');
            Route::get('/{testimonial}/edit', [SuperAdminTestimonialController::class, 'edit'])->name('edit');
            Route::put('/{testimonial}', [SuperAdminTestimonialController::class, 'update'])->name('update');
            Route::delete('/{testimonial}', [SuperAdminTestimonialController::class, 'destroy'])->name('destroy');
            Route::post('/{testimonial}/toggle-active', [SuperAdminTestimonialController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{testimonial}/toggle-featured', [SuperAdminTestimonialController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/reorder', [SuperAdminTestimonialController::class, 'reorder'])->name('reorder');
        });

        // Case Studies
        Route::prefix('case-studies')->name('case-studies.')->group(function () {
            Route::get('/', [SuperAdminCaseStudyController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminCaseStudyController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminCaseStudyController::class, 'store'])->name('store');
            Route::get('/{caseStudy}', [SuperAdminCaseStudyController::class, 'show'])->name('show');
            Route::get('/{caseStudy}/edit', [SuperAdminCaseStudyController::class, 'edit'])->name('edit');
            Route::put('/{caseStudy}', [SuperAdminCaseStudyController::class, 'update'])->name('update');
            Route::delete('/{caseStudy}', [SuperAdminCaseStudyController::class, 'destroy'])->name('destroy');
            Route::post('/{caseStudy}/toggle-publish', [SuperAdminCaseStudyController::class, 'togglePublish'])->name('toggle-publish');
            Route::post('/{caseStudy}/toggle-featured', [SuperAdminCaseStudyController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/reorder', [SuperAdminCaseStudyController::class, 'reorder'])->name('reorder');
        });

        // FAQ Categories
        Route::prefix('faq-categories')->name('faq-categories.')->group(function () {
            Route::get('/', [SuperAdminFaqCategoryController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminFaqCategoryController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminFaqCategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [SuperAdminFaqCategoryController::class, 'show'])->name('show');
            Route::get('/{category}/edit', [SuperAdminFaqCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [SuperAdminFaqCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [SuperAdminFaqCategoryController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [SuperAdminFaqCategoryController::class, 'reorder'])->name('reorder');
        });

        // FAQ Items
        Route::prefix('faqs')->name('faqs.')->group(function () {
            Route::get('/', [SuperAdminFaqController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminFaqController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminFaqController::class, 'store'])->name('store');
            Route::get('/{faq}', [SuperAdminFaqController::class, 'show'])->name('show');
            Route::get('/{faq}/edit', [SuperAdminFaqController::class, 'edit'])->name('edit');
            Route::put('/{faq}', [SuperAdminFaqController::class, 'update'])->name('update');
            Route::delete('/{faq}', [SuperAdminFaqController::class, 'destroy'])->name('destroy');
            Route::post('/{faq}/toggle-active', [SuperAdminFaqController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{faq}/toggle-featured', [SuperAdminFaqController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/reorder', [SuperAdminFaqController::class, 'reorder'])->name('reorder');
        });

        // Team Members
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [SuperAdminTeamController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminTeamController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminTeamController::class, 'store'])->name('store');
            Route::get('/{member}', [SuperAdminTeamController::class, 'show'])->name('show');
            Route::get('/{member}/edit', [SuperAdminTeamController::class, 'edit'])->name('edit');
            Route::put('/{member}', [SuperAdminTeamController::class, 'update'])->name('update');
            Route::delete('/{member}', [SuperAdminTeamController::class, 'destroy'])->name('destroy');
            Route::post('/{member}/toggle-active', [SuperAdminTeamController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{member}/toggle-featured', [SuperAdminTeamController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/reorder', [SuperAdminTeamController::class, 'reorder'])->name('reorder');
        });

        // Partners
        Route::prefix('partners')->name('partners.')->group(function () {
            Route::get('/', [SuperAdminPartnerController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminPartnerController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminPartnerController::class, 'store'])->name('store');
            Route::get('/{partner}', [SuperAdminPartnerController::class, 'show'])->name('show');
            Route::get('/{partner}/edit', [SuperAdminPartnerController::class, 'edit'])->name('edit');
            Route::put('/{partner}', [SuperAdminPartnerController::class, 'update'])->name('update');
            Route::delete('/{partner}', [SuperAdminPartnerController::class, 'destroy'])->name('destroy');
            Route::post('/{partner}/toggle-active', [SuperAdminPartnerController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{partner}/toggle-featured', [SuperAdminPartnerController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/reorder', [SuperAdminPartnerController::class, 'reorder'])->name('reorder');
        });

        // Blog Categories
        Route::prefix('blog-categories')->name('blog-categories.')->group(function () {
            Route::get('/', [SuperAdminBlogCategoryController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminBlogCategoryController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminBlogCategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [SuperAdminBlogCategoryController::class, 'show'])->name('show');
            Route::get('/{category}/edit', [SuperAdminBlogCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [SuperAdminBlogCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [SuperAdminBlogCategoryController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [SuperAdminBlogCategoryController::class, 'reorder'])->name('reorder');
        });

        // Blog Posts
        Route::prefix('blog')->name('blog.')->group(function () {
            Route::get('/', [SuperAdminBlogController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminBlogController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminBlogController::class, 'store'])->name('store');
            Route::get('/{post}', [SuperAdminBlogController::class, 'show'])->name('show');
            Route::get('/{post}/edit', [SuperAdminBlogController::class, 'edit'])->name('edit');
            Route::put('/{post}', [SuperAdminBlogController::class, 'update'])->name('update');
            Route::delete('/{post}', [SuperAdminBlogController::class, 'destroy'])->name('destroy');
            Route::post('/{post}/toggle-publish', [SuperAdminBlogController::class, 'togglePublish'])->name('toggle-publish');
            Route::post('/{post}/toggle-featured', [SuperAdminBlogController::class, 'toggleFeatured'])->name('toggle-featured');
        });

        // Navigation Menus
        Route::prefix('navigation')->name('navigation.')->group(function () {
            Route::get('/', [SuperAdminNavigationController::class, 'index'])->name('index');
            Route::get('/menus/{menu}', [SuperAdminNavigationController::class, 'showMenu'])->name('menu');
            Route::post('/menus/{menu}/items', [SuperAdminNavigationController::class, 'storeItem'])->name('items.store');
            Route::put('/items/{item}', [SuperAdminNavigationController::class, 'updateItem'])->name('items.update');
            Route::delete('/items/{item}', [SuperAdminNavigationController::class, 'destroyItem'])->name('items.destroy');
            Route::post('/items/{item}/toggle-active', [SuperAdminNavigationController::class, 'toggleItemActive'])->name('items.toggle-active');
            Route::post('/menus/{menu}/reorder', [SuperAdminNavigationController::class, 'reorderItems'])->name('items.reorder');
        });

        // Website Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SuperAdminWebsiteSettingsController::class, 'index'])->name('index');
            Route::post('/', [SuperAdminWebsiteSettingsController::class, 'update'])->name('update');
            Route::get('/seo', [SuperAdminWebsiteSettingsController::class, 'seo'])->name('seo');
            Route::post('/seo', [SuperAdminWebsiteSettingsController::class, 'updateSeo'])->name('seo.update');
            Route::get('/social', [SuperAdminWebsiteSettingsController::class, 'social'])->name('social');
            Route::post('/social', [SuperAdminWebsiteSettingsController::class, 'updateSocial'])->name('social.update');
            Route::get('/analytics', [SuperAdminWebsiteSettingsController::class, 'analytics'])->name('analytics');
            Route::post('/analytics', [SuperAdminWebsiteSettingsController::class, 'updateAnalytics'])->name('analytics.update');
        });
    });
});
