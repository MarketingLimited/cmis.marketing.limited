<?php

use App\Http\Controllers\API\VectorEmbeddingsV2Controller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vector Embeddings v2.0 API Routes
|--------------------------------------------------------------------------
|
| جميع API endpoints لنظام Vector Embeddings v2.0
| يتضمن: البحث الدلالي، البحث الهجين، معالجة القائمة، المراقبة، إلخ
|
*/

Route::prefix('v2/vector')->name('vector.v2.')->middleware(['api'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | البحث (Search)
    |--------------------------------------------------------------------------
    */

    // البحث الدلالي المتقدم مع النوايا والمقاصد
    Route::post('/semantic-search', [VectorEmbeddingsV2Controller::class, 'semanticSearchAdvanced'])
        ->name('semantic-search')
        ->middleware(['permission:vector.semantic_search', 'throttle:60,1']);

    // البحث الهجين (نصي + vector)
    Route::post('/hybrid-search', [VectorEmbeddingsV2Controller::class, 'hybridSearch'])
        ->name('hybrid-search')
        ->middleware(['permission:vector.hybrid_search', 'throttle:60,1']);

    // تحميل السياق الذكي v2
    Route::post('/smart-context', [VectorEmbeddingsV2Controller::class, 'smartContextLoader'])
        ->name('smart-context')
        ->middleware(['permission:vector.load_context', 'throttle:100,1']);

    /*
    |--------------------------------------------------------------------------
    | إدارة المعرفة (Knowledge Management)
    |--------------------------------------------------------------------------
    */

    // تسجيل معرفة جديدة مع vectors مخصصة
    Route::post('/register-knowledge', [VectorEmbeddingsV2Controller::class, 'registerKnowledgeWithVectors'])
        ->name('register-knowledge')
        ->middleware(['permission:knowledge.register', 'throttle:30,1']);

    /*
    |--------------------------------------------------------------------------
    | معالجة Embeddings (Processing)
    |--------------------------------------------------------------------------
    */

    // معالجة قائمة انتظار Embeddings
    Route::post('/process-queue', [VectorEmbeddingsV2Controller::class, 'processQueue'])
        ->name('process-queue')
        ->middleware(['permission:vector.process_queue', 'throttle:10,1']);

    /*
    |--------------------------------------------------------------------------
    | المراقبة والإحصائيات (Monitoring & Analytics)
    |--------------------------------------------------------------------------
    */

    // حالة Embeddings
    Route::get('/embedding-status', [VectorEmbeddingsV2Controller::class, 'embeddingStatus'])
        ->name('embedding-status')
        ->middleware(['permission:vector.view_status', 'throttle:120,1']);

    // تحليل النوايا
    Route::get('/intent-analysis', [VectorEmbeddingsV2Controller::class, 'intentAnalysis'])
        ->name('intent-analysis')
        ->middleware(['permission:vector.view_analytics', 'throttle:120,1']);

    // حالة قائمة الانتظار
    Route::get('/queue-status', [VectorEmbeddingsV2Controller::class, 'queueStatus'])
        ->name('queue-status')
        ->middleware(['permission:vector.view_status', 'throttle:120,1']);

    // أداء البحث
    Route::get('/search-performance', [VectorEmbeddingsV2Controller::class, 'searchPerformance'])
        ->name('search-performance')
        ->middleware(['permission:vector.view_analytics', 'throttle:120,1']);

    // تقرير شامل للنظام
    Route::get('/system-report', [VectorEmbeddingsV2Controller::class, 'systemReport'])
        ->name('system-report')
        ->middleware(['permission:vector.view_analytics', 'throttle:60,1']);

    // التحقق من التثبيت
    Route::get('/verify-installation', [VectorEmbeddingsV2Controller::class, 'verifyInstallation'])
        ->name('verify-installation')
        ->middleware(['permission:vector.manage_system', 'throttle:20,1']);
});
