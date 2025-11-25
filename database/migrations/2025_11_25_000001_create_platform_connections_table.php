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
        // Create table using raw SQL to avoid schema issues
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.platform_connections (
                connection_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                platform VARCHAR(50) NOT NULL,
                account_id VARCHAR(255) NOT NULL,
                account_name VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'pending',
                access_token TEXT,
                refresh_token TEXT,
                token_expires_at TIMESTAMP,
                scopes JSONB,
                account_metadata JSONB,
                last_sync_at TIMESTAMP,
                last_error_at TIMESTAMP,
                last_error_message TEXT,
                auto_sync BOOLEAN NOT NULL DEFAULT true,
                sync_frequency_minutes INTEGER NOT NULL DEFAULT 15,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP,
                CONSTRAINT fk_platform_connections_org FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE
            )
        ");

        // Create indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_platform_connections_org_id ON cmis.platform_connections(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_platform_connections_platform ON cmis.platform_connections(platform)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_platform_connections_status ON cmis.platform_connections(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_platform_connections_org_platform ON cmis.platform_connections(org_id, platform)');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_platform_connections_unique ON cmis.platform_connections(org_id, platform, account_id)');

        // Enable RLS
        DB::statement('ALTER TABLE cmis.platform_connections ENABLE ROW LEVEL SECURITY');

        // Create RLS policy
        DB::statement("DROP POLICY IF EXISTS platform_connections_org_isolation ON cmis.platform_connections");
        DB::statement("
            CREATE POLICY platform_connections_org_isolation ON cmis.platform_connections
            FOR ALL
            USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)
        ");

        // Force RLS for table owner
        DB::statement('ALTER TABLE cmis.platform_connections FORCE ROW LEVEL SECURITY');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS platform_connections_org_isolation ON cmis.platform_connections');
        DB::statement('DROP TABLE IF EXISTS cmis.platform_connections CASCADE');
    }
};
