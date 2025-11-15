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
        ->name('semantic-search');

    // البحث الهجين (نصي + vector)
    Route::post('/hybrid-search', [VectorEmbeddingsV2Controller::class, 'hybridSearch'])
        ->name('hybrid-search');

    // تحميل السياق الذكي v2
    Route::post('/smart-context', [VectorEmbeddingsV2Controller::class, 'smartContextLoader'])
        ->name('smart-context');

    /*
    |--------------------------------------------------------------------------
    | إدارة المعرفة (Knowledge Management)
    |--------------------------------------------------------------------------
    */

    // تسجيل معرفة جديدة مع vectors مخصصة
    Route::post('/register-knowledge', [VectorEmbeddingsV2Controller::class, 'registerKnowledgeWithVectors'])
        ->name('register-knowledge');

    /*
    |--------------------------------------------------------------------------
    | معالجة Embeddings (Processing)
    |--------------------------------------------------------------------------
    */

    // معالجة قائمة انتظار Embeddings
    Route::post('/process-queue', [VectorEmbeddingsV2Controller::class, 'processQueue'])
        ->name('process-queue');

    /*
    |--------------------------------------------------------------------------
    | المراقبة والإحصائيات (Monitoring & Analytics)
    |--------------------------------------------------------------------------
    */

    // حالة Embeddings
    Route::get('/embedding-status', [VectorEmbeddingsV2Controller::class, 'embeddingStatus'])
        ->name('embedding-status');

    // تحليل النوايا
    Route::get('/intent-analysis', [VectorEmbeddingsV2Controller::class, 'intentAnalysis'])
        ->name('intent-analysis');

    // حالة قائمة الانتظار
    Route::get('/queue-status', [VectorEmbeddingsV2Controller::class, 'queueStatus'])
        ->name('queue-status');

    // أداء البحث
    Route::get('/search-performance', [VectorEmbeddingsV2Controller::class, 'searchPerformance'])
        ->name('search-performance');

    // تقرير شامل للنظام
    Route::get('/system-report', [VectorEmbeddingsV2Controller::class, 'systemReport'])
        ->name('system-report');

    // التحقق من التثبيت
    Route::get('/verify-installation', [VectorEmbeddingsV2Controller::class, 'verifyInstallation'])
        ->name('verify-installation');
});
