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

    public function up(): void
    {
        // API tokens for external integrations
        if (!$this->tableExists('cmis', 'api_tokens')) {
            DB::statement("
                CREATE TABLE cmis.api_tokens (
                    token_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    created_by UUID NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    token_hash TEXT NOT NULL,
                    token_prefix TEXT NOT NULL,
                    scopes JSONB NOT NULL DEFAULT '[]',
                    rate_limits JSONB NULL,
                    last_used_at TIMESTAMP NULL,
                    usage_count INTEGER NOT NULL DEFAULT 0,
                    expires_at TIMESTAMP NULL,
                    is_active BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_api_tokens_org_id ON cmis.api_tokens(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_api_tokens_prefix ON cmis.api_tokens(token_prefix)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_api_tokens_active ON cmis.api_tokens(is_active, expires_at)");

            DB::statement('ALTER TABLE cmis.api_tokens ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.api_tokens");
            DB::statement("CREATE POLICY org_isolation ON cmis.api_tokens USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // Data export configurations
        if (!$this->tableExists('cmis', 'data_export_configs')) {
            DB::statement("
                CREATE TABLE cmis.data_export_configs (
                    config_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    created_by UUID NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    export_type VARCHAR(50) NOT NULL,
                    format VARCHAR(20) NOT NULL DEFAULT 'json' CHECK (format IN ('json', 'csv', 'xlsx', 'parquet')),
                    delivery_method VARCHAR(20) NOT NULL DEFAULT 'download' CHECK (delivery_method IN ('download', 'webhook', 'sftp', 's3')),
                    data_config JSONB NOT NULL DEFAULT '{}',
                    delivery_config JSONB NOT NULL DEFAULT '{}',
                    schedule JSONB NULL,
                    is_active BOOLEAN NOT NULL DEFAULT TRUE,
                    last_export_at TIMESTAMP NULL,
                    export_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_export_configs_org_id ON cmis.data_export_configs(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_export_configs_type ON cmis.data_export_configs(export_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_export_configs_active ON cmis.data_export_configs(is_active, last_export_at)");

            DB::statement('ALTER TABLE cmis.data_export_configs ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.data_export_configs");
            DB::statement("CREATE POLICY org_isolation ON cmis.data_export_configs USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // Export execution logs
        if (!$this->tableExists('cmis', 'data_export_logs')) {
            DB::statement("
                CREATE TABLE cmis.data_export_logs (
                    log_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    config_id UUID NULL REFERENCES cmis.data_export_configs(config_id) ON DELETE SET NULL,
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
                    format VARCHAR(20) NOT NULL,
                    records_count INTEGER NOT NULL DEFAULT 0,
                    file_size BIGINT NOT NULL DEFAULT 0,
                    file_path TEXT NULL,
                    file_url TEXT NULL,
                    delivery_url TEXT NULL,
                    error_message TEXT NULL,
                    execution_time_ms INTEGER NULL,
                    metadata JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_export_logs_config ON cmis.data_export_logs(config_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_export_logs_org ON cmis.data_export_logs(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_export_logs_status ON cmis.data_export_logs(status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_export_logs_started ON cmis.data_export_logs(started_at)");

            DB::statement('ALTER TABLE cmis.data_export_logs ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.data_export_logs");
            DB::statement("CREATE POLICY org_isolation ON cmis.data_export_logs USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.data_export_logs CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.data_export_configs CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.api_tokens CASCADE');
    }
};
