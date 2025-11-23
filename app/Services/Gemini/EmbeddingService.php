<?php

namespace App\Services\Gemini;

/**
 * Google Gemini Embedding Service Stub
 *
 * This is a stub class to satisfy dependency injection requirements.
 * Full implementation should be added later for semantic search functionality.
 */
class EmbeddingService
{
    /**
     * Generate embedding for text
     *
     * @param string $text
     * @return array
     */
    public function generateEmbedding(string $text): array
    {
        // Stub implementation
        return array_fill(0, 768, 0.0);
    }

    /**
     * Generate embeddings for multiple texts
     *
     * @param array $texts
     * @return array
     */
    public function generateEmbeddings(array $texts): array
    {
        // Stub implementation
        return array_map(fn($text) => $this->generateEmbedding($text), $texts);
    }
}
