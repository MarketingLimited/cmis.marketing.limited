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
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.brand_safety_policies (
                policy_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                profile_group_id UUID,
                name VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                is_active BOOLEAN DEFAULT true,
                prohibit_derogatory_language BOOLEAN DEFAULT false,
                prohibit_profanity BOOLEAN DEFAULT false,
                prohibit_offensive_content BOOLEAN DEFAULT false,
                custom_banned_words JSONB DEFAULT '[]',
                custom_banned_phrases JSONB DEFAULT '[]',
                custom_requirements TEXT,
                require_disclosure BOOLEAN DEFAULT false,
                disclosure_text VARCHAR(255),
                require_fact_checking BOOLEAN DEFAULT false,
                require_source_citation BOOLEAN DEFAULT false,
                industry_regulations JSONB DEFAULT '[]',
                compliance_regions JSONB DEFAULT '[]',
                enforcement_level VARCHAR(20) DEFAULT 'warning',
                auto_reject_violations BOOLEAN DEFAULT false,
                use_default_template BOOLEAN DEFAULT false,
                template_name VARCHAR(100),
                created_by UUID NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP
            )
        ");

        // Create indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_brand_safety_policies_org_id ON cmis.brand_safety_policies(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_brand_safety_policies_profile_group_id ON cmis.brand_safety_policies(profile_group_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_brand_safety_policies_org_id_is_active ON cmis.brand_safety_policies(org_id, is_active)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_brand_safety_policies_created_by ON cmis.brand_safety_policies(created_by)');

        // Create foreign keys (skip if exist)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'brand_safety_policies_org_id_foreign') THEN
                    ALTER TABLE cmis.brand_safety_policies ADD CONSTRAINT brand_safety_policies_org_id_foreign FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'brand_safety_policies_profile_group_id_foreign') THEN
                    ALTER TABLE cmis.brand_safety_policies ADD CONSTRAINT brand_safety_policies_profile_group_id_foreign FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'brand_safety_policies_created_by_foreign') THEN
                    ALTER TABLE cmis.brand_safety_policies ADD CONSTRAINT brand_safety_policies_created_by_foreign FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE RESTRICT;
                END IF;
            END \$\$;
        ");

        // Enable RLS
        DB::statement('ALTER TABLE cmis.brand_safety_policies ENABLE ROW LEVEL SECURITY');

        // Create RLS policies (skip if exist)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'brand_safety_policies' AND policyname = 'brand_safety_policies_select_policy') THEN
                    CREATE POLICY brand_safety_policies_select_policy ON cmis.brand_safety_policies FOR SELECT USING (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'brand_safety_policies' AND policyname = 'brand_safety_policies_insert_policy') THEN
                    CREATE POLICY brand_safety_policies_insert_policy ON cmis.brand_safety_policies FOR INSERT WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'brand_safety_policies' AND policyname = 'brand_safety_policies_update_policy') THEN
                    CREATE POLICY brand_safety_policies_update_policy ON cmis.brand_safety_policies FOR UPDATE USING (org_id = current_setting('app.current_org_id', true)::uuid) WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'brand_safety_policies' AND policyname = 'brand_safety_policies_delete_policy') THEN
                    CREATE POLICY brand_safety_policies_delete_policy ON cmis.brand_safety_policies FOR DELETE USING (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
            END \$\$;
        ");

        // Add foreign key from profile_groups to brand_safety_policies
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'profile_groups_brand_safety_policy_id_foreign') THEN
                    ALTER TABLE cmis.profile_groups ADD CONSTRAINT profile_groups_brand_safety_policy_id_foreign FOREIGN KEY (brand_safety_policy_id) REFERENCES cmis.brand_safety_policies(policy_id) ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE cmis.profile_groups DROP CONSTRAINT IF EXISTS profile_groups_brand_safety_policy_id_foreign');
        DB::statement('DROP TABLE IF EXISTS cmis.brand_safety_policies CASCADE');
    }
};
