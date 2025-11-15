<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketingObjectivesSeeder extends Seeder
{
    public function run(): void
    {
        $objectives = [
            // Awareness
            ['objective' => 'brand_awareness', 'display_name' => 'Brand Awareness', 'category' => 'awareness', 'description' => 'Increase recognition and visibility of your brand'],
            ['objective' => 'reach', 'display_name' => 'Reach', 'category' => 'awareness', 'description' => 'Show your ads to the maximum number of people'],

            // Understanding
            ['objective' => 'traffic', 'display_name' => 'Traffic', 'category' => 'understanding', 'description' => 'Send people to a destination like a website or app'],
            ['objective' => 'engagement', 'display_name' => 'Engagement', 'category' => 'understanding', 'description' => 'Get more post engagement, page likes, event responses or offer claims'],
            ['objective' => 'video_views', 'display_name' => 'Video Views', 'category' => 'understanding', 'description' => 'Get more people to watch your videos'],

            // Emotion
            ['objective' => 'app_installs', 'display_name' => 'App Installs', 'category' => 'emotion', 'description' => 'Get people to install your app'],
            ['objective' => 'lead_generation', 'display_name' => 'Lead Generation', 'category' => 'emotion', 'description' => 'Collect leads for your business'],

            // Trust
            ['objective' => 'messages', 'display_name' => 'Messages', 'category' => 'trust', 'description' => 'Get more people to message your business'],
            ['objective' => 'store_visits', 'display_name' => 'Store Visits', 'category' => 'trust', 'description' => 'Drive visits to physical stores'],

            // Conversion
            ['objective' => 'conversions', 'display_name' => 'Conversions', 'category' => 'conversion', 'description' => 'Encourage people to take valuable actions on your website or app'],
            ['objective' => 'catalog_sales', 'display_name' => 'Catalog Sales', 'category' => 'conversion', 'description' => 'Show products from your catalog to generate sales'],
            ['objective' => 'sales', 'display_name' => 'Sales', 'category' => 'conversion', 'description' => 'Drive sales on your website or app'],
        ];

        foreach ($objectives as $objective) {
            DB::table('public.marketing_objectives')->insert($objective);
        }

        $this->command->info('Marketing objectives seeded!');
    }
}
