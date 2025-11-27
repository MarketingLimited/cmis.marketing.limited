<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create Media Assets Table
 *
 * Stores all media assets (images, videos, thumbnails) for social posts
 * with comprehensive visual analysis, design extraction, and layout mapping.
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
            CREATE TABLE IF NOT EXISTS cmis.media_assets (
                asset_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                post_id UUID NOT NULL,

                -- Media Basic Info
                media_type VARCHAR(50) NOT NULL,
                original_url TEXT,
                storage_path TEXT,
                file_name VARCHAR(255),
                mime_type VARCHAR(100),
                file_size BIGINT,
                width INTEGER,
                height INTEGER,
                aspect_ratio DECIMAL(5,2),
                duration_seconds INTEGER,
                position INTEGER DEFAULT 0,

                -- Analysis Status
                is_analyzed BOOLEAN DEFAULT false,
                analysis_status VARCHAR(50) DEFAULT 'pending',
                analyzed_at TIMESTAMP,
                analysis_error TEXT,

                -- Visual Analysis & Scene Description
                visual_caption TEXT,
                scene_description TEXT,
                detected_objects JSONB,
                detected_people JSONB,
                camera_angle VARCHAR(100),
                depth_of_field VARCHAR(50),
                lighting VARCHAR(100),

                -- OCR & Text Layout
                text_blocks JSONB,
                extracted_text TEXT,
                primary_language VARCHAR(10),

                -- Design & Layout
                design_prompt TEXT,
                style_profile JSONB,
                layout_map JSONB,
                element_positions JSONB,

                -- Visual Attributes
                color_palette JSONB,
                typography JSONB,
                art_direction VARCHAR(255),
                mood VARCHAR(255),
                visual_message VARCHAR(255),
                look_and_feel VARCHAR(255),
                imagery_and_graphics JSONB,
                icons_and_symbols JSONB,
                composition VARCHAR(255),
                background_style VARCHAR(255),
                highlight_elements JSONB,
                deemphasize_elements JSONB,

                -- Motion Analysis (videos)
                motion_analysis JSONB,
                video_completion_rate DECIMAL(5,2),
                video_retention_curve JSONB,

                -- Brand Consistency
                brand_consistency_score DECIMAL(5,4),
                style_deviations JSONB,

                -- Metadata
                metadata JSONB,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP,

                CONSTRAINT fk_media_assets_org FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE
            )
        ");

        // Create indexes
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_org_id ON cmis.media_assets(org_id);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_post_id ON cmis.media_assets(post_id);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_media_type ON cmis.media_assets(media_type);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_analysis_status ON cmis.media_assets(analysis_status);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_is_analyzed ON cmis.media_assets(is_analyzed);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_post_position ON cmis.media_assets(post_id, position);');

        // JSONB GIN indexes
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_color_palette_gin ON cmis.media_assets USING GIN (color_palette);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_text_blocks_gin ON cmis.media_assets USING GIN (text_blocks);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_layout_map_gin ON cmis.media_assets USING GIN (layout_map);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_media_assets_detected_objects_gin ON cmis.media_assets USING GIN (detected_objects);');

        // Full-text search on extracted text
        DB::unprepared("CREATE INDEX IF NOT EXISTS idx_media_assets_extracted_text_ft ON cmis.media_assets USING GIN (to_tsvector('english', COALESCE(extracted_text, '')));");

        // Enable RLS
        DB::unprepared('ALTER TABLE cmis.media_assets ENABLE ROW LEVEL SECURITY;');

        // Create RLS policy
        DB::unprepared("DROP POLICY IF EXISTS media_assets_org_isolation ON cmis.media_assets;");
        DB::unprepared("
            CREATE POLICY media_assets_org_isolation ON cmis.media_assets
            FOR ALL
            USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid);
        ");

        DB::unprepared('ALTER TABLE cmis.media_assets FORCE ROW LEVEL SECURITY;');
    }

    public function down(): void
    {
        DB::unprepared('DROP POLICY IF EXISTS media_assets_org_isolation ON cmis.media_assets;');
        DB::unprepared('DROP TABLE IF EXISTS cmis.media_assets CASCADE;');
    }
};
