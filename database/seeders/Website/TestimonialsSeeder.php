<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestimonialsSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'author_en' => 'Mohammed Al-Farsi',
                'author_ar' => 'محمد الفارسي',
                'role_en' => 'Marketing Director',
                'role_ar' => 'مدير التسويق',
                'company_en' => 'E-Commerce Pro',
                'company_ar' => 'إي-كوميرس برو',
                'quote_en' => 'CMIS has transformed how we manage our digital advertising. We have seen a 40% increase in ROAS since switching to the platform.',
                'quote_ar' => 'غير CMIS طريقة إدارتنا للإعلانات الرقمية. شهدنا زيادة بنسبة 40% في عائد الإنفاق الإعلاني منذ التحول إلى المنصة.',
                'rating' => 5,
                'image_url' => '/images/testimonials/mohammed-alfarsi.jpg',
                'sort_order' => 1,
            ],
            [
                'author_en' => 'Sarah Johnson',
                'author_ar' => 'سارة جونسون',
                'role_en' => 'CEO',
                'role_ar' => 'الرئيسة التنفيذية',
                'company_en' => 'GrowthLab Agency',
                'company_ar' => 'وكالة جروث لاب',
                'quote_en' => 'The AI recommendations alone have paid for our subscription many times over. Our clients are seeing better results than ever.',
                'quote_ar' => 'التوصيات المدعومة بالذكاء الاصطناعي وحدها قد استردت تكلفة اشتراكنا عدة مرات. يشهد عملاؤنا نتائج أفضل من أي وقت مضى.',
                'rating' => 5,
                'image_url' => '/images/testimonials/sarah-johnson.jpg',
                'sort_order' => 2,
            ],
            [
                'author_en' => 'Ahmad Nasser',
                'author_ar' => 'أحمد ناصر',
                'role_en' => 'Digital Marketing Manager',
                'role_ar' => 'مدير التسويق الرقمي',
                'company_en' => 'Tech Solutions MENA',
                'company_ar' => 'حلول التقنية الشرق الأوسط',
                'quote_en' => 'Managing campaigns across 6 platforms used to take us days. With CMIS, we do it in hours with better results.',
                'quote_ar' => 'كانت إدارة الحملات عبر 6 منصات تستغرق منا أياماً. مع CMIS، ننجزها في ساعات مع نتائج أفضل.',
                'rating' => 5,
                'image_url' => '/images/testimonials/ahmad-nasser.jpg',
                'sort_order' => 3,
            ],
            [
                'author_en' => 'Lisa Chen',
                'author_ar' => 'ليزا تشن',
                'role_en' => 'Performance Marketing Lead',
                'role_ar' => 'قائدة تسويق الأداء',
                'company_en' => 'Venture Scale',
                'company_ar' => 'فينتشر سكيل',
                'quote_en' => 'The cross-platform reporting is incredible. We finally have a unified view of our marketing performance.',
                'quote_ar' => 'التقارير عبر المنصات مذهلة. لدينا أخيراً رؤية موحدة لأداء التسويق لدينا.',
                'rating' => 5,
                'image_url' => '/images/testimonials/lisa-chen.jpg',
                'sort_order' => 4,
            ],
            [
                'author_en' => 'Khalid Al-Mahmoud',
                'author_ar' => 'خالد المحمود',
                'role_en' => 'Head of Growth',
                'role_ar' => 'رئيس النمو',
                'company_en' => 'FinTech Arabia',
                'company_ar' => 'فينتك العربية',
                'quote_en' => 'CMIS helped us scale our advertising budget from $10K to $100K monthly while maintaining strong efficiency metrics.',
                'quote_ar' => 'ساعدنا CMIS في توسيع ميزانيتنا الإعلانية من 10 آلاف دولار إلى 100 ألف دولار شهرياً مع الحفاظ على مقاييس كفاءة قوية.',
                'rating' => 5,
                'image_url' => '/images/testimonials/khalid-almahmoud.jpg',
                'sort_order' => 5,
            ],
            [
                'author_en' => 'Emma Williams',
                'author_ar' => 'إيما ويليامز',
                'role_en' => 'CMO',
                'role_ar' => 'مديرة التسويق',
                'company_en' => 'Retail Plus',
                'company_ar' => 'ريتيل بلس',
                'quote_en' => 'The automation features save our team 20+ hours per week. We can now focus on strategy instead of manual tasks.',
                'quote_ar' => 'توفر ميزات الأتمتة لفريقنا أكثر من 20 ساعة أسبوعياً. يمكننا الآن التركيز على الاستراتيجية بدلاً من المهام اليدوية.',
                'rating' => 5,
                'image_url' => '/images/testimonials/emma-williams.jpg',
                'sort_order' => 6,
            ],
            [
                'author_en' => 'Rami Saleh',
                'author_ar' => 'رامي صالح',
                'role_en' => 'Founder',
                'role_ar' => 'المؤسس',
                'company_en' => 'Digital First Agency',
                'company_ar' => 'وكالة ديجيتال فيرست',
                'quote_en' => 'As an agency, CMIS allows us to manage multiple client accounts efficiently. The white-label options are perfect.',
                'quote_ar' => 'كوكالة، يتيح لنا CMIS إدارة حسابات عملاء متعددة بكفاءة. خيارات العلامة البيضاء مثالية.',
                'rating' => 5,
                'image_url' => '/images/testimonials/rami-saleh.jpg',
                'sort_order' => 7,
            ],
            [
                'author_en' => 'Jennifer Park',
                'author_ar' => 'جينيفر بارك',
                'role_en' => 'VP Marketing',
                'role_ar' => 'نائبة رئيس التسويق',
                'company_en' => 'SaaS Global',
                'company_ar' => 'ساس جلوبال',
                'quote_en' => 'The budget optimization AI has reduced our CPA by 35%. This platform is a game-changer for B2B marketing.',
                'quote_ar' => 'خفض الذكاء الاصطناعي لتحسين الميزانية تكلفة الاكتساب لدينا بنسبة 35%. هذه المنصة تغير قواعد اللعبة في التسويق B2B.',
                'rating' => 5,
                'image_url' => '/images/testimonials/jennifer-park.jpg',
                'sort_order' => 8,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            DB::table('cmis_website.testimonials')->insert([
                'id' => Str::uuid(),
                'author_name_en' => $testimonial['author_en'],
                'author_name_ar' => $testimonial['author_ar'],
                'author_role_en' => $testimonial['role_en'],
                'author_role_ar' => $testimonial['role_ar'],
                'company_name_en' => $testimonial['company_en'],
                'company_name_ar' => $testimonial['company_ar'],
                'quote_en' => $testimonial['quote_en'],
                'quote_ar' => $testimonial['quote_ar'],
                'rating' => $testimonial['rating'],
                'author_image_url' => $testimonial['image_url'],
                'sort_order' => $testimonial['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
