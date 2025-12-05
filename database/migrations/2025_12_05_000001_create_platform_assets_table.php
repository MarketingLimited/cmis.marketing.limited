<?php

use Database\Migrations\Concerns\HasRLSPolicies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Helper to check if table exists
     */
    private function tableExists(string $schema, string $table): bool
    {
        $result = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_name = ?
        ", [$schema, $table]);
        return $result->count > 0;
    }

    /**
     * Run the migrations.
     *
     * Creates the platform_assets table for storing canonical asset data from all platforms.
     * This table has PUBLIC RLS (no org filtering) because assets are shared across organizations.
     * The same Facebook Page can be accessed by multiple organizations - we store it once.
     */
    public function up(): void
    {
        if (!$this->tableExists('cmis', 'platform_assets')) {
            DB::statement("
                CREATE TABLE cmis.platform_assets (
                    asset_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

                    -- Platform identification (unique constraint below)
                    platform VARCHAR(50) NOT NULL,
                    platform_asset_id VARCHAR(255) NOT NULL,
                    asset_type VARCHAR(100) NOT NULL,

                    -- Asset details
                    asset_name VARCHAR(500) NULL,
                    asset_data JSONB NOT NULL DEFAULT '{}',

                    -- Ownership context (from platform API)
                    ownership_type VARCHAR(50) NULL CHECK (ownership_type IN ('owned', 'client', 'personal', 'managed', 'unknown')),
                    parent_asset_id UUID NULL REFERENCES cmis.platform_assets(asset_id) ON DELETE SET NULL,
                    business_id VARCHAR(255) NULL,
                    business_name VARCHAR(500) NULL,

                    -- Sync metadata
                    first_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    last_synced_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    last_sync_source VARCHAR(255) NULL,
                    sync_count INTEGER NOT NULL DEFAULT 1,

                    -- Status
                    is_active BOOLEAN NOT NULL DEFAULT TRUE,
                    deleted_at TIMESTAMP WITH TIME ZONE NULL,

                    -- Timestamps
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    -- Unique constraint: one record per platform asset type combination
                    CONSTRAINT uq_platform_assets UNIQUE (platform, platform_asset_id, asset_type)
                )
            ");

            // Performance indexes
            DB::statement("CREATE INDEX idx_platform_assets_platform_type ON cmis.platform_assets(platform, asset_type)");
            DB::statement("CREATE INDEX idx_platform_assets_last_synced ON cmis.platform_assets(last_synced_at)");
            DB::statement("CREATE INDEX idx_platform_assets_parent ON cmis.platform_assets(parent_asset_id) WHERE parent_asset_id IS NOT NULL");
            DB::statement("CREATE INDEX idx_platform_assets_business ON cmis.platform_assets(business_id) WHERE business_id IS NOT NULL");
            DB::statement("CREATE INDEX idx_platform_assets_active ON cmis.platform_assets(is_active) WHERE is_active = TRUE");
            DB::statement("CREATE INDEX idx_platform_assets_data ON cmis.platform_assets USING GIN (asset_data)");

            // Enable PUBLIC RLS - assets are shared across organizations
            // This allows any authenticated user to read/write to this table
            // Access control is enforced via the org_asset_access table
            $this->enablePublicRLS('cmis.platform_assets');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop RLS policies first
        DB::statement("DROP POLICY IF EXISTS allow_all ON cmis.platform_assets");
        DB::statement("ALTER TABLE cmis.platform_assets DISABLE ROW LEVEL SECURITY");

        // Drop the table
        DB::statement('DROP TABLE IF EXISTS cmis.platform_assets CASCADE');
    }
};
