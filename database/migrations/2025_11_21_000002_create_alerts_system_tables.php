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
     */
    public function up(): void
    {
        // Create alert_rules table
        if (!$this->tableExists('cmis', 'alert_rules')) {
            DB::statement("
                CREATE TABLE cmis.alert_rules (
                    rule_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    created_by UUID NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id UUID NULL,
                    metric VARCHAR(100) NOT NULL,
                    condition VARCHAR(20) NOT NULL CHECK (condition IN ('gt', 'gte', 'lt', 'lte', 'eq', 'ne', 'change_pct')),
                    threshold DECIMAL(20, 4) NOT NULL,
                    time_window_minutes INTEGER NOT NULL DEFAULT 60,
                    severity VARCHAR(20) NOT NULL DEFAULT 'medium' CHECK (severity IN ('critical', 'high', 'medium', 'low')),
                    notification_channels JSONB NOT NULL DEFAULT '[]',
                    notification_config JSONB NOT NULL DEFAULT '{}',
                    cooldown_minutes INTEGER NOT NULL DEFAULT 60,
                    is_active BOOLEAN NOT NULL DEFAULT TRUE,
                    last_triggered_at TIMESTAMP NULL,
                    trigger_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    deleted_at TIMESTAMP NULL
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_rules_org_id ON cmis.alert_rules(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_rules_created_by ON cmis.alert_rules(created_by)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_rules_entity ON cmis.alert_rules(entity_type, entity_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_rules_active ON cmis.alert_rules(is_active, last_triggered_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_rules_severity ON cmis.alert_rules(severity)");

            $this->enableRLS('cmis.alert_rules');
        }

        // Create alert_history table
        if (!$this->tableExists('cmis', 'alert_history')) {
            DB::statement("
                CREATE TABLE cmis.alert_history (
                    alert_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    rule_id UUID NOT NULL REFERENCES cmis.alert_rules(rule_id) ON DELETE CASCADE,
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    triggered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id UUID NULL,
                    metric VARCHAR(100) NOT NULL,
                    actual_value DECIMAL(20, 4) NOT NULL,
                    threshold_value DECIMAL(20, 4) NOT NULL,
                    condition VARCHAR(20) NOT NULL,
                    severity VARCHAR(20) NOT NULL CHECK (severity IN ('critical', 'high', 'medium', 'low')),
                    message TEXT NOT NULL,
                    metadata JSONB NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'acknowledged', 'resolved', 'snoozed')),
                    acknowledged_by UUID NULL REFERENCES cmis.users(user_id) ON DELETE SET NULL,
                    acknowledged_at TIMESTAMP NULL,
                    resolved_at TIMESTAMP NULL,
                    snoozed_until TIMESTAMP NULL,
                    resolution_notes TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_history_rule_id ON cmis.alert_history(rule_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_history_org_id ON cmis.alert_history(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_history_entity ON cmis.alert_history(entity_type, entity_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_history_triggered ON cmis.alert_history(triggered_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_history_status ON cmis.alert_history(status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_history_severity ON cmis.alert_history(severity)");

            $this->enableRLS('cmis.alert_history');
        }

        // Create alert_notifications table
        if (!$this->tableExists('cmis', 'alert_notifications')) {
            DB::statement("
                CREATE TABLE cmis.alert_notifications (
                    notification_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    alert_id UUID NOT NULL REFERENCES cmis.alert_history(alert_id) ON DELETE CASCADE,
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    channel VARCHAR(50) NOT NULL,
                    recipient TEXT NOT NULL,
                    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed', 'delivered', 'read')),
                    error_message TEXT NULL,
                    retry_count INTEGER NOT NULL DEFAULT 0,
                    delivered_at TIMESTAMP NULL,
                    read_at TIMESTAMP NULL,
                    metadata JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_notifications_alert_id ON cmis.alert_notifications(alert_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_notifications_org_id ON cmis.alert_notifications(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_notifications_channel_status ON cmis.alert_notifications(channel, status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_notifications_sent_at ON cmis.alert_notifications(sent_at)");

            $this->enableRLS('cmis.alert_notifications');
        }

        // Create alert_templates table
        if (!$this->tableExists('cmis', 'alert_templates')) {
            DB::statement("
                CREATE TABLE cmis.alert_templates (
                    template_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    created_by UUID NULL REFERENCES cmis.users(user_id) ON DELETE SET NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    category VARCHAR(50) NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    default_config JSONB NOT NULL DEFAULT '{}',
                    is_public BOOLEAN NOT NULL DEFAULT FALSE,
                    is_system BOOLEAN NOT NULL DEFAULT FALSE,
                    usage_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_templates_category ON cmis.alert_templates(category)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_templates_entity_type ON cmis.alert_templates(entity_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_alert_templates_is_public ON cmis.alert_templates(is_public)");

            $this->enablePublicRLS('cmis.alert_templates');
        }

        // Create escalation_policies table
        if (!$this->tableExists('cmis', 'escalation_policies')) {
            DB::statement("
                CREATE TABLE cmis.escalation_policies (
                    policy_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    escalation_levels JSONB NOT NULL DEFAULT '[]',
                    is_active BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_escalation_policies_org_id ON cmis.escalation_policies(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_escalation_policies_is_active ON cmis.escalation_policies(is_active)");

            $this->enableRLS('cmis.escalation_policies');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis.escalation_policies CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.alert_templates CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.alert_notifications CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.alert_history CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.alert_rules CASCADE");
    }
};
