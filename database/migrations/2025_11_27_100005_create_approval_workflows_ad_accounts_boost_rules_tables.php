<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure ad_accounts has a primary key (needed for foreign key reference)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = 'cmis' AND table_name = 'ad_accounts' AND constraint_type = 'PRIMARY KEY') THEN
                    ALTER TABLE cmis.ad_accounts ADD PRIMARY KEY (id);
                END IF;
            END \$\$;
        ");

        // ========== APPROVAL WORKFLOWS ==========
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.approval_workflows (
                workflow_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                profile_group_id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                is_active BOOLEAN DEFAULT true,
                apply_to_platforms JSONB DEFAULT '[]',
                apply_to_users JSONB DEFAULT '[]',
                apply_to_post_types JSONB DEFAULT '[]',
                approval_steps JSONB DEFAULT '[]',
                notify_on_submission BOOLEAN DEFAULT true,
                notify_on_approval BOOLEAN DEFAULT true,
                notify_on_rejection BOOLEAN DEFAULT true,
                created_by UUID NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP
            )
        ");

        DB::statement('CREATE INDEX IF NOT EXISTS idx_approval_workflows_org_id ON cmis.approval_workflows(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_approval_workflows_profile_group_id ON cmis.approval_workflows(profile_group_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_approval_workflows_profile_group_id_is_active ON cmis.approval_workflows(profile_group_id, is_active)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_approval_workflows_created_by ON cmis.approval_workflows(created_by)');

        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'approval_workflows_org_id_foreign') THEN
                    ALTER TABLE cmis.approval_workflows ADD CONSTRAINT approval_workflows_org_id_foreign FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'approval_workflows_profile_group_id_foreign') THEN
                    ALTER TABLE cmis.approval_workflows ADD CONSTRAINT approval_workflows_profile_group_id_foreign FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'approval_workflows_created_by_foreign') THEN
                    ALTER TABLE cmis.approval_workflows ADD CONSTRAINT approval_workflows_created_by_foreign FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE RESTRICT;
                END IF;
            END \$\$;
        ");

        DB::statement('ALTER TABLE cmis.approval_workflows ENABLE ROW LEVEL SECURITY');

        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'approval_workflows' AND policyname = 'approval_workflows_policy') THEN
                    CREATE POLICY approval_workflows_policy ON cmis.approval_workflows
                    USING (org_id = current_setting('app.current_org_id', true)::uuid)
                    WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
            END \$\$;
        ");

        // ========== BOOST RULES ==========
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.boost_rules (
                boost_rule_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                profile_group_id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                is_active BOOLEAN DEFAULT true,
                trigger_type VARCHAR(30) DEFAULT 'manual',
                trigger_threshold NUMERIC,
                trigger_metric VARCHAR(50),
                trigger_time_window_hours INTEGER,
                budget_type VARCHAR(20),
                budget_amount NUMERIC,
                budget_currency VARCHAR(3) DEFAULT 'USD',
                duration_hours INTEGER,
                delay_after_publish JSONB,
                performance_threshold JSONB,
                apply_to_social_profiles JSONB DEFAULT '[]',
                ad_account_id UUID,
                boost_config JSONB DEFAULT '{}',
                targeting_options JSONB DEFAULT '{}',
                max_boosts_per_day INTEGER,
                max_budget_per_day NUMERIC,
                platforms JSONB DEFAULT '[]',
                settings JSONB DEFAULT '{}',
                created_by UUID NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP
            )
        ");

        DB::statement('CREATE INDEX IF NOT EXISTS idx_boost_rules_org_id ON cmis.boost_rules(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_boost_rules_profile_group_id ON cmis.boost_rules(profile_group_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_boost_rules_profile_group_id_is_active ON cmis.boost_rules(profile_group_id, is_active)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_boost_rules_ad_account_id ON cmis.boost_rules(ad_account_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_boost_rules_created_by ON cmis.boost_rules(created_by)');

        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'boost_rules_org_id_foreign') THEN
                    ALTER TABLE cmis.boost_rules ADD CONSTRAINT boost_rules_org_id_foreign FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'boost_rules_profile_group_id_foreign') THEN
                    ALTER TABLE cmis.boost_rules ADD CONSTRAINT boost_rules_profile_group_id_foreign FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'boost_rules_ad_account_id_foreign') THEN
                    ALTER TABLE cmis.boost_rules ADD CONSTRAINT boost_rules_ad_account_id_foreign FOREIGN KEY (ad_account_id) REFERENCES cmis.ad_accounts(id) ON DELETE SET NULL;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'boost_rules_created_by_foreign') THEN
                    ALTER TABLE cmis.boost_rules ADD CONSTRAINT boost_rules_created_by_foreign FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE RESTRICT;
                END IF;
            END \$\$;
        ");

        DB::statement('ALTER TABLE cmis.boost_rules ENABLE ROW LEVEL SECURITY');

        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'boost_rules' AND policyname = 'boost_rules_policy') THEN
                    CREATE POLICY boost_rules_policy ON cmis.boost_rules
                    USING (org_id = current_setting('app.current_org_id', true)::uuid)
                    WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
            END \$\$;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.boost_rules CASCADE');
        DB::statement('DROP TABLE IF EXISTS cmis.approval_workflows CASCADE');
    }
};
