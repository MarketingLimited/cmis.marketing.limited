<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeaturesSeeder extends Seeder
{
    public function run(): void
    {
        // Get category IDs
        $categories = DB::table('cmis_website.feature_categories')
            ->pluck('id', 'name_en')
            ->toArray();

        $features = [
            // Campaign Management
            [
                'category' => 'Campaign Management',
                'title_en' => 'Multi-Platform Campaigns',
                'title_ar' => 'حملات متعددة المنصات',
                'description_en' => 'Create and manage campaigns across Meta, Google, TikTok, LinkedIn, Twitter, and Snapchat from one place.',
                'description_ar' => 'إنشاء وإدارة الحملات عبر Meta وGoogle وTikTok وLinkedIn وTwitter وSnapchat من مكان واحد.',
                'icon' => 'fas fa-bullseye',
                'sort_order' => 1,
            ],
            [
                'category' => 'Campaign Management',
                'title_en' => 'Budget Optimization',
                'title_ar' => 'تحسين الميزانية',
                'description_en' => 'AI-powered budget allocation that maximizes your advertising ROI across all channels.',
                'description_ar' => 'تخصيص الميزانية المدعوم بالذكاء الاصطناعي لتعظيم عائد الاستثمار الإعلاني عبر جميع القنوات.',
                'icon' => 'fas fa-coins',
                'sort_order' => 2,
            ],
            [
                'category' => 'Campaign Management',
                'title_en' => 'A/B Testing',
                'title_ar' => 'اختبار A/B',
                'description_en' => 'Automatically test creative variations and audiences to find what works best.',
                'description_ar' => 'اختبار تلقائي للتباينات الإبداعية والجماهير للعثور على الأفضل.',
                'icon' => 'fas fa-flask',
                'sort_order' => 3,
            ],
            // AI & Analytics
            [
                'category' => 'AI & Analytics',
                'title_en' => 'Predictive Analytics',
                'title_ar' => 'التحليلات التنبؤية',
                'description_en' => 'Forecast campaign performance and optimize strategies with machine learning.',
                'description_ar' => 'توقع أداء الحملات وتحسين الاستراتيجيات باستخدام التعلم الآلي.',
                'icon' => 'fas fa-chart-line',
                'sort_order' => 1,
            ],
            [
                'category' => 'AI & Analytics',
                'title_en' => 'Smart Recommendations',
                'title_ar' => 'توصيات ذكية',
                'description_en' => 'Get AI-powered suggestions to improve your campaign performance in real-time.',
                'description_ar' => 'احصل على اقتراحات مدعومة بالذكاء الاصطناعي لتحسين أداء حملاتك في الوقت الفعلي.',
                'icon' => 'fas fa-lightbulb',
                'sort_order' => 2,
            ],
            [
                'category' => 'AI & Analytics',
                'title_en' => 'Semantic Search',
                'title_ar' => 'البحث الدلالي',
                'description_en' => 'Find campaigns, content, and insights using natural language queries.',
                'description_ar' => 'العثور على الحملات والمحتوى والرؤى باستخدام استعلامات اللغة الطبيعية.',
                'icon' => 'fas fa-search',
                'sort_order' => 3,
            ],
            // Social Publishing
            [
                'category' => 'Social Publishing',
                'title_en' => 'Content Calendar',
                'title_ar' => 'تقويم المحتوى',
                'description_en' => 'Plan and schedule your social media content across all platforms visually.',
                'description_ar' => 'تخطيط وجدولة محتوى وسائل التواصل الاجتماعي عبر جميع المنصات بصرياً.',
                'icon' => 'fas fa-calendar-alt',
                'sort_order' => 1,
            ],
            [
                'category' => 'Social Publishing',
                'title_en' => 'Automated Posting',
                'title_ar' => 'النشر الآلي',
                'description_en' => 'Schedule posts for optimal times based on audience engagement patterns.',
                'description_ar' => 'جدولة المنشورات للأوقات المثلى بناءً على أنماط تفاعل الجمهور.',
                'icon' => 'fas fa-clock',
                'sort_order' => 2,
            ],
            [
                'category' => 'Social Publishing',
                'title_en' => 'Media Library',
                'title_ar' => 'مكتبة الوسائط',
                'description_en' => 'Centralized asset management for all your images, videos, and creative content.',
                'description_ar' => 'إدارة مركزية للأصول لجميع صورك وفيديوهاتك ومحتواك الإبداعي.',
                'icon' => 'fas fa-images',
                'sort_order' => 3,
            ],
            // Platform Integrations
            [
                'category' => 'Platform Integrations',
                'title_en' => 'Meta Business Suite',
                'title_ar' => 'Meta Business Suite',
                'description_en' => 'Full integration with Facebook, Instagram, and WhatsApp advertising.',
                'description_ar' => 'تكامل كامل مع إعلانات Facebook وInstagram وWhatsApp.',
                'icon' => 'fab fa-meta',
                'sort_order' => 1,
            ],
            [
                'category' => 'Platform Integrations',
                'title_en' => 'Google Ads',
                'title_ar' => 'إعلانات Google',
                'description_en' => 'Manage Search, Display, Shopping, and YouTube campaigns seamlessly.',
                'description_ar' => 'إدارة حملات البحث والعرض والتسوق وYouTube بسلاسة.',
                'icon' => 'fab fa-google',
                'sort_order' => 2,
            ],
            [
                'category' => 'Platform Integrations',
                'title_en' => 'TikTok Ads',
                'title_ar' => 'إعلانات TikTok',
                'description_en' => 'Create and optimize TikTok ad campaigns for maximum engagement.',
                'description_ar' => 'إنشاء وتحسين حملات إعلانات TikTok لأقصى تفاعل.',
                'icon' => 'fab fa-tiktok',
                'sort_order' => 3,
            ],
            // Reporting & Insights
            [
                'category' => 'Reporting & Insights',
                'title_en' => 'Custom Dashboards',
                'title_ar' => 'لوحات تحكم مخصصة',
                'description_en' => 'Build personalized dashboards with the metrics that matter most to you.',
                'description_ar' => 'بناء لوحات تحكم مخصصة بالمقاييس الأهم بالنسبة لك.',
                'icon' => 'fas fa-tachometer-alt',
                'sort_order' => 1,
            ],
            [
                'category' => 'Reporting & Insights',
                'title_en' => 'Automated Reports',
                'title_ar' => 'تقارير آلية',
                'description_en' => 'Schedule and deliver comprehensive reports to stakeholders automatically.',
                'description_ar' => 'جدولة وتقديم تقارير شاملة لأصحاب المصلحة تلقائياً.',
                'icon' => 'fas fa-file-alt',
                'sort_order' => 2,
            ],
            [
                'category' => 'Reporting & Insights',
                'title_en' => 'Cross-Platform Analytics',
                'title_ar' => 'تحليلات عبر المنصات',
                'description_en' => 'Compare performance across all platforms in unified reports.',
                'description_ar' => 'مقارنة الأداء عبر جميع المنصات في تقارير موحدة.',
                'icon' => 'fas fa-chart-bar',
                'sort_order' => 3,
            ],
            // Team Collaboration
            [
                'category' => 'Team Collaboration',
                'title_en' => 'Role-Based Access',
                'title_ar' => 'الوصول القائم على الأدوار',
                'description_en' => 'Define custom roles and permissions for team members.',
                'description_ar' => 'تحديد أدوار وصلاحيات مخصصة لأعضاء الفريق.',
                'icon' => 'fas fa-user-shield',
                'sort_order' => 1,
            ],
            [
                'category' => 'Team Collaboration',
                'title_en' => 'Approval Workflows',
                'title_ar' => 'سير عمل الموافقات',
                'description_en' => 'Set up review and approval processes for campaigns and content.',
                'description_ar' => 'إعداد عمليات المراجعة والموافقة للحملات والمحتوى.',
                'icon' => 'fas fa-tasks',
                'sort_order' => 2,
            ],
            [
                'category' => 'Team Collaboration',
                'title_en' => 'Activity Logs',
                'title_ar' => 'سجلات النشاط',
                'description_en' => 'Track all team activities with comprehensive audit logs.',
                'description_ar' => 'تتبع جميع أنشطة الفريق مع سجلات تدقيق شاملة.',
                'icon' => 'fas fa-history',
                'sort_order' => 3,
            ],
        ];

        foreach ($features as $feature) {
            $categoryId = $categories[$feature['category']] ?? null;
            if (!$categoryId) continue;

            // Generate slug from title
            $slug = Str::slug($feature['title_en']);

            DB::table('cmis_website.features')->insert([
                'id' => Str::uuid(),
                'slug' => $slug,
                'category_id' => $categoryId,
                'title_en' => $feature['title_en'],
                'title_ar' => $feature['title_ar'],
                'description_en' => $feature['description_en'],
                'description_ar' => $feature['description_ar'],
                'icon' => $feature['icon'],
                'sort_order' => $feature['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
