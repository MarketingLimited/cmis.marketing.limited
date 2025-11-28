<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Component Language Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | Arabic translations for reusable Blade components
    |
    */

    // Alert Component
    'alert' => [
        'close' => 'إغلاق',
    ],

    // Breadcrumb Component
    'breadcrumb' => [
        'home' => 'الرئيسية',
    ],

    // Delete Confirmation Modal
    'delete_modal' => [
        'title' => 'تأكيد الحذف',
        'confirm_message' => 'هل أنت متأكد أنك تريد حذف',
        'warning' => 'تحذير',
        'cascade_info' => 'سيتم حذف العناصر التالية أيضاً:',
        'can_restore' => 'يمكن استعادة :item المحذوف خلال 30 يوماً.',
        'delete_button' => 'حذف',
        'deleting' => 'جارٍ الحذف...',
        'cancel' => 'إلغاء',
        'success' => 'تم الحذف بنجاح',
        'failed' => 'فشل الحذف',
        'error' => 'حدث خطأ أثناء الحذف. الرجاء المحاولة مرة أخرى.',
    ],

    // File Upload Component
    'file_upload' => [
        'upload_file' => 'رفع ملف',
        'click_or_drag' => 'اضغط للرفع أو اسحب الملفات هنا',
        'max_size' => 'الحد الأقصى',
        'allowed_types' => 'الأنواع المسموحة',
        'add_more' => 'إضافة ملفات أخرى',
        'size_exceeded' => 'حجم الملف :filename يتجاوز الحد الأقصى المسموح (:maxsize)',
    ],

    // Organization Switcher
    'org_switcher' => [
        'current_org' => 'المنظمة الحالية',
        'loading' => 'جاري التحميل...',
        'choose_org' => 'اختر منظمة',
        'switch_between' => 'تبديل بين المنظمات الخاصة بك',
        'no_organizations' => 'لا توجد منظمات',
        'switching' => 'جاري التبديل...',
        'switch_failed' => 'فشل تبديل المنظمة. الرجاء المحاولة مرة أخرى.',
        'no_slug' => 'لا يوجد رمز',
    ],

    // Language Switcher
    'language' => [
        'arabic' => 'العربية',
        'english' => 'English',
        'arabic_sub' => 'Arabic',
        'english_sub' => 'الإنجليزية',
    ],

    // Pagination
    'pagination' => [
        'previous' => 'السابق',
        'next' => 'التالي',
        'showing' => 'عرض',
        'to' => 'إلى',
        'of' => 'من',
        'results' => 'نتيجة',
        'go_to_page' => 'انتقل إلى الصفحة :page',
    ],

    // Publish Modal
    'publish' => [
        'create_post' => 'إنشاء منشور جديد',
        'edit_post' => 'تعديل المنشور',
        'save_draft' => 'حفظ كمسودة',

        // Profile Groups
        'profile_groups' => 'مجموعات الحسابات',
        'select_all' => 'تحديد الكل',
        'clear' => 'مسح',

        // Profiles
        'profiles' => 'الحسابات',
        'selected' => 'محدد',
        'search_profiles' => 'ابحث في الحسابات...',
        'all_platforms' => 'الكل',
        'choose_groups_first' => 'اختر مجموعات الحسابات أولاً',
        'select_group_above' => 'حدد مجموعة أو أكثر من الأعلى',
        'select_all_profiles' => 'تحديد كل الحسابات',
        'connection_error' => 'خطأ في الاتصال',

        // Content
        'global_content' => 'المحتوى العام',
        'post_content' => 'محتوى المنشور',
        'what_to_share' => 'ماذا تريد أن تشارك؟',
        'emoji' => 'إيموجي',
        'hashtags' => 'هاشتاقات',
        'mention' => 'إشارة',
        'ai_assistant' => 'مساعد الذكاء الاصطناعي',

        // Media
        'media' => 'الوسائط',
        'drag_or_click' => 'اسحب الملفات هنا أو انقر للرفع',
        'media_formats' => 'صور: JPG, PNG, GIF | فيديو: MP4, MOV (بحد أقصى 100MB)',

        // Link
        'link_optional' => 'الرابط (اختياري)',
        'shorten' => 'اختصار',

        // Labels
        'labels' => 'التصنيفات',
        'add_label' => 'إضافة تصنيف...',

        // Platform-specific
        'customize_for' => 'تخصيص المحتوى لـ :platform',
        'leave_empty_global' => 'اتركه فارغاً لاستخدام المحتوى العام.',
        'custom_content_for' => 'محتوى مخصص لـ :platform...',

        // Instagram
        'post_type' => 'نوع المنشور',
        'feed_post' => 'منشور',
        'reel' => 'ريل',
        'story' => 'قصة',
        'first_comment' => 'التعليق الأول',
        'hashtags_as_comment' => 'أضف الهاشتاقات هنا كتعليق أول...',

        // Twitter
        'reply_settings' => 'إعدادات الرد',
        'everyone_reply' => 'الجميع يمكنهم الرد',
        'following_reply' => 'من تتابعهم فقط',
        'mentioned_reply' => 'المذكورون فقط',

        // Scheduling
        'schedule' => 'جدولة',
        'best_times' => 'أفضل الأوقات',
        'timezone_riyadh' => 'توقيت الرياض',
        'timezone_dubai' => 'توقيت دبي',
        'timezone_london' => 'لندن',
        'timezone_newyork' => 'نيويورك',

        // Preview
        'preview' => 'المعاينة',
        'account_name' => 'Account Name',
        'just_now' => 'Just now',
        'scheduled_at' => 'Scheduled: :date :time',
        'post_preview' => 'Your post content will appear here...',

        // Brand Safety
        'brand_safe' => 'المحتوى يتوافق مع معايير العلامة التجارية',
        'brand_issues' => 'تم اكتشاف مشاكل في المحتوى',

        // Approval
        'requires_approval' => 'يتطلب الموافقة قبل النشر',
        'submit_for_approval' => 'إرسال للموافقة',

        // Actions
        'cancel' => 'إلغاء',
        'publish_now' => 'نشر الآن',
        'schedule_post' => 'جدولة المنشور',

        // AI Assistant
        'ai_assistant_title' => 'مساعد الذكاء الاصطناعي',
        'brand_voice' => 'صوت العلامة التجارية',
        'default' => 'الافتراضي',
        'tone' => 'النبرة',
        'tone_professional' => 'احترافي',
        'tone_friendly' => 'ودود',
        'tone_casual' => 'عفوي',
        'tone_formal' => 'رسمي',
        'tone_humorous' => 'فكاهي',
        'tone_inspirational' => 'ملهم',
        'length' => 'الطول',
        'shorter' => 'أقصر',
        'same_length' => 'نفس الطول',
        'longer' => 'أطول',
        'custom_instructions' => 'تعليمات مخصصة',
        'custom_prompt_placeholder' => 'أضف أي تعليمات محددة...',
        'generate_content' => 'إنشاء المحتوى',
        'generating' => 'جاري الإنشاء...',
        'suggestions' => 'الاقتراحات',
    ],

    // Platform Selector
    'platform_selector' => [
        'all' => 'الكل',
        'facebook' => 'فيسبوك',
        'instagram' => 'إنستغرام',
        'twitter' => 'تويتر',
        'linkedin' => 'لينكد إن',
        'tiktok' => 'تيك توك',
        'snapchat' => 'سناب شات',
    ],

    // Stats Card
    'stats' => [
        'total' => 'الإجمالي',
        'change' => 'التغيير',
        'vs_previous' => 'مقارنة بالفترة السابقة',
    ],

    // Progress Bar
    'progress' => [
        'complete' => 'مكتمل',
        'in_progress' => 'قيد التنفيذ',
    ],

];
