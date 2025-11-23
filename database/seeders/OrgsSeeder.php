<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrgsSeeder extends Seeder
{
    /**
     * Seed demo organizations with complete data.
     */
    public function run(): void
    {
        // Bypass RLS for seeding
        DB::statement('SET LOCAL app.is_admin = true');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        $orgs = [
            [
                'org_id' => '9a5e0b1c-3d4e-4f5a-8b7c-1d2e3f4a5b6c',
                'name' => 'TechVision Solutions',
                'default_locale' => 'en',
                'currency' => 'USD',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'org_id' => '8b6f1a2d-4e5f-5a6b-9c8d-2e3f4a5b6c7d',
                'name' => 'الشركة العربية للتسويق',
                'default_locale' => 'ar',
                'currency' => 'SAR',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'org_id' => '7c8e2b3f-5f6a-6b7c-0d9e-3f4a5b6c7d8e',
                'name' => 'FashionHub Retail',
                'default_locale' => 'en',
                'currency' => 'EUR',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'org_id' => '6d9f3c4a-6a7b-7c8d-1e0f-4a5b6c7d8e9f',
                'name' => 'HealthWell Clinic',
                'default_locale' => 'en',
                'currency' => 'AED',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
        ];

        foreach ($orgs as $org) {
            DB::table('cmis.orgs')->insert($org);
        }

        $this->command->info('Organizations seeded successfully!');
    }
}
