<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UI Language Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | User interface text and labels
    |
    */

    // Dashboard
    'welcome' => 'مرحباً',
    'welcome_back' => 'مرحباً بعودتك',
    'overview' => 'نظرة عامة',
    'quick_actions' => 'إجراءات سريعة',
    'recent_activity' => 'النشاط الأخير',
    'statistics' => 'الإحصائيات',

    // Onboarding
    'onboarding' => [
        'welcome_title' => 'مرحباً في CMIS!',
        'welcome_message' => 'نظام ذكي لإدارة حملاتك التسويقية',
        'step_1_title' => 'ربط حساب ميتا',
        'step_1_desc' => 'اربط حسابك في فيسبوك وإنستغرام لبدء إدارة حملاتك',
        'step_2_title' => 'إنشاء أول حملة',
        'step_2_desc' => 'جرّب مساعدنا الذكي لإنشاء حملة إعلانية احترافية',
        'step_3_title' => 'استكشف الميزات',
        'step_3_desc' => 'تعرّف على لوحة التحكم والتقارير والأدوات المتقدمة',
        'skip_tour' => 'تخطي الجولة',
        'start_tour' => 'بدء الجولة',
        'next_step' => 'الخطوة التالية',
        'complete' => 'إنهاء',
    ],

    // Navigation
    'menu' => [
        'campaigns' => 'الحملات',
        'content' => 'المحتوى',
        'analytics' => 'التحليلات',
        'audience' => 'الجمهور',
        'platforms' => 'المنصات',
        'team' => 'الفريق',
        'billing' => 'الفواتير',
        'settings' => 'الإعدادات',
        'help' => 'المساعدة',
    ],

    // Platform Features
    'features' => [
        'paid_campaigns' => 'الحملات الممولة',
        'organic_posts' => 'المنشورات العضوية',
        'scheduling' => 'جدولة المحتوى',
        'analytics' => 'التحليلات',
        'ai_generation' => 'التوليد الذكي',
        'coming_soon' => 'قريباً في المرحلة :phase',
    ],

    // Quota Widgets
    'quota' => [
        'title' => 'استخدام الذكاء الاصطناعي',
        'daily_usage' => 'الاستخدام اليومي',
        'monthly_usage' => 'الاستخدام الشهري',
        'remaining' => 'متبقي',
        'used' => 'مستخدم',
        'limit' => 'الحد الأقصى',
        'upgrade_needed' => 'تحتاج لترقية خطتك',
        'view_details' => 'عرض التفاصيل',
        'gpt_quota' => 'حصة GPT',
        'embeddings_quota' => 'حصة Embeddings',
        'cost_this_month' => 'التكلفة هذا الشهر',
    ],

    // Campaign Wizard
    'wizard' => [
        'create_campaign' => 'إنشاء حملة جديدة',
        'step' => 'الخطوة',
        'of' => 'من',
        'basics' => 'المعلومات الأساسية',
        'targeting' => 'الاستهداف',
        'creative' => 'المحتوى الإبداعي',
        'review' => 'المراجعة',
        'use_ai' => 'استخدم الذكاء الاصطناعي',
        'ai_help' => 'دع الذكاء الاصطناعي يساعدك في كتابة إعلان مميز',
        'generate_suggestions' => 'توليد اقتراحات',
        'select_principle' => 'اختر مبدأ تسويقي',
        'customize' => 'تخصيص',
    ],

    // Templates
    'templates' => [
        'title' => 'قوالب الحملات',
        'use_template' => 'استخدام قالب',
        'brand_awareness' => 'الوعي بالعلامة التجارية',
        'brand_awareness_desc' => 'زد من وعي الجمهور بعلامتك التجارية',
        'lead_generation' => 'جمع العملاء المحتملين',
        'lead_generation_desc' => 'اجمع معلومات العملاء المهتمين',
        'product_launch' => 'إطلاق منتج',
        'product_launch_desc' => 'روّج لمنتج جديد بشكل فعال',
        'event_promotion' => 'الترويج لحدث',
        'event_promotion_desc' => 'زد من حضور فعاليتك',
    ],

    // Help & Tooltips
    'help' => [
        'need_help' => 'تحتاج مساعدة؟',
        'documentation' => 'الوثائق',
        'video_tutorials' => 'دروس الفيديو',
        'contact_support' => 'اتصل بالدعم',
        'keyboard_shortcuts' => 'اختصارات لوحة المفاتيح',
    ],

    // Notifications
    'notifications' => [
        'title' => 'الإشعارات',
        'mark_all_read' => 'تعليم الكل كمقروء',
        'no_notifications' => 'لا توجد إشعارات',
        'campaign_approved' => 'تمت الموافقة على حملتك',
        'budget_warning' => 'تحذير: اقتربت من نفاد ميزانيتك',
        'quota_warning' => 'تحذير: اقتربت من حد استخدام الذكاء الاصطناعي',
    ],

    // Empty States
    'empty' => [
        'no_campaigns' => 'لا توجد حملات بعد',
        'no_campaigns_desc' => 'ابدأ بإنشاء حملتك الأولى الآن',
        'create_first_campaign' => 'إنشاء أول حملة',
        'no_results' => 'لا توجد نتائج',
        'try_different_filters' => 'جرب فلاتر مختلفة',
    ],

    // Billing
    'billing' => [
        'current_plan' => 'الخطة الحالية',
        'upgrade' => 'ترقية',
        'downgrade' => 'تخفيض',
        'free_tier' => 'مجاني',
        'pro_tier' => 'احترافي',
        'enterprise_tier' => 'للشركات',
        'monthly' => 'شهري',
        'yearly' => 'سنوي',
        'save_20' => 'وفّر 20%',
    ],

];
