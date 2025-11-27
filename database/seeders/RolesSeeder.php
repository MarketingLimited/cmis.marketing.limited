<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Seed system and organization-specific roles.
     * Uses fixed UUIDs from SeederConstants for consistency across seeders.
     */
    public function run(): void
    {
        $roles = [
            // System Roles (org_id = null)
            [
                'role_id' => SeederConstants::ROLE_OWNER,
                'org_id' => null,
                'role_name' => 'Owner',
                'role_code' => 'owner',
                'description' => 'Organization owner with full permissions',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => SeederConstants::ROLE_ADMIN,
                'org_id' => null,
                'role_name' => 'Admin',
                'role_code' => 'admin',
                'description' => 'Administrator with management permissions',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => SeederConstants::ROLE_MARKETING_MANAGER,
                'org_id' => null,
                'role_name' => 'Marketing Manager',
                'role_code' => 'marketing_manager',
                'description' => 'Can manage campaigns, content, and creative assets',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => SeederConstants::ROLE_CONTENT_CREATOR,
                'org_id' => null,
                'role_name' => 'Content Creator',
                'role_code' => 'content_creator',
                'description' => 'Can create and edit content and social posts',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => SeederConstants::ROLE_SOCIAL_MANAGER,
                'org_id' => null,
                'role_name' => 'Social Media Manager',
                'role_code' => 'social_manager',
                'description' => 'Can manage social media accounts and posts',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => SeederConstants::ROLE_ANALYST,
                'org_id' => null,
                'role_name' => 'Analyst',
                'role_code' => 'analyst',
                'description' => 'Can view analytics and create reports',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => SeederConstants::ROLE_VIEWER,
                'org_id' => null,
                'role_name' => 'Viewer',
                'role_code' => 'viewer',
                'description' => 'Read-only access to campaigns and content',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
        ];

        // Delete existing system roles and re-insert for clean seeding
        DB::table('cmis.roles')->whereIn('role_id', array_column($roles, 'role_id'))->delete();

        foreach ($roles as $role) {
            DB::table('cmis.roles')->insert($role);
        }

        $this->command->info('Roles seeded successfully!');
    }
}
