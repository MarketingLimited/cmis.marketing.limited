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
        // Create table if not exists
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.profile_groups (
                group_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                client_location JSONB,
                logo_url TEXT,
                color VARCHAR(7) DEFAULT '#3B82F6',
                default_link_shortener VARCHAR(50),
                timezone VARCHAR(100) DEFAULT 'UTC',
                language VARCHAR(10) DEFAULT 'ar',
                brand_voice_id UUID,
                brand_safety_policy_id UUID,
                created_by UUID NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP
            )
        ");

        // Create indexes if not exist
        DB::statement('CREATE INDEX IF NOT EXISTS idx_profile_groups_org_id ON cmis.profile_groups(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_profile_groups_created_by ON cmis.profile_groups(created_by)');

        // Create foreign keys if not exist (using DO block)
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'profile_groups_org_id_foreign'
                    AND table_name = 'profile_groups'
                ) THEN
                    ALTER TABLE cmis.profile_groups
                    ADD CONSTRAINT profile_groups_org_id_foreign
                    FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;
                END IF;
            END $$;
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'profile_groups_created_by_foreign'
                    AND table_name = 'profile_groups'
                ) THEN
                    ALTER TABLE cmis.profile_groups
                    ADD CONSTRAINT profile_groups_created_by_foreign
                    FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE RESTRICT;
                END IF;
            END $$;
        ");

        // Enable RLS
        DB::statement('ALTER TABLE cmis.profile_groups ENABLE ROW LEVEL SECURITY');

        // Create RLS policies if not exist
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_policies
                    WHERE tablename = 'profile_groups' AND policyname = 'profile_groups_select_policy'
                ) THEN
                    CREATE POLICY profile_groups_select_policy ON cmis.profile_groups
                    FOR SELECT
                    USING (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
            END $$;
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_policies
                    WHERE tablename = 'profile_groups' AND policyname = 'profile_groups_insert_policy'
                ) THEN
                    CREATE POLICY profile_groups_insert_policy ON cmis.profile_groups
                    FOR INSERT
                    WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
            END $$;
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_policies
                    WHERE tablename = 'profile_groups' AND policyname = 'profile_groups_update_policy'
                ) THEN
                    CREATE POLICY profile_groups_update_policy ON cmis.profile_groups
                    FOR UPDATE
                    USING (org_id = current_setting('app.current_org_id', true)::uuid)
                    WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
            END $$;
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_policies
                    WHERE tablename = 'profile_groups' AND policyname = 'profile_groups_delete_policy'
                ) THEN
                    CREATE POLICY profile_groups_delete_policy ON cmis.profile_groups
                    FOR DELETE
                    USING (org_id = current_setting('app.current_org_id', true)::uuid);
                END IF;
            END $$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.profile_groups CASCADE');
    }
};
