<?php

namespace App\Services\Embedding;

interface EmbeddingProviderInterface
{
    /**
     * Generate embedding for a single text
     *
     * @param string $text
     * @param string $taskType
     * @return array
     */
    public function generateEmbedding(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array;

    /**
     * Generate embeddings for multiple texts
     *
     * @param array $texts
     * @param string $taskType
     * @return array
     */
    public function generateBatchEmbeddings(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT'): array;

    /**
     * Get embedding dimension
     *
     * @return int
     */
    public function getDimension(): int;

    /**
     * Get provider name
     *
     * @return string
     */
    public function getName(): string;
}
