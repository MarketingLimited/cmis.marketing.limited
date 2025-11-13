<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface KnowledgeRepositoryInterface
{
    /**
     * Register new knowledge entry
     */
    public function registerKnowledge(
        string $domain,
        string $category,
        string $topic,
        string $content,
        int $tier = 2,
        array $keywords = []
    ): string;

    /**
     * Auto analyze knowledge based on query
     */
    public function autoAnalyzeKnowledge(
        string $query,
        ?string $domain = null,
        string $category = 'dev',
        int $maxBatches = 5,
        int $batchLimit = 20
    ): ?object;

    /**
     * Auto retrieve knowledge with batching
     */
    public function autoRetrieveKnowledge(
        string $query,
        ?string $domain = null,
        string $category = 'dev',
        int $maxBatches = 5,
        int $batchLimit = 20
    ): Collection;

    /**
     * Smart context loader for retrieving relevant knowledge
     */
    public function smartContextLoader(
        string $query,
        ?string $domain = null,
        string $category = 'dev',
        int $tokenLimit = 5000
    ): ?object;

    /**
     * Generate system report
     */
    public function generateSystemReport(): ?object;

    /**
     * Semantic analysis of search logs
     */
    public function semanticAnalysis(): Collection;

    /**
     * Advanced semantic search with multiple parameters
     */
    public function semanticSearchAdvanced(
        string $query,
        ?string $intent = null,
        ?string $direction = null,
        ?string $purpose = null,
        ?string $category = null,
        int $limit = 10,
        float $threshold = 0.3
    ): Collection;

    /**
     * Cleanup old embeddings and cache
     */
    public function cleanupOldEmbeddings(): bool;

    /**
     * Verify knowledge system installation
     */
    public function verifyInstallation(): ?object;
}
