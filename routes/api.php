<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\API\CMISEmbeddingController;
use App\Http\Controllers\API\SemanticSearchController;

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
    });
});

/*
|--------------------------------------------------------------------------
| مسارات المستخدم (User Level) - بدون org_id
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // قائمة الشركات للمستخدم
    Route::get('/user/orgs', function(\Illuminate\Http\Request $request) {
        return response()->json([
            'orgs' => $request->user()->orgs
        ]);
    })->name('user.orgs');

    // إنشاء شركة جديدة (سيتم إضافة Controller لاحقاً)
    // Route::post('/orgs', [OrgController::class, 'store'])->name('orgs.store');
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
    | الشركة (Organization Info)
    |----------------------------------------------------------------------
    */
    Route::get('/', function($orgId) {
        return response()->json([
            'org' => \App\Models\Org::find($orgId)
        ]);
    })->name('show');

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
    | المسارات الجديدة (سيتم إضافتها تدريجياً)
    |----------------------------------------------------------------------
    */

    // الحملات (Campaigns)
    // Route::apiResource('campaigns', CampaignController::class);

    // المحتوى الإبداعي (Creative Assets)
    // Route::apiResource('creative/assets', CreativeAssetController::class);

    // القنوات (Social Channels)
    // Route::apiResource('channels', ChannelController::class);

    // التحليلات (Analytics)
    // Route::prefix('analytics')->group(function () {
    //     Route::get('/kpis', [KpiController::class, 'index']);
    //     Route::get('/overview', [AnalyticsController::class, 'overview']);
    // });
});

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
