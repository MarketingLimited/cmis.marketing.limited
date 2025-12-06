<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamMembersSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'name_en' => 'Ahmed Hassan',
                'name_ar' => 'أحمد حسن',
                'role_en' => 'CEO & Co-Founder',
                'role_ar' => 'الرئيس التنفيذي والشريك المؤسس',
                'bio_en' => 'Ahmed has 15+ years of experience in digital marketing and technology. He previously led product teams at major tech companies before founding CMIS.',
                'bio_ar' => 'لدى أحمد أكثر من 15 عاماً من الخبرة في التسويق الرقمي والتكنولوجيا. قاد سابقاً فرق المنتجات في شركات تقنية كبرى قبل تأسيس CMIS.',
                'image_url' => '/images/team/ahmed-hassan.jpg',
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/ahmed-hassan',
                    'twitter' => 'https://twitter.com/ahmedhassan',
                ]),
                'sort_order' => 1,
            ],
            [
                'name_en' => 'Sara Al-Rashid',
                'name_ar' => 'سارة الراشد',
                'role_en' => 'CTO & Co-Founder',
                'role_ar' => 'المديرة التقنية والشريكة المؤسسة',
                'bio_en' => 'Sara is an AI and machine learning expert with a PhD from MIT. She leads the development of our AI-powered marketing optimization engine.',
                'bio_ar' => 'سارة خبيرة في الذكاء الاصطناعي والتعلم الآلي حاصلة على درجة الدكتوراه من MIT. تقود تطوير محرك تحسين التسويق المدعوم بالذكاء الاصطناعي.',
                'image_url' => '/images/team/sara-alrashid.jpg',
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/sara-alrashid',
                    'github' => 'https://github.com/saraalrashid',
                ]),
                'sort_order' => 2,
            ],
            [
                'name_en' => 'Michael Chen',
                'name_ar' => 'مايكل تشن',
                'role_en' => 'VP of Engineering',
                'role_ar' => 'نائب رئيس الهندسة',
                'bio_en' => 'Michael brings extensive experience in building scalable platforms. He previously built advertising systems used by millions of advertisers worldwide.',
                'bio_ar' => 'يجلب مايكل خبرة واسعة في بناء منصات قابلة للتوسع. بنى سابقاً أنظمة إعلانية يستخدمها ملايين المعلنين حول العالم.',
                'image_url' => '/images/team/michael-chen.jpg',
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/michaelchen',
                ]),
                'sort_order' => 3,
            ],
            [
                'name_en' => 'Fatima Al-Sayed',
                'name_ar' => 'فاطمة السيد',
                'role_en' => 'VP of Product',
                'role_ar' => 'نائبة رئيس المنتج',
                'bio_en' => 'Fatima has led product development at several successful startups. She ensures CMIS delivers the features marketers need most.',
                'bio_ar' => 'قادت فاطمة تطوير المنتجات في عدة شركات ناشئة ناجحة. تضمن أن CMIS يقدم الميزات التي يحتاجها المسوقون أكثر.',
                'image_url' => '/images/team/fatima-alsayed.jpg',
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/fatima-alsayed',
                    'twitter' => 'https://twitter.com/fatimaalsayed',
                ]),
                'sort_order' => 4,
            ],
            [
                'name_en' => 'Omar Khalil',
                'name_ar' => 'عمر خليل',
                'role_en' => 'VP of Sales',
                'role_ar' => 'نائب رئيس المبيعات',
                'bio_en' => 'Omar has 12 years of experience in SaaS sales and has helped grow multiple companies from startup to scale-up.',
                'bio_ar' => 'لدى عمر 12 عاماً من الخبرة في مبيعات SaaS وساعد في نمو شركات متعددة من بداية التأسيس إلى التوسع.',
                'image_url' => '/images/team/omar-khalil.jpg',
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/omar-khalil',
                ]),
                'sort_order' => 5,
            ],
            [
                'name_en' => 'Layla Ibrahim',
                'name_ar' => 'ليلى إبراهيم',
                'role_en' => 'VP of Customer Success',
                'role_ar' => 'نائبة رئيس نجاح العملاء',
                'bio_en' => 'Layla is passionate about customer experience and has built world-class support teams at leading technology companies.',
                'bio_ar' => 'ليلى شغوفة بتجربة العملاء وقد بنت فرق دعم عالمية المستوى في شركات تقنية رائدة.',
                'image_url' => '/images/team/layla-ibrahim.jpg',
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/layla-ibrahim',
                ]),
                'sort_order' => 6,
            ],
            [
                'name_en' => 'David Kim',
                'name_ar' => 'ديفيد كيم',
                'role_en' => 'Head of Data Science',
                'role_ar' => 'رئيس علوم البيانات',
                'bio_en' => 'David leads our data science team, building the predictive models that power CMIS intelligent recommendations.',
                'bio_ar' => 'يقود ديفيد فريق علوم البيانات لدينا، وينشئ النماذج التنبؤية التي تشغل توصيات CMIS الذكية.',
                'image_url' => '/images/team/david-kim.jpg',
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/david-kim',
                    'github' => 'https://github.com/davidkim',
                ]),
                'sort_order' => 7,
            ],
            [
                'name_en' => 'Nour Mansour',
                'name_ar' => 'نور منصور',
                'role_en' => 'Head of Marketing',
                'role_ar' => 'رئيسة التسويق',
                'bio_en' => 'Nour brings creative marketing expertise and has led successful campaigns for Fortune 500 companies and startups alike.',
                'bio_ar' => 'تجلب نور خبرة تسويقية إبداعية وقد قادت حملات ناجحة لشركات Fortune 500 والشركات الناشئة على حد سواء.',
                'image_url' => '/images/team/nour-mansour.jpg',
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/in/nour-mansour',
                    'twitter' => 'https://twitter.com/nourmansour',
                ]),
                'sort_order' => 8,
            ],
        ];

        foreach ($members as $member) {
            DB::table('cmis_website.team_members')->insert([
                'id' => Str::uuid(),
                'name_en' => $member['name_en'],
                'name_ar' => $member['name_ar'],
                'role_en' => $member['role_en'],
                'role_ar' => $member['role_ar'],
                'bio_en' => $member['bio_en'],
                'bio_ar' => $member['bio_ar'],
                'image_url' => $member['image_url'],
                'social_links' => $member['social_links'],
                'sort_order' => $member['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
