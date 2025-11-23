<?php

namespace Database\Seeders;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Core\UserOrg;
use App\Models\Campaign\Campaign;
use App\Models\Creative\ContentPlan;
use App\Models\Creative\CreativeAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DuskTestSeeder - Comprehensive test data seeder for Laravel Dusk tests
 *
 * This seeder creates a consistent test environment with:
 * - Test organizations with proper RLS context
 * - Test users with proper org associations
 * - Sample campaigns, content plans, and creative assets
 */
class DuskTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test organizations
        $org1 = Org::create([
            'org_id' => \Illuminate\Support\Str::uuid(),
            'org_name' => 'Test Organization 1',
            'org_type' => 'ENTERPRISE',
            'timezone' => 'UTC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $org2 = Org::create([
            'org_id' => \Illuminate\Support\Str::uuid(),
            'org_name' => 'Test Organization 2',
            'org_type' => 'STARTUP',
            'timezone' => 'UTC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create test users
        $testUser = User::create([
            'user_id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'current_org_id' => $org1->org_id,
            'status' => 'active',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminUser = User::create([
            'user_id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'current_org_id' => $org1->org_id,
            'status' => 'active',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Associate users with organizations
        UserOrg::create([
            'user_org_id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $testUser->user_id,
            'org_id' => $org1->org_id,
            'role' => 'member',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        UserOrg::create([
            'user_org_id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $adminUser->user_id,
            'org_id' => $org1->org_id,
            'role' => 'admin',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        UserOrg::create([
            'user_org_id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $testUser->user_id,
            'org_id' => $org2->org_id,
            'role' => 'member',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Set RLS context for org1 to create sample data
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org1->org_id]);

        // Create sample campaigns for org1
        for ($i = 1; $i <= 5; $i++) {
            Campaign::create([
                'campaign_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $org1->org_id,
                'campaign_name' => "Test Campaign {$i}",
                'objective' => 'CONVERSIONS',
                'status' => ['ACTIVE', 'PAUSED', 'DRAFT'][$i % 3],
                'start_date' => now()->addDays($i),
                'end_date' => now()->addDays($i + 30),
                'budget' => 1000 * $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create sample content plans for org1
        for ($i = 1; $i <= 3; $i++) {
            ContentPlan::create([
                'plan_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $org1->org_id,
                'plan_name' => "Test Content Plan {$i}",
                'description' => "This is a test content plan for Dusk testing",
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create sample creative assets for org1
        for ($i = 1; $i <= 5; $i++) {
            CreativeAsset::create([
                'asset_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $org1->org_id,
                'asset_name' => "Test Asset {$i}",
                'asset_type' => ['IMAGE', 'VIDEO', 'TEXT'][$i % 3],
                'file_path' => "/test/path/asset_{$i}.jpg",
                'file_size' => 1024 * $i,
                'mime_type' => 'image/jpeg',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Set RLS context for org2 to create sample data
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org2->org_id]);

        // Create a few campaigns for org2 to test multi-tenancy isolation
        for ($i = 1; $i <= 2; $i++) {
            Campaign::create([
                'campaign_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $org2->org_id,
                'campaign_name' => "Org2 Campaign {$i}",
                'objective' => 'AWARENESS',
                'status' => 'ACTIVE',
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'budget' => 500 * $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Clear RLS context
        DB::statement("SELECT set_config('app.current_org_id', NULL, false)");

        $this->command->info('✅ Dusk test data seeded successfully!');
        $this->command->info("   - Organizations: 2");
        $this->command->info("   - Users: 2 (test@example.com / password123, admin@example.com / admin123)");
        $this->command->info("   - Campaigns: 7 total (5 for org1, 2 for org2)");
        $this->command->info("   - Content Plans: 3");
        $this->command->info("   - Creative Assets: 5");
    }
}
