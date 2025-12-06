<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BlogPostsSeeder extends Seeder
{
    public function run(): void
    {
        // Get category IDs
        $categories = DB::table('cmis_website.blog_categories')
            ->pluck('id', 'slug')
            ->toArray();

        $posts = [
            [
                'category' => 'marketing-tips',
                'title_en' => '10 Essential Marketing Automation Best Practices for 2024',
                'title_ar' => '10 ممارسات أساسية لأتمتة التسويق لعام 2024',
                'slug' => '10-marketing-automation-best-practices-2024',
                'excerpt_en' => 'Discover the top marketing automation strategies that will help you maximize efficiency and ROI in 2024.',
                'excerpt_ar' => 'اكتشف أفضل استراتيجيات أتمتة التسويق التي ستساعدك على تعظيم الكفاءة والعائد على الاستثمار في 2024.',
                'content_en' => $this->getPostContent1En(),
                'content_ar' => $this->getPostContent1Ar(),
                'featured_image_url' => '/images/blog/marketing-automation.jpg',
                'reading_time' => 8,
                'published_at' => Carbon::now()->subDays(5),
            ],
            [
                'category' => 'ai-automation',
                'title_en' => 'How AI is Revolutionizing Digital Advertising',
                'title_ar' => 'كيف يُحدث الذكاء الاصطناعي ثورة في الإعلان الرقمي',
                'slug' => 'ai-revolutionizing-digital-advertising',
                'excerpt_en' => 'Explore how artificial intelligence is transforming the way businesses approach digital advertising and campaign optimization.',
                'excerpt_ar' => 'استكشف كيف يغير الذكاء الاصطناعي طريقة تعامل الشركات مع الإعلان الرقمي وتحسين الحملات.',
                'content_en' => $this->getPostContent2En(),
                'content_ar' => $this->getPostContent2Ar(),
                'featured_image_url' => '/images/blog/ai-advertising.jpg',
                'reading_time' => 10,
                'published_at' => Carbon::now()->subDays(10),
            ],
            [
                'category' => 'social-media',
                'title_en' => 'Mastering Social Media Scheduling: A Complete Guide',
                'title_ar' => 'إتقان جدولة وسائل التواصل الاجتماعي: دليل شامل',
                'slug' => 'mastering-social-media-scheduling',
                'excerpt_en' => 'Learn how to create an effective social media scheduling strategy that maximizes engagement and saves time.',
                'excerpt_ar' => 'تعلم كيفية إنشاء استراتيجية جدولة فعالة لوسائل التواصل الاجتماعي تزيد التفاعل وتوفر الوقت.',
                'content_en' => $this->getPostContent3En(),
                'content_ar' => $this->getPostContent3Ar(),
                'featured_image_url' => '/images/blog/social-scheduling.jpg',
                'reading_time' => 7,
                'published_at' => Carbon::now()->subDays(15),
            ],
            [
                'category' => 'product-updates',
                'title_en' => 'Introducing CMIS 3.0: Unified Analytics Dashboard',
                'title_ar' => 'نقدم CMIS 3.0: لوحة تحليلات موحدة',
                'slug' => 'introducing-cmis-3-unified-analytics',
                'excerpt_en' => 'We are excited to announce our biggest update yet with powerful new features for cross-platform analytics.',
                'excerpt_ar' => 'يسعدنا الإعلان عن أكبر تحديث لدينا حتى الآن مع ميزات قوية جديدة للتحليلات عبر المنصات.',
                'content_en' => $this->getPostContent4En(),
                'content_ar' => $this->getPostContent4Ar(),
                'featured_image_url' => '/images/blog/cmis-update.jpg',
                'reading_time' => 5,
                'published_at' => Carbon::now()->subDays(3),
            ],
            [
                'category' => 'industry-insights',
                'title_en' => 'The State of Digital Advertising in MENA: 2024 Report',
                'title_ar' => 'حالة الإعلان الرقمي في الشرق الأوسط وشمال أفريقيا: تقرير 2024',
                'slug' => 'state-digital-advertising-mena-2024',
                'excerpt_en' => 'Our comprehensive analysis of digital advertising trends and opportunities in the Middle East and North Africa region.',
                'excerpt_ar' => 'تحليلنا الشامل لاتجاهات الإعلان الرقمي والفرص في منطقة الشرق الأوسط وشمال أفريقيا.',
                'content_en' => $this->getPostContent5En(),
                'content_ar' => $this->getPostContent5Ar(),
                'featured_image_url' => '/images/blog/mena-report.jpg',
                'reading_time' => 12,
                'published_at' => Carbon::now()->subDays(7),
            ],
        ];

        $sortOrder = 1;
        foreach ($posts as $post) {
            $categoryId = $categories[$post['category']] ?? null;
            if (!$categoryId) continue;

            DB::table('cmis_website.blog_posts')->insert([
                'id' => Str::uuid(),
                'category_id' => $categoryId,
                'title_en' => $post['title_en'],
                'title_ar' => $post['title_ar'],
                'slug' => $post['slug'],
                'excerpt_en' => $post['excerpt_en'],
                'excerpt_ar' => $post['excerpt_ar'],
                'content_en' => $post['content_en'],
                'content_ar' => $post['content_ar'],
                'featured_image_url' => $post['featured_image_url'],
                'reading_time_minutes' => $post['reading_time'],
                'published_at' => $post['published_at'],
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sortOrder++;
        }
    }

    private function getPostContent1En(): string
    {
        return <<<HTML
<p>Marketing automation has become essential for businesses of all sizes. Here are 10 best practices to maximize your success in 2024.</p>

<h2>1. Start with Clear Goals</h2>
<p>Before implementing any automation, define what you want to achieve. Whether it's lead generation, customer retention, or brand awareness, clear goals guide your strategy.</p>

<h2>2. Segment Your Audience</h2>
<p>One-size-fits-all messaging doesn't work. Use data to segment your audience and deliver personalized experiences.</p>

<h2>3. Map the Customer Journey</h2>
<p>Understanding your customer's journey helps you deliver the right message at the right time through the right channel.</p>

<h2>4. Leverage AI and Machine Learning</h2>
<p>Modern automation platforms use AI to optimize campaigns in real-time, predict outcomes, and suggest improvements.</p>

<h2>5. Test and Iterate</h2>
<p>Continuous testing is crucial. A/B test everything from subject lines to landing pages to improve performance.</p>

<h2>6. Integrate Your Tools</h2>
<p>Ensure your marketing automation platform integrates seamlessly with your CRM, analytics, and other tools.</p>

<h2>7. Focus on Quality Content</h2>
<p>Automation amplifies your content. Make sure what you're automating is valuable to your audience.</p>

<h2>8. Monitor and Measure</h2>
<p>Set up comprehensive tracking and regularly review your metrics to identify opportunities for improvement.</p>

<h2>9. Maintain Human Touch</h2>
<p>Automation should enhance, not replace, human interaction. Know when personal touch is needed.</p>

<h2>10. Stay Compliant</h2>
<p>Ensure your automation practices comply with GDPR, CCPA, and other relevant regulations.</p>
HTML;
    }

    private function getPostContent1Ar(): string
    {
        return <<<HTML
<p>أصبحت أتمتة التسويق ضرورية للشركات من جميع الأحجام. إليك 10 ممارسات أفضل لتعظيم نجاحك في 2024.</p>

<h2>1. ابدأ بأهداف واضحة</h2>
<p>قبل تنفيذ أي أتمتة، حدد ما تريد تحقيقه. سواء كان توليد العملاء المحتملين أو الاحتفاظ بالعملاء أو الوعي بالعلامة التجارية، الأهداف الواضحة توجه استراتيجيتك.</p>

<h2>2. قسّم جمهورك</h2>
<p>الرسائل العامة لا تعمل. استخدم البيانات لتقسيم جمهورك وتقديم تجارب مخصصة.</p>

<h2>3. ارسم رحلة العميل</h2>
<p>فهم رحلة عميلك يساعدك على تقديم الرسالة الصحيحة في الوقت الصحيح عبر القناة الصحيحة.</p>

<h2>4. استفد من الذكاء الاصطناعي والتعلم الآلي</h2>
<p>تستخدم منصات الأتمتة الحديثة الذكاء الاصطناعي لتحسين الحملات في الوقت الفعلي والتنبؤ بالنتائج واقتراح التحسينات.</p>

<h2>5. اختبر وكرر</h2>
<p>الاختبار المستمر حاسم. اختبر كل شيء من عناوين الموضوعات إلى صفحات الهبوط لتحسين الأداء.</p>

<h2>6. ادمج أدواتك</h2>
<p>تأكد من تكامل منصة أتمتة التسويق الخاصة بك بسلاسة مع CRM والتحليلات والأدوات الأخرى.</p>

<h2>7. ركز على المحتوى الجيد</h2>
<p>الأتمتة تضخم محتواك. تأكد من أن ما تقوم بأتمتته ذو قيمة لجمهورك.</p>

<h2>8. راقب وقس</h2>
<p>أعد تتبعاً شاملاً وراجع مقاييسك بانتظام لتحديد فرص التحسين.</p>

<h2>9. حافظ على اللمسة الإنسانية</h2>
<p>يجب أن تعزز الأتمتة التفاعل البشري لا أن تحل محله. اعرف متى تحتاج اللمسة الشخصية.</p>

<h2>10. التزم بالقوانين</h2>
<p>تأكد من أن ممارسات الأتمتة تتوافق مع GDPR وCCPA والقوانين الأخرى ذات الصلة.</p>
HTML;
    }

    private function getPostContent2En(): string
    {
        return <<<HTML
<p>Artificial intelligence is fundamentally changing digital advertising. Here's how AI is revolutionizing the industry.</p>

<h2>Predictive Analytics</h2>
<p>AI algorithms analyze vast amounts of data to predict which audiences are most likely to convert, allowing for more efficient budget allocation.</p>

<h2>Automated Optimization</h2>
<p>Machine learning models continuously optimize bids, targeting, and creative elements in real-time, far faster than any human could.</p>

<h2>Personalization at Scale</h2>
<p>AI enables truly personalized advertising experiences for millions of users simultaneously, improving relevance and engagement.</p>

<h2>Creative Intelligence</h2>
<p>AI tools can now generate and test ad creative variations, identifying winning combinations automatically.</p>

<h2>The Future is Here</h2>
<p>Businesses that embrace AI-powered advertising tools are seeing significant competitive advantages in performance and efficiency.</p>
HTML;
    }

    private function getPostContent2Ar(): string
    {
        return <<<HTML
<p>يغير الذكاء الاصطناعي الإعلان الرقمي بشكل جذري. إليك كيف يُحدث الذكاء الاصطناعي ثورة في الصناعة.</p>

<h2>التحليلات التنبؤية</h2>
<p>تحلل خوارزميات الذكاء الاصطناعي كميات هائلة من البيانات للتنبؤ بالجماهير الأكثر احتمالية للتحويل، مما يسمح بتخصيص ميزانية أكثر كفاءة.</p>

<h2>التحسين الآلي</h2>
<p>تحسن نماذج التعلم الآلي باستمرار العروض والاستهداف والعناصر الإبداعية في الوقت الفعلي، أسرع بكثير مما يمكن لأي إنسان.</p>

<h2>التخصيص على نطاق واسع</h2>
<p>يمكّن الذكاء الاصطناعي تجارب إعلانية مخصصة حقاً لملايين المستخدمين في وقت واحد، مما يحسن الصلة والتفاعل.</p>

<h2>الذكاء الإبداعي</h2>
<p>يمكن لأدوات الذكاء الاصطناعي الآن إنشاء واختبار تباينات إبداعية للإعلانات، وتحديد التركيبات الفائزة تلقائياً.</p>

<h2>المستقبل هنا</h2>
<p>الشركات التي تتبنى أدوات الإعلان المدعومة بالذكاء الاصطناعي تشهد مزايا تنافسية كبيرة في الأداء والكفاءة.</p>
HTML;
    }

    private function getPostContent3En(): string
    {
        return <<<HTML
<p>Effective social media scheduling is crucial for maintaining consistent engagement. Here's your complete guide.</p>

<h2>Understanding Your Audience</h2>
<p>Analyze when your audience is most active on each platform and schedule posts accordingly.</p>

<h2>Content Calendar Planning</h2>
<p>Create a content calendar that balances promotional content with valuable, engaging posts.</p>

<h2>Platform-Specific Strategies</h2>
<p>Each social platform has its own best practices. Tailor your scheduling strategy for each.</p>

<h2>Automation Tools</h2>
<p>Use scheduling tools to save time while maintaining a consistent posting schedule.</p>
HTML;
    }

    private function getPostContent3Ar(): string
    {
        return <<<HTML
<p>جدولة وسائل التواصل الاجتماعي الفعالة حاسمة للحفاظ على تفاعل مستمر. إليك دليلك الشامل.</p>

<h2>فهم جمهورك</h2>
<p>حلل متى يكون جمهورك أكثر نشاطاً على كل منصة وجدول المنشورات وفقاً لذلك.</p>

<h2>تخطيط تقويم المحتوى</h2>
<p>أنشئ تقويم محتوى يوازن بين المحتوى الترويجي والمنشورات القيمة والجذابة.</p>

<h2>استراتيجيات خاصة بالمنصة</h2>
<p>لكل منصة اجتماعية أفضل ممارساتها الخاصة. صمم استراتيجية الجدولة لكل منها.</p>

<h2>أدوات الأتمتة</h2>
<p>استخدم أدوات الجدولة لتوفير الوقت مع الحفاظ على جدول نشر متسق.</p>
HTML;
    }

    private function getPostContent4En(): string
    {
        return <<<HTML
<p>We're thrilled to announce CMIS 3.0, our most powerful update yet!</p>

<h2>What's New</h2>
<ul>
<li>Unified analytics dashboard with cross-platform insights</li>
<li>Enhanced AI recommendations engine</li>
<li>New automation workflows</li>
<li>Improved performance and speed</li>
</ul>

<h2>Getting Started</h2>
<p>Existing users will be automatically upgraded. New features are available immediately in your dashboard.</p>
HTML;
    }

    private function getPostContent4Ar(): string
    {
        return <<<HTML
<p>يسعدنا الإعلان عن CMIS 3.0، أقوى تحديث لدينا حتى الآن!</p>

<h2>ما الجديد</h2>
<ul>
<li>لوحة تحليلات موحدة مع رؤى عبر المنصات</li>
<li>محرك توصيات ذكاء اصطناعي محسّن</li>
<li>سير عمل أتمتة جديد</li>
<li>أداء وسرعة محسّنين</li>
</ul>

<h2>البدء</h2>
<p>سيتم ترقية المستخدمين الحاليين تلقائياً. الميزات الجديدة متاحة فوراً في لوحة التحكم الخاصة بك.</p>
HTML;
    }

    private function getPostContent5En(): string
    {
        return <<<HTML
<p>Our 2024 report provides comprehensive insights into digital advertising in the MENA region.</p>

<h2>Key Findings</h2>
<p>Digital ad spend in MENA grew by 18% year-over-year, with mobile accounting for 72% of all digital advertising.</p>

<h2>Platform Trends</h2>
<p>TikTok saw the fastest growth at 45%, while Meta platforms remain the largest by total spend.</p>

<h2>Opportunities</h2>
<p>Video content and AI-powered optimization present the biggest opportunities for growth in the region.</p>
HTML;
    }

    private function getPostContent5Ar(): string
    {
        return <<<HTML
<p>يقدم تقريرنا لعام 2024 رؤى شاملة حول الإعلان الرقمي في منطقة الشرق الأوسط وشمال أفريقيا.</p>

<h2>النتائج الرئيسية</h2>
<p>نما الإنفاق الإعلاني الرقمي في المنطقة بنسبة 18% على أساس سنوي، حيث يمثل الجوال 72% من إجمالي الإعلان الرقمي.</p>

<h2>اتجاهات المنصات</h2>
<p>شهد TikTok أسرع نمو بنسبة 45%، بينما تظل منصات Meta الأكبر من حيث إجمالي الإنفاق.</p>

<h2>الفرص</h2>
<p>يمثل محتوى الفيديو والتحسين المدعوم بالذكاء الاصطناعي أكبر الفرص للنمو في المنطقة.</p>
HTML;
    }
}
