<?php

namespace App\Services\Embedding;

use App\Models\Knowledge\EmbeddingsCache;
use App\Repositories\Contracts\EmbeddingRepositoryInterface;
use Illuminate\Support\Facades\Log;

class EmbeddingOrchestrator
{
    protected EmbeddingProviderInterface $provider;
    protected EmbeddingRepositoryInterface $repository;

    public function __construct(
        EmbeddingProviderInterface $provider,
        EmbeddingRepositoryInterface $repository
    ) {
        $this->provider = $provider;
        $this->repository = $repository;
    }

    /**
     * Generate embedding with caching
     */
    public function generateEmbedding(string $content, ?string $orgId = null, string $taskType = 'RETRIEVAL_DOCUMENT'): ?array
    {
        // Check cache first
        $contentHash = md5($content);
        $cached = $this->getCachedEmbedding($contentHash);

        if ($cached) {
            $this->recordCacheAccess($contentHash);
            return $cached;
        }

        // Generate new embedding
        try {
            $embedding = $this->provider->generateEmbedding($content, $taskType);

            // Cache the result
            $this->cacheEmbedding($contentHash, $embedding, $content);

            return $embedding;
        } catch (\Exception $e) {
            Log::error('Embedding generation failed', [
                'provider' => $this->provider->getName(),
                'error' => $e->getMessage(),
                'content_length' => strlen($content),
            ]);
            return null;
        }
    }

    /**
     * Generate batch embeddings with caching
     */
    public function generateBatchEmbeddings(array $contents, ?string $orgId = null, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        $embeddings = [];
        $uncachedContents = [];
        $uncachedIndexes = [];

        // Check cache for each content
        foreach ($contents as $index => $content) {
            $contentHash = md5($content);
            $cached = $this->getCachedEmbedding($contentHash);

            if ($cached) {
                $embeddings[$index] = $cached;
                $this->recordCacheAccess($contentHash);
            } else {
                $uncachedContents[] = $content;
                $uncachedIndexes[] = $index;
            }
        }

        // Generate embeddings for uncached content
        if (!empty($uncachedContents)) {
            try {
                $newEmbeddings = $this->provider->generateBatchEmbeddings($uncachedContents, $taskType);

                foreach ($uncachedIndexes as $i => $originalIndex) {
                    $embedding = $newEmbeddings[$i] ?? null;
                    $embeddings[$originalIndex] = $embedding;

                    // Cache successful embeddings
                    if ($embedding) {
                        $contentHash = md5($uncachedContents[$i]);
                        $this->cacheEmbedding($contentHash, $embedding, $uncachedContents[$i]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Batch embedding generation failed', [
                    'provider' => $this->provider->getName(),
                    'error' => $e->getMessage(),
                    'batch_size' => count($uncachedContents),
                ]);

                // Fill remaining with nulls
                foreach ($uncachedIndexes as $index) {
                    if (!isset($embeddings[$index])) {
                        $embeddings[$index] = null;
                    }
                }
            }
        }

        // Ensure proper ordering
        ksort($embeddings);
        return array_values($embeddings);
    }

    /**
     * Store embedding in database using repository
     */
    public function storeEmbedding(string $entityType, string $entityId, array $embedding, ?array $metadata = null): bool
    {
        try {
            return $this->repository->storeEmbedding($entityType, $entityId, $embedding, $metadata);
        } catch (\Exception $e) {
            Log::error('Failed to store embedding', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Search similar embeddings using repository
     */
    public function searchSimilar(array $queryEmbedding, string $entityType, int $limit = 10, float $threshold = 0.7): array
    {
        try {
            $results = $this->repository->searchSimilarEmbeddings($queryEmbedding, $entityType, $limit, $threshold);
            return $results->toArray();
        } catch (\Exception $e) {
            Log::error('Similarity search failed', [
                'entity_type' => $entityType,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get cached embedding
     */
    protected function getCachedEmbedding(string $contentHash): ?array
    {
        $cached = EmbeddingsCache::where('content_hash', $contentHash)->first();
        return $cached ? $cached->embedding : null;
    }

    /**
     * Cache embedding
     */
    protected function cacheEmbedding(string $contentHash, array $embedding, string $content): void
    {
        try {
            EmbeddingsCache::create([
                'cache_id' => \Illuminate\Support\Str::uuid(),
                'content_hash' => $contentHash,
                'content_type' => 'text',
                'embedding' => $embedding,
                'model_name' => $this->provider->getName(),
                'embedding_dim' => $this->provider->getDimension(),
                'cached_at' => now(),
                'last_accessed' => now(),
                'access_count' => 1,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to cache embedding', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Record cache access
     */
    protected function recordCacheAccess(string $contentHash): void
    {
        try {
            EmbeddingsCache::where('content_hash', $contentHash)->update([
                'last_accessed' => now(),
                'access_count' => \DB::raw('access_count + 1'),
            ]);
        } catch (\Exception $e) {
            // Non-critical, just log
            Log::debug('Failed to record cache access', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
