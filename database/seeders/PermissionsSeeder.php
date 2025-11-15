<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionsSeeder extends Seeder
{
    /**
     * Seed comprehensive permissions for the application.
     */
    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        $permissions = [
            // Organization Management
            ['code' => 'org.view', 'name' => 'View Organization', 'category' => 'organization', 'is_dangerous' => false],
            ['code' => 'org.edit', 'name' => 'Edit Organization', 'category' => 'organization', 'is_dangerous' => false],
            ['code' => 'org.delete', 'name' => 'Delete Organization', 'category' => 'organization', 'is_dangerous' => true],

            // User Management
            ['code' => 'user.view', 'name' => 'View Users', 'category' => 'user_management', 'is_dangerous' => false],
            ['code' => 'user.create', 'name' => 'Create Users', 'category' => 'user_management', 'is_dangerous' => false],
            ['code' => 'user.edit', 'name' => 'Edit Users', 'category' => 'user_management', 'is_dangerous' => false],
            ['code' => 'user.delete', 'name' => 'Delete Users', 'category' => 'user_management', 'is_dangerous' => true],
            ['code' => 'user.invite', 'name' => 'Invite Users', 'category' => 'user_management', 'is_dangerous' => false],

            // Role & Permission Management
            ['code' => 'role.view', 'name' => 'View Roles', 'category' => 'access_control', 'is_dangerous' => false],
            ['code' => 'role.create', 'name' => 'Create Roles', 'category' => 'access_control', 'is_dangerous' => false],
            ['code' => 'role.edit', 'name' => 'Edit Roles', 'category' => 'access_control', 'is_dangerous' => false],
            ['code' => 'role.delete', 'name' => 'Delete Roles', 'category' => 'access_control', 'is_dangerous' => true],
            ['code' => 'permission.grant', 'name' => 'Grant Permissions', 'category' => 'access_control', 'is_dangerous' => true],
            ['code' => 'permission.revoke', 'name' => 'Revoke Permissions', 'category' => 'access_control', 'is_dangerous' => true],

            // Campaign Management
            ['code' => 'campaign.view', 'name' => 'View Campaigns', 'category' => 'campaigns', 'is_dangerous' => false],
            ['code' => 'campaign.create', 'name' => 'Create Campaigns', 'category' => 'campaigns', 'is_dangerous' => false],
            ['code' => 'campaign.edit', 'name' => 'Edit Campaigns', 'category' => 'campaigns', 'is_dangerous' => false],
            ['code' => 'campaign.delete', 'name' => 'Delete Campaigns', 'category' => 'campaigns', 'is_dangerous' => false],
            ['code' => 'campaign.publish', 'name' => 'Publish Campaigns', 'category' => 'campaigns', 'is_dangerous' => false],

            // Creative Assets
            ['code' => 'creative.view', 'name' => 'View Creative Assets', 'category' => 'creative', 'is_dangerous' => false],
            ['code' => 'creative.create', 'name' => 'Create Creative Assets', 'category' => 'creative', 'is_dangerous' => false],
            ['code' => 'creative.edit', 'name' => 'Edit Creative Assets', 'category' => 'creative', 'is_dangerous' => false],
            ['code' => 'creative.delete', 'name' => 'Delete Creative Assets', 'category' => 'creative', 'is_dangerous' => false],
            ['code' => 'creative.approve', 'name' => 'Approve Creative Assets', 'category' => 'creative', 'is_dangerous' => false],

            // Content Management
            ['code' => 'content.view', 'name' => 'View Content', 'category' => 'content', 'is_dangerous' => false],
            ['code' => 'content.create', 'name' => 'Create Content', 'category' => 'content', 'is_dangerous' => false],
            ['code' => 'content.edit', 'name' => 'Edit Content', 'category' => 'content', 'is_dangerous' => false],
            ['code' => 'content.delete', 'name' => 'Delete Content', 'category' => 'content', 'is_dangerous' => false],
            ['code' => 'content.publish', 'name' => 'Publish Content', 'category' => 'content', 'is_dangerous' => false],

            // Social Media Management
            ['code' => 'social.view', 'name' => 'View Social Posts', 'category' => 'social_media', 'is_dangerous' => false],
            ['code' => 'social.create', 'name' => 'Create Social Posts', 'category' => 'social_media', 'is_dangerous' => false],
            ['code' => 'social.edit', 'name' => 'Edit Social Posts', 'category' => 'social_media', 'is_dangerous' => false],
            ['code' => 'social.delete', 'name' => 'Delete Social Posts', 'category' => 'social_media', 'is_dangerous' => false],
            ['code' => 'social.publish', 'name' => 'Publish Social Posts', 'category' => 'social_media', 'is_dangerous' => false],
            ['code' => 'social.schedule', 'name' => 'Schedule Social Posts', 'category' => 'social_media', 'is_dangerous' => false],
            ['code' => 'social.respond', 'name' => 'Respond to Messages', 'category' => 'social_media', 'is_dangerous' => false],

            // Integration Management
            ['code' => 'integration.view', 'name' => 'View Integrations', 'category' => 'integrations', 'is_dangerous' => false],
            ['code' => 'integration.create', 'name' => 'Create Integrations', 'category' => 'integrations', 'is_dangerous' => false],
            ['code' => 'integration.edit', 'name' => 'Edit Integrations', 'category' => 'integrations', 'is_dangerous' => false],
            ['code' => 'integration.delete', 'name' => 'Delete Integrations', 'category' => 'integrations', 'is_dangerous' => true],
            ['code' => 'integration.sync', 'name' => 'Sync Integration Data', 'category' => 'integrations', 'is_dangerous' => false],

            // Ad Platform Management
            ['code' => 'ads.view', 'name' => 'View Ads', 'category' => 'advertising', 'is_dangerous' => false],
            ['code' => 'ads.create', 'name' => 'Create Ads', 'category' => 'advertising', 'is_dangerous' => false],
            ['code' => 'ads.edit', 'name' => 'Edit Ads', 'category' => 'advertising', 'is_dangerous' => false],
            ['code' => 'ads.delete', 'name' => 'Delete Ads', 'category' => 'advertising', 'is_dangerous' => false],
            ['code' => 'ads.publish', 'name' => 'Publish Ads', 'category' => 'advertising', 'is_dangerous' => false],

            // Analytics & Reporting
            ['code' => 'analytics.view', 'name' => 'View Analytics', 'category' => 'analytics', 'is_dangerous' => false],
            ['code' => 'analytics.export', 'name' => 'Export Analytics', 'category' => 'analytics', 'is_dangerous' => false],
            ['code' => 'report.view', 'name' => 'View Reports', 'category' => 'analytics', 'is_dangerous' => false],
            ['code' => 'report.create', 'name' => 'Create Reports', 'category' => 'analytics', 'is_dangerous' => false],

            // System Administration
            ['code' => 'system.settings', 'name' => 'Manage System Settings', 'category' => 'system', 'is_dangerous' => true],
            ['code' => 'system.logs', 'name' => 'View System Logs', 'category' => 'system', 'is_dangerous' => false],
            ['code' => 'system.audit', 'name' => 'View Audit Logs', 'category' => 'system', 'is_dangerous' => false],
        ];

        foreach ($permissions as $permission) {
            DB::table('cmis.permissions')->insert([
                'permission_id' => Str::uuid(),
                'permission_code' => $permission['code'],
                'permission_name' => $permission['name'],
                'category' => $permission['category'],
                'description' => null,
                'is_dangerous' => $permission['is_dangerous'],
                'deleted_at' => null,
                'provider' => null,
            ]);
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
