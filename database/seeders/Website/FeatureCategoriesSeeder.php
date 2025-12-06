<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeatureCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_en' => 'Campaign Management',
                'name_ar' => 'إدارة الحملات',
                'slug' => 'campaign-management',
                'icon' => 'fas fa-bullhorn',
                'sort_order' => 1,
            ],
            [
                'name_en' => 'AI & Analytics',
                'name_ar' => 'الذكاء الاصطناعي والتحليلات',
                'slug' => 'ai-analytics',
                'icon' => 'fas fa-brain',
                'sort_order' => 2,
            ],
            [
                'name_en' => 'Social Publishing',
                'name_ar' => 'النشر الاجتماعي',
                'slug' => 'social-publishing',
                'icon' => 'fas fa-share-alt',
                'sort_order' => 3,
            ],
            [
                'name_en' => 'Platform Integrations',
                'name_ar' => 'تكاملات المنصات',
                'slug' => 'platform-integrations',
                'icon' => 'fas fa-plug',
                'sort_order' => 4,
            ],
            [
                'name_en' => 'Reporting & Insights',
                'name_ar' => 'التقارير والرؤى',
                'slug' => 'reporting-insights',
                'icon' => 'fas fa-chart-line',
                'sort_order' => 5,
            ],
            [
                'name_en' => 'Team Collaboration',
                'name_ar' => 'تعاون الفريق',
                'slug' => 'team-collaboration',
                'icon' => 'fas fa-users',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('cmis_website.feature_categories')->insert([
                'id' => Str::uuid(),
                'slug' => $category['slug'],
                'name_en' => $category['name_en'],
                'name_ar' => $category['name_ar'],
                'icon' => $category['icon'],
                'sort_order' => $category['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
