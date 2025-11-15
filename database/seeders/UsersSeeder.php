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

        $users = [
            [
                'user_id' => 'd76b3d33-4d67-4dd6-9df9-845a18ba3435',
                'name' => 'Admin User',
                'email' => 'admin@cmis.test',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'user_id' => Str::uuid(),
                'name' => 'Sarah Johnson',
                'email' => 'sarah@techvision.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'user_id' => Str::uuid(),
                'name' => 'محمد أحمد',
                'email' => 'mohamed@arabic-marketing.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'user_id' => Str::uuid(),
                'name' => 'Emma Williams',
                'email' => 'emma@fashionhub.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'user_id' => Str::uuid(),
                'name' => 'David Chen',
                'email' => 'david@healthwell.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'user_id' => Str::uuid(),
                'name' => 'Maria Garcia',
                'email' => 'maria@techvision.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'user_id' => Str::uuid(),
                'name' => 'Ahmed Al-Rashid',
                'email' => 'ahmed@arabic-marketing.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ];

        foreach ($users as $user) {
            DB::table('cmis.users')->insert($user);
        }

        $this->command->info('Users seeded successfully! Default password: password');
    }
}
