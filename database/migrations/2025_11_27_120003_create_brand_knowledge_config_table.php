<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create Brand Knowledge Config Table
 *
 * Stores configuration for automatic brand knowledge base building per profile group.
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
            CREATE TABLE IF NOT EXISTS cmis.brand_knowledge_config (
                config_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                profile_group_id UUID NOT NULL UNIQUE,

                -- Auto-Build Configuration
                auto_build_enabled BOOLEAN DEFAULT false,
                auto_build_min_posts INTEGER DEFAULT 50,
                auto_build_min_days INTEGER DEFAULT 7,
                auto_analyze_new_posts BOOLEAN DEFAULT false,

                -- Analysis Preferences
                enabled_dimensions JSONB,
                analysis_platforms JSONB,
                min_success_percentile INTEGER DEFAULT 75,
                analyze_visual_content BOOLEAN DEFAULT true,
                analyze_video_content BOOLEAN DEFAULT true,

                -- KB Status & Stats
                total_posts_imported INTEGER DEFAULT 0,
                total_posts_analyzed INTEGER DEFAULT 0,
                total_success_posts INTEGER DEFAULT 0,
                total_dimensions_extracted INTEGER DEFAULT 0,
                first_import_at TIMESTAMP,
                last_import_at TIMESTAMP,
                last_analysis_at TIMESTAMP,
                kb_built_at TIMESTAMP,
                kb_updated_at TIMESTAMP,

                -- Notification Preferences
                notify_on_kb_ready BOOLEAN DEFAULT true,
                notify_on_analysis_complete BOOLEAN DEFAULT true,
                notify_on_import_milestone BOOLEAN DEFAULT true,
                notification_recipients JSONB,

                -- Processing Limits
                max_concurrent_analysis INTEGER DEFAULT 5,
                daily_analysis_limit INTEGER,
                monthly_ai_budget DECIMAL(10,2),
                current_month_spend DECIMAL(10,2) DEFAULT 0.00,

                -- Metadata
                metadata JSONB,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP,

                CONSTRAINT fk_brand_knowledge_config_org FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                CONSTRAINT fk_brand_knowledge_config_profile_group FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE CASCADE
            )
        ");

        // Create indexes
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_config_org_id ON cmis.brand_knowledge_config(org_id);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_brand_knowledge_config_auto_build ON cmis.brand_knowledge_config(auto_build_enabled, first_import_at) WHERE auto_build_enabled = true;');

        // Enable RLS
        DB::unprepared('ALTER TABLE cmis.brand_knowledge_config ENABLE ROW LEVEL SECURITY;');

        // Create RLS policy
        DB::unprepared("DROP POLICY IF EXISTS brand_knowledge_config_org_isolation ON cmis.brand_knowledge_config;");
        DB::unprepared("
            CREATE POLICY brand_knowledge_config_org_isolation ON cmis.brand_knowledge_config
            FOR ALL
            USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid);
        ");

        DB::unprepared('ALTER TABLE cmis.brand_knowledge_config FORCE ROW LEVEL SECURITY;');
    }

    public function down(): void
    {
        DB::unprepared('DROP POLICY IF EXISTS brand_knowledge_config_org_isolation ON cmis.brand_knowledge_config;');
        DB::unprepared('DROP TABLE IF EXISTS cmis.brand_knowledge_config CASCADE;');
    }
};
