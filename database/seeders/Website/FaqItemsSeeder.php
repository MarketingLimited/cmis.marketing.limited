<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FaqItemsSeeder extends Seeder
{
    public function run(): void
    {
        // Get category IDs
        $categories = DB::table('cmis_website.faq_categories')
            ->pluck('id', 'name_en')
            ->toArray();

        $faqs = [
            // General
            [
                'category' => 'General',
                'question_en' => 'What is CMIS?',
                'question_ar' => 'ما هو CMIS؟',
                'answer_en' => 'CMIS (Cognitive Marketing Intelligence Suite) is an AI-powered marketing automation platform that helps businesses manage, optimize, and analyze their digital advertising campaigns across multiple platforms including Meta, Google, TikTok, LinkedIn, Twitter, and Snapchat.',
                'answer_ar' => 'CMIS (منصة ذكاء التسويق المعرفي) هي منصة أتمتة تسويق مدعومة بالذكاء الاصطناعي تساعد الشركات في إدارة وتحسين وتحليل حملاتها الإعلانية الرقمية عبر منصات متعددة بما في ذلك Meta وGoogle وTikTok وLinkedIn وTwitter وSnapchat.',
                'sort_order' => 1,
            ],
            [
                'category' => 'General',
                'question_en' => 'How do I get started with CMIS?',
                'question_ar' => 'كيف أبدأ مع CMIS؟',
                'answer_en' => 'Getting started is easy! Sign up for a free trial, connect your advertising accounts, and our onboarding wizard will guide you through setting up your first campaigns. Our support team is also available to help you every step of the way.',
                'answer_ar' => 'البدء سهل! سجل للحصول على تجربة مجانية، وقم بربط حساباتك الإعلانية، وسيرشدك معالج الإعداد لدينا خلال إعداد حملاتك الأولى. فريق الدعم لدينا متاح أيضاً لمساعدتك في كل خطوة.',
                'sort_order' => 2,
            ],
            // Pricing & Billing
            [
                'category' => 'Pricing & Billing',
                'question_en' => 'What pricing plans are available?',
                'question_ar' => 'ما هي خطط الأسعار المتاحة؟',
                'answer_en' => 'We offer flexible pricing plans to suit businesses of all sizes: Starter for small teams, Professional for growing businesses, and Enterprise for large organizations. All plans come with a 14-day free trial.',
                'answer_ar' => 'نقدم خطط أسعار مرنة تناسب الشركات من جميع الأحجام: المبتدئ للفرق الصغيرة، والاحترافي للشركات النامية، والمؤسسي للمنظمات الكبيرة. تأتي جميع الخطط مع تجربة مجانية لمدة 14 يوماً.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Pricing & Billing',
                'question_en' => 'Can I upgrade or downgrade my plan?',
                'question_ar' => 'هل يمكنني ترقية أو تخفيض خطتي؟',
                'answer_en' => 'Yes! You can upgrade or downgrade your plan at any time. When upgrading, you will have immediate access to new features. When downgrading, the change will take effect at the start of your next billing cycle.',
                'answer_ar' => 'نعم! يمكنك ترقية أو تخفيض خطتك في أي وقت. عند الترقية، ستحصل على وصول فوري للميزات الجديدة. عند التخفيض، سيسري التغيير في بداية دورة الفوترة التالية.',
                'sort_order' => 2,
            ],
            [
                'category' => 'Pricing & Billing',
                'question_en' => 'What payment methods do you accept?',
                'question_ar' => 'ما طرق الدفع التي تقبلونها؟',
                'answer_en' => 'We accept all major credit cards (Visa, Mastercard, American Express), PayPal, and bank transfers for enterprise customers. All payments are processed securely.',
                'answer_ar' => 'نقبل جميع بطاقات الائتمان الرئيسية (Visa، Mastercard، American Express)، وPayPal، والتحويلات البنكية لعملاء المؤسسات. تتم معالجة جميع المدفوعات بشكل آمن.',
                'sort_order' => 3,
            ],
            // Features & Capabilities
            [
                'category' => 'Features & Capabilities',
                'question_en' => 'Which advertising platforms does CMIS support?',
                'question_ar' => 'ما المنصات الإعلانية التي يدعمها CMIS؟',
                'answer_en' => 'CMIS integrates with all major advertising platforms: Meta (Facebook, Instagram), Google Ads, TikTok, LinkedIn, Twitter (X), and Snapchat. We are constantly adding support for new platforms.',
                'answer_ar' => 'يتكامل CMIS مع جميع منصات الإعلان الرئيسية: Meta (Facebook، Instagram)، وإعلانات Google، وTikTok، وLinkedIn، وTwitter (X)، وSnapchat. نحن نضيف باستمرار دعماً لمنصات جديدة.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Features & Capabilities',
                'question_en' => 'How does the AI optimization work?',
                'question_ar' => 'كيف يعمل التحسين بالذكاء الاصطناعي؟',
                'answer_en' => 'Our AI analyzes your campaign performance data in real-time, identifies patterns and opportunities, and provides actionable recommendations. It can automatically adjust budgets, bids, and targeting to optimize for your goals.',
                'answer_ar' => 'يحلل ذكاؤنا الاصطناعي بيانات أداء حملتك في الوقت الفعلي، ويحدد الأنماط والفرص، ويقدم توصيات قابلة للتنفيذ. يمكنه تعديل الميزانيات والعروض والاستهداف تلقائياً للتحسين وفقاً لأهدافك.',
                'sort_order' => 2,
            ],
            // Platform Integrations
            [
                'category' => 'Platform Integrations',
                'question_en' => 'How do I connect my ad accounts?',
                'question_ar' => 'كيف أربط حساباتي الإعلانية؟',
                'answer_en' => 'Go to Settings > Integrations and click "Connect" next to the platform you want to add. You will be redirected to authorize CMIS access to your advertising account. The process takes just a few clicks.',
                'answer_ar' => 'انتقل إلى الإعدادات > التكاملات واضغط على "ربط" بجوار المنصة التي تريد إضافتها. ستتم إعادة توجيهك لتفويض CMIS بالوصول إلى حسابك الإعلاني. تستغرق العملية بضع نقرات فقط.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Platform Integrations',
                'question_en' => 'Is my ad account data safe?',
                'question_ar' => 'هل بيانات حسابي الإعلاني آمنة؟',
                'answer_en' => 'Absolutely. We use bank-level encryption (AES-256) to protect your data. We are OAuth certified and never store your platform passwords. You can revoke access at any time from your platform settings.',
                'answer_ar' => 'بالتأكيد. نستخدم تشفيراً بمستوى البنوك (AES-256) لحماية بياناتك. نحن معتمدون بـ OAuth ولا نخزن كلمات مرور منصاتك أبداً. يمكنك إلغاء الوصول في أي وقت من إعدادات منصتك.',
                'sort_order' => 2,
            ],
            // Security & Privacy
            [
                'category' => 'Security & Privacy',
                'question_en' => 'Is CMIS GDPR compliant?',
                'question_ar' => 'هل CMIS متوافق مع GDPR؟',
                'answer_en' => 'Yes, CMIS is fully GDPR compliant. We have implemented comprehensive data protection measures, including data encryption, access controls, and user consent management. You can request data export or deletion at any time.',
                'answer_ar' => 'نعم، CMIS متوافق تماماً مع GDPR. لقد قمنا بتنفيذ تدابير شاملة لحماية البيانات، بما في ذلك تشفير البيانات وضوابط الوصول وإدارة موافقة المستخدم. يمكنك طلب تصدير البيانات أو حذفها في أي وقت.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Security & Privacy',
                'question_en' => 'Do you offer two-factor authentication?',
                'question_ar' => 'هل تقدمون المصادقة الثنائية؟',
                'answer_en' => 'Yes, we support two-factor authentication (2FA) using authenticator apps or SMS. We strongly recommend enabling 2FA for all accounts to add an extra layer of security.',
                'answer_ar' => 'نعم، ندعم المصادقة الثنائية (2FA) باستخدام تطبيقات المصادقة أو الرسائل القصيرة. نوصي بشدة بتمكين 2FA لجميع الحسابات لإضافة طبقة إضافية من الأمان.',
                'sort_order' => 2,
            ],
            // Technical Support
            [
                'category' => 'Technical Support',
                'question_en' => 'What support options are available?',
                'question_ar' => 'ما خيارات الدعم المتاحة؟',
                'answer_en' => 'We offer multiple support channels: live chat during business hours, email support 24/7, comprehensive documentation, video tutorials, and priority phone support for enterprise customers.',
                'answer_ar' => 'نقدم قنوات دعم متعددة: دردشة مباشرة خلال ساعات العمل، ودعم البريد الإلكتروني على مدار الساعة، ووثائق شاملة، ودروس فيديو، ودعم هاتفي ذو أولوية لعملاء المؤسسات.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Technical Support',
                'question_en' => 'Do you offer onboarding assistance?',
                'question_ar' => 'هل تقدمون مساعدة في الإعداد؟',
                'answer_en' => 'Yes! All plans include access to our self-service onboarding wizard and documentation. Professional and Enterprise plans include dedicated onboarding sessions with our customer success team.',
                'answer_ar' => 'نعم! تتضمن جميع الخطط الوصول إلى معالج الإعداد الذاتي والوثائق. تتضمن الخطط الاحترافية والمؤسسية جلسات إعداد مخصصة مع فريق نجاح العملاء لدينا.',
                'sort_order' => 2,
            ],
        ];

        foreach ($faqs as $faq) {
            $categoryId = $categories[$faq['category']] ?? null;
            if (!$categoryId) continue;

            DB::table('cmis_website.faq_items')->insert([
                'id' => Str::uuid(),
                'category_id' => $categoryId,
                'question_en' => $faq['question_en'],
                'question_ar' => $faq['question_ar'],
                'answer_en' => $faq['answer_en'],
                'answer_ar' => $faq['answer_ar'],
                'sort_order' => $faq['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
