<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.profile_group_members (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                profile_group_id UUID NOT NULL,
                user_id UUID NOT NULL,
                role VARCHAR(20) DEFAULT 'contributor',
                permissions JSONB DEFAULT '{
                    \"can_publish\": false,
                    \"can_schedule\": false,
                    \"can_edit_drafts\": true,
                    \"can_delete\": false,
                    \"can_manage_team\": false,
                    \"can_manage_brand_voice\": false,
                    \"can_manage_ad_accounts\": false,
                    \"requires_approval\": true
                }'::jsonb,
                assigned_by UUID NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_active_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP,
                CONSTRAINT profile_group_members_unique UNIQUE (profile_group_id, user_id)
            )
        ");

        // Create indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_profile_group_members_profile_group_id ON cmis.profile_group_members(profile_group_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_profile_group_members_user_id ON cmis.profile_group_members(user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_profile_group_members_assigned_by ON cmis.profile_group_members(assigned_by)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_profile_group_members_deleted_at ON cmis.profile_group_members(deleted_at) WHERE deleted_at IS NULL');

        // Create foreign keys (skip if exist)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'profile_group_members_profile_group_id_foreign') THEN
                    ALTER TABLE cmis.profile_group_members ADD CONSTRAINT profile_group_members_profile_group_id_foreign FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'profile_group_members_user_id_foreign') THEN
                    ALTER TABLE cmis.profile_group_members ADD CONSTRAINT profile_group_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'profile_group_members_assigned_by_foreign') THEN
                    ALTER TABLE cmis.profile_group_members ADD CONSTRAINT profile_group_members_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES cmis.users(user_id) ON DELETE RESTRICT;
                END IF;
            END \$\$;
        ");

        // Enable RLS (inherited from profile_groups relationship)
        DB::statement('ALTER TABLE cmis.profile_group_members ENABLE ROW LEVEL SECURITY');

        // Create RLS policy (skip if exists)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'profile_group_members' AND policyname = 'profile_group_members_policy') THEN
                    CREATE POLICY profile_group_members_policy ON cmis.profile_group_members
                    USING (
                        EXISTS (
                            SELECT 1 FROM cmis.profile_groups pg
                            WHERE pg.group_id = profile_group_members.profile_group_id
                            AND pg.org_id = current_setting('app.current_org_id', true)::uuid
                        )
                    )
                    WITH CHECK (
                        EXISTS (
                            SELECT 1 FROM cmis.profile_groups pg
                            WHERE pg.group_id = profile_group_members.profile_group_id
                            AND pg.org_id = current_setting('app.current_org_id', true)::uuid
                        )
                    );
                END IF;
            END \$\$;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS cmis.profile_group_members CASCADE');
    }
};
