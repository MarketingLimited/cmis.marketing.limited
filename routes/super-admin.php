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
    });
});
