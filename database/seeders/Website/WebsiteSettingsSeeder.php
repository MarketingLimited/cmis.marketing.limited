<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebsiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'value_en' => 'CMIS - Cognitive Marketing Intelligence Suite',
                'value_ar' => 'CMIS - منصة ذكاء التسويق المعرفي',
                'group' => 'general',
            ],
            [
                'key' => 'site_tagline',
                'value_en' => 'AI-Powered Marketing Automation',
                'value_ar' => 'أتمتة التسويق بالذكاء الاصطناعي',
                'group' => 'general',
            ],
            [
                'key' => 'site_description',
                'value_en' => 'Transform your marketing with AI-powered insights and automation across all major platforms.',
                'value_ar' => 'حول تسويقك مع رؤى مدعومة بالذكاء الاصطناعي وأتمتة عبر جميع المنصات الرئيسية.',
                'group' => 'general',
            ],
            // Contact Settings
            [
                'key' => 'contact_email',
                'value_en' => 'contact@cmis.io',
                'value_ar' => 'contact@cmis.io',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_phone',
                'value_en' => '+1 (555) 123-4567',
                'value_ar' => '+966 50 123 4567',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_address',
                'value_en' => '123 Innovation Drive, Tech City, CA 94102',
                'value_ar' => 'شارع الابتكار 123، مدينة التقنية، الرياض',
                'group' => 'contact',
            ],
            // Social Links
            [
                'key' => 'social_facebook',
                'value_en' => 'https://facebook.com/cmis',
                'value_ar' => 'https://facebook.com/cmis',
                'group' => 'social',
            ],
            [
                'key' => 'social_twitter',
                'value_en' => 'https://twitter.com/cmis',
                'value_ar' => 'https://twitter.com/cmis',
                'group' => 'social',
            ],
            [
                'key' => 'social_linkedin',
                'value_en' => 'https://linkedin.com/company/cmis',
                'value_ar' => 'https://linkedin.com/company/cmis',
                'group' => 'social',
            ],
            [
                'key' => 'social_instagram',
                'value_en' => 'https://instagram.com/cmis',
                'value_ar' => 'https://instagram.com/cmis',
                'group' => 'social',
            ],
            // SEO Settings
            [
                'key' => 'meta_title',
                'value_en' => 'CMIS - AI Marketing Platform | Automate & Optimize Your Campaigns',
                'value_ar' => 'CMIS - منصة التسويق بالذكاء الاصطناعي | أتمتة وتحسين حملاتك',
                'group' => 'seo',
            ],
            [
                'key' => 'meta_description',
                'value_en' => 'CMIS is the leading AI-powered marketing platform. Automate campaigns, analyze performance, and boost ROI across Meta, Google, TikTok, and more.',
                'value_ar' => 'CMIS هي منصة التسويق الرائدة بالذكاء الاصطناعي. أتمتة الحملات، تحليل الأداء، وزيادة العائد على الاستثمار عبر Meta، Google، TikTok والمزيد.',
                'group' => 'seo',
            ],
            [
                'key' => 'google_analytics_id',
                'value_en' => 'G-XXXXXXXXXX',
                'value_ar' => 'G-XXXXXXXXXX',
                'group' => 'seo',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('cmis_website.website_settings')->insert([
                'id' => Str::uuid(),
                'key' => $setting['key'],
                'value_en' => $setting['value_en'],
                'value_ar' => $setting['value_ar'],
                'group' => $setting['group'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
