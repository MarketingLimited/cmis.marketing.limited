<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Onboarding Language Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | Arabic translations for user onboarding experience
    |
    */

    // General
    'welcome' => 'مرحباً',
    'welcome_title' => 'مرحباً بك في CMIS يا :name!',
    'welcome_message' => 'دعنا نساعدك في الإعداد في بضع خطوات فقط. لن يستغرق الأمر سوى 15 دقيقة تقريباً.',
    'your_progress' => 'تقدمك',
    'steps_completed' => 'تم إكمال :completed من :total خطوات',
    'steps_remaining' => ':remaining خطوات متبقية',
    'estimated_time' => 'الوقت المقدر',

    // Status
    'completed' => 'مكتمل',
    'in_progress' => 'قيد التنفيذ',
    'not_started' => 'لم يبدأ',

    // Actions
    'continue' => 'متابعة',
    'start' => 'بدء',
    'skip_step' => 'تخطي هذه الخطوة',
    'skip_for_now' => 'سأفعل ذلك لاحقاً',
    'complete_and_continue' => 'إكمال والمتابعة',
    'finish_onboarding' => 'إنهاء التأهيل',
    'back_to_overview' => 'العودة إلى النظرة العامة',
    'go_to_profile_settings' => 'الذهاب إلى إعدادات الملف الشخصي',
    'go_to_integrations' => 'الذهاب إلى التكاملات',
    'start_campaign_wizard' => 'بدء معالج الحملة',
    'go_to_team_management' => 'الذهاب إلى إدارة الفريق',
    'go_to_analytics' => 'الذهاب إلى لوحة التحليلات',

    // Steps
    'step_x' => 'الخطوة :number',
    'step_x_of_y' => 'الخطوة :current من :total',
    'tasks_to_complete' => 'المهام المطلوب إكمالها',
    'helpful_tips' => 'نصائح مفيدة',
    'complete_all_tasks' => 'يرجى إكمال جميع المهام قبل المتابعة',

    // Confirmation
    'confirm_skip' => 'هل أنت متأكد من تخطي هذه الخطوة؟',
    'confirm_dismiss' => 'هل أنت متأكد من إلغاء التأهيل؟ يمكنك دائماً إعادة تشغيله لاحقاً من الإعدادات.',

    // Step Details
    'profile_details' => 'أكمل ملفك الشخصي',
    'profile_setup_description' => 'أضف معلوماتك الشخصية وتفضيلاتك لتخصيص تجربتك.',
    'connect_platform' => 'ربط منصة الإعلانات',
    'platform_connection_description' => 'اربط حساب Meta (فيسبوك) الخاص بك لبدء إدارة الحملات.',
    'create_first_campaign' => 'إنشاء حملتك الأولى',
    'first_campaign_description' => 'استخدم معالجنا الموجه لإنشاء أول حملة تسويقية لك في دقائق.',
    'invite_team' => 'دعوة فريقك',
    'team_setup_description' => 'ادع أعضاء الفريق وعين الأدوار للتعاون بشكل فعال.',
    'explore_analytics' => 'استكشف التحليلات',
    'analytics_tour_description' => 'تعلم كيفية تتبع الأداء وقياس نجاح حملتك.',

    // Messages
    'step_completed' => 'تم إكمال الخطوة بنجاح!',
    'step_skipped' => 'تم تخطي الخطوة',
    'reset_complete' => 'تم إعادة تعيين تقدم التأهيل',
    'dismissed' => 'تم إلغاء التأهيل. يمكنك إعادة التشغيل من الإعدادات.',

    // Step Definitions (used in controller)
    'steps' => [
        'profile_setup' => [
            'title' => 'إكمال ملفك الشخصي',
            'description' => 'قم بإعداد حسابك بمعلوماتك الشخصية وتفضيلاتك',
            'tasks' => [
                'complete_profile' => 'أضف اسمك الكامل وصورة الملف الشخصي',
                'upload_logo' => 'ارفع شعار شركتك',
                'set_preferences' => 'اضبط تفضيلات اللغة والإشعارات',
            ],
        ],
        'platform_connection' => [
            'title' => 'ربط منصة الإعلانات',
            'description' => 'اربط حساب Meta (فيسبوك) الخاص بك لبدء إدارة الحملات',
            'tasks' => [
                'connect_meta' => 'اربط حساب Meta Business الخاص بك',
                'authorize_access' => 'امنح CMIS الوصول إلى حسابات إعلاناتك',
                'sync_accounts' => 'مزامنة حملاتك وبياناتك الحالية',
            ],
        ],
        'first_campaign' => [
            'title' => 'إنشاء أول حملة',
            'description' => 'أطلق أول حملة تسويقية لك باستخدام معالجنا الموجه',
            'tasks' => [
                'use_wizard' => 'أكمل معالج إنشاء الحملة',
                'set_budget' => 'اضبط ميزانية وجدول حملتك',
                'review_launch' => 'راجع وانشر حملتك',
            ],
        ],
        'team_setup' => [
            'title' => 'إعداد فريقك',
            'description' => 'ادع أعضاء الفريق واضبط الأدوار والأذونات',
            'tasks' => [
                'invite_members' => 'أرسل دعوات لأعضاء فريقك',
                'assign_roles' => 'عين الأدوار المناسبة (مدير، مشرف، مشاهد)',
                'configure_permissions' => 'اضبط الأذونات المخصصة إذا لزم الأمر',
            ],
        ],
        'analytics_tour' => [
            'title' => 'استكشف التحليلات',
            'description' => 'تعلم كيفية تتبع وقياس أداء التسويق الخاص بك',
            'tasks' => [
                'explore_dashboard' => 'جولة في لوحة التحليلات',
                'understand_metrics' => 'تعرف على مؤشرات الأداء الرئيسية',
                'setup_alerts' => 'اضبط تنبيهات الأداء والإشعارات',
            ],
        ],
    ],

];
