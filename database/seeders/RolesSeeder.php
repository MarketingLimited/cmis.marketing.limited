<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolesSeeder extends Seeder
{
    /**
     * Seed system and organization-specific roles.
     */
    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Get PDO for prepared statements
        $pdo = DB::connection()->getPdo();

        // Set autocommit mode to ensure each statement commits immediately
        $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);

        // Temporarily disable RLS for seeding (system roles have org_id = null)
        $pdo->exec('ALTER TABLE cmis.roles DISABLE ROW LEVEL SECURITY');

        $now = now()->toDateTimeString();

        $roles = [
            // System Roles (org_id = null)
            [
                'role_id' => '90def48b-062e-4c13-a8d9-a0c6361d6057',
                'org_id' => null,
                'role_name' => 'Owner',
                'role_code' => 'owner',
                'description' => 'Organization owner with full permissions',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => Str::uuid(),
                'org_id' => null,
                'role_name' => 'Admin',
                'role_code' => 'admin',
                'description' => 'Administrator with management permissions',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => Str::uuid(),
                'org_id' => null,
                'role_name' => 'Marketing Manager',
                'role_code' => 'marketing_manager',
                'description' => 'Can manage campaigns, content, and creative assets',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => Str::uuid(),
                'org_id' => null,
                'role_name' => 'Content Creator',
                'role_code' => 'content_creator',
                'description' => 'Can create and edit content and social posts',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => Str::uuid(),
                'org_id' => null,
                'role_name' => 'Social Media Manager',
                'role_code' => 'social_manager',
                'description' => 'Can manage social media accounts and posts',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => Str::uuid(),
                'org_id' => null,
                'role_name' => 'Analyst',
                'role_code' => 'analyst',
                'description' => 'Can view analytics and create reports',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'role_id' => Str::uuid(),
                'org_id' => null,
                'role_name' => 'Viewer',
                'role_code' => 'viewer',
                'description' => 'Read-only access to campaigns and content',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'created_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
        ];

        // Use prepared statement for secure insertion
        $stmt = $pdo->prepare("
            INSERT INTO cmis.roles (role_id, org_id, role_name, role_code, description, is_system, is_active, created_at, created_by, deleted_at, provider)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($roles as $role) {
            $result = $stmt->execute([
                $role['role_id'],
                $role['org_id'],
                $role['role_name'],
                $role['role_code'],
                $role['description'],
                $role['is_system'],
                $role['is_active'],
                $role['created_at'],
                $role['created_by'],
                $role['deleted_at'],
                $role['provider'],
            ]);

            if (!$result) {
                $error = $stmt->errorInfo();
                $this->command->error("Failed to insert role {$role['role_name']}: " . $error[2]);
            }
        }

        // Re-enable RLS after seeding
        $pdo->exec('ALTER TABLE cmis.roles ENABLE ROW LEVEL SECURITY');

        $this->command->info('Roles seeded successfully!');
    }
}
