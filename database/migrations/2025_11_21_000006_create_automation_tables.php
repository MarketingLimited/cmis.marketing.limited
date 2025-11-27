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
     * Run the migrations (Phase 17: Campaign Automation & Orchestration).
     */
    public function up(): void
    {
        // ===== Automation Rules Table =====
        if (!$this->tableExists('cmis', 'automation_rules')) {
            DB::statement("
                CREATE TABLE cmis.automation_rules (
                    rule_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    created_by UUID NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    rule_type VARCHAR(50) NOT NULL,
                    entity_type VARCHAR(50) NULL,
                    entity_id UUID NULL,
                    conditions JSONB NOT NULL DEFAULT '[]',
                    condition_logic VARCHAR(10) NOT NULL DEFAULT 'and' CHECK (condition_logic IN ('and', 'or')),
                    actions JSONB NOT NULL DEFAULT '[]',
                    priority VARCHAR(20) NOT NULL DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high', 'critical')),
                    status VARCHAR(30) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused', 'archived')),
                    enabled BOOLEAN NOT NULL DEFAULT TRUE,
                    max_executions_per_day INTEGER NULL,
                    cooldown_minutes INTEGER NOT NULL DEFAULT 60,
                    last_executed_at TIMESTAMP NULL,
                    execution_count INTEGER NOT NULL DEFAULT 0,
                    success_count INTEGER NOT NULL DEFAULT 0,
                    failure_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_rules_org_id ON cmis.automation_rules(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_rules_entity ON cmis.automation_rules(org_id, entity_type, entity_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_rules_status ON cmis.automation_rules(status, enabled)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_rules_type ON cmis.automation_rules(rule_type)");

            DB::statement('ALTER TABLE cmis.automation_rules ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.automation_rules");
            DB::statement("CREATE POLICY org_isolation ON cmis.automation_rules USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Automation Executions Table =====
        if (!$this->tableExists('cmis', 'automation_executions')) {
            DB::statement("
                CREATE TABLE cmis.automation_executions (
                    execution_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    rule_id UUID NOT NULL REFERENCES cmis.automation_rules(rule_id) ON DELETE CASCADE,
                    entity_id UUID NULL,
                    status VARCHAR(30) NOT NULL CHECK (status IN ('success', 'failure', 'partial', 'skipped')),
                    executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    duration_ms INTEGER NULL,
                    conditions_evaluated JSONB NOT NULL DEFAULT '[]',
                    actions_executed JSONB NOT NULL DEFAULT '[]',
                    results JSONB NOT NULL DEFAULT '{}',
                    error_message TEXT NULL,
                    context JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_executions_org_id ON cmis.automation_executions(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_executions_rule ON cmis.automation_executions(org_id, rule_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_executions_executed ON cmis.automation_executions(executed_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_executions_status ON cmis.automation_executions(status)");

            DB::statement('ALTER TABLE cmis.automation_executions ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.automation_executions");
            DB::statement("CREATE POLICY org_isolation ON cmis.automation_executions USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Automation Workflows Table =====
        if (!$this->tableExists('cmis', 'automation_workflows')) {
            DB::statement("
                CREATE TABLE cmis.automation_workflows (
                    workflow_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    created_by UUID NULL REFERENCES cmis.users(user_id),
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    category VARCHAR(50) NOT NULL CHECK (category IN ('performance', 'budget', 'creative', 'scheduling')),
                    is_template BOOLEAN NOT NULL DEFAULT FALSE,
                    rules JSONB NOT NULL DEFAULT '[]',
                    config JSONB NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'active', 'archived')),
                    usage_count INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_workflows_org ON cmis.automation_workflows(org_id, category)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_workflows_template ON cmis.automation_workflows(is_template, status)");

            DB::statement('ALTER TABLE cmis.automation_workflows ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.automation_workflows");
            DB::statement("CREATE POLICY org_isolation ON cmis.automation_workflows USING (org_id IS NULL OR org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Automation Schedules Table =====
        if (!$this->tableExists('cmis', 'automation_schedules')) {
            DB::statement("
                CREATE TABLE cmis.automation_schedules (
                    schedule_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    rule_id UUID NOT NULL REFERENCES cmis.automation_rules(rule_id) ON DELETE CASCADE,
                    frequency VARCHAR(50) NOT NULL CHECK (frequency IN ('once', 'hourly', 'daily', 'weekly', 'monthly', 'custom')),
                    cron_expression VARCHAR(100) NULL,
                    time_of_day TIME NULL,
                    days_of_week JSONB NULL,
                    day_of_month INTEGER NULL,
                    timezone VARCHAR(50) NOT NULL DEFAULT 'UTC',
                    starts_at TIMESTAMP NULL,
                    ends_at TIMESTAMP NULL,
                    last_run_at TIMESTAMP NULL,
                    next_run_at TIMESTAMP NULL,
                    enabled BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_schedules_org_id ON cmis.automation_schedules(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_schedules_rule ON cmis.automation_schedules(org_id, rule_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_schedules_next_run ON cmis.automation_schedules(next_run_at, enabled)");

            DB::statement('ALTER TABLE cmis.automation_schedules ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.automation_schedules");
            DB::statement("CREATE POLICY org_isolation ON cmis.automation_schedules USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }

        // ===== Automation Audit Log Table =====
        if (!$this->tableExists('cmis', 'automation_audit_log')) {
            DB::statement("
                CREATE TABLE cmis.automation_audit_log (
                    audit_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                    rule_id UUID NULL REFERENCES cmis.automation_rules(rule_id) ON DELETE SET NULL,
                    execution_id UUID NULL REFERENCES cmis.automation_executions(execution_id) ON DELETE SET NULL,
                    user_id UUID NULL,
                    action VARCHAR(100) NOT NULL,
                    entity_type VARCHAR(50) NULL,
                    entity_id UUID NULL,
                    changes JSONB NULL,
                    metadata JSONB NULL,
                    ip_address VARCHAR(45) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_audit_org_id ON cmis.automation_audit_log(org_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_audit_rule ON cmis.automation_audit_log(org_id, rule_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_audit_created ON cmis.automation_audit_log(created_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_automation_audit_action ON cmis.automation_audit_log(action)");

            DB::statement('ALTER TABLE cmis.automation_audit_log ENABLE ROW LEVEL SECURITY');
            DB::statement("DROP POLICY IF EXISTS org_isolation ON cmis.automation_audit_log");
            DB::statement("CREATE POLICY org_isolation ON cmis.automation_audit_log USING (org_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.automation_audit_log CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.automation_schedules CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.automation_workflows CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.automation_executions CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.automation_rules CASCADE');
    }
};
