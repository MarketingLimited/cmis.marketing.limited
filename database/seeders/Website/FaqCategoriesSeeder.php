<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FaqCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_en' => 'General',
                'name_ar' => 'عام',
                'slug' => 'general',
                'sort_order' => 1,
            ],
            [
                'name_en' => 'Pricing & Billing',
                'name_ar' => 'الأسعار والفوترة',
                'slug' => 'pricing-billing',
                'sort_order' => 2,
            ],
            [
                'name_en' => 'Features & Capabilities',
                'name_ar' => 'الميزات والقدرات',
                'slug' => 'features-capabilities',
                'sort_order' => 3,
            ],
            [
                'name_en' => 'Platform Integrations',
                'name_ar' => 'تكاملات المنصات',
                'slug' => 'platform-integrations',
                'sort_order' => 4,
            ],
            [
                'name_en' => 'Security & Privacy',
                'name_ar' => 'الأمان والخصوصية',
                'slug' => 'security-privacy',
                'sort_order' => 5,
            ],
            [
                'name_en' => 'Technical Support',
                'name_ar' => 'الدعم الفني',
                'slug' => 'technical-support',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('cmis_website.faq_categories')->insert([
                'id' => Str::uuid(),
                'slug' => $category['slug'],
                'name_en' => $category['name_en'],
                'name_ar' => $category['name_ar'],
                'sort_order' => $category['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
