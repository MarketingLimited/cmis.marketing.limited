<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'terms',
                'title_en' => 'Terms of Service',
                'title_ar' => 'شروط الخدمة',
                'content_en' => $this->getTermsContentEn(),
                'content_ar' => $this->getTermsContentAr(),
                'template' => 'legal',
                'is_published' => true,
            ],
            [
                'slug' => 'privacy',
                'title_en' => 'Privacy Policy',
                'title_ar' => 'سياسة الخصوصية',
                'content_en' => $this->getPrivacyContentEn(),
                'content_ar' => $this->getPrivacyContentAr(),
                'template' => 'legal',
                'is_published' => true,
            ],
            [
                'slug' => 'cookies',
                'title_en' => 'Cookie Policy',
                'title_ar' => 'سياسة ملفات تعريف الارتباط',
                'content_en' => $this->getCookiesContentEn(),
                'content_ar' => $this->getCookiesContentAr(),
                'template' => 'legal',
                'is_published' => true,
            ],
        ];

        $sortOrder = 1;
        foreach ($pages as $page) {
            DB::table('cmis_website.pages')->insert([
                'id' => Str::uuid(),
                'slug' => $page['slug'],
                'title_en' => $page['title_en'],
                'title_ar' => $page['title_ar'],
                'content_en' => $page['content_en'],
                'content_ar' => $page['content_ar'],
                'template' => $page['template'],
                'is_published' => $page['is_published'],
                'sort_order' => $sortOrder++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function getTermsContentEn(): string
    {
        return <<<HTML
<h2>1. Acceptance of Terms</h2>
<p>By accessing and using CMIS (Cognitive Marketing Intelligence Suite), you accept and agree to be bound by the terms and provisions of this agreement.</p>

<h2>2. Description of Service</h2>
<p>CMIS provides AI-powered marketing automation tools including campaign management, analytics, social publishing, and platform integrations.</p>

<h2>3. User Accounts</h2>
<p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

<h2>4. Acceptable Use</h2>
<p>You agree to use the service only for lawful purposes and in accordance with these Terms. You agree not to use the service in any way that could damage, disable, or impair the service.</p>

<h2>5. Data Protection</h2>
<p>We take data protection seriously. Your data is encrypted and stored securely. We comply with GDPR and other applicable data protection regulations.</p>

<h2>6. Intellectual Property</h2>
<p>The service and its original content, features, and functionality are owned by CMIS and are protected by international copyright, trademark, and other intellectual property laws.</p>

<h2>7. Termination</h2>
<p>We may terminate or suspend your account and bar access to the service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever.</p>

<h2>8. Changes to Terms</h2>
<p>We reserve the right to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days notice prior to any new terms taking effect.</p>
HTML;
    }

    private function getTermsContentAr(): string
    {
        return <<<HTML
<h2>1. قبول الشروط</h2>
<p>من خلال الوصول إلى واستخدام CMIS (منصة ذكاء التسويق المعرفي)، فإنك توافق وتلتزم بشروط وأحكام هذه الاتفاقية.</p>

<h2>2. وصف الخدمة</h2>
<p>تقدم CMIS أدوات أتمتة التسويق المدعومة بالذكاء الاصطناعي بما في ذلك إدارة الحملات والتحليلات والنشر الاجتماعي وتكاملات المنصات.</p>

<h2>3. حسابات المستخدمين</h2>
<p>أنت مسؤول عن الحفاظ على سرية بيانات اعتماد حسابك وعن جميع الأنشطة التي تحدث تحت حسابك.</p>

<h2>4. الاستخدام المقبول</h2>
<p>أنت توافق على استخدام الخدمة فقط لأغراض قانونية ووفقاً لهذه الشروط. أنت توافق على عدم استخدام الخدمة بأي طريقة يمكن أن تلحق الضرر بالخدمة أو تعطلها.</p>

<h2>5. حماية البيانات</h2>
<p>نحن نأخذ حماية البيانات على محمل الجد. بياناتك مشفرة ومخزنة بشكل آمن. نحن نلتزم بـ GDPR وغيرها من لوائح حماية البيانات المعمول بها.</p>

<h2>6. الملكية الفكرية</h2>
<p>الخدمة ومحتواها الأصلي وميزاتها ووظائفها مملوكة لـ CMIS ومحمية بموجب قوانين حقوق الطبع والنشر والعلامات التجارية الدولية.</p>

<h2>7. الإنهاء</h2>
<p>يجوز لنا إنهاء أو تعليق حسابك ومنع الوصول إلى الخدمة فوراً، دون إشعار مسبق أو مسؤولية، وفقاً لتقديرنا الخاص، لأي سبب من الأسباب.</p>

<h2>8. التغييرات في الشروط</h2>
<p>نحتفظ بالحق في تعديل أو استبدال هذه الشروط في أي وقت. إذا كانت المراجعة جوهرية، فسنقدم إشعاراً قبل 30 يوماً على الأقل من سريان أي شروط جديدة.</p>
HTML;
    }

    private function getPrivacyContentEn(): string
    {
        return <<<HTML
<h2>1. Information We Collect</h2>
<p>We collect information you provide directly to us, such as when you create an account, use our services, or contact us for support.</p>

<h2>2. How We Use Your Information</h2>
<p>We use the information we collect to provide, maintain, and improve our services, process transactions, and communicate with you.</p>

<h2>3. Information Sharing</h2>
<p>We do not share your personal information with third parties except as described in this policy or with your consent.</p>

<h2>4. Data Security</h2>
<p>We use industry-standard security measures to protect your data, including encryption, secure servers, and access controls.</p>

<h2>5. Your Rights</h2>
<p>You have the right to access, correct, or delete your personal information. You can also opt out of marketing communications at any time.</p>

<h2>6. Cookies</h2>
<p>We use cookies and similar technologies to improve your experience and analyze usage patterns. See our Cookie Policy for details.</p>

<h2>7. International Transfers</h2>
<p>Your information may be transferred to and processed in countries other than your country of residence, subject to appropriate safeguards.</p>

<h2>8. Contact Us</h2>
<p>If you have questions about this Privacy Policy, please contact us at privacy@cmis.io.</p>
HTML;
    }

    private function getPrivacyContentAr(): string
    {
        return <<<HTML
<h2>1. المعلومات التي نجمعها</h2>
<p>نجمع المعلومات التي تقدمها لنا مباشرة، مثل عندما تنشئ حساباً أو تستخدم خدماتنا أو تتصل بنا للحصول على الدعم.</p>

<h2>2. كيف نستخدم معلوماتك</h2>
<p>نستخدم المعلومات التي نجمعها لتقديم خدماتنا وصيانتها وتحسينها ومعالجة المعاملات والتواصل معك.</p>

<h2>3. مشاركة المعلومات</h2>
<p>لا نشارك معلوماتك الشخصية مع أطراف ثالثة إلا كما هو موضح في هذه السياسة أو بموافقتك.</p>

<h2>4. أمن البيانات</h2>
<p>نستخدم تدابير أمنية قياسية في الصناعة لحماية بياناتك، بما في ذلك التشفير والخوادم الآمنة وضوابط الوصول.</p>

<h2>5. حقوقك</h2>
<p>لديك الحق في الوصول إلى معلوماتك الشخصية أو تصحيحها أو حذفها. يمكنك أيضاً إلغاء الاشتراك في الاتصالات التسويقية في أي وقت.</p>

<h2>6. ملفات تعريف الارتباط</h2>
<p>نستخدم ملفات تعريف الارتباط والتقنيات المماثلة لتحسين تجربتك وتحليل أنماط الاستخدام. راجع سياسة ملفات تعريف الارتباط للحصول على التفاصيل.</p>

<h2>7. النقل الدولي</h2>
<p>قد يتم نقل معلوماتك ومعالجتها في بلدان أخرى غير بلد إقامتك، مع مراعاة الضمانات المناسبة.</p>

<h2>8. اتصل بنا</h2>
<p>إذا كانت لديك أسئلة حول سياسة الخصوصية هذه، يرجى الاتصال بنا على privacy@cmis.io.</p>
HTML;
    }

    private function getCookiesContentEn(): string
    {
        return <<<HTML
<h2>1. What Are Cookies</h2>
<p>Cookies are small text files stored on your device when you visit a website. They help us provide a better user experience.</p>

<h2>2. Types of Cookies We Use</h2>
<p>We use essential cookies for site functionality, analytics cookies to understand usage, and marketing cookies for personalized advertising.</p>

<h2>3. Essential Cookies</h2>
<p>These cookies are necessary for the website to function properly. They enable basic functions like page navigation and access to secure areas.</p>

<h2>4. Analytics Cookies</h2>
<p>We use analytics cookies to understand how visitors interact with our website. This helps us improve our services and user experience.</p>

<h2>5. Marketing Cookies</h2>
<p>These cookies are used to deliver relevant advertisements and track advertising campaign performance.</p>

<h2>6. Managing Cookies</h2>
<p>You can control cookies through your browser settings. However, disabling certain cookies may affect your experience on our website.</p>

<h2>7. Updates to This Policy</h2>
<p>We may update this Cookie Policy from time to time. We will notify you of any significant changes.</p>
HTML;
    }

    private function getCookiesContentAr(): string
    {
        return <<<HTML
<h2>1. ما هي ملفات تعريف الارتباط</h2>
<p>ملفات تعريف الارتباط هي ملفات نصية صغيرة مخزنة على جهازك عند زيارة موقع ويب. تساعدنا في توفير تجربة مستخدم أفضل.</p>

<h2>2. أنواع ملفات تعريف الارتباط التي نستخدمها</h2>
<p>نستخدم ملفات تعريف الارتباط الأساسية لوظائف الموقع، وملفات تعريف الارتباط التحليلية لفهم الاستخدام، وملفات تعريف الارتباط التسويقية للإعلانات المخصصة.</p>

<h2>3. ملفات تعريف الارتباط الأساسية</h2>
<p>هذه الملفات ضرورية لعمل الموقع بشكل صحيح. تمكن الوظائف الأساسية مثل التنقل في الصفحات والوصول إلى المناطق الآمنة.</p>

<h2>4. ملفات تعريف الارتباط التحليلية</h2>
<p>نستخدم ملفات تعريف الارتباط التحليلية لفهم كيفية تفاعل الزوار مع موقعنا. يساعدنا هذا في تحسين خدماتنا وتجربة المستخدم.</p>

<h2>5. ملفات تعريف الارتباط التسويقية</h2>
<p>تُستخدم هذه الملفات لتقديم إعلانات ذات صلة وتتبع أداء الحملات الإعلانية.</p>

<h2>6. إدارة ملفات تعريف الارتباط</h2>
<p>يمكنك التحكم في ملفات تعريف الارتباط من خلال إعدادات متصفحك. ومع ذلك، قد يؤثر تعطيل بعض الملفات على تجربتك على موقعنا.</p>

<h2>7. تحديثات هذه السياسة</h2>
<p>قد نقوم بتحديث سياسة ملفات تعريف الارتباط هذه من وقت لآخر. سنخطرك بأي تغييرات جوهرية.</p>
HTML;
    }
}
