<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Seed demo users for the application.
     * Uses fixed UUIDs from SeederConstants for consistency.
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

        // Use fixed UUIDs from SeederConstants for consistency across seeders
        $users = [
            [
                SeederConstants::USER_ADMIN,
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
                SeederConstants::USER_SARAH,
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
                SeederConstants::USER_MOHAMED,
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
                SeederConstants::USER_EMMA,
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
                SeederConstants::USER_DAVID,
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
                SeederConstants::USER_MARIA,
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
                SeederConstants::USER_AHMED,
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
     * Uses SeederConstants for role and org IDs.
     */
    private function assignUsersToOrgs($pdo, array $users): void
    {
        // Use SeederConstants for role IDs - no database query needed
        $ownerRoleId = SeederConstants::ROLE_OWNER;
        $adminRoleId = SeederConstants::ROLE_ADMIN;
        $managerRoleId = SeederConstants::ROLE_MARKETING_MANAGER;

        // Use SeederConstants for organization IDs
        $orgs = [
            'techvision' => SeederConstants::ORG_TECHVISION,
            'arabic' => SeederConstants::ORG_ARABIC_MARKETING,
            'fashionhub' => SeederConstants::ORG_FASHIONHUB,
            'healthwell' => SeederConstants::ORG_HEALTHWELL,
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
