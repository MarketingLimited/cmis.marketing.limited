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
        // Create team_invitations table
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.team_invitations (
                invitation_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                email VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL,
                invited_by UUID,
                invitation_token VARCHAR(255) UNIQUE NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, accepted, expired, cancelled
                message TEXT,
                account_access JSONB, -- Array of social_account_ids
                accepted_at TIMESTAMPTZ,
                accepted_by UUID,
                expires_at TIMESTAMPTZ NOT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (invited_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL,
                FOREIGN KEY (accepted_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL
            )
        ");

        // Create index on org_id and status for invitation lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_team_invitations_org_status
            ON cmis.team_invitations(org_id, status)
        ");

        // Create index on email for invitation lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_team_invitations_email
            ON cmis.team_invitations(email)
        ");

        // Create index on token for fast lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_team_invitations_token
            ON cmis.team_invitations(invitation_token)
        ");

        // Create team_account_access table for granular account permissions
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.team_account_access (
                access_id UUID PRIMARY KEY,
                org_user_id UUID NOT NULL,
                social_account_id UUID NOT NULL,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_user_id) REFERENCES cmis.org_users(org_user_id) ON DELETE CASCADE,
                FOREIGN KEY (social_account_id) REFERENCES cmis.social_accounts(social_account_id) ON DELETE CASCADE,

                UNIQUE(org_user_id, social_account_id)
            )
        ");

        // Create index on org_user_id for fast lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_team_account_access_org_user
            ON cmis.team_account_access(org_user_id)
        ");

        // Create index on social_account_id for reverse lookups
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_team_account_access_account
            ON cmis.team_account_access(social_account_id)
        ");

        // Add comment to tables
        DB::statement("
            COMMENT ON TABLE cmis.team_invitations IS 'Team member invitations - Sprint 5.1'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.team_account_access IS 'Granular social account access for team members - Sprint 5.1'
        ");

        // Add role column to org_users if it doesn't exist (might already exist from Phase 1)
        DB::statement("
            ALTER TABLE cmis.org_users
            ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'contributor'
        ");

        // Add joined_at column to org_users if it doesn't exist
        DB::statement("
            ALTER TABLE cmis.org_users
            ADD COLUMN IF NOT EXISTS joined_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
        ");

        // Create index on role for filtering
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_org_users_role
            ON cmis.org_users(role)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS cmis.team_account_access CASCADE");
        DB::statement("DROP TABLE IF NOT EXISTS cmis.team_invitations CASCADE");
        DB::statement("ALTER TABLE cmis.org_users DROP COLUMN IF EXISTS role");
        DB::statement("ALTER TABLE cmis.org_users DROP COLUMN IF EXISTS joined_at");
    }
};
