<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates all CMIS permissions and assigns them to default roles.
     */
    public function run(): void
    {
        $this->command->info('Seeding CMIS permissions...');

        // Define all permissions by category
        $permissions = $this->getPermissions();

        // Insert permissions
        foreach ($permissions as $category => $categoryPermissions) {
            $this->command->info("Creating {$category} permissions...");

            foreach ($categoryPermissions as $permission) {
                DB::table('cmis.permissions')->insertOrIgnore([
                    'permission_id' => (string) Str::uuid(),
                    'permission_code' => $permission['code'],
                    'permission_name' => $permission['name'],
                    'category' => $category,
                    'description' => $permission['description'],
                    'is_dangerous' => $permission['is_dangerous'] ?? false,
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }

        $this->command->info('✓ All permissions created');

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info('✓ Permission seeding completed successfully');
    }

    /**
     * Get all permissions organized by category
     */
    protected function getPermissions(): array
    {
        return [
            'campaigns' => [
                ['code' => 'cmis.campaigns.view', 'name' => 'View Campaigns', 'description' => 'View campaign list and details'],
                ['code' => 'cmis.campaigns.create', 'name' => 'Create Campaigns', 'description' => 'Create new campaigns'],
                ['code' => 'cmis.campaigns.update', 'name' => 'Update Campaigns', 'description' => 'Update existing campaigns'],
                ['code' => 'cmis.campaigns.delete', 'name' => 'Delete Campaigns', 'description' => 'Soft delete campaigns'],
                ['code' => 'cmis.campaigns.restore', 'name' => 'Restore Campaigns', 'description' => 'Restore deleted campaigns'],
                ['code' => 'cmis.campaigns.force_delete', 'name' => 'Force Delete Campaigns', 'description' => 'Permanently delete campaigns', 'is_dangerous' => true],
                ['code' => 'cmis.campaigns.publish', 'name' => 'Publish Campaigns', 'description' => 'Publish campaigns to platforms'],
                ['code' => 'cmis.campaigns.view_analytics', 'name' => 'View Campaign Analytics', 'description' => 'View campaign analytics and performance'],
                ['code' => 'cmis.campaigns.duplicate', 'name' => 'Duplicate Campaigns', 'description' => 'Create campaign copies'],
                ['code' => 'cmis.campaigns.export', 'name' => 'Export Campaigns', 'description' => 'Export campaign data'],
            ],

            'assets' => [
                ['code' => 'cmis.assets.view', 'name' => 'View Assets', 'description' => 'View creative assets'],
                ['code' => 'cmis.assets.create', 'name' => 'Create Assets', 'description' => 'Upload and create creative assets'],
                ['code' => 'cmis.assets.update', 'name' => 'Update Assets', 'description' => 'Update existing assets'],
                ['code' => 'cmis.assets.delete', 'name' => 'Delete Assets', 'description' => 'Delete creative assets'],
                ['code' => 'cmis.assets.download', 'name' => 'Download Assets', 'description' => 'Download asset files'],
                ['code' => 'cmis.assets.approve', 'name' => 'Approve Assets', 'description' => 'Approve assets for use'],
                ['code' => 'cmis.assets.reject', 'name' => 'Reject Assets', 'description' => 'Reject submitted assets'],
            ],

            'content' => [
                ['code' => 'cmis.content.view', 'name' => 'View Content', 'description' => 'View content plans and items'],
                ['code' => 'cmis.content.create', 'name' => 'Create Content', 'description' => 'Create content plans and items'],
                ['code' => 'cmis.content.update', 'name' => 'Update Content', 'description' => 'Update content plans and items'],
                ['code' => 'cmis.content.delete', 'name' => 'Delete Content', 'description' => 'Delete content plans and items'],
                ['code' => 'cmis.content.approve', 'name' => 'Approve Content', 'description' => 'Approve content for publishing'],
                ['code' => 'cmis.content.schedule', 'name' => 'Schedule Content', 'description' => 'Schedule content publishing'],
            ],

            'integrations' => [
                ['code' => 'cmis.integrations.view', 'name' => 'View Integrations', 'description' => 'View platform integrations'],
                ['code' => 'cmis.integrations.create', 'name' => 'Create Integrations', 'description' => 'Connect new platform integrations'],
                ['code' => 'cmis.integrations.update', 'name' => 'Update Integrations', 'description' => 'Update integration settings'],
                ['code' => 'cmis.integrations.delete', 'name' => 'Delete Integrations', 'description' => 'Remove platform integrations', 'is_dangerous' => true],
                ['code' => 'cmis.integrations.configure', 'name' => 'Configure Integrations', 'description' => 'Configure integration settings'],
                ['code' => 'cmis.integrations.sync', 'name' => 'Sync Integrations', 'description' => 'Manually trigger integration sync'],
                ['code' => 'cmis.integrations.view_credentials', 'name' => 'View Integration Credentials', 'description' => 'View integration API credentials', 'is_dangerous' => true],
            ],

            'analytics' => [
                ['code' => 'cmis.analytics.view_dashboard', 'name' => 'View Analytics Dashboard', 'description' => 'View analytics dashboard'],
                ['code' => 'cmis.analytics.view_reports', 'name' => 'View Analytics Reports', 'description' => 'View analytics reports'],
                ['code' => 'cmis.analytics.create_report', 'name' => 'Create Analytics Reports', 'description' => 'Create custom analytics reports'],
                ['code' => 'cmis.analytics.export', 'name' => 'Export Analytics', 'description' => 'Export analytics data to CSV/PDF'],
                ['code' => 'cmis.analytics.view_insights', 'name' => 'View Analytics Insights', 'description' => 'View AI-generated insights'],
                ['code' => 'cmis.analytics.view_performance', 'name' => 'View Performance Metrics', 'description' => 'View detailed performance metrics'],
                ['code' => 'cmis.analytics.manage_dashboard', 'name' => 'Manage Analytics Dashboard', 'description' => 'Customize analytics dashboard'],
            ],

            'users' => [
                ['code' => 'cmis.users.view', 'name' => 'View Users', 'description' => 'View organization users'],
                ['code' => 'cmis.users.create', 'name' => 'Create Users', 'description' => 'Create new users'],
                ['code' => 'cmis.users.invite', 'name' => 'Invite Users', 'description' => 'Invite users to organization'],
                ['code' => 'cmis.users.update', 'name' => 'Update Users', 'description' => 'Update user information'],
                ['code' => 'cmis.users.delete', 'name' => 'Delete Users', 'description' => 'Remove users from organization', 'is_dangerous' => true],
                ['code' => 'cmis.users.assign_role', 'name' => 'Assign User Roles', 'description' => 'Assign roles to users', 'is_dangerous' => true],
                ['code' => 'cmis.users.grant_permission', 'name' => 'Grant User Permissions', 'description' => 'Grant specific permissions to users', 'is_dangerous' => true],
                ['code' => 'cmis.users.view_activity', 'name' => 'View User Activity', 'description' => 'View user activity logs'],
            ],

            'organizations' => [
                ['code' => 'cmis.organizations.view', 'name' => 'View Organization', 'description' => 'View organization details'],
                ['code' => 'cmis.organizations.update', 'name' => 'Update Organization', 'description' => 'Update organization settings'],
                ['code' => 'cmis.organizations.delete', 'name' => 'Delete Organization', 'description' => 'Delete organization', 'is_dangerous' => true],
                ['code' => 'cmis.organizations.manage_billing', 'name' => 'Manage Billing', 'description' => 'Manage organization billing'],
                ['code' => 'cmis.organizations.manage_settings', 'name' => 'Manage Settings', 'description' => 'Manage organization settings'],
            ],

            'ai' => [
                ['code' => 'cmis.ai.generate_content', 'name' => 'Generate AI Content', 'description' => 'Generate content using AI'],
                ['code' => 'cmis.ai.generate_campaign', 'name' => 'Generate AI Campaigns', 'description' => 'Generate campaigns using AI'],
                ['code' => 'cmis.ai.view_recommendations', 'name' => 'View AI Recommendations', 'description' => 'View AI-generated recommendations'],
                ['code' => 'cmis.ai.semantic_search', 'name' => 'Use Semantic Search', 'description' => 'Use AI semantic search'],
                ['code' => 'cmis.ai.manage_knowledge', 'name' => 'Manage AI Knowledge Base', 'description' => 'Manage AI knowledge base'],
                ['code' => 'cmis.ai.manage_prompts', 'name' => 'Manage AI Prompts', 'description' => 'Manage AI prompt templates'],
                ['code' => 'cmis.ai.view_insights', 'name' => 'View AI Insights', 'description' => 'View AI-generated insights'],
            ],

            'channels' => [
                ['code' => 'cmis.channels.view', 'name' => 'View Channels', 'description' => 'View marketing channels'],
                ['code' => 'cmis.channels.create', 'name' => 'Create Channels', 'description' => 'Create new channels'],
                ['code' => 'cmis.channels.update', 'name' => 'Update Channels', 'description' => 'Update channel settings'],
                ['code' => 'cmis.channels.delete', 'name' => 'Delete Channels', 'description' => 'Delete channels'],
            ],

            'offerings' => [
                ['code' => 'cmis.offerings.view', 'name' => 'View Offerings', 'description' => 'View product offerings'],
                ['code' => 'cmis.offerings.create', 'name' => 'Create Offerings', 'description' => 'Create new offerings'],
                ['code' => 'cmis.offerings.update', 'name' => 'Update Offerings', 'description' => 'Update offering details'],
                ['code' => 'cmis.offerings.delete', 'name' => 'Delete Offerings', 'description' => 'Delete offerings'],
            ],
        ];
    }

    /**
     * Assign permissions to default roles
     */
    protected function assignPermissionsToRoles(): void
    {
        $this->command->info('Assigning permissions to roles...');

        // Create helper function if it doesn't exist
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis.assign_permissions_to_role(
                p_role_code text,
                p_permissions text[]
            ) RETURNS void AS $$
            DECLARE
                v_role_id uuid;
                v_permission_id uuid;
                v_permission text;
            BEGIN
                -- Get role ID
                SELECT role_id INTO v_role_id
                FROM cmis.roles
                WHERE role_code = p_role_code
                LIMIT 1;

                IF v_role_id IS NULL THEN
                    RAISE NOTICE 'Role % not found', p_role_code;
                    RETURN;
                END IF;

                -- Assign each permission
                FOREACH v_permission IN ARRAY p_permissions
                LOOP
                    SELECT permission_id INTO v_permission_id
                    FROM cmis.permissions
                    WHERE permission_code = v_permission
                    LIMIT 1;

                    IF v_permission_id IS NOT NULL THEN
                        INSERT INTO cmis.role_permissions (id, role_id, permission_id, granted_at)
                        VALUES (gen_random_uuid(), v_role_id, v_permission_id, NOW())
                        ON CONFLICT DO NOTHING;
                    ELSE
                        RAISE NOTICE 'Permission % not found', v_permission;
                    END IF;
                END LOOP;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Owner: Full access to everything
        DB::unprepared("
            SELECT cmis.assign_permissions_to_role(
                'owner',
                ARRAY[
                    -- All permissions for owners
                    'cmis.campaigns.view', 'cmis.campaigns.create', 'cmis.campaigns.update', 'cmis.campaigns.delete',
                    'cmis.campaigns.restore', 'cmis.campaigns.force_delete', 'cmis.campaigns.publish', 'cmis.campaigns.view_analytics',
                    'cmis.campaigns.duplicate', 'cmis.campaigns.export',
                    'cmis.assets.view', 'cmis.assets.create', 'cmis.assets.update', 'cmis.assets.delete',
                    'cmis.assets.download', 'cmis.assets.approve', 'cmis.assets.reject',
                    'cmis.content.view', 'cmis.content.create', 'cmis.content.update', 'cmis.content.delete',
                    'cmis.content.approve', 'cmis.content.schedule',
                    'cmis.integrations.view', 'cmis.integrations.create', 'cmis.integrations.update', 'cmis.integrations.delete',
                    'cmis.integrations.configure', 'cmis.integrations.sync', 'cmis.integrations.view_credentials',
                    'cmis.analytics.view_dashboard', 'cmis.analytics.view_reports', 'cmis.analytics.create_report',
                    'cmis.analytics.export', 'cmis.analytics.view_insights', 'cmis.analytics.view_performance',
                    'cmis.analytics.manage_dashboard',
                    'cmis.users.view', 'cmis.users.create', 'cmis.users.invite', 'cmis.users.update',
                    'cmis.users.delete', 'cmis.users.assign_role', 'cmis.users.grant_permission', 'cmis.users.view_activity',
                    'cmis.organizations.view', 'cmis.organizations.update', 'cmis.organizations.delete',
                    'cmis.organizations.manage_billing', 'cmis.organizations.manage_settings',
                    'cmis.ai.generate_content', 'cmis.ai.generate_campaign', 'cmis.ai.view_recommendations',
                    'cmis.ai.semantic_search', 'cmis.ai.manage_knowledge', 'cmis.ai.manage_prompts', 'cmis.ai.view_insights',
                    'cmis.channels.view', 'cmis.channels.create', 'cmis.channels.update', 'cmis.channels.delete',
                    'cmis.offerings.view', 'cmis.offerings.create', 'cmis.offerings.update', 'cmis.offerings.delete'
                ]
            );
        ");

        // Admin: Almost full access (no org deletion, no dangerous operations)
        DB::unprepared("
            SELECT cmis.assign_permissions_to_role(
                'admin',
                ARRAY[
                    'cmis.campaigns.view', 'cmis.campaigns.create', 'cmis.campaigns.update', 'cmis.campaigns.delete',
                    'cmis.campaigns.restore', 'cmis.campaigns.publish', 'cmis.campaigns.view_analytics',
                    'cmis.campaigns.duplicate', 'cmis.campaigns.export',
                    'cmis.assets.view', 'cmis.assets.create', 'cmis.assets.update', 'cmis.assets.delete',
                    'cmis.assets.download', 'cmis.assets.approve', 'cmis.assets.reject',
                    'cmis.content.view', 'cmis.content.create', 'cmis.content.update', 'cmis.content.delete',
                    'cmis.content.approve', 'cmis.content.schedule',
                    'cmis.integrations.view', 'cmis.integrations.create', 'cmis.integrations.update',
                    'cmis.integrations.configure', 'cmis.integrations.sync',
                    'cmis.analytics.view_dashboard', 'cmis.analytics.view_reports', 'cmis.analytics.create_report',
                    'cmis.analytics.export', 'cmis.analytics.view_insights', 'cmis.analytics.view_performance',
                    'cmis.analytics.manage_dashboard',
                    'cmis.users.view', 'cmis.users.invite', 'cmis.users.update',
                    'cmis.users.assign_role', 'cmis.users.view_activity',
                    'cmis.organizations.view', 'cmis.organizations.update', 'cmis.organizations.manage_settings',
                    'cmis.ai.generate_content', 'cmis.ai.generate_campaign', 'cmis.ai.view_recommendations',
                    'cmis.ai.semantic_search', 'cmis.ai.view_insights',
                    'cmis.channels.view', 'cmis.channels.create', 'cmis.channels.update', 'cmis.channels.delete',
                    'cmis.offerings.view', 'cmis.offerings.create', 'cmis.offerings.update', 'cmis.offerings.delete'
                ]
            );
        ");

        // Manager: Moderate access (no user/integration management)
        DB::unprepared("
            SELECT cmis.assign_permissions_to_role(
                'manager',
                ARRAY[
                    'cmis.campaigns.view', 'cmis.campaigns.create', 'cmis.campaigns.update', 'cmis.campaigns.delete',
                    'cmis.campaigns.publish', 'cmis.campaigns.view_analytics', 'cmis.campaigns.duplicate', 'cmis.campaigns.export',
                    'cmis.assets.view', 'cmis.assets.create', 'cmis.assets.update', 'cmis.assets.delete',
                    'cmis.assets.download', 'cmis.assets.approve',
                    'cmis.content.view', 'cmis.content.create', 'cmis.content.update', 'cmis.content.delete',
                    'cmis.content.approve', 'cmis.content.schedule',
                    'cmis.integrations.view', 'cmis.integrations.sync',
                    'cmis.analytics.view_dashboard', 'cmis.analytics.view_reports', 'cmis.analytics.create_report',
                    'cmis.analytics.export', 'cmis.analytics.view_insights', 'cmis.analytics.view_performance',
                    'cmis.users.view',
                    'cmis.organizations.view',
                    'cmis.ai.generate_content', 'cmis.ai.generate_campaign', 'cmis.ai.view_recommendations',
                    'cmis.ai.semantic_search', 'cmis.ai.view_insights',
                    'cmis.channels.view', 'cmis.channels.create', 'cmis.channels.update',
                    'cmis.offerings.view', 'cmis.offerings.create', 'cmis.offerings.update'
                ]
            );
        ");

        // Editor: Basic content creation and editing
        DB::unprepared("
            SELECT cmis.assign_permissions_to_role(
                'editor',
                ARRAY[
                    'cmis.campaigns.view', 'cmis.campaigns.create', 'cmis.campaigns.update',
                    'cmis.campaigns.view_analytics',
                    'cmis.assets.view', 'cmis.assets.create', 'cmis.assets.update', 'cmis.assets.download',
                    'cmis.content.view', 'cmis.content.create', 'cmis.content.update',
                    'cmis.integrations.view',
                    'cmis.analytics.view_dashboard', 'cmis.analytics.view_reports',
                    'cmis.users.view',
                    'cmis.organizations.view',
                    'cmis.ai.generate_content', 'cmis.ai.view_recommendations', 'cmis.ai.semantic_search',
                    'cmis.channels.view',
                    'cmis.offerings.view'
                ]
            );
        ");

        // Viewer: Read-only access
        DB::unprepared("
            SELECT cmis.assign_permissions_to_role(
                'viewer',
                ARRAY[
                    'cmis.campaigns.view', 'cmis.campaigns.view_analytics',
                    'cmis.assets.view', 'cmis.assets.download',
                    'cmis.content.view',
                    'cmis.integrations.view',
                    'cmis.analytics.view_dashboard', 'cmis.analytics.view_reports', 'cmis.analytics.view_insights',
                    'cmis.users.view',
                    'cmis.organizations.view',
                    'cmis.ai.view_recommendations', 'cmis.ai.semantic_search', 'cmis.ai.view_insights',
                    'cmis.channels.view',
                    'cmis.offerings.view'
                ]
            );
        ");

        $this->command->info('✓ Permissions assigned to roles');
    }
}
