<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CaseStudiesSeeder extends Seeder
{
    public function run(): void
    {
        $caseStudies = [
            [
                'title_en' => 'How E-Commerce Pro Increased ROAS by 40%',
                'title_ar' => 'كيف زادت إي-كوميرس برو عائد الإنفاق الإعلاني بنسبة 40%',
                'slug' => 'ecommerce-pro-roas-increase',
                'client_en' => 'E-Commerce Pro',
                'client_ar' => 'إي-كوميرس برو',
                'industry_en' => 'E-Commerce',
                'industry_ar' => 'التجارة الإلكترونية',
                'challenge_en' => 'E-Commerce Pro was struggling to manage advertising across multiple platforms efficiently. Their team spent hours on manual optimization, and their ROAS was declining despite increased spending.',
                'challenge_ar' => 'كانت إي-كوميرس برو تكافح لإدارة الإعلانات عبر منصات متعددة بكفاءة. أمضى فريقهم ساعات في التحسين اليدوي، وكان عائد الإنفاق الإعلاني يتراجع رغم زيادة الإنفاق.',
                'solution_en' => 'Implemented CMIS AI-powered budget optimization and cross-platform campaign management. Automated bid adjustments and audience targeting based on real-time performance data.',
                'solution_ar' => 'تم تنفيذ تحسين الميزانية المدعوم بالذكاء الاصطناعي من CMIS وإدارة الحملات عبر المنصات. تعديلات العروض الآلية واستهداف الجمهور بناءً على بيانات الأداء في الوقت الفعلي.',
                'results_en' => 'Within 3 months, E-Commerce Pro achieved a 40% increase in ROAS, reduced campaign management time by 60%, and scaled their ad spend profitably.',
                'results_ar' => 'في غضون 3 أشهر، حققت إي-كوميرس برو زيادة بنسبة 40% في عائد الإنفاق الإعلاني، وخفضت وقت إدارة الحملات بنسبة 60%، ووسعت إنفاقها الإعلاني بشكل مربح.',
                'metrics' => json_encode([
                    ['label_en' => 'ROAS Increase', 'label_ar' => 'زيادة عائد الإنفاق', 'value' => '40%'],
                    ['label_en' => 'Time Saved', 'label_ar' => 'الوقت الموفر', 'value' => '60%'],
                    ['label_en' => 'Ad Spend Scaled', 'label_ar' => 'توسع الإنفاق الإعلاني', 'value' => '3x'],
                ]),
                'featured_image_url' => '/images/case-studies/ecommerce-pro.jpg',
                'client_logo_url' => '/images/case-studies/ecommerce-pro-logo.png',
                'sort_order' => 1,
            ],
            [
                'title_en' => 'GrowthLab Agency Scales to 50+ Clients with CMIS',
                'title_ar' => 'وكالة جروث لاب تتوسع لأكثر من 50 عميلاً مع CMIS',
                'slug' => 'growthlab-agency-scale',
                'client_en' => 'GrowthLab Agency',
                'client_ar' => 'وكالة جروث لاب',
                'industry_en' => 'Marketing Agency',
                'industry_ar' => 'وكالة تسويق',
                'challenge_en' => 'GrowthLab needed to scale their operations to handle more clients without proportionally increasing team size. Manual reporting and optimization were creating bottlenecks.',
                'challenge_ar' => 'احتاجت جروث لاب إلى توسيع عملياتها للتعامل مع المزيد من العملاء دون زيادة حجم الفريق بشكل متناسب. كانت التقارير والتحسين اليدوي تخلق اختناقات.',
                'solution_en' => 'Deployed CMIS white-label solution with automated reporting, cross-platform campaign management, and AI-powered optimization across all client accounts.',
                'solution_ar' => 'تم نشر حل CMIS ذو العلامة البيضاء مع التقارير الآلية وإدارة الحملات عبر المنصات والتحسين المدعوم بالذكاء الاصطناعي عبر جميع حسابات العملاء.',
                'results_en' => 'GrowthLab grew from 20 to 50+ clients in 6 months while maintaining the same team size. Client satisfaction scores improved by 45%.',
                'results_ar' => 'نمت جروث لاب من 20 إلى أكثر من 50 عميلاً في 6 أشهر مع الحفاظ على نفس حجم الفريق. تحسنت درجات رضا العملاء بنسبة 45%.',
                'metrics' => json_encode([
                    ['label_en' => 'Client Growth', 'label_ar' => 'نمو العملاء', 'value' => '150%'],
                    ['label_en' => 'Satisfaction Increase', 'label_ar' => 'زيادة الرضا', 'value' => '45%'],
                    ['label_en' => 'Team Efficiency', 'label_ar' => 'كفاءة الفريق', 'value' => '2.5x'],
                ]),
                'featured_image_url' => '/images/case-studies/growthlab.jpg',
                'client_logo_url' => '/images/case-studies/growthlab-logo.png',
                'sort_order' => 2,
            ],
            [
                'title_en' => 'FinTech Arabia Reduces CPA by 35% with AI Optimization',
                'title_ar' => 'فينتك العربية تخفض تكلفة الاكتساب بنسبة 35% مع التحسين بالذكاء الاصطناعي',
                'slug' => 'fintech-arabia-cpa-reduction',
                'client_en' => 'FinTech Arabia',
                'client_ar' => 'فينتك العربية',
                'industry_en' => 'Financial Services',
                'industry_ar' => 'الخدمات المالية',
                'challenge_en' => 'FinTech Arabia faced high customer acquisition costs in a competitive market. Their manual bidding strategies were not adapting quickly to market changes.',
                'challenge_ar' => 'واجهت فينتك العربية تكاليف اكتساب عملاء مرتفعة في سوق تنافسي. لم تكن استراتيجيات المزايدة اليدوية تتكيف بسرعة مع تغيرات السوق.',
                'solution_en' => 'Leveraged CMIS predictive analytics and automated bidding to optimize campaigns in real-time. Used AI recommendations to identify high-value audience segments.',
                'solution_ar' => 'استفادوا من التحليلات التنبؤية والمزايدة الآلية من CMIS لتحسين الحملات في الوقت الفعلي. استخدموا توصيات الذكاء الاصطناعي لتحديد شرائح الجمهور عالية القيمة.',
                'results_en' => 'Achieved 35% reduction in CPA, 50% improvement in lead quality, and successfully scaled monthly ad budget from $10K to $100K.',
                'results_ar' => 'حققوا خفض بنسبة 35% في تكلفة الاكتساب، وتحسين بنسبة 50% في جودة العملاء المحتملين، ونجحوا في توسيع الميزانية الإعلانية الشهرية من 10 آلاف دولار إلى 100 ألف دولار.',
                'metrics' => json_encode([
                    ['label_en' => 'CPA Reduction', 'label_ar' => 'خفض تكلفة الاكتساب', 'value' => '35%'],
                    ['label_en' => 'Lead Quality', 'label_ar' => 'جودة العملاء المحتملين', 'value' => '+50%'],
                    ['label_en' => 'Budget Scaled', 'label_ar' => 'توسع الميزانية', 'value' => '10x'],
                ]),
                'featured_image_url' => '/images/case-studies/fintech-arabia.jpg',
                'client_logo_url' => '/images/case-studies/fintech-arabia-logo.png',
                'sort_order' => 3,
            ],
            [
                'title_en' => 'Retail Plus Saves 20+ Hours Weekly with Automation',
                'title_ar' => 'ريتيل بلس توفر أكثر من 20 ساعة أسبوعياً مع الأتمتة',
                'slug' => 'retail-plus-automation-savings',
                'client_en' => 'Retail Plus',
                'client_ar' => 'ريتيل بلس',
                'industry_en' => 'Retail',
                'industry_ar' => 'التجزئة',
                'challenge_en' => 'Retail Plus marketing team was spending the majority of their time on manual campaign management across 4 platforms instead of strategic planning.',
                'challenge_ar' => 'كان فريق التسويق في ريتيل بلس يقضي معظم وقته في إدارة الحملات اليدوية عبر 4 منصات بدلاً من التخطيط الاستراتيجي.',
                'solution_en' => 'Implemented CMIS comprehensive automation including automated campaign creation, budget allocation, bid management, and scheduled reporting.',
                'solution_ar' => 'تم تنفيذ الأتمتة الشاملة من CMIS بما في ذلك إنشاء الحملات الآلي وتخصيص الميزانية وإدارة العروض والتقارير المجدولة.',
                'results_en' => 'Marketing team now saves 20+ hours weekly, campaign performance improved by 25%, and they successfully launched 3 new market expansion campaigns.',
                'results_ar' => 'يوفر فريق التسويق الآن أكثر من 20 ساعة أسبوعياً، وتحسن أداء الحملات بنسبة 25%، وأطلقوا بنجاح 3 حملات توسع سوقية جديدة.',
                'metrics' => json_encode([
                    ['label_en' => 'Time Saved Weekly', 'label_ar' => 'الوقت الموفر أسبوعياً', 'value' => '20+ hrs'],
                    ['label_en' => 'Performance Boost', 'label_ar' => 'تحسين الأداء', 'value' => '25%'],
                    ['label_en' => 'New Campaigns', 'label_ar' => 'حملات جديدة', 'value' => '3'],
                ]),
                'featured_image_url' => '/images/case-studies/retail-plus.jpg',
                'client_logo_url' => '/images/case-studies/retail-plus-logo.png',
                'sort_order' => 4,
            ],
        ];

        foreach ($caseStudies as $study) {
            DB::table('cmis_website.case_studies')->insert([
                'id' => Str::uuid(),
                'title_en' => $study['title_en'],
                'title_ar' => $study['title_ar'],
                'slug' => $study['slug'],
                'client_name_en' => $study['client_en'],
                'client_name_ar' => $study['client_ar'],
                'industry_en' => $study['industry_en'],
                'industry_ar' => $study['industry_ar'],
                'challenge_en' => $study['challenge_en'],
                'challenge_ar' => $study['challenge_ar'],
                'solution_en' => $study['solution_en'],
                'solution_ar' => $study['solution_ar'],
                'results_en' => $study['results_en'],
                'results_ar' => $study['results_ar'],
                'metrics' => $study['metrics'],
                'featured_image_url' => $study['featured_image_url'],
                'client_logo_url' => $study['client_logo_url'],
                'sort_order' => $study['sort_order'],
                'is_published' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
