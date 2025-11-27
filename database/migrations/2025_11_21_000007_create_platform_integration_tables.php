<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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
     * Run the migrations (Phase 18: Platform Integration & API Orchestration).
     */
    public function up(): void
    {
        // ===== Platform Connections Table =====
        if (!$this->tableExists('cmis', 'platform_connections')) {
            DB::statement("
                CREATE TABLE cmis.platform_connections (
                    connection_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    platform VARCHAR(50) NOT NULL,
                    account_id VARCHAR(255) NOT NULL,
                    account_name VARCHAR(255) NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'expired', 'revoked', 'error')),
                    access_token TEXT NULL,
                    refresh_token TEXT NULL,
                    token_expires_at TIMESTAMP NULL,
                    scopes JSONB NULL,
                    account_metadata JSONB NULL,
                    last_sync_at TIMESTAMP NULL,
                    last_error_at TIMESTAMP NULL,
                    last_error_message TEXT NULL,
                    auto_sync BOOLEAN NOT NULL DEFAULT TRUE,
                    sync_frequency_minutes INTEGER NOT NULL DEFAULT 15,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT uq_platform_connections UNIQUE (org_id, platform, account_id)
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_connections_org_id ON cmis.platform_connections(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_connections_platform_status ON cmis.platform_connections(platform, status)");

            DB::statement('ALTER TABLE cmis.platform_connections ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.platform_connections");
            DB::statement("CREATE POLICY org_isolation ON cmis.platform_connections USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Platform Sync Logs Table =====
        if (!$this->tableExists('cmis', 'platform_sync_logs')) {
            DB::statement("
                CREATE TABLE cmis.platform_sync_logs (
                    sync_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    connection_id UUID NOT NULL REFERENCES cmis.platform_connections(connection_id) ON DELETE CASCADE,
                    sync_type VARCHAR(50) NOT NULL CHECK (sync_type IN ('full', 'incremental', 'entity_specific')),
                    entity_type VARCHAR(50) NULL,
                    direction VARCHAR(20) NOT NULL CHECK (direction IN ('import', 'export', 'bidirectional')),
                    status VARCHAR(30) NOT NULL CHECK (status IN ('running', 'completed', 'failed', 'partial')),
                    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP NULL,
                    duration_ms INTEGER NULL,
                    entities_processed INTEGER NOT NULL DEFAULT 0,
                    entities_created INTEGER NOT NULL DEFAULT 0,
                    entities_updated INTEGER NOT NULL DEFAULT 0,
                    entities_failed INTEGER NOT NULL DEFAULT 0,
                    summary JSONB NULL,
                    error_message TEXT NULL,
                    error_details JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_sync_logs_org_id ON cmis.platform_sync_logs(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_sync_logs_connection ON cmis.platform_sync_logs(org_id, connection_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_sync_logs_started ON cmis.platform_sync_logs(started_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_sync_logs_status ON cmis.platform_sync_logs(status)");

            DB::statement('ALTER TABLE cmis.platform_sync_logs ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.platform_sync_logs");
            DB::statement("CREATE POLICY org_isolation ON cmis.platform_sync_logs USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Platform API Calls Table =====
        if (!$this->tableExists('cmis', 'platform_api_calls')) {
            DB::statement("
                CREATE TABLE cmis.platform_api_calls (
                    call_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    connection_id UUID NOT NULL REFERENCES cmis.platform_connections(connection_id) ON DELETE CASCADE,
                    platform VARCHAR(50) NOT NULL,
                    endpoint VARCHAR(500) NOT NULL,
                    method VARCHAR(10) NOT NULL CHECK (method IN ('GET', 'POST', 'PUT', 'DELETE', 'PATCH')),
                    action_type VARCHAR(100) NULL,
                    http_status INTEGER NULL,
                    duration_ms INTEGER NULL,
                    success BOOLEAN NOT NULL DEFAULT FALSE,
                    error_message TEXT NULL,
                    request_payload JSONB NULL,
                    response_data JSONB NULL,
                    rate_limit_remaining INTEGER NULL,
                    rate_limit_reset_at TIMESTAMP NULL,
                    called_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_api_calls_org_id ON cmis.platform_api_calls(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_api_calls_platform ON cmis.platform_api_calls(org_id, platform)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_api_calls_connection ON cmis.platform_api_calls(connection_id, called_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_api_calls_success ON cmis.platform_api_calls(success)");

            DB::statement('ALTER TABLE cmis.platform_api_calls ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.platform_api_calls");
            DB::statement("CREATE POLICY org_isolation ON cmis.platform_api_calls USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Platform Rate Limits Table =====
        if (!$this->tableExists('cmis', 'platform_rate_limits')) {
            DB::statement("
                CREATE TABLE cmis.platform_rate_limits (
                    limit_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    connection_id UUID NOT NULL REFERENCES cmis.platform_connections(connection_id) ON DELETE CASCADE,
                    platform VARCHAR(50) NOT NULL,
                    limit_type VARCHAR(50) NOT NULL CHECK (limit_type IN ('hourly', 'daily', 'per_call', 'burst')),
                    limit_max INTEGER NOT NULL,
                    limit_current INTEGER NOT NULL DEFAULT 0,
                    window_start TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    window_end TIMESTAMP NOT NULL,
                    resets_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_rate_limits_org_id ON cmis.platform_rate_limits(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_rate_limits_connection ON cmis.platform_rate_limits(connection_id, limit_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_rate_limits_resets ON cmis.platform_rate_limits(resets_at)");

            DB::statement('ALTER TABLE cmis.platform_rate_limits ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.platform_rate_limits");
            DB::statement("CREATE POLICY org_isolation ON cmis.platform_rate_limits USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Platform Webhooks Table =====
        if (!$this->tableExists('cmis', 'platform_webhooks')) {
            DB::statement("
                CREATE TABLE cmis.platform_webhooks (
                    webhook_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    connection_id UUID NOT NULL REFERENCES cmis.platform_connections(connection_id) ON DELETE CASCADE,
                    platform VARCHAR(50) NOT NULL,
                    event_type VARCHAR(100) NOT NULL,
                    platform_webhook_id VARCHAR(255) NULL,
                    callback_url TEXT NOT NULL,
                    verify_token TEXT NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused', 'failed')),
                    event_filters JSONB NULL,
                    last_triggered_at TIMESTAMP NULL,
                    trigger_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_webhooks_org_id ON cmis.platform_webhooks(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_webhooks_connection ON cmis.platform_webhooks(org_id, connection_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_webhooks_platform_event ON cmis.platform_webhooks(platform, event_type)");

            DB::statement('ALTER TABLE cmis.platform_webhooks ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.platform_webhooks");
            DB::statement("CREATE POLICY org_isolation ON cmis.platform_webhooks USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Platform Entity Mappings Table =====
        if (!$this->tableExists('cmis', 'platform_entity_mappings')) {
            DB::statement("
                CREATE TABLE cmis.platform_entity_mappings (
                    mapping_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    connection_id UUID NOT NULL REFERENCES cmis.platform_connections(connection_id) ON DELETE CASCADE,
                    platform VARCHAR(50) NOT NULL,
                    cmis_entity_id UUID NOT NULL,
                    cmis_entity_type VARCHAR(50) NOT NULL,
                    platform_entity_id VARCHAR(255) NOT NULL,
                    platform_entity_type VARCHAR(50) NULL,
                    first_synced_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    last_synced_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    sync_metadata JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT uq_platform_entity_mappings UNIQUE (connection_id, cmis_entity_id, cmis_entity_type)
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_entity_mappings_org_id ON cmis.platform_entity_mappings(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_entity_mappings_platform ON cmis.platform_entity_mappings(platform, platform_entity_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_platform_entity_mappings_cmis ON cmis.platform_entity_mappings(cmis_entity_type, cmis_entity_id)");

            DB::statement('ALTER TABLE cmis.platform_entity_mappings ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.platform_entity_mappings");
            DB::statement("CREATE POLICY org_isolation ON cmis.platform_entity_mappings USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.platform_entity_mappings CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.platform_webhooks CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.platform_rate_limits CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.platform_api_calls CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.platform_sync_logs CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.platform_connections CASCADE');
    }
};
