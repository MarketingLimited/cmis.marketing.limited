<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if required tables don't exist yet (migration ordering)
        if (!Schema::hasTable('cmis.orgs') || !Schema::hasTable('cmis.users')) {
            return;
        }

        // Create integrations table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.integrations (
                integration_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                integration_key VARCHAR(50) NOT NULL,
                integration_name VARCHAR(255) NOT NULL,
                auth_type VARCHAR(20) NOT NULL, -- api_key, oauth, basic
                credentials TEXT, -- Encrypted credentials
                config JSONB,
                is_active BOOLEAN DEFAULT true,
                created_by UUID NOT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on org_id for integration lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_integrations_org
            ON cmis.integrations(org_id)
        ");

        // Create index on integration_key
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_integrations_key
            ON cmis.integrations(integration_key)
        ");

        // Create webhooks table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.webhooks (
                webhook_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                webhook_name VARCHAR(255) NOT NULL,
                webhook_url VARCHAR(500) NOT NULL,
                webhook_secret VARCHAR(255) NOT NULL,
                events JSONB NOT NULL, -- Array of event types
                is_active BOOLEAN DEFAULT true,
                created_by UUID NOT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on org_id for webhook lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_webhooks_org
            ON cmis.webhooks(org_id)
        ");

        // Create webhook_deliveries table for tracking
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.webhook_deliveries (
                delivery_id UUID PRIMARY KEY,
                webhook_id UUID NOT NULL,
                event VARCHAR(100) NOT NULL,
                status_code INTEGER,
                response TEXT,
                delivered_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (webhook_id) REFERENCES cmis.webhooks(webhook_id) ON DELETE CASCADE
            )
        ");

        // Create index on webhook_id
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_webhook_deliveries_webhook
            ON cmis.webhook_deliveries(webhook_id, delivered_at DESC)
        ");

        // Create api_keys table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.api_keys (
                api_key_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                key_name VARCHAR(255) NOT NULL,
                api_key_hash VARCHAR(255) NOT NULL UNIQUE,
                permissions JSONB NOT NULL, -- Array of permissions: read, write, delete, admin
                is_active BOOLEAN DEFAULT true,
                expires_at TIMESTAMPTZ,
                last_used_at TIMESTAMPTZ,
                created_by UUID NOT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                revoked_at TIMESTAMPTZ,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on org_id for API key lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_api_keys_org
            ON cmis.api_keys(org_id)
        ");

        // Create index on api_key_hash for authentication
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_api_keys_hash
            ON cmis.api_keys(api_key_hash)
        ");

        // Create integration_logs table for tracking
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.integration_logs (
                log_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                integration_id UUID,
                action VARCHAR(100) NOT NULL,
                status VARCHAR(20) NOT NULL, -- success, failure
                request_data JSONB,
                response_data JSONB,
                error_message TEXT,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL
            )
        ");

        // Create index on org_id and integration_id
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_integration_logs_org_integration
            ON cmis.integration_logs(org_id, integration_id, created_at DESC)
        ");

        // Add comments to tables
        DB::statement("
            COMMENT ON TABLE cmis.integrations IS 'Third-party integrations - Sprint 6.4'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.webhooks IS 'Webhook configurations for event notifications - Sprint 6.4'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.webhook_deliveries IS 'Webhook delivery tracking - Sprint 6.4'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.api_keys IS 'API keys for external access - Sprint 6.4'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.integration_logs IS 'Integration activity logs - Sprint 6.4'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis.integration_logs CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.webhook_deliveries CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.api_keys CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.webhooks CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.integrations CASCADE");
    }
};
