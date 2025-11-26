<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrgsSeeder extends Seeder
{
    /**
     * Seed demo organizations with complete data.
     * Uses fixed UUIDs from SeederConstants for consistency.
     */
    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        $orgs = [
            [
                'org_id' => SeederConstants::ORG_CMIS,
                'name' => 'CMIS',
                'default_locale' => 'en',
                'currency' => 'USD',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'org_id' => SeederConstants::ORG_TECHVISION,
                'name' => 'TechVision Solutions',
                'default_locale' => 'en',
                'currency' => 'USD',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'org_id' => SeederConstants::ORG_ARABIC_MARKETING,
                'name' => 'الشركة العربية للتسويق',
                'default_locale' => 'ar',
                'currency' => 'SAR',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'org_id' => SeederConstants::ORG_FASHIONHUB,
                'name' => 'FashionHub Retail',
                'default_locale' => 'en',
                'currency' => 'EUR',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'org_id' => SeederConstants::ORG_HEALTHWELL,
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
