<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create Vector Indexes for Embeddings (Phase 1 Week 3 - Task 3.1)
 *
 * This migration creates IVFFlat indexes on embedding columns for fast
 * cosine similarity search using pgvector extension.
 *
 * IVFFlat (Inverted File with Flat compression) provides approximate
 * nearest neighbor search with good performance characteristics for
 * vector similarity queries.
 *
 * Index Configuration:
 * - lists = 100: Number of inverted lists (clusters)
 * - Recommended: sqrt(number_of_rows) for small to medium datasets
 * - operator class: vector_cosine_ops (cosine similarity)
 *
 * Performance Impact:
 * - Index creation: O(n log n) where n = number of vectors
 * - Query time: O(sqrt(n)) approximate, vs O(n) exact scan
 * - Expected improvement: 10-100x faster for similarity searches
 *
 * Prerequisites:
 * - pgvector extension must be installed
 * - Tables must exist with vector columns
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define all tables with embedding columns
        $tables = [
            'cmis.embeddings_cache' => 'embedding',
            'cmis_ai.campaign_embeddings' => 'embedding',
            'cmis_ai.content_embeddings' => 'embedding',
            'cmis_ai.creative_embeddings' => 'embedding',
        ];

        foreach ($tables as $table => $column) {
            // Generate safe index name (max 63 characters for PostgreSQL)
            $tableName = str_replace('.', '_', $table);
            $indexName = "idx_{$tableName}_{$column}_ivfflat";

            // Check if table exists before creating index
            $tableExists = DB::select("
                SELECT EXISTS (
                    SELECT FROM pg_tables
                    WHERE schemaname || '.' || tablename = ?
                )
            ", [$table]);

            if (!$tableExists[0]->exists) {
                // Log warning but continue (table may be created later)
                echo "Warning: Table {$table} does not exist. Skipping index creation.\n";
                continue;
            }

            // Check if index already exists
            $indexExists = DB::select("
                SELECT EXISTS (
                    SELECT FROM pg_indexes
                    WHERE indexname = ?
                )
            ", [$indexName]);

            if ($indexExists[0]->exists) {
                echo "Info: Index {$indexName} already exists. Skipping.\n";
                continue;
            }

            try {
                // Create IVFFlat index for fast cosine similarity search
                // Using vector_cosine_ops for 1 - cosine_distance metric
                DB::statement("
                    CREATE INDEX {$indexName}
                    ON {$table}
                    USING ivfflat ({$column} vector_cosine_ops)
                    WITH (lists = 100)
                ");

                echo "Success: Created index {$indexName} on {$table}.{$column}\n";

            } catch (\Exception $e) {
                // Log error but continue with other indexes
                echo "Error creating index {$indexName}: " . $e->getMessage() . "\n";
            }
        }

        // Create additional utility function for similarity search
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis_ai.calculate_similarity(
                vec1 vector,
                vec2 vector
            )
            RETURNS float
            LANGUAGE sql
            IMMUTABLE PARALLEL SAFE
            AS $$
                SELECT 1 - (vec1 <=> vec2)
            $$;
        ");

        echo "Success: Created similarity calculation function\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all vector indexes
        $indexes = [
            'idx_cmis_embeddings_cache_embedding_ivfflat',
            'idx_cmis_ai_campaign_embeddings_embedding_ivfflat',
            'idx_cmis_ai_content_embeddings_embedding_ivfflat',
            'idx_cmis_ai_creative_embeddings_embedding_ivfflat',
        ];

        foreach ($indexes as $indexName) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
                echo "Dropped index: {$indexName}\n";
            } catch (\Exception $e) {
                echo "Error dropping index {$indexName}: " . $e->getMessage() . "\n";
            }
        }

        // Drop utility function
        DB::statement("DROP FUNCTION IF EXISTS cmis_ai.calculate_similarity(vector, vector)");
        echo "Dropped similarity calculation function\n";
    }
};
