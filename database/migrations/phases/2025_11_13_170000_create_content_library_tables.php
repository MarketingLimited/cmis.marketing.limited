<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create content_library_folders table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.content_library_folders (
                folder_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                parent_folder_id UUID,
                folder_name VARCHAR(255) NOT NULL,
                description TEXT,
                created_by UUID NOT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (parent_folder_id) REFERENCES cmis.content_library_folders(folder_id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on org_id for folder lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_content_library_folders_org
            ON cmis.content_library_folders(org_id)
        ");

        // Create index on parent_folder_id for hierarchy
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_content_library_folders_parent
            ON cmis.content_library_folders(parent_folder_id)
        ");

        // Create content_library table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.content_library (
                asset_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                folder_id UUID,
                asset_name VARCHAR(255) NOT NULL,
                asset_type VARCHAR(20) NOT NULL, -- image, video, document, audio
                file_path VARCHAR(500) NOT NULL,
                file_size BIGINT NOT NULL,
                mime_type VARCHAR(100),
                extension VARCHAR(10),
                description TEXT,
                tags JSONB,
                uploaded_by UUID NOT NULL,
                is_public BOOLEAN DEFAULT false,
                is_deleted BOOLEAN DEFAULT false,
                thumbnail_path VARCHAR(500),
                metadata JSONB,
                deleted_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (folder_id) REFERENCES cmis.content_library_folders(folder_id) ON DELETE SET NULL,
                FOREIGN KEY (uploaded_by) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on org_id for asset lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_content_library_org
            ON cmis.content_library(org_id)
        ");

        // Create index on folder_id for folder contents
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_content_library_folder
            ON cmis.content_library(folder_id)
        ");

        // Create index on asset_type for filtering
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_content_library_type
            ON cmis.content_library(asset_type)
        ");

        // Create index on tags for tag-based search
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_content_library_tags
            ON cmis.content_library USING GIN(tags)
        ");

        // Create index on created_at for sorting
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_content_library_created_at
            ON cmis.content_library(created_at DESC)
        ");

        // Create index on asset_name for search
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_content_library_name
            ON cmis.content_library(asset_name)
        ");

        // Create asset_usage table to track where assets are used
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.asset_usage (
                usage_id UUID PRIMARY KEY,
                asset_id UUID NOT NULL,
                entity_type VARCHAR(50) NOT NULL, -- post, campaign, ad
                entity_id UUID NOT NULL,
                used_by UUID,
                used_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (asset_id) REFERENCES cmis.content_library(asset_id) ON DELETE CASCADE,
                FOREIGN KEY (used_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL
            )
        ");

        // Create index on asset_id for usage tracking
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_asset_usage_asset
            ON cmis.asset_usage(asset_id)
        ");

        // Create index on entity for reverse lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_asset_usage_entity
            ON cmis.asset_usage(entity_type, entity_id)
        ");

        // Add comments to tables
        DB::statement("
            COMMENT ON TABLE cmis.content_library_folders IS 'Folder organization for content library - Sprint 5.4'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.content_library IS 'Shared content library for media assets - Sprint 5.4'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.asset_usage IS 'Track where library assets are used - Sprint 5.4'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis.asset_usage CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.content_library CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.content_library_folders CASCADE");
    }
};
