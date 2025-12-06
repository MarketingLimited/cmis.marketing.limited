<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HeroSlidesSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'headline_en' => 'Transform Your Marketing with AI',
                'headline_ar' => 'حوّل تسويقك بالذكاء الاصطناعي',
                'subheadline_en' => 'Automate campaigns, optimize performance, and drive results across all major advertising platforms.',
                'subheadline_ar' => 'أتمتة الحملات، تحسين الأداء، وتحقيق النتائج عبر جميع منصات الإعلان الرئيسية.',
                'cta_text_en' => 'Start Free Trial',
                'cta_text_ar' => 'ابدأ تجربة مجانية',
                'cta_url' => '/demo',
                'background_image_url' => '/images/hero/hero-1.jpg',
                'sort_order' => 1,
            ],
            [
                'headline_en' => 'All Your Platforms, One Dashboard',
                'headline_ar' => 'جميع منصاتك، لوحة تحكم واحدة',
                'subheadline_en' => 'Manage Meta, Google, TikTok, LinkedIn, and more from a single unified interface.',
                'subheadline_ar' => 'إدارة Meta وGoogle وTikTok وLinkedIn والمزيد من واجهة موحدة واحدة.',
                'cta_text_en' => 'See Features',
                'cta_text_ar' => 'شاهد الميزات',
                'cta_url' => '/features',
                'background_image_url' => '/images/hero/hero-2.jpg',
                'sort_order' => 2,
            ],
            [
                'headline_en' => 'Data-Driven Decisions, Real Results',
                'headline_ar' => 'قرارات مبنية على البيانات، نتائج حقيقية',
                'subheadline_en' => 'Advanced analytics and AI insights to maximize your marketing ROI.',
                'subheadline_ar' => 'تحليلات متقدمة ورؤى الذكاء الاصطناعي لتعظيم عائد الاستثمار التسويقي.',
                'cta_text_en' => 'View Pricing',
                'cta_text_ar' => 'عرض الأسعار',
                'cta_url' => '/pricing',
                'background_image_url' => '/images/hero/hero-3.jpg',
                'sort_order' => 3,
            ],
        ];

        foreach ($slides as $slide) {
            DB::table('cmis_website.hero_slides')->insert([
                'id' => Str::uuid(),
                'headline_en' => $slide['headline_en'],
                'headline_ar' => $slide['headline_ar'],
                'subheadline_en' => $slide['subheadline_en'],
                'subheadline_ar' => $slide['subheadline_ar'],
                'cta_text_en' => $slide['cta_text_en'],
                'cta_text_ar' => $slide['cta_text_ar'],
                'cta_url' => $slide['cta_url'],
                'background_image_url' => $slide['background_image_url'],
                'sort_order' => $slide['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
