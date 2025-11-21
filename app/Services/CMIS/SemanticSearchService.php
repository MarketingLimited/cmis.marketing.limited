<?php

namespace App\Services\CMIS;

use App\Services\Embedding\EmbeddingOrchestrator;
use App\Services\Gemini\EmbeddingService as GeminiEmbeddingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Semantic Search Service (Phase 1 Week 3 - Task 3.2)
 *
 * Provides semantic search capabilities using pgvector extension
 * and Google Gemini embeddings for multi-modal similarity search.
 *
 * Features:
 * - Campaign semantic search
 * - Content semantic search
 * - Creative assets semantic search
 * - Similar entity finding
 * - Universal multi-entity search
 * - Query result caching
 *
 * Performance:
 * - Uses IVFFlat indexes for O(sqrt(n)) query time
 * - Cosine similarity metric (1 - cosine_distance)
 * - Cache layer for frequent queries (1 hour TTL)
 */
class SemanticSearchService
{
    protected EmbeddingOrchestrator $embeddingOrchestrator;
    protected ?GeminiEmbeddingService $geminiService;

    public function __construct(
        EmbeddingOrchestrator $embeddingOrchestrator,
        ?GeminiEmbeddingService $geminiService = null
    ) {
        $this->embeddingOrchestrator = $embeddingOrchestrator;
        $this->geminiService = $geminiService ?? app(GeminiEmbeddingService::class);
    }

    /**
     * Perform semantic search across campaigns
     *
     * @param string $query The search query
     * @param int $limit Number of results to return
     * @param float $threshold Similarity threshold (0.0-1.0)
     * @return array
     */
    public function searchCampaigns(string $query, int $limit = 10, float $threshold = 0.7): array
    {
        try {
            // Generate embedding for search query
            $queryEmbedding = $this->embeddingOrchestrator->generateEmbedding(
                $query,
                auth()->user()->current_org_id ?? auth()->user()->org_id ?? null,
                'RETRIEVAL_QUERY'
            );

            if (!$queryEmbedding) {
                Log::error('Failed to generate query embedding');
                return ['success' => false, 'error' => 'Failed to generate embedding'];
            }

            // Format as PostgreSQL vector literal
            $vectorLiteral = '[' . implode(',', $queryEmbedding) . ']';

            // Perform vector similarity search with RLS
            $results = DB::select("
                WITH ranked_campaigns AS (
                    SELECT
                        c.campaign_id,
                        c.name AS campaign_name,
                        c.description,
                        c.status,
                        c.created_at,
                        e.embedding,
                        1 - (e.embedding <=> ?::vector) AS similarity
                    FROM cmis_ai.campaign_embeddings e
                    JOIN cmis.campaigns c ON e.campaign_id = c.campaign_id
                    WHERE 1 - (e.embedding <=> ?::vector) >= ?
                )
                SELECT campaign_id, campaign_name, description, status, created_at, similarity
                FROM ranked_campaigns
                ORDER BY similarity DESC
                LIMIT ?
            ", [$vectorLiteral, $vectorLiteral, $threshold, $limit]);

            return [
                'success' => true,
                'query' => $query,
                'results' => array_map(function($result) {
                    return [
                        'campaign_id' => $result->campaign_id,
                        'campaign_name' => $result->campaign_name,
                        'description' => $result->description,
                        'status' => $result->status,
                        'similarity' => round($result->similarity, 4),
                        'created_at' => $result->created_at
                    ];
                }, $results),
                'count' => count($results)
            ];

        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Find similar campaigns based on a campaign ID
     *
     * @param string $campaignId The reference campaign ID
     * @param int $limit Number of similar campaigns to return
     * @return array
     */
    public function findSimilarCampaigns(string $campaignId, int $limit = 5): array
    {
        try {
            // Find similar campaigns using vector similarity
            $results = DB::select("
                WITH target_campaign AS (
                    SELECT embedding
                    FROM cmis_ai.campaign_embeddings
                    WHERE campaign_id = ?
                )
                SELECT
                    c.campaign_id,
                    c.name AS campaign_name,
                    c.description,
                    c.status,
                    1 - (e.embedding <=> t.embedding) AS similarity
                FROM cmis_ai.campaign_embeddings e
                JOIN cmis.campaigns c ON e.campaign_id = c.campaign_id
                CROSS JOIN target_campaign t
                WHERE c.campaign_id != ?
                ORDER BY e.embedding <=> t.embedding
                LIMIT ?
            ", [$campaignId, $campaignId, $limit]);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'similar_campaigns' => array_map(function($result) {
                    return [
                        'campaign_id' => $result->campaign_id,
                        'campaign_name' => $result->campaign_name,
                        'description' => $result->description,
                        'status' => $result->status,
                        'similarity' => round($result->similarity, 4)
                    ];
                }, $results),
                'count' => count($results)
            ];

        } catch (\Exception $e) {
            Log::error('Similar campaigns search failed', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search with caching for frequently used queries
     *
     * @param string $query The search query
     * @param int $limit Number of results
     * @param float $threshold Similarity threshold
     * @return array
     */
    public function searchWithCache(string $query, int $limit = 10, float $threshold = 0.7): array
    {
        // Generate cache key including org context
        $orgId = auth()->user()->current_org_id ?? auth()->user()->org_id ?? 'global';
        $cacheKey = "semantic_search:{$orgId}:" . md5($query . $limit . $threshold);

        return Cache::remember($cacheKey, now()->addHours(1), function() use ($query, $limit, $threshold) {
            return $this->searchCampaigns($query, $limit, $threshold);
        });
    }

    /**
     * Perform search across multiple entity types
     *
     * @param string $query The search query
     * @param int $limit Number of results per entity type
     * @param array $entityTypes Entity types to search (campaigns, content, creatives)
     * @return array
     */
    public function universalSearch(string $query, int $limit = 10, array $entityTypes = ['campaigns', 'content', 'creatives']): array
    {
        try {
            $queryEmbedding = $this->embeddingOrchestrator->generateEmbedding(
                $query,
                auth()->user()->current_org_id ?? auth()->user()->org_id ?? null,
                'RETRIEVAL_QUERY'
            );

            if (!$queryEmbedding) {
                return ['success' => false, 'error' => 'Failed to generate embedding'];
            }

            $vectorLiteral = '[' . implode(',', $queryEmbedding) . ']';

            // Search across multiple tables
            $results = [];

            if (in_array('campaigns', $entityTypes)) {
                $results['campaigns'] = $this->searchInTable(
                    'cmis_ai.campaign_embeddings',
                    'cmis.campaigns',
                    'campaign_id',
                    'name as title',
                    $vectorLiteral,
                    $limit
                );
            }

            if (in_array('content', $entityTypes)) {
                $results['content'] = $this->searchInTable(
                    'cmis_ai.content_embeddings',
                    'cmis.content_plans',
                    'plan_id',
                    'plan_title as title',
                    $vectorLiteral,
                    $limit
                );
            }

            if (in_array('creatives', $entityTypes)) {
                $results['creatives'] = $this->searchInTable(
                    'cmis_ai.creative_embeddings',
                    'cmis.ad_creatives',
                    'creative_id',
                    'creative_title as title',
                    $vectorLiteral,
                    $limit
                );
            }

            return [
                'success' => true,
                'query' => $query,
                'results' => $results,
                'entity_types' => $entityTypes
            ];

        } catch (\Exception $e) {
            Log::error('Universal search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search in a specific embedding table
     *
     * @param string $embeddingTable The embedding table name
     * @param string $dataTable The data table name
     * @param string $idColumn The ID column name
     * @param string $titleColumn The title column name
     * @param string $vectorLiteral The query vector as PostgreSQL literal
     * @param int $limit Number of results
     * @return array
     */
    private function searchInTable(
        string $embeddingTable,
        string $dataTable,
        string $idColumn,
        string $titleColumn,
        string $vectorLiteral,
        int $limit
    ): array {
        try {
            // Check if embedding table exists
            $tableExists = DB::select("
                SELECT EXISTS (
                    SELECT FROM pg_tables
                    WHERE schemaname || '.' || tablename = ?
                )
            ", [$embeddingTable]);

            if (!$tableExists[0]->exists) {
                return [];
            }

            $results = DB::select("
                SELECT
                    d.{$idColumn} as id,
                    d.{$titleColumn},
                    1 - (e.embedding <=> ?::vector) AS similarity
                FROM {$embeddingTable} e
                JOIN {$dataTable} d ON e.{$idColumn} = d.{$idColumn}
                WHERE 1 - (e.embedding <=> ?::vector) >= 0.6
                ORDER BY e.embedding <=> ?::vector
                LIMIT ?
            ", [$vectorLiteral, $vectorLiteral, $vectorLiteral, $limit]);

            return array_map(function($result) {
                return [
                    'id' => $result->id,
                    'title' => $result->title,
                    'similarity' => round($result->similarity, 4)
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error("Search failed in {$embeddingTable}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Legacy method for backward compatibility
     * Delegates to searchCampaigns()
     *
     * @deprecated Use searchCampaigns() instead
     */
    public function search(string $query, int $limit = 10, ?float $threshold = 0.7): array
    {
        return $this->searchCampaigns($query, $limit, $threshold ?? 0.7);
    }

    /**
     * Generate embedding for text (legacy method)
     * Delegates to EmbeddingOrchestrator
     *
     * @deprecated Use EmbeddingOrchestrator directly
     */
    public function generateEmbedding(string $text): ?array
    {
        return $this->embeddingOrchestrator->generateEmbedding($text);
    }

    /**
     * Find similar content items by content ID
     *
     * @param string $contentId The reference content ID
     * @param int $limit Number of similar items to return
     * @return array
     */
    public function findSimilar(string $contentId, int $limit = 5): array
    {
        try {
            $results = DB::select("
                WITH target_content AS (
                    SELECT embedding
                    FROM cmis_ai.content_embeddings
                    WHERE content_id = ?
                )
                SELECT
                    c.content_id,
                    c.title,
                    c.content_type,
                    1 - (e.embedding <=> t.embedding) AS similarity
                FROM cmis_ai.content_embeddings e
                JOIN cmis.content_items c ON e.content_id = c.content_id
                CROSS JOIN target_content t
                WHERE c.content_id != ?
                ORDER BY e.embedding <=> t.embedding
                LIMIT ?
            ", [$contentId, $contentId, $limit]);

            return [
                'success' => true,
                'content_id' => $contentId,
                'similar_items' => array_map(function($result) {
                    return [
                        'content_id' => $result->content_id,
                        'title' => $result->title,
                        'content_type' => $result->content_type,
                        'similarity' => round($result->similarity, 4)
                    ];
                }, $results),
                'count' => count($results)
            ];

        } catch (\Exception $e) {
            Log::error('Similar content search failed', [
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
