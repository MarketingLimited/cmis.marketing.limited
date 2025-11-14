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

        // Create automation_rules table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.automation_rules (
                rule_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                rule_name VARCHAR(255) NOT NULL,
                rule_type VARCHAR(50) NOT NULL, -- post_scheduling, budget_adjustment, response_automation
                trigger_condition JSONB NOT NULL,
                action JSONB NOT NULL,
                is_active BOOLEAN DEFAULT true,
                created_by UUID NOT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE CASCADE
            )
        ");

        // Create index on org_id for rule lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_automation_rules_org
            ON cmis.automation_rules(org_id)
        ");

        // Create index on rule_type for filtering
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_automation_rules_type
            ON cmis.automation_rules(rule_type)
        ");

        // Create index on is_active for filtering active rules
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_automation_rules_active
            ON cmis.automation_rules(is_active)
        ");

        // Add comment to table
        DB::statement("
            COMMENT ON TABLE cmis.automation_rules IS 'AI-powered automation rules - Sprint 6.2'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis.automation_rules CASCADE");
    }
};
