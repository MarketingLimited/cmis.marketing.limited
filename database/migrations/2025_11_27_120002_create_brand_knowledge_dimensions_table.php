<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create Brand Knowledge Dimensions Table
 *
 * Stores extracted marketing DNA and brand knowledge from historical social content.
 */
return new class extends Migration
{
    /**
     * Disable automatic transaction wrapping for this migration
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS cmis.brand_knowledge_dimensions (
                dimension_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                profile_group_id UUID NOT NULL,
                post_id UUID,
                media_asset_id UUID,

                -- Dimension Classification
                dimension_category VARCHAR(100) NOT NULL,
                dimension_type VARCHAR(100) NOT NULL,
                dimension_value VARCHAR(500) NOT NULL,
                dimension_details TEXT,

                -- Confidence & Core DNA
                confidence_score DECIMAL(5,4) DEFAULT 0.5000,
                is_core_dna BOOLEAN DEFAULT false,
                frequency_count INTEGER DEFAULT 1,
                first_seen_at TIMESTAMP,
                last_seen_at TIMESTAMP,

                -- Performance Correlation
                avg_success_score DECIMAL(5,4),
                success_post_count INTEGER DEFAULT 0,
                total_post_count INTEGER DEFAULT 0,

                -- Contextual Relationships
                co_occurring_dimensions JSONB,
                performance_context JSONB,

                -- Platform & Temporal Context
                platform VARCHAR(50),
                season VARCHAR(50),
                year INTEGER,
                month INTEGER,

                -- Status & Validation
                status VARCHAR(50) DEFAULT 'active',
                is_validated BOOLEAN DEFAULT false,
                validated_by UUID,
                validated_at TIMESTAMP,

                -- Metadata
                metadata JSONB,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP,

                CONSTRAINT fk_brand_knowledge_org FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                CONSTRAINT fk_brand_knowledge_profile_group FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE CASCADE,
                CONSTRAINT fk_brand_knowledge_media_asset FOREIGN KEY (media_asset_id) REFERENCES cmis.media_assets(asset_id) ON DELETE CASCADE
            )
        ");

        // Create indexes
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_org_id ON cmis.brand_knowledge_dimensions(org_id);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_profile_group_id ON cmis.brand_knowledge_dimensions(profile_group_id);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_post_id ON cmis.brand_knowledge_dimensions(post_id);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_dimension_category ON cmis.brand_knowledge_dimensions(dimension_category);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_dimension_type ON cmis.brand_knowledge_dimensions(dimension_type);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_is_core_dna ON cmis.brand_knowledge_dimensions(is_core_dna);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_status ON cmis.brand_knowledge_dimensions(status);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_platform ON cmis.brand_knowledge_dimensions(platform) WHERE platform IS NOT NULL;');

        // Composite indexes
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_profile_group_type ON cmis.brand_knowledge_dimensions(profile_group_id, dimension_type, is_core_dna DESC);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_core_dna_freq ON cmis.brand_knowledge_dimensions(profile_group_id, is_core_dna, frequency_count DESC) WHERE is_core_dna = true;');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_success ON cmis.brand_knowledge_dimensions(profile_group_id, avg_success_score DESC) WHERE avg_success_score IS NOT NULL;');

        // JSONB GIN indexes
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_co_occurring_gin ON cmis.brand_knowledge_dimensions USING GIN (co_occurring_dimensions);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_performance_context_gin ON cmis.brand_knowledge_dimensions USING GIN (performance_context);');

        // Unique constraint
        DB::unprepared("
            CREATE UNIQUE INDEX IF NOT EXISTS idx_brand_knowledge_unique
            ON cmis.brand_knowledge_dimensions (profile_group_id, dimension_type, dimension_value, COALESCE(post_id::text, 'null'), COALESCE(platform, 'all'))
            WHERE deleted_at IS NULL;
        ");

        // Enable RLS
        DB::unprepared('ALTER TABLE cmis.brand_knowledge_dimensions ENABLE ROW LEVEL SECURITY;');

        // Create RLS policy
        DB::unprepared("DROP POLICY IF EXISTS brand_knowledge_dimensions_org_isolation ON cmis.brand_knowledge_dimensions;");
        DB::unprepared("
            CREATE POLICY brand_knowledge_dimensions_org_isolation ON cmis.brand_knowledge_dimensions
            FOR ALL
            USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid);
        ");

        DB::unprepared('ALTER TABLE cmis.brand_knowledge_dimensions FORCE ROW LEVEL SECURITY;');
    }

    public function down(): void
    {
        DB::unprepared('DROP POLICY IF EXISTS brand_knowledge_dimensions_org_isolation ON cmis.brand_knowledge_dimensions;');
        DB::unprepared('DROP TABLE IF EXISTS cmis.brand_knowledge_dimensions CASCADE;');
    }
};
