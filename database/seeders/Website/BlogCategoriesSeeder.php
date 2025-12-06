<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_en' => 'Marketing Tips',
                'name_ar' => 'نصائح التسويق',
                'slug' => 'marketing-tips',
                'description_en' => 'Expert tips and strategies for digital marketing success.',
                'description_ar' => 'نصائح واستراتيجيات الخبراء لنجاح التسويق الرقمي.',
                'sort_order' => 1,
            ],
            [
                'name_en' => 'AI & Automation',
                'name_ar' => 'الذكاء الاصطناعي والأتمتة',
                'slug' => 'ai-automation',
                'description_en' => 'Latest trends in AI-powered marketing automation.',
                'description_ar' => 'أحدث الاتجاهات في أتمتة التسويق بالذكاء الاصطناعي.',
                'sort_order' => 2,
            ],
            [
                'name_en' => 'Social Media',
                'name_ar' => 'وسائل التواصل الاجتماعي',
                'slug' => 'social-media',
                'description_en' => 'Social media marketing strategies and best practices.',
                'description_ar' => 'استراتيجيات التسويق عبر وسائل التواصل الاجتماعي وأفضل الممارسات.',
                'sort_order' => 3,
            ],
            [
                'name_en' => 'Case Studies',
                'name_ar' => 'دراسات الحالة',
                'slug' => 'case-studies',
                'description_en' => 'Real success stories from our customers.',
                'description_ar' => 'قصص نجاح حقيقية من عملائنا.',
                'sort_order' => 4,
            ],
            [
                'name_en' => 'Product Updates',
                'name_ar' => 'تحديثات المنتج',
                'slug' => 'product-updates',
                'description_en' => 'Latest features and improvements to CMIS.',
                'description_ar' => 'أحدث الميزات والتحسينات في CMIS.',
                'sort_order' => 5,
            ],
            [
                'name_en' => 'Industry Insights',
                'name_ar' => 'رؤى الصناعة',
                'slug' => 'industry-insights',
                'description_en' => 'Market trends and industry analysis.',
                'description_ar' => 'اتجاهات السوق وتحليل الصناعة.',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('cmis_website.blog_categories')->insert([
                'id' => Str::uuid(),
                'name_en' => $category['name_en'],
                'name_ar' => $category['name_ar'],
                'slug' => $category['slug'],
                'description_en' => $category['description_en'],
                'description_ar' => $category['description_ar'],
                'sort_order' => $category['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
