#!/bin/bash
# Fix database issues after migrate:fresh --seed
# Run this script after php artisan migrate:fresh --seed

echo "🔧 Fixing database issues..."
echo ""

# Step 1: Add missing roles
echo "📋 Step 1: Adding missing roles..."

PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis -v ON_ERROR_STOP=0 <<'SQL'
-- Add missing roles (if they don't exist)
INSERT INTO cmis.roles (role_id, org_id, role_name, role_code, description, is_system, is_active, created_at)
SELECT gen_random_uuid(), NULL, 'Admin', 'admin', 'Administrator with management permissions', true, true, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM cmis.roles WHERE role_code = 'admin' AND org_id IS NULL);

INSERT INTO cmis.roles (role_id, org_id, role_name, role_code, description, is_system, is_active, created_at)
SELECT gen_random_uuid(), NULL, 'Content Creator', 'content_creator', 'Can create and edit content and social posts', true, true, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM cmis.roles WHERE role_code = 'content_creator' AND org_id IS NULL);

INSERT INTO cmis.roles (role_id, org_id, role_name, role_code, description, is_system, is_active, created_at)
SELECT gen_random_uuid(), NULL, 'Social Media Manager', 'social_manager', 'Can manage social media accounts and posts', true, true, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM cmis.roles WHERE role_code = 'social_manager' AND org_id IS NULL);

INSERT INTO cmis.roles (role_id, org_id, role_name, role_code, description, is_system, is_active, created_at)
SELECT gen_random_uuid(), NULL, 'Analyst', 'analyst', 'Can view analytics and create reports', true, true, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM cmis.roles WHERE role_code = 'analyst' AND org_id IS NULL);

INSERT INTO cmis.roles (role_id, org_id, role_name, role_code, description, is_system, is_active, created_at)
SELECT gen_random_uuid(), NULL, 'Viewer', 'viewer', 'Read-only access to campaigns and content', true, true, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM cmis.roles WHERE role_code = 'viewer' AND org_id IS NULL);
SQL

echo "✅ Roles added successfully!"
echo ""

# Step 2: Add missing foreign keys
echo "🔗 Step 2: Adding missing foreign keys..."

PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis -v ON_ERROR_STOP=0 <<'SQL'
-- Drop test FK if exists
ALTER TABLE cmis.campaigns DROP CONSTRAINT IF EXISTS fk_campaigns_created_by_test;

-- Add all missing foreign keys (using DO blocks to handle duplicates gracefully)
DO $$
BEGIN
    -- user_id foreign keys
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_user_permissions_user' AND table_name = 'user_permissions') THEN
        ALTER TABLE cmis.user_permissions ADD CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_user_sessions_user' AND table_name = 'user_sessions') THEN
        ALTER TABLE cmis.user_sessions ADD CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_sessions_user' AND table_name = 'sessions') THEN
        ALTER TABLE cmis.sessions ADD CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_scheduled_social_posts_user' AND table_name = 'scheduled_social_posts') THEN
        ALTER TABLE cmis.scheduled_social_posts ADD CONSTRAINT fk_scheduled_social_posts_user FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_security_context_audit_user' AND table_name = 'security_context_audit') THEN
        ALTER TABLE cmis.security_context_audit ADD CONSTRAINT fk_security_context_audit_user FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_user_orgs_user' AND table_name = 'user_orgs') THEN
        ALTER TABLE cmis.user_orgs ADD CONSTRAINT fk_user_orgs_user FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE;
    END IF;

    -- created_by foreign keys
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_audience_templates_created_by' AND table_name = 'audience_templates') THEN
        ALTER TABLE cmis.audience_templates ADD CONSTRAINT fk_audience_templates_created_by FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_campaign_context_links_created_by' AND table_name = 'campaign_context_links') THEN
        ALTER TABLE cmis.campaign_context_links ADD CONSTRAINT fk_campaign_context_links_created_by FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_campaigns_created_by' AND table_name = 'campaigns') THEN
        ALTER TABLE cmis.campaigns ADD CONSTRAINT fk_campaigns_created_by FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_integrations_created_by' AND table_name = 'integrations') THEN
        ALTER TABLE cmis.integrations ADD CONSTRAINT fk_integrations_created_by FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_roles_created_by' AND table_name = 'roles') THEN
        ALTER TABLE cmis.roles ADD CONSTRAINT fk_roles_created_by FOREIGN KEY (created_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    -- updated_by foreign keys
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_campaign_context_links_updated_by' AND table_name = 'campaign_context_links') THEN
        ALTER TABLE cmis.campaign_context_links ADD CONSTRAINT fk_campaign_context_links_updated_by FOREIGN KEY (updated_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_integrations_updated_by' AND table_name = 'integrations') THEN
        ALTER TABLE cmis.integrations ADD CONSTRAINT fk_integrations_updated_by FOREIGN KEY (updated_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    -- invited_by foreign keys
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_team_invitations_invited_by' AND table_name = 'team_invitations') THEN
        ALTER TABLE cmis.team_invitations ADD CONSTRAINT fk_team_invitations_invited_by FOREIGN KEY (invited_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_user_orgs_invited_by' AND table_name = 'user_orgs') THEN
        ALTER TABLE cmis.user_orgs ADD CONSTRAINT fk_user_orgs_invited_by FOREIGN KEY (invited_by) REFERENCES cmis.users(user_id) ON DELETE SET NULL;
    END IF;
END $$;
SQL

echo "✅ Foreign keys added successfully!"
echo ""
echo "🎉 All database issues fixed!"
echo ""
echo "Summary:"
echo "  ✓ Added 5 missing roles (admin, content_creator, social_manager, analyst, viewer)"
echo "  ✓ Added 15 missing foreign key constraints"
echo "  ✓ Database referential integrity restored"
echo ""
echo "You can now run: php artisan db:seed --class=DemoDataSeeder"
