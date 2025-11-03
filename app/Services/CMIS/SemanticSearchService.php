<?php

namespace App\Services\CMIS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SemanticSearchService
{
    private GeminiEmbeddingService $embeddingService;
    
    public function __construct(GeminiEmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }
    
    /**
     * Perform semantic search
     */
    public function search(
        string $query,
        ?string $intent = null,
        ?string $direction = null,
        ?string $purpose = null,
        int $limit = 10,
        float $threshold = 0.7
    ): array {
        // Generate query embeddings
        $embeddings = $this->generateQueryEmbeddings($query, $intent, $direction, $purpose);
        
        // Execute search
        $results = $this->executeSearch($embeddings, $limit, $threshold);
        
        // Log search
        $this->logSearch($query, $intent, $direction, $purpose, count($results));
        
        return $results;
    }
    
    /**
     * Generate embeddings for search query
     */
    private function generateQueryEmbeddings(
        string $query,
        ?string $intent,
        ?string $direction,
        ?string $purpose
    ): array {
        $embeddings = [
            'query' => $this->embeddingService->generateEmbedding($query, 'RETRIEVAL_QUERY')
        ];
        
        if ($intent) {
            $embeddings['intent'] = $this->embeddingService->generateEmbedding($intent, 'RETRIEVAL_QUERY');
        }
        
        if ($direction) {
            $embeddings['direction'] = $this->embeddingService->generateEmbedding($direction, 'RETRIEVAL_QUERY');
        }
        
        if ($purpose) {
            $embeddings['purpose'] = $this->embeddingService->generateEmbedding($purpose, 'RETRIEVAL_QUERY');
        }
        
        return $embeddings;
    }
    
    /**
     * Execute semantic search in database
     */
    private function executeSearch(array $embeddings, int $limit, float $threshold): array
    {
        $queryEmbedding = '[' . implode(',', $embeddings['query']) . ']';
        
        // Check cache first
        $cacheKey = 'search_' . md5(json_encode($embeddings) . $limit . $threshold);
        
        return Cache::remember($cacheKey, config('cmis-embeddings.search.cache_ttl_seconds'), 
            function () use ($queryEmbedding, $threshold, $limit) {
                
                $query = "
                    WITH scored_results AS (
                        SELECT 
                            ki.knowledge_id,
                            ki.domain,
                            ki.topic,
                            ki.category,
                            ki.tier,
                            ki.keywords,
                            1 - (ki.topic_embedding <=> ?::vector) AS similarity,
                            COALESCE(kd.content, km.content, ko.content, kr.content) AS content
                        FROM cmis_knowledge.index ki
                        LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
                        LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
                        LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
                        LEFT JOIN cmis_knowledge.research kr USING (knowledge_id)
                        WHERE 
                            ki.topic_embedding IS NOT NULL
                            AND ki.is_deprecated = false
                    )
                    SELECT *
                    FROM scored_results
                    WHERE similarity >= ?
                    ORDER BY similarity DESC
                    LIMIT ?
                ";
                
                return DB::connection(config('cmis-embeddings.database.connection'))
                    ->select($query, [$queryEmbedding, $threshold, $limit]);
            });
    }
    
    /**
     * Advanced search with multiple embeddings
     */
    public function advancedSearch(array $criteria): array
    {
        $embeddings = [];
        
        // Generate embeddings for each criterion
        foreach ($criteria as $field => $value) {
            if ($value) {
                $embeddings[$field] = $this->embeddingService->generateEmbedding($value, 'RETRIEVAL_QUERY');
            }
        }
        
        // Build dynamic query based on available embeddings
        return $this->executeAdvancedSearch($embeddings, 
            $criteria['limit'] ?? 10, 
            $criteria['threshold'] ?? 0.7
        );
    }
    
    /**
     * Execute advanced search with multiple embeddings
     */
    private function executeAdvancedSearch(array $embeddings, int $limit, float $threshold): array
    {
        $selectParts = [];
        $whereParts = [];
        $params = [];
        
        foreach ($embeddings as $field => $embedding) {
            $embeddingStr = '[' . implode(',', $embedding) . ']';
            $selectParts[] = "1 - (ki.{$field}_embedding <=> ?::vector) AS {$field}_similarity";
            $params[] = $embeddingStr;
        }
        
        // Calculate average similarity
        $similarityFields = array_map(fn($field) => "{$field}_similarity", array_keys($embeddings));
        $avgSimilarity = '(' . implode(' + ', $similarityFields) . ') / ' . count($similarityFields);
        
        $query = "
            WITH scored_results AS (
                SELECT 
                    ki.*,
                    " . implode(', ', $selectParts) . ",
                    {$avgSimilarity} AS avg_similarity
                FROM cmis_knowledge.index ki
                WHERE 
                    ki.topic_embedding IS NOT NULL
                    AND ki.is_deprecated = false
            )
            SELECT *
            FROM scored_results
            WHERE avg_similarity >= ?
            ORDER BY avg_similarity DESC
            LIMIT ?
        ";
        
        $params[] = $threshold;
        $params[] = $limit;
        
        return DB::connection(config('cmis-embeddings.database.connection'))
            ->select($query, $params);
    }
    
    /**
     * Find similar knowledge items
     */
    public function findSimilar(string $knowledgeId, int $limit = 5): array
    {
        // Get the item's embedding
        $item = DB::connection(config('cmis-embeddings.database.connection'))
            ->table('cmis_knowledge.index')
            ->where('knowledge_id', $knowledgeId)
            ->first();
            
        if (!$item || !$item->topic_embedding) {
            return [];
        }
        
        $query = "
            SELECT 
                knowledge_id,
                domain,
                topic,
                category,
                1 - (topic_embedding <=> ?::vector) AS similarity
            FROM cmis_knowledge.index
            WHERE 
                knowledge_id != ?
                AND topic_embedding IS NOT NULL
                AND is_deprecated = false
            ORDER BY topic_embedding <=> ?::vector
            LIMIT ?
        ";
        
        return DB::connection(config('cmis-embeddings.database.connection'))
            ->select($query, [
                $item->topic_embedding,
                $knowledgeId,
                $item->topic_embedding,
                $limit
            ]);
    }
    
    /**
     * Log search query
     */
    private function logSearch(
        string $query,
        ?string $intent,
        ?string $direction,
        ?string $purpose,
        int $resultCount
    ): void {
        $queryHash = md5($query . $intent . $direction . $purpose);
        
        DB::connection(config('cmis-embeddings.database.connection'))
            ->table('cmis_knowledge.semantic_search_results_cache')
            ->insert([
                'query_hash' => $queryHash,
                'query_text' => $query,
                'intent' => $intent,
                'direction' => $direction,
                'purpose' => $purpose,
                'result_count' => $resultCount,
                'created_at' => now()
            ]);
    }
}