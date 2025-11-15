<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with reference data and comprehensive demo data.
     *
     * Seeding order follows foreign key dependencies:
     * 1. Reference data (channels, industries, markets, etc.)
     * 2. Core entities (orgs, permissions, roles, users)
     * 3. Demo data (integrations, campaigns, content, social posts, ads)
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();

        // Level 1: Reference Data (no dependencies)
        $this->command->info('📚 Seeding reference data...');
        $this->call([
            ChannelsSeeder::class,
            ChannelFormatsSeeder::class,
            IndustriesSeeder::class,
            MarketsSeeder::class,
            MarketingObjectivesSeeder::class,
            ReferenceDataSeeder::class, // awareness_stages, funnel_stages, tones, strategies, kpis
        ]);

        $this->command->newLine();
        $this->command->info('🏢 Seeding core entities...');

        // Level 2: Core Entities
        $this->call([
            OrgsSeeder::class,
            PermissionsSeeder::class,
            RolesSeeder::class,
            UsersSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('🎭 Seeding comprehensive demo data...');

        // Level 3: Demo Data (integrations, campaigns, content, social, ads)
        $this->call([
            DemoDataSeeder::class,
        ]);

        // Optional: Session data from backup (for development/testing)
        if (app()->environment('local', 'development')) {
            $this->call([
                SessionsSeeder::class,
            ]);
        }

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
    }
}
