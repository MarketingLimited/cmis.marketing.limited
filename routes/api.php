<?php

use App\Http\Controllers\API\CMISEmbeddingController;

Route::prefix('cmis')->group(function () {
    Route::post('/search', [CMISEmbeddingController::class, 'search']);
    Route::post('/knowledge/{id}/process', [CMISEmbeddingController::class, 'processKnowledge']);
    Route::get('/knowledge/{id}/similar', [CMISEmbeddingController::class, 'findSimilar']);
    Route::get('/status', [CMISEmbeddingController::class, 'status']);
});\nuse App\Http\Controllers\API\SemanticSearchController;\nRoute::post('/semantic-search', [SemanticSearchController::class, 'search'])->middleware(['auth:sanctum'])->name('api.semantic.search');\n
