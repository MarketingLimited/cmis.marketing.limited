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
     * Creates the batch_request_queue table for queuing platform API requests.
     * Requests are collected and executed in batches to reduce API calls and respect rate limits.
     *
     * Uses org_id RLS isolation - each organization can only see their own queued requests.
     */
    public function up(): void
    {
        if (!$this->tableExists('cmis', 'batch_request_queue')) {
            DB::statement("
                CREATE TABLE cmis.batch_request_queue (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

                    -- Organization context (for RLS)
                    org_id UUID NOT NULL,

                    -- Platform and connection
                    platform VARCHAR(50) NOT NULL CHECK (platform IN ('meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat', 'pinterest')),
                    connection_id UUID NOT NULL,

                    -- Request details
                    request_type VARCHAR(100) NOT NULL,
                    request_key VARCHAR(64) NOT NULL,
                    request_params JSONB NOT NULL DEFAULT '{}',

                    -- Batching
                    batch_group VARCHAR(100) NULL,
                    batch_id UUID NULL,
                    priority INTEGER NOT NULL DEFAULT 5 CHECK (priority BETWEEN 1 AND 10),

                    -- Status tracking
                    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'queued', 'processing', 'completed', 'failed', 'cancelled')),
                    attempts INTEGER NOT NULL DEFAULT 0,
                    max_attempts INTEGER NOT NULL DEFAULT 3,

                    -- Response
                    response_data JSONB NULL,
                    error_message TEXT NULL,
                    error_code VARCHAR(50) NULL,

                    -- Scheduling
                    scheduled_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    started_at TIMESTAMP WITH TIME ZONE NULL,
                    completed_at TIMESTAMP WITH TIME ZONE NULL,
                    next_retry_at TIMESTAMP WITH TIME ZONE NULL,

                    -- Timestamps
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

                    -- Foreign keys
                    CONSTRAINT fk_batch_queue_connection FOREIGN KEY (connection_id)
                        REFERENCES cmis.platform_connections(connection_id) ON DELETE CASCADE
                )
            ");

            // Performance indexes for batch processing
            DB::statement("CREATE INDEX idx_batch_queue_pending ON cmis.batch_request_queue(platform, status, scheduled_at) WHERE status = 'pending'");
            DB::statement("CREATE INDEX idx_batch_queue_priority ON cmis.batch_request_queue(priority, scheduled_at) WHERE status = 'pending'");
            DB::statement("CREATE INDEX idx_batch_queue_connection ON cmis.batch_request_queue(connection_id, status)");
            DB::statement("CREATE INDEX idx_batch_queue_batch_id ON cmis.batch_request_queue(batch_id) WHERE batch_id IS NOT NULL");
            DB::statement("CREATE INDEX idx_batch_queue_org ON cmis.batch_request_queue(org_id, status)");
            DB::statement("CREATE INDEX idx_batch_queue_retry ON cmis.batch_request_queue(next_retry_at) WHERE status = 'failed' AND attempts < max_attempts");

            // Deduplication: only one pending request per platform/key combination
            DB::statement("CREATE UNIQUE INDEX idx_batch_queue_dedup ON cmis.batch_request_queue(platform, request_key) WHERE status IN ('pending', 'queued', 'processing')");

            // Enable RLS with org_id isolation
            $this->enableRLS('cmis.batch_request_queue');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->disableRLS('cmis.batch_request_queue');
        DB::statement('DROP TABLE IF EXISTS cmis.batch_request_queue CASCADE');
    }
};
