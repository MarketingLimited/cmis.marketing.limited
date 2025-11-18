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
    }
}
