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
        ->name('dashboard');

    // تحليل النوايا
    Route::get('/intent-analysis', [VectorEmbeddingsController::class, 'intentAnalysis'])
        ->name('intent-analysis');

    // إدارة قائمة الانتظار
    Route::get('/queue', [VectorEmbeddingsController::class, 'queueManager'])
        ->name('queue');

    // معالجة قائمة الانتظار (Action)
    Route::post('/queue/process', [VectorEmbeddingsController::class, 'processQueue'])
        ->name('queue.process');

    // البحث الدلالي
    Route::get('/search', [VectorEmbeddingsController::class, 'search'])
        ->name('search');

    Route::post('/search', [VectorEmbeddingsController::class, 'executeSearch'])
        ->name('search.execute');

    // إحصائيات الأداء
    Route::get('/performance', [VectorEmbeddingsController::class, 'performance'])
        ->name('performance');

    // API للبيانات الحية (للـ charts)
    Route::get('/api/live-data', [VectorEmbeddingsController::class, 'liveData'])
        ->name('api.live-data');
});
