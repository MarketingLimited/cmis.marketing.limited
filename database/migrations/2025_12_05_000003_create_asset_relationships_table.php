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
     * Creates the asset_relationships table for storing parent-child and other
     * relationships between platform assets.
     * This table has PUBLIC RLS because relationships are structural data shared across orgs.
     *
     * Example relationships:
     * - page_owns_instagram: A Facebook Page owns an Instagram Business Account
     * - business_manages_page: A Business Manager manages a Facebook Page
     * - business_owns_ad_account: A Business owns an Ad Account
     * - ad_account_has_pixel: An Ad Account has a Pixel attached
     */
    public function up(): void
    {
        if (!$this->tableExists('cmis', 'asset_relationships')) {
            DB::statement("
                CREATE TABLE cmis.asset_relationships (
                    relationship_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

                    -- Relationship endpoints
                    parent_asset_id UUID NOT NULL REFERENCES cmis.platform_assets(asset_id) ON DELETE CASCADE,
                    child_asset_id UUID NOT NULL REFERENCES cmis.platform_assets(asset_id) ON DELETE CASCADE,

                    -- Relationship type
                    relationship_type VARCHAR(100) NOT NULL,
                    relationship_data JSONB NOT NULL DEFAULT '{}',

                    -- Tracking
                    discovered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    last_verified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                    -- Timestamps
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    -- Unique constraint: one relationship type per parent/child pair
                    CONSTRAINT uq_asset_relationships UNIQUE (parent_asset_id, child_asset_id, relationship_type),

                    -- Prevent self-referential relationships
                    CONSTRAINT chk_no_self_reference CHECK (parent_asset_id != child_asset_id)
                )
            ");

            // Performance indexes
            DB::statement("CREATE INDEX idx_asset_relationships_parent ON cmis.asset_relationships(parent_asset_id)");
            DB::statement("CREATE INDEX idx_asset_relationships_child ON cmis.asset_relationships(child_asset_id)");
            DB::statement("CREATE INDEX idx_asset_relationships_type ON cmis.asset_relationships(relationship_type)");
            DB::statement("CREATE INDEX idx_asset_relationships_verified ON cmis.asset_relationships(last_verified_at)");

            // Enable PUBLIC RLS - relationships are structural data shared across organizations
            $this->enablePublicRLS('cmis.asset_relationships');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop RLS policies first
        DB::statement("DROP POLICY IF EXISTS allow_all ON cmis.asset_relationships");
        DB::statement("ALTER TABLE cmis.asset_relationships DISABLE ROW LEVEL SECURITY");

        // Drop the table
        DB::statement('DROP TABLE IF EXISTS cmis.asset_relationships CASCADE');
    }
};
