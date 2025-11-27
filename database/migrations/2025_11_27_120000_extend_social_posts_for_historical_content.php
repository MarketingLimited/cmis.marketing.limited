<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Extend Social Posts Table for Historical Content & Knowledge Base
 *
 * This migration adds columns to support:
 * 1. Historical content import from social platforms
 * 2. Brand knowledge base extraction and analysis
 * 3. Success post detection and hypothesis
 * 4. Visual and creative memory
 * 5. Link to profile groups (brands)
 *
 * @package Database\Migrations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check and add columns individually
        $columnsToAdd = [
            // Profile Group / Brand Association
            'profile_group_id' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS profile_group_id UUID NULL",

            // Historical Content Flags
            'source' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS source VARCHAR(50) DEFAULT 'user_created'",
            'is_historical' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS is_historical BOOLEAN DEFAULT false",
            'is_schedulable' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS is_schedulable BOOLEAN DEFAULT true",
            'is_editable' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS is_editable BOOLEAN DEFAULT true",

            // Knowledge Base Analysis Flags
            'is_analyzed' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS is_analyzed BOOLEAN DEFAULT false",
            'is_in_knowledge_base' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS is_in_knowledge_base BOOLEAN DEFAULT false",
            'analysis_status' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS analysis_status VARCHAR(50) DEFAULT 'pending'",
            'analyzed_at' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS analyzed_at TIMESTAMP NULL",
            'analysis_error' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS analysis_error TEXT NULL",

            // Success Post Detection
            'success_score' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS success_score DECIMAL(5, 4) NULL",
            'success_label' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS success_label VARCHAR(50) NULL",
            'success_hypothesis' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS success_hypothesis TEXT NULL",
            'performance_percentile' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS performance_percentile INTEGER NULL",

            // Platform Metrics (Detailed Raw Data)
            'platform_metrics' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS platform_metrics JSONB NULL",

            // Brand DNA Extraction
            'extracted_entities' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS extracted_entities JSONB NULL",
            'extracted_tones' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS extracted_tones JSONB NULL",
            'extracted_hooks' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS extracted_hooks JSONB NULL",
            'extracted_ctas' => "ALTER TABLE cmis.social_posts ADD COLUMN IF NOT EXISTS extracted_ctas JSONB NULL",
        ];

        foreach ($columnsToAdd as $columnName => $sql) {
            $exists = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = 'social_posts'
                    AND column_name = ?
                ) as exists
            ", [$columnName]);

            if (!$exists->exists) {
                DB::statement($sql);
                echo "✓ Added {$columnName} to cmis.social_posts\n";
            }
        }

        // Add indexes individually
        $indexesToAdd = [
            'idx_social_posts_profile_group_id' => 'CREATE INDEX IF NOT EXISTS idx_social_posts_profile_group_id ON cmis.social_posts (profile_group_id)',
            'idx_social_posts_source' => 'CREATE INDEX IF NOT EXISTS idx_social_posts_source ON cmis.social_posts (source)',
            'idx_social_posts_is_historical' => 'CREATE INDEX IF NOT EXISTS idx_social_posts_is_historical ON cmis.social_posts (is_historical)',
            'idx_social_posts_is_analyzed' => 'CREATE INDEX IF NOT EXISTS idx_social_posts_is_analyzed ON cmis.social_posts (is_analyzed)',
            'idx_social_posts_is_in_knowledge_base' => 'CREATE INDEX IF NOT EXISTS idx_social_posts_is_in_knowledge_base ON cmis.social_posts (is_in_knowledge_base)',
            'idx_social_posts_analysis_status' => 'CREATE INDEX IF NOT EXISTS idx_social_posts_analysis_status ON cmis.social_posts (analysis_status)',
            'idx_social_posts_success_score' => 'CREATE INDEX IF NOT EXISTS idx_social_posts_success_score ON cmis.social_posts (success_score)',
            'idx_social_posts_success_label' => 'CREATE INDEX IF NOT EXISTS idx_social_posts_success_label ON cmis.social_posts (success_label)',
        ];

        foreach ($indexesToAdd as $indexName => $sql) {
            DB::statement($sql);
        }

        // ============================================================
        // Add Foreign Key Constraint for Profile Group
        // ============================================================
        $constraintExists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.table_constraints
                WHERE table_schema = 'cmis'
                AND table_name = 'social_posts'
                AND constraint_name = 'fk_social_posts_profile_group'
            ) as exists
        ");

        if (!$constraintExists->exists) {
            DB::statement('
                ALTER TABLE cmis.social_posts
                ADD CONSTRAINT fk_social_posts_profile_group
                FOREIGN KEY (profile_group_id)
                REFERENCES cmis.profile_groups(group_id)
                ON DELETE SET NULL;
            ');
            echo "✓ Added foreign key constraint fk_social_posts_profile_group\n";
        }

        // ============================================================
        // Create Complex Indexes for Performance
        // ============================================================

        // Historical content queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_historical ON cmis.social_posts (is_historical, published_at DESC) WHERE is_historical = true;');

        // Knowledge base queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_in_kb ON cmis.social_posts (is_in_knowledge_base, success_score DESC) WHERE is_in_knowledge_base = true;');

        // Success posts queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_success ON cmis.social_posts (success_label, success_score DESC) WHERE success_label = \'success_post\';');

        // Analysis status queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_analysis_pending ON cmis.social_posts (analysis_status, created_at ASC) WHERE analysis_status IN (\'pending\', \'queued\');');

        // Profile group + historical
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_profile_group_historical ON cmis.social_posts (profile_group_id, is_historical, published_at DESC) WHERE profile_group_id IS NOT NULL;');

        // JSONB GIN indexes for extracted data
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_platform_metrics_gin ON cmis.social_posts USING GIN (platform_metrics);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_extracted_entities_gin ON cmis.social_posts USING GIN (extracted_entities);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_extracted_tones_gin ON cmis.social_posts USING GIN (extracted_tones);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_historical;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_in_kb;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_success;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_analysis_pending;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_profile_group_historical;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_platform_metrics_gin;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_extracted_entities_gin;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_extracted_tones_gin;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_profile_group_id;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_source;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_is_historical;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_is_analyzed;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_is_in_knowledge_base;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_analysis_status;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_success_score;');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_success_label;');

        // Drop foreign key
        DB::statement('ALTER TABLE cmis.social_posts DROP CONSTRAINT IF EXISTS fk_social_posts_profile_group;');

        // Drop columns
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS profile_group_id;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS source;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS is_historical;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS is_schedulable;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS is_editable;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS is_analyzed;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS is_in_knowledge_base;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS analysis_status;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS analyzed_at;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS analysis_error;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS success_score;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS success_label;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS success_hypothesis;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS performance_percentile;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS platform_metrics;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS extracted_entities;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS extracted_tones;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS extracted_hooks;');
        DB::statement('ALTER TABLE cmis.social_posts DROP COLUMN IF EXISTS extracted_ctas;');
    }
};
