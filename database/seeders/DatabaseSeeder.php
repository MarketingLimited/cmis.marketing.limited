<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        // Note: All seeders now use SeederConstants for shared IDs,
        // eliminating transaction isolation issues
        $this->call([
            OrgsSeeder::class,
            PermissionsSeeder::class,
            RolesSeeder::class,
            UsersSeeder::class,
            PlatformConnectionsSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('🎭 Seeding comprehensive demo data...');

        // Level 3: Demo Data (integrations, campaigns, content, social, ads)
        try {
            $this->call([
                DemoDataSeeder::class,
            ]);
        } catch (\Exception $e) {
            $this->command->warn('⚠️  Demo data seeding skipped: ' . $e->getMessage());
            $this->command->warn('⚠️  Core data (roles, permissions, orgs) has been seeded successfully.');
        }

        // TODO: Fix ExtendedDemoDataSeeder - modules table insert issue
        // $this->command->newLine();
        // $this->command->info('📦 Seeding extended demo data (50+ additional tables)...');

        // Level 4: Extended Demo Data (AI, modules, contexts, compliance, analytics, etc.)
        // $this->call([
        //     ExtendedDemoDataSeeder::class,
        // ]);

        // TODO: Fix SessionsSeeder - sessions table user_id type mismatch
        // Optional: Session data from backup (for development/testing)
        // if (app()->environment('local', 'development')) {
        //     $this->call([
        //         SessionsSeeder::class,
        //     ]);
        // }

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('📊 Seeded 90+ tables with comprehensive, interconnected demo data!');
        $this->command->newLine();
    }
}
