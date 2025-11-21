<?php

namespace App\Repositories\Knowledge;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Knowledge Functions
 * Encapsulates PostgreSQL functions related to knowledge management
 */
class KnowledgeRepository
{
    /**
     * Register new knowledge entry
     * Corresponds to: cmis_knowledge.register_knowledge()
     *
     * @param string $domain Domain of knowledge
     * @param string $category Category (dev, marketing, org, research)
     * @param string $topic Topic/title
     * @param string $content Content text
     * @param int $tier Priority tier (default: 2)
     * @param array $keywords Array of keywords (optional)
     * @return string UUID of created knowledge entry
     */
    public function registerKnowledge(
        string $domain,
        string $category,
        string $topic,
        string $content,
        int $tier = 2,
        array $keywords = []
    ): string {
        // Security: Use JSON binding instead of raw SQL string concatenation
        $keywordsJson = json_encode($keywords);

        $result = DB::select(
            'SELECT cmis_knowledge.register_knowledge(?, ?, ?, ?, ?,
                ARRAY(SELECT jsonb_array_elements_text(?::jsonb))
            ) as knowledge_id',
            [
                $domain,
                $category,
                $topic,
                $content,
                $tier,
                $keywordsJson
            ]
        );

        return $result[0]->knowledge_id;
    }

    /**
     * Auto analyze knowledge based on query
     * Corresponds to: cmis_knowledge.auto_analyze_knowledge()
     *
     * @param string $query Search query
     * @param string|null $domain Domain filter (optional)
     * @param string $category Category filter (default: 'dev')
     * @param int $maxBatches Maximum batches to retrieve (default: 5)
     * @param int $batchLimit Items per batch (default: 20)
     * @return object|null JSON object containing analysis
     */
    public function autoAnalyzeKnowledge(
        string $query,
        ?string $domain = null,
        string $category = 'dev',
        int $maxBatches = 5,
        int $batchLimit = 20
    ): ?object {
        $results = DB::select(
            'SELECT cmis_knowledge.auto_analyze_knowledge(?, ?, ?, ?, ?) as analysis',
            [$query, $domain, $category, $maxBatches, $batchLimit]
        );

        return $results[0]->analysis ?? null;
    }

    /**
     * Auto retrieve knowledge with batching
     * Corresponds to: cmis_knowledge.auto_retrieve_knowledge()
     *
     * @param string $query Search query
     * @param string|null $domain Domain filter (optional)
     * @param string $category Category filter (default: 'dev')
     * @param int $maxBatches Maximum batches to retrieve (default: 5)
     * @param int $batchLimit Items per batch (default: 20)
     * @return Collection Collection of knowledge entries
     */
    public function autoRetrieveKnowledge(
        string $query,
        ?string $domain = null,
        string $category = 'dev',
        int $maxBatches = 5,
        int $batchLimit = 20
    ): Collection {
        $results = DB::select(
            'SELECT * FROM cmis_knowledge.auto_retrieve_knowledge(?, ?, ?, ?, ?)',
            [$query, $domain, $category, $maxBatches, $batchLimit]
        );

        return collect($results);
    }

    /**
     * Smart context loader for retrieving relevant knowledge
     * Corresponds to: cmis_knowledge.smart_context_loader()
     *
     * @param string $query Search query
     * @param string|null $domain Domain filter (optional)
     * @param string $category Category filter (default: 'dev')
     * @param int $tokenLimit Maximum tokens to load (default: 5000)
     * @return object|null JSON object containing loaded context
     */
    public function smartContextLoader(
        string $query,
        ?string $domain = null,
        string $category = 'dev',
        int $tokenLimit = 5000
    ): ?object {
        $results = DB::select(
            'SELECT cmis_knowledge.smart_context_loader(?, ?, ?, ?) as context',
            [$query, $domain, $category, $tokenLimit]
        );

        return $results[0]->context ?? null;
    }

    /**
     * Generate system report
     * Corresponds to: cmis_knowledge.generate_system_report()
     *
     * @return object|null JSON object containing system report
     */
    public function generateSystemReport(): ?object
    {
        $results = DB::select('SELECT cmis_knowledge.generate_system_report() as report');

        return $results[0]->report ?? null;
    }

    /**
     * Semantic analysis of search logs
     * Corresponds to: cmis_knowledge.semantic_analysis()
     *
     * @return Collection Collection of intent analysis results
     */
    public function semanticAnalysis(): Collection
    {
        $results = DB::select('SELECT * FROM cmis_knowledge.semantic_analysis()');

        return collect($results);
    }

    /**
     * Advanced semantic search with multiple parameters
     * Corresponds to: cmis_knowledge.semantic_search_advanced()
     *
     * @param string $query Search query
     * @param string|null $intent Intent filter (optional)
     * @param string|null $direction Direction filter (optional)
     * @param string|null $purpose Purpose filter (optional)
     * @param string|null $category Category filter (optional)
     * @param int $limit Result limit (default: 10)
     * @param float $threshold Similarity threshold (default: 0.3)
     * @return Collection Collection of search results with scores
     */
    public function semanticSearchAdvanced(
        string $query,
        ?string $intent = null,
        ?string $direction = null,
        ?string $purpose = null,
        ?string $category = null,
        int $limit = 10,
        float $threshold = 0.3
    ): Collection {
        $results = DB::select(
            'SELECT * FROM cmis_knowledge.semantic_search_advanced(?, ?, ?, ?, ?, ?, ?)',
            [$query, $intent, $direction, $purpose, $category, $limit, $threshold]
        );

        return collect($results);
    }

    /**
     * Cleanup old embeddings and cache
     * Corresponds to: cmis_knowledge.cleanup_old_embeddings()
     *
     * @return bool Success status
     */
    public function cleanupOldEmbeddings(): bool
    {
        return DB::statement('SELECT cmis_knowledge.cleanup_old_embeddings()');
    }

    /**
     * Verify knowledge system installation
     * Corresponds to: cmis_knowledge.verify_installation()
     *
     * @return object|null JSON object containing verification results
     */
    public function verifyInstallation(): ?object
    {
        $results = DB::select('SELECT cmis_knowledge.verify_installation() as verification');

        return $results[0]->verification ?? null;
    }
}
