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
     * Creates the batch_execution_log table for tracking batch API executions.
     * Used for monitoring, analytics, and debugging batch processing performance.
     *
     * Uses PUBLIC RLS - this is monitoring/analytics data that needs to be
     * accessible for system-wide dashboards and reporting.
     */
    public function up(): void
    {
        if (!$this->tableExists('cmis', 'batch_execution_log')) {
            DB::statement("
                CREATE TABLE cmis.batch_execution_log (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

                    -- Batch identification
                    batch_id UUID NOT NULL,
                    platform VARCHAR(50) NOT NULL CHECK (platform IN ('meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat', 'pinterest')),
                    batch_type VARCHAR(50) NOT NULL DEFAULT 'standard',

                    -- Connection context
                    connection_id UUID NULL,
                    org_id UUID NULL,

                    -- Request counts
                    request_count INTEGER NOT NULL DEFAULT 0,
                    success_count INTEGER NOT NULL DEFAULT 0,
                    failure_count INTEGER NOT NULL DEFAULT 0,
                    skipped_count INTEGER NOT NULL DEFAULT 0,

                    -- Performance metrics
                    duration_ms INTEGER NULL,
                    api_calls_made INTEGER NOT NULL DEFAULT 1,
                    bytes_received BIGINT NULL,

                    -- Rate limit tracking
                    rate_limit_remaining INTEGER NULL,
                    rate_limit_reset_at TIMESTAMP WITH TIME ZONE NULL,
                    rate_limit_hit BOOLEAN NOT NULL DEFAULT FALSE,

                    -- Response data
                    response_summary JSONB NULL,
                    errors JSONB NULL,

                    -- Execution window
                    started_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP WITH TIME ZONE NULL,

                    -- Timestamps
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

                    -- Foreign keys (nullable for flexibility)
                    CONSTRAINT fk_batch_log_connection FOREIGN KEY (connection_id)
                        REFERENCES cmis.platform_connections(connection_id) ON DELETE SET NULL
                )
            ");

            // Performance indexes for monitoring and analytics
            DB::statement("CREATE INDEX idx_batch_log_platform ON cmis.batch_execution_log(platform, started_at DESC)");
            DB::statement("CREATE INDEX idx_batch_log_batch_id ON cmis.batch_execution_log(batch_id)");
            DB::statement("CREATE INDEX idx_batch_log_connection ON cmis.batch_execution_log(connection_id, started_at DESC) WHERE connection_id IS NOT NULL");
            DB::statement("CREATE INDEX idx_batch_log_org ON cmis.batch_execution_log(org_id, started_at DESC) WHERE org_id IS NOT NULL");
            DB::statement("CREATE INDEX idx_batch_log_rate_limit ON cmis.batch_execution_log(platform, rate_limit_hit) WHERE rate_limit_hit = TRUE");
            DB::statement("CREATE INDEX idx_batch_log_failures ON cmis.batch_execution_log(platform, started_at DESC) WHERE failure_count > 0");

            // Enable PUBLIC RLS - monitoring data accessible system-wide
            $this->enablePublicRLS('cmis.batch_execution_log');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP POLICY IF EXISTS allow_all ON cmis.batch_execution_log");
        DB::statement("ALTER TABLE cmis.batch_execution_log DISABLE ROW LEVEL SECURITY");
        DB::statement('DROP TABLE IF EXISTS cmis.batch_execution_log CASCADE');
    }
};
