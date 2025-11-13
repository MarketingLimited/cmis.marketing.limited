<?php

namespace App\Repositories\Contracts;

interface EmbeddingRepositoryInterface
{
    /**
     * Generate embedding for text (improved version)
     */
    public function generateEmbedding(string $text): string;

    /**
     * Generate mock embedding for text
     */
    public function generateMockEmbedding(string $text): string;

    /**
     * Batch update embeddings
     */
    public function batchUpdateEmbeddings(int $batchSize = 100, ?string $category = null): ?object;

    /**
     * Update single embedding
     */
    public function updateSingleEmbedding(string $knowledgeId): ?object;
}
