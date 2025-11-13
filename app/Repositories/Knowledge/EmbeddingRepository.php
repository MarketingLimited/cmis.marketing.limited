<?php

namespace App\Repositories\Knowledge;

use App\Repositories\Contracts\EmbeddingRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Repository for CMIS Knowledge Embedding Functions
 * Encapsulates PostgreSQL functions related to vector embeddings
 */
class EmbeddingRepository implements EmbeddingRepositoryInterface
{
    /**
     * Generate embedding for text (improved version)
     * Corresponds to: cmis_knowledge.generate_embedding_improved()
     *
     * @param string $text Input text to generate embedding for
     * @return string Vector representation (as string)
     */
    public function generateEmbedding(string $text): string
    {
        $result = DB::select(
            'SELECT cmis_knowledge.generate_embedding_improved(?)::text as embedding',
            [$text]
        );

        return $result[0]->embedding ?? '';
    }

    /**
     * Generate mock embedding for text
     * Corresponds to: cmis_knowledge.generate_embedding_mock()
     *
     * @param string $text Input text to generate embedding for
     * @return string Vector representation (as string)
     */
    public function generateMockEmbedding(string $text): string
    {
        $result = DB::select(
            'SELECT cmis_knowledge.generate_embedding_mock(?)::text as embedding',
            [$text]
        );

        return $result[0]->embedding ?? '';
    }

    /**
     * Batch update embeddings
     * Corresponds to: cmis_knowledge.batch_update_embeddings()
     *
     * @param int $batchSize Number of items to process (default: 100)
     * @param string|null $category Category filter (optional)
     * @return object|null JSON object containing batch update results
     */
    public function batchUpdateEmbeddings(int $batchSize = 100, ?string $category = null): ?object
    {
        $results = DB::select(
            'SELECT cmis_knowledge.batch_update_embeddings(?, ?) as result',
            [$batchSize, $category]
        );

        return $results[0]->result ?? null;
    }

    /**
     * Update single embedding
     * Corresponds to: cmis_knowledge.update_single_embedding()
     *
     * @param string $knowledgeId UUID of knowledge entry
     * @return object|null JSON object containing update result
     */
    public function updateSingleEmbedding(string $knowledgeId): ?object
    {
        $results = DB::select(
            'SELECT cmis_knowledge.update_single_embedding(?) as result',
            [$knowledgeId]
        );

        return $results[0]->result ?? null;
    }
}
