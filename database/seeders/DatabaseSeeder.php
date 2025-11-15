<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed data from backup file
        // Note: MigrationsSeeder is intentionally skipped as migrations are managed separately

        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            SessionsSeeder::class,
        ]);

        $this->command->info('Database seeding from backup completed successfully!');
    }
}
