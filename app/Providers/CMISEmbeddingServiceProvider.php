<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CMIS\GeminiEmbeddingService;
use App\Services\CMIS\KnowledgeEmbeddingProcessor;
use App\Services\CMIS\SemanticSearchService;

class CMISEmbeddingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Gemini Service as singleton
        $this->app->singleton(GeminiEmbeddingService::class, function ($app) {
            return new GeminiEmbeddingService(
                config('cmis-embeddings.gemini')
            );
        });

        // Bind Knowledge Processor
        $this->app->bind(KnowledgeEmbeddingProcessor::class, function ($app) {
            return new KnowledgeEmbeddingProcessor(
                $app->make(GeminiEmbeddingService::class)
            );
        });

        // Bind Semantic Search Service
        $this->app->bind(SemanticSearchService::class, function ($app) {
            return new SemanticSearchService(
                $app->make(GeminiEmbeddingService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/cmis-embeddings.php' => config_path('cmis-embeddings.php'),
        ], 'cmis-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ProcessEmbeddings::class,
                \App\Console\Commands\SearchKnowledge::class,
                \App\Console\Commands\UpdateKnowledge::class,
                \App\Console\Commands\SystemStatus::class,
            ]);
        }
    }
}