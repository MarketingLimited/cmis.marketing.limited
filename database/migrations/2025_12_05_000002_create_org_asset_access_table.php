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
     * Creates the org_asset_access table for tracking which organizations have
     * access to which platform assets via which connections.
     * This table has RLS enabled (org_id filtering) for multi-tenancy isolation.
     */
    public function up(): void
    {
        if (!$this->tableExists('cmis', 'org_asset_access')) {
            DB::statement("
                CREATE TABLE cmis.org_asset_access (
                    access_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

                    -- Core relationships
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    asset_id UUID NOT NULL REFERENCES cmis.platform_assets(asset_id) ON DELETE CASCADE,
                    connection_id UUID NOT NULL REFERENCES cmis.platform_connections(connection_id) ON DELETE CASCADE,

                    -- Access details
                    access_types JSONB NOT NULL DEFAULT '[]',
                    permissions JSONB NOT NULL DEFAULT '{}',
                    roles JSONB NOT NULL DEFAULT '[]',

                    -- Grant tracking
                    granted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    granted_by_user_id UUID NULL,

                    -- Verification tracking
                    last_verified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    verification_count INTEGER NOT NULL DEFAULT 1,

                    -- Selection state
                    is_active BOOLEAN NOT NULL DEFAULT TRUE,
                    is_selected BOOLEAN NOT NULL DEFAULT FALSE,

                    -- Soft delete
                    deleted_at TIMESTAMP WITH TIME ZONE NULL,

                    -- Timestamps
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    -- Unique constraint: one access record per org/asset/connection combination
                    CONSTRAINT uq_org_asset_access UNIQUE (org_id, asset_id, connection_id)
                )
            ");

            // Performance indexes
            DB::statement("CREATE INDEX idx_org_asset_access_org ON cmis.org_asset_access(org_id)");
            DB::statement("CREATE INDEX idx_org_asset_access_asset ON cmis.org_asset_access(asset_id)");
            DB::statement("CREATE INDEX idx_org_asset_access_connection ON cmis.org_asset_access(connection_id)");
            DB::statement("CREATE INDEX idx_org_asset_access_selected ON cmis.org_asset_access(org_id, is_selected) WHERE is_selected = TRUE");
            DB::statement("CREATE INDEX idx_org_asset_access_verified ON cmis.org_asset_access(last_verified_at)");
            DB::statement("CREATE INDEX idx_org_asset_access_active ON cmis.org_asset_access(org_id, is_active) WHERE is_active = TRUE");

            // Enable RLS with organization isolation
            $this->enableRLS('cmis.org_asset_access');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->disableRLS('cmis.org_asset_access');
        DB::statement('DROP TABLE IF EXISTS cmis.org_asset_access CASCADE');
    }
};
