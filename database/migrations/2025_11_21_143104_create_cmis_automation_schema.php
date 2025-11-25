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
        // Create cmis_automation schema
        DB::statement('CREATE SCHEMA IF NOT EXISTS cmis_automation');

        // Create automation_rules table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis_automation.automation_rules (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL REFERENCES cmis.organizations(id) ON DELETE CASCADE,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                condition JSONB NOT NULL,
                action JSONB NOT NULL,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Add indexes for automation_rules
        DB::statement('CREATE INDEX IF NOT EXISTS idx_automation_rules_org_id ON cmis_automation.automation_rules(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_automation_rules_is_active ON cmis_automation.automation_rules(is_active)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_automation_rules_created_at ON cmis_automation.automation_rules(created_at)');

        // Enable RLS for automation_rules
        DB::statement('ALTER TABLE cmis_automation.automation_rules ENABLE ROW LEVEL SECURITY');

        // Drop existing policy if it exists, then create RLS policy for automation_rules
        DB::statement('DROP POLICY IF EXISTS org_isolation_policy ON cmis_automation.automation_rules');

        DB::statement("
            CREATE POLICY org_isolation_policy ON cmis_automation.automation_rules
            FOR ALL
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
        ");

        // Create rule_execution_log table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis_automation.rule_execution_log (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                rule_id UUID REFERENCES cmis_automation.automation_rules(id) ON DELETE SET NULL,
                campaign_id UUID NOT NULL REFERENCES cmis.campaigns(id) ON DELETE CASCADE,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Add indexes for rule_execution_log
        DB::statement('CREATE INDEX IF NOT EXISTS idx_rule_execution_log_rule_id ON cmis_automation.rule_execution_log(rule_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_rule_execution_log_campaign_id ON cmis_automation.rule_execution_log(campaign_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_rule_execution_log_executed_at ON cmis_automation.rule_execution_log(executed_at DESC)');

        // Enable RLS for rule_execution_log
        DB::statement('ALTER TABLE cmis_automation.rule_execution_log ENABLE ROW LEVEL SECURITY');

        // Drop existing policy if it exists, then create RLS policy for rule_execution_log (access via campaign ownership)
        DB::statement('DROP POLICY IF EXISTS org_isolation_policy ON cmis_automation.rule_execution_log');

        DB::statement("
            CREATE POLICY org_isolation_policy ON cmis_automation.rule_execution_log
            FOR ALL
            USING (
                campaign_id IN (
                    SELECT id FROM cmis.campaigns
                    WHERE org_id = current_setting('app.current_org_id', true)::uuid
                )
            )
        ");

        // Grant permissions to application role (if exists)
        DB::statement('GRANT USAGE ON SCHEMA cmis_automation TO begin');
        DB::statement('GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA cmis_automation TO begin');
        DB::statement('GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA cmis_automation TO begin');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop policies first
        DB::statement('DROP POLICY IF EXISTS org_isolation_policy ON cmis_automation.rule_execution_log');
        DB::statement('DROP POLICY IF EXISTS org_isolation_policy ON cmis_automation.automation_rules');

        // Drop tables
        DB::statement('DROP TABLE IF EXISTS cmis_automation.rule_execution_log CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis_automation.automation_rules CASCADE');

        // Drop schema
        DB::statement('DROP SCHEMA IF EXISTS cmis_automation CASCADE');
    }
};
