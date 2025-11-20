<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Set admin context
        DB::statement("SET LOCAL app.is_admin = true");

        $now = now();

        $roles = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Super Administrator',
                'slug' => 'super_admin',
                'description' => 'Full system access including feature flag management',
                'permissions' => json_encode([
                    'features' => ['view', 'use', 'manage'],
                    'users' => ['view', 'create', 'update', 'delete'],
                    'organizations' => ['view', 'create', 'update', 'delete'],
                    'campaigns' => ['view', 'create', 'update', 'delete'],
                    'platforms' => ['all'],
                ]),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Organization administrator with feature management capabilities',
                'permissions' => json_encode([
                    'features' => ['view', 'use', 'manage'],
                    'users' => ['view', 'create', 'update'],
                    'campaigns' => ['view', 'create', 'update', 'delete'],
                    'platforms' => ['all'],
                ]),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Platform Manager',
                'slug' => 'platform_manager',
                'description' => 'Can manage campaigns and access all enabled platforms',
                'permissions' => json_encode([
                    'features' => ['view', 'use'],
                    'campaigns' => ['view', 'create', 'update', 'delete'],
                    'platforms' => ['enabled_only'], // Only platforms with features enabled
                ]),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Campaign Manager',
                'slug' => 'campaign_manager',
                'description' => 'Can create and manage campaigns on assigned platforms',
                'permissions' => json_encode([
                    'features' => ['view', 'use'],
                    'campaigns' => ['view', 'create', 'update'],
                    'platforms' => ['assigned'], // Only platforms assigned via feature_permissions
                ]),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Content Creator',
                'slug' => 'content_creator',
                'description' => 'Can create content and schedule posts',
                'permissions' => json_encode([
                    'features' => ['view', 'use'],
                    'campaigns' => ['view'],
                    'content' => ['view', 'create', 'update'],
                    'scheduling' => ['view', 'create', 'update'],
                    'platforms' => ['assigned'],
                ]),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Analyst',
                'slug' => 'analyst',
                'description' => 'Read-only access to analytics and reports',
                'permissions' => json_encode([
                    'features' => ['view'],
                    'campaigns' => ['view'],
                    'analytics' => ['view'],
                    'platforms' => ['enabled_only'],
                ]),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Basic user with limited access',
                'permissions' => json_encode([
                    'features' => ['view'],
                    'campaigns' => ['view'],
                    'platforms' => ['none'],
                ]),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert roles
        DB::table('cmis.roles')->insert($roles);

        $this->command->info('✅ Roles seeded successfully');
        $this->command->info('📊 Roles created:');
        foreach ($roles as $role) {
            $this->command->info("   - {$role['name']} ({$role['slug']})");
        }

        // Example: Assign platform-specific permissions
        $this->seedExamplePlatformPermissions();
    }

    /**
     * Seed example platform-specific permissions
     */
    protected function seedExamplePlatformPermissions()
    {
        $now = now();

        // Get campaign_manager role
        $campaignManagerRole = DB::table('cmis.roles')
            ->where('slug', 'campaign_manager')
            ->first();

        if (!$campaignManagerRole) {
            return;
        }

        // Example: Grant campaign manager access to Meta and TikTok only
        $permissions = [
            [
                'id' => Str::uuid()->toString(),
                'role_id' => $campaignManagerRole->id,
                'user_id' => null,
                'feature_key' => 'paid_campaigns.meta.enabled',
                'permission_type' => 'use',
                'granted' => true,
                'reason' => 'Default access for Campaign Manager role',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'role_id' => $campaignManagerRole->id,
                'user_id' => null,
                'feature_key' => 'paid_campaigns.tiktok.enabled',
                'permission_type' => 'use',
                'granted' => true,
                'reason' => 'Default access for Campaign Manager role',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'role_id' => $campaignManagerRole->id,
                'user_id' => null,
                'feature_key' => 'scheduling.meta.enabled',
                'permission_type' => 'use',
                'granted' => true,
                'reason' => 'Default access for Campaign Manager role',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('cmis.feature_permissions')->insert($permissions);

        $this->command->info('✅ Example platform permissions seeded');
    }
}
