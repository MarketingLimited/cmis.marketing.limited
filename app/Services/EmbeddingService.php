<?php

namespace App\Services;

use App\Models\Knowledge\EmbeddingApiConfig;
use App\Models\Knowledge\EmbeddingApiLog;
use App\Models\Knowledge\EmbeddingsCache;
use App\Models\Knowledge\EmbeddingUpdateQueue;
use App\Models\Knowledge\KnowledgeIndex;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    /**
     * Generate embedding for content
     */
    public function generateEmbedding(string $content, ?string $orgId = null): ?array
    {
        // Check cache first
        $contentHash = md5($content);
        $cached = EmbeddingsCache::findByHash($contentHash);

        if ($cached) {
            $cached->recordAccess();
            return $cached->embedding;
        }

        // Get API config
        $config = $this->getApiConfig($orgId);

        if (!$config) {
            Log::error('No embedding API config found', ['org_id' => $orgId]);
            return null;
        }

        // Make API request
        $startTime = microtime(true);

        try {
            $response = $this->callEmbeddingApi($config, $content);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response || !isset($response['embedding'])) {
                $this->logApiCall($config, 'generate', strlen($content), $responseTime, 500, 'Invalid response');
                return null;
            }

            // Log successful API call
            $this->logApiCall($config, 'generate', strlen($content), $responseTime, 200);

            // Cache the embedding
            EmbeddingsCache::create([
                'cache_id' => \Illuminate\Support\Str::uuid(),
                'content_hash' => $contentHash,
                'content_type' => 'text',
                'embedding' => $response['embedding'],
                'model_name' => $config->model_name,
                'embedding_dim' => count($response['embedding']),
                'cached_at' => now(),
                'last_accessed' => now(),
                'access_count' => 1,
            ]);

            $config->recordUsage(true);

            return $response['embedding'];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logApiCall($config, 'generate', strlen($content), $responseTime, 500, $e->getMessage());
            $config->recordUsage(false);

            Log::error('Embedding generation failed', [
                'error' => $e->getMessage(),
                'content_length' => strlen($content),
            ]);

            return null;
        }
    }

    /**
     * Generate embeddings in batch
     */
    public function generateBatchEmbeddings(array $contents, ?string $orgId = null): array
    {
        $embeddings = [];

        foreach ($contents as $index => $content) {
            $embeddings[$index] = $this->generateEmbedding($content, $orgId);
        }

        return $embeddings;
    }

    /**
     * Index content with embedding
     */
    public function indexContent(
        string $content,
        string $sourceType,
        string $sourceId,
        array $metadata = [],
        ?string $orgId = null
    ): ?KnowledgeIndex {
        $embedding = $this->generateEmbedding($content, $orgId);

        if (!$embedding) {
            return null;
        }

        $knowledgeId = \Illuminate\Support\Str::uuid();

        return KnowledgeIndex::create([
            'knowledge_id' => $knowledgeId,
            'org_id' => $orgId ?? session('current_org_id'),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'title' => $metadata['title'] ?? '',
            'content' => $content,
            'content_summary' => $metadata['summary'] ?? substr($content, 0, 500),
            'embedding' => $embedding,
            'metadata' => $metadata,
            'tags' => $metadata['tags'] ?? [],
            'category' => $metadata['category'] ?? 'general',
            'language' => $metadata['language'] ?? 'en',
            'indexed_at' => now(),
            'access_count' => 0,
            'relevance_score' => 1.0,
            'is_verified' => false,
        ]);
    }

    /**
     * Perform semantic search
     */
    public function semanticSearch(string $query, int $limit = 10, ?string $orgId = null): array
    {
        $queryEmbedding = $this->generateEmbedding($query, $orgId);

        if (!$queryEmbedding) {
            return [];
        }

        return KnowledgeIndex::semanticSearch($queryEmbedding, $limit, $orgId ?? session('current_org_id'))
            ->toArray();
    }

    /**
     * Queue content for embedding
     */
    public function queueForEmbedding(string $content, string $sourceType, string $sourceId, int $priority = 5): void
    {
        EmbeddingUpdateQueue::create([
            'queue_id' => \Illuminate\Support\Str::uuid(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'content' => $content,
            'priority' => $priority,
            'status' => 'pending',
            'retry_count' => 0,
            'queued_at' => now(),
        ]);
    }

    /**
     * Process embedding queue
     */
    public function processQueue(int $batchSize = 10): int
    {
        $items = EmbeddingUpdateQueue::pending()
            ->limit($batchSize)
            ->get();

        $processed = 0;

        foreach ($items as $item) {
            $item->markProcessing();

            try {
                $this->indexContent(
                    $item->content,
                    $item->source_type,
                    $item->source_id,
                    $item->metadata ?? []
                );

                $item->markCompleted();
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to process embedding queue item', [
                    'queue_id' => $item->queue_id,
                    'error' => $e->getMessage(),
                ]);

                $item->markFailed($e->getMessage());

                // Retry if under limit
                if ($item->retry_count < 3) {
                    $item->resetForRetry();
                }
            }
        }

        return $processed;
    }

    /**
     * Get API config for organization
     */
    protected function getApiConfig(?string $orgId = null): ?EmbeddingApiConfig
    {
        $query = EmbeddingApiConfig::active();

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        return $query->where('is_default', true)->first()
            ?? $query->first();
    }

    /**
     * Call embedding API
     */
    protected function callEmbeddingApi(EmbeddingApiConfig $config, string $content): ?array
    {
        // This is a placeholder - implement actual API calls based on provider
        // For OpenAI, Anthropic, etc.

        if ($config->provider_name === 'openai') {
            return $this->callOpenAIEmbedding($config, $content);
        }

        // Add other providers as needed

        return null;
    }

    /**
     * Call OpenAI embedding API
     */
    protected function callOpenAIEmbedding(EmbeddingApiConfig $config, string $content): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($config->api_key_encrypted),
                'Content-Type' => 'application/json',
            ])->post($config->endpoint_url ?? 'https://api.openai.com/v1/embeddings', [
                'model' => $config->model_name ?? 'text-embedding-ada-002',
                'input' => $content,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'embedding' => $data['data'][0]['embedding'] ?? null,
                    'usage' => $data['usage'] ?? null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI embedding API call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Log API call
     */
    protected function logApiCall(
        EmbeddingApiConfig $config,
        string $requestType,
        int $inputTokens,
        int $responseTimeMs,
        int $statusCode,
        ?string $errorMessage = null
    ): void {
        EmbeddingApiLog::create([
            'log_id' => \Illuminate\Support\Str::uuid(),
            'config_id' => $config->config_id,
            'request_type' => $requestType,
            'input_tokens' => $inputTokens,
            'response_time_ms' => $responseTimeMs,
            'status_code' => $statusCode,
            'error_message' => $errorMessage,
            'logged_at' => now(),
        ]);
    }
}
