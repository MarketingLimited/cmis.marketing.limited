<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    /**
     * Seed demo users for the application.
     * Default password for all users: password
     */
    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Get PDO for prepared statements (safer than string concatenation)
        $pdo = DB::connection()->getPdo();

        // Temporarily disable RLS for seeding
        $pdo->exec('ALTER TABLE cmis.users DISABLE ROW LEVEL SECURITY');

        // Truncate users table first (CASCADE will handle dependent records)
        $pdo->exec('TRUNCATE TABLE cmis.users CASCADE');

        $hashedPassword = Hash::make('password');
        $now = now()->toDateTimeString();

        $users = [
            [
                'd76b3d33-4d67-4dd6-9df9-845a18ba3435',
                'Admin User',
                'admin@cmis.test',
                $now,
                $hashedPassword,
                null,
                $now,
                $now,
                null,
            ],
            [
                (string) Str::uuid(),
                'Sarah Johnson',
                'sarah@techvision.com',
                $now,
                $hashedPassword,
                null,
                $now,
                $now,
                null,
            ],
            [
                (string) Str::uuid(),
                'محمد أحمد',
                'mohamed@arabic-marketing.com',
                $now,
                $hashedPassword,
                null,
                $now,
                $now,
                null,
            ],
            [
                (string) Str::uuid(),
                'Emma Williams',
                'emma@fashionhub.com',
                $now,
                $hashedPassword,
                null,
                $now,
                $now,
                null,
            ],
            [
                (string) Str::uuid(),
                'David Chen',
                'david@healthwell.com',
                $now,
                $hashedPassword,
                null,
                $now,
                $now,
                null,
            ],
            [
                (string) Str::uuid(),
                'Maria Garcia',
                'maria@techvision.com',
                $now,
                $hashedPassword,
                null,
                $now,
                $now,
                null,
            ],
            [
                (string) Str::uuid(),
                'Ahmed Al-Rashid',
                'ahmed@arabic-marketing.com',
                $now,
                $hashedPassword,
                null,
                $now,
                $now,
                null,
            ],
        ];

        // Use prepared statement for secure insertion
        $stmt = $pdo->prepare("
            INSERT INTO cmis.users (user_id, name, email, email_verified_at, password, remember_token, created_at, updated_at, deleted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($users as $user) {
            $stmt->execute($user);
        }

        // Re-enable RLS after seeding
        $pdo->exec('ALTER TABLE cmis.users ENABLE ROW LEVEL SECURITY');

        $this->command->info('Users seeded successfully! Default password: password');

        // Assign users to organizations with roles
        $this->assignUsersToOrgs($pdo, $users);
    }

    /**
     * Assign users to organizations with appropriate roles.
     */
    private function assignUsersToOrgs($pdo, array $users): void
    {
        // Get role IDs
        $ownerRoleId = DB::table('cmis.roles')->where('role_name', 'Owner')->value('role_id');
        $adminRoleId = DB::table('cmis.roles')->where('role_name', 'Admin')->value('role_id');
        $managerRoleId = DB::table('cmis.roles')->where('role_name', 'Marketing Manager')->value('role_id');

        if (!$ownerRoleId || !$adminRoleId || !$managerRoleId) {
            $this->command->warn('Roles not found. Run RolesAndPermissionsSeeder first.');
            return;
        }

        // Organization IDs from OrgsSeeder
        $orgs = [
            'techvision' => '9a5e0b1c-3d4e-4f5a-8b7c-1d2e3f4a5b6c',
            'arabic' => '8b6f1a2d-4e5f-5a6b-9c8d-2e3f4a5b6c7d',
            'fashionhub' => '7c8e2b3f-5f6a-6b7c-0d9e-3f4a5b6c7d8e',
            'healthwell' => '6d9f3c4a-6a7b-7c8d-1e0f-4a5b6c7d8e9f',
        ];

        $userOrgs = [
            // Admin User - Owner of all orgs
            [$users[0][0], $orgs['techvision'], $ownerRoleId],
            [$users[0][0], $orgs['arabic'], $ownerRoleId],
            [$users[0][0], $orgs['fashionhub'], $ownerRoleId],
            [$users[0][0], $orgs['healthwell'], $ownerRoleId],

            // Sarah Johnson - TechVision Admin
            [$users[1][0], $orgs['techvision'], $adminRoleId],

            // محمد أحمد - Arabic Marketing Admin
            [$users[2][0], $orgs['arabic'], $adminRoleId],

            // Emma Williams - FashionHub Manager
            [$users[3][0], $orgs['fashionhub'], $managerRoleId],

            // David Chen - HealthWell Manager
            [$users[4][0], $orgs['healthwell'], $managerRoleId],

            // Maria Garcia - TechVision Manager
            [$users[5][0], $orgs['techvision'], $managerRoleId],

            // Ahmed Al-Rashid - Arabic Marketing Manager
            [$users[6][0], $orgs['arabic'], $managerRoleId],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO cmis.user_orgs (user_id, org_id, role_id, is_active, joined_at)
            VALUES (?, ?, ?, true, NOW())
        ");

        foreach ($userOrgs as $userOrg) {
            $stmt->execute($userOrg);
        }

        $this->command->info('User-organization relationships created successfully!');
    }
}
