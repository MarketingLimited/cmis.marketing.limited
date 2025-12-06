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
     * Creates the webhook_events table for storing incoming platform webhook events.
     * This provides an audit trail and allows for reliable event reprocessing.
     *
     * Uses PUBLIC RLS because webhook events arrive from external platforms
     * before organization context can be determined.
     */
    public function up(): void
    {
        if (!$this->tableExists('cmis', 'webhook_events')) {
            DB::statement("
                CREATE TABLE cmis.webhook_events (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

                    -- Platform identification
                    platform VARCHAR(50) NOT NULL CHECK (platform IN ('meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat', 'whatsapp')),
                    event_type VARCHAR(100) NOT NULL,
                    external_event_id VARCHAR(255) NULL,

                    -- Request data
                    headers JSONB NOT NULL DEFAULT '{}',
                    payload JSONB NOT NULL DEFAULT '{}',
                    raw_payload TEXT NULL,

                    -- Signature verification
                    signature VARCHAR(500) NULL,
                    signature_valid BOOLEAN NULL,

                    -- Request metadata
                    source_ip VARCHAR(45) NULL,
                    user_agent VARCHAR(500) NULL,
                    request_method VARCHAR(10) NOT NULL DEFAULT 'POST',

                    -- Processing status
                    status VARCHAR(20) NOT NULL DEFAULT 'received' CHECK (status IN ('received', 'processing', 'processed', 'failed', 'ignored', 'duplicate')),
                    attempts INTEGER NOT NULL DEFAULT 0,
                    max_attempts INTEGER NOT NULL DEFAULT 3,
                    error_message TEXT NULL,
                    error_code VARCHAR(50) NULL,

                    -- Organization context (determined after processing)
                    org_id UUID NULL,
                    connection_id UUID NULL,
                    related_asset_id UUID NULL,

                    -- Timing
                    received_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    processed_at TIMESTAMP WITH TIME ZONE NULL,
                    next_retry_at TIMESTAMP WITH TIME ZONE NULL,

                    -- Timestamps
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

                    -- Foreign keys (nullable - set after processing)
                    CONSTRAINT fk_webhook_events_connection FOREIGN KEY (connection_id)
                        REFERENCES cmis.platform_connections(connection_id) ON DELETE SET NULL,
                    CONSTRAINT fk_webhook_events_asset FOREIGN KEY (related_asset_id)
                        REFERENCES cmis.platform_assets(asset_id) ON DELETE SET NULL
                )
            ");

            // Performance indexes for event processing
            DB::statement("CREATE INDEX idx_webhook_events_unprocessed ON cmis.webhook_events(platform, status, received_at) WHERE status IN ('received', 'failed')");
            DB::statement("CREATE INDEX idx_webhook_events_platform_type ON cmis.webhook_events(platform, event_type)");
            DB::statement("CREATE INDEX idx_webhook_events_external_id ON cmis.webhook_events(platform, external_event_id) WHERE external_event_id IS NOT NULL");
            DB::statement("CREATE INDEX idx_webhook_events_org ON cmis.webhook_events(org_id, received_at) WHERE org_id IS NOT NULL");
            DB::statement("CREATE INDEX idx_webhook_events_retry ON cmis.webhook_events(next_retry_at) WHERE status = 'failed' AND attempts < max_attempts");
            DB::statement("CREATE INDEX idx_webhook_events_received ON cmis.webhook_events(received_at DESC)");
            DB::statement("CREATE INDEX idx_webhook_events_payload ON cmis.webhook_events USING GIN (payload)");

            // Enable PUBLIC RLS - events arrive before org identification
            $this->enablePublicRLS('cmis.webhook_events');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP POLICY IF EXISTS allow_all ON cmis.webhook_events");
        DB::statement("ALTER TABLE cmis.webhook_events DISABLE ROW LEVEL SECURITY");
        DB::statement('DROP TABLE IF EXISTS cmis.webhook_events CASCADE');
    }
};
