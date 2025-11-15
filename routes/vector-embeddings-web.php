<?php

use App\Http\Controllers\Web\VectorEmbeddingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vector Embeddings v2.0 Web Routes
|--------------------------------------------------------------------------
|
| واجهات الويب لمراقبة وإدارة نظام Vector Embeddings v2.0
|
*/

Route::prefix('vector-embeddings')->name('vector-embeddings.')->middleware(['auth'])->group(function () {

    // لوحة المعلومات الرئيسية
    Route::get('/dashboard', [VectorEmbeddingsController::class, 'dashboard'])
        ->name('dashboard')
        ->middleware(['permission:vector.view_status']);

    // تحليل النوايا
    Route::get('/intent-analysis', [VectorEmbeddingsController::class, 'intentAnalysis'])
        ->name('intent-analysis')
        ->middleware(['permission:vector.view_analytics']);

    // إدارة قائمة الانتظار
    Route::get('/queue', [VectorEmbeddingsController::class, 'queueManager'])
        ->name('queue')
        ->middleware(['permission:vector.view_status']);

    // معالجة قائمة الانتظار (Action)
    Route::post('/queue/process', [VectorEmbeddingsController::class, 'processQueue'])
        ->name('queue.process')
        ->middleware(['permission:vector.process_queue']);

    // البحث الدلالي
    Route::get('/search', [VectorEmbeddingsController::class, 'search'])
        ->name('search')
        ->middleware(['permission:vector.semantic_search']);

    Route::post('/search', [VectorEmbeddingsController::class, 'executeSearch'])
        ->name('search.execute')
        ->middleware(['permission:vector.semantic_search', 'throttle:60,1']);

    // إحصائيات الأداء
    Route::get('/performance', [VectorEmbeddingsController::class, 'performance'])
        ->name('performance')
        ->middleware(['permission:vector.view_analytics']);

    // API للبيانات الحية (للـ charts)
    Route::get('/api/live-data', [VectorEmbeddingsController::class, 'liveData'])
        ->name('api.live-data')
        ->middleware(['permission:vector.view_status', 'throttle:120,1']);
});
