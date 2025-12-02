<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace Language Lines (Arabic)
    |--------------------------------------------------------------------------
    | Translation keys for the Apps Marketplace feature
    */

    // Page titles and headers
    'title' => 'سوق التطبيقات',
    'subtitle' => 'خصص تجربة CMIS الخاصة بك عن طريق تفعيل التطبيقات التي تحتاجها',

    // Search and filter
    'search_placeholder' => 'البحث عن التطبيقات...',
    'all_categories' => 'الكل',

    // App states
    'enable' => 'تفعيل',
    'disable' => 'تعطيل',
    'enabled' => 'مفعّل',
    'disabled' => 'معطّل',
    'premium' => 'مميز',
    'requires' => 'يتطلب',
    'core_feature' => 'ميزة أساسية',

    // Messages
    'app_enabled' => 'تم تفعيل :app بنجاح',
    'app_enabled_with_dependencies' => 'تم تفعيل :app. تم تفعيل أيضاً: :dependencies',
    'app_disabled' => 'تم تعطيل :app بنجاح',
    'app_not_found' => 'التطبيق غير موجود',
    'app_not_enabled' => 'هذه الميزة غير مفعلة لمؤسستك. قم بتفعيلها من سوق التطبيقات.',
    'cannot_modify_core_app' => 'لا يمكن تعديل التطبيقات الأساسية',
    'cannot_disable_has_dependents' => 'لا يمكن تعطيل هذا التطبيق. مطلوب من قبل: :apps',
    'premium_required' => 'يتطلب هذا التطبيق اشتراكاً مميزاً',

    // Premium info
    'premium_info_title' => 'افتح التطبيقات المميزة',
    'premium_info_description' => 'قم بالترقية إلى المميز للوصول إلى ميزات متقدمة مثل مساعد الذكاء الاصطناعي والتحليلات التنبؤية والمزيد.',
    'upgrade_now' => 'ترقية الآن',

    // Empty states
    'no_results_title' => 'لم يتم العثور على تطبيقات',
    'no_results_description' => 'حاول تعديل معايير البحث أو التصفية',

    // Categories
    'categories' => [
        'core' => 'أساسي',
        'core_description' => 'الميزات الأساسية المتاحة دائماً',
        'marketing' => 'التسويق',
        'marketing_description' => 'إدارة الحملات وأدوات الجمهور',
        'analytics' => 'التحليلات',
        'analytics_description' => 'تتبع الأداء والرؤى',
        'ai' => 'الذكاء الاصطناعي',
        'ai_description' => 'ميزات ذكية مدعومة بالذكاء الاصطناعي',
        'automation' => 'الأتمتة',
        'automation_description' => 'أتمتة سير العمل والتنبيهات',
        'system' => 'أدوات النظام',
        'system_description' => 'أدوات إدارة البيانات والإدارة',
    ],

    // App names and descriptions
    'apps' => [
        // Core Apps
        'dashboard' => [
            'name' => 'الرئيسية',
            'description' => 'مركزك الرئيسي لمراقبة جميع الأنشطة والمقاييس',
        ],
        'social_media' => [
            'name' => 'التواصل الاجتماعي',
            'description' => 'إنشاء وجدولة وإدارة منشورات وسائل التواصل الاجتماعي',
        ],
        'profile_groups' => [
            'name' => 'مجموعات الحسابات',
            'description' => 'نظم حساباتك الاجتماعية في مجموعات قابلة للإدارة',
        ],
        'inbox' => [
            'name' => 'صندوق الرسائل',
            'description' => 'صندوق وارد موحد لجميع رسائل وسائل التواصل الاجتماعي',
        ],
        'settings' => [
            'name' => 'الإعدادات',
            'description' => 'تكوين مؤسستك واتصالات المنصات',
        ],
        'marketplace' => [
            'name' => 'سوق التطبيقات',
            'description' => 'تصفح وإدارة التطبيقات المتاحة لمؤسستك',
        ],
        'historical_content' => [
            'name' => 'المحتوى التاريخي',
            'description' => 'عرض وتحليل منشوراتك السابقة على وسائل التواصل الاجتماعي وأدائها',
        ],

        // Marketing Apps
        'campaigns' => [
            'name' => 'الحملات',
            'description' => 'إنشاء وإدارة الحملات الإعلانية عبر المنصات',
        ],
        'audiences' => [
            'name' => 'الجماهير',
            'description' => 'بناء وإدارة الجماهير المستهدفة لحملاتك',
        ],
        'influencers' => [
            'name' => 'تسويق المؤثرين',
            'description' => 'اكتشاف وإدارة شراكات المؤثرين',
        ],
        'orchestration' => [
            'name' => 'تنسيق الحملات',
            'description' => 'تنسيق متقدم للحملات عبر القنوات',
        ],

        // Analytics Apps
        'analytics' => [
            'name' => 'التحليلات',
            'description' => 'تتبع مقاييس الأداء وتوليد الرؤى',
        ],
        'predictive' => [
            'name' => 'التحليلات التنبؤية',
            'description' => 'التنبؤ والتوقعات المدعومة بالذكاء الاصطناعي',
        ],
        'ab_testing' => [
            'name' => 'اختبار A/B',
            'description' => 'اختبار وتحسين حملاتك بالتجارب',
        ],
        'optimization' => [
            'name' => 'محرك التحسين',
            'description' => 'تحسين الحملات التلقائي والتوصيات',
        ],

        // AI Apps
        'ai_assistant' => [
            'name' => 'مساعد الذكاء الاصطناعي',
            'description' => 'مساعد ذكي لإنشاء المحتوى والرؤى',
        ],
        'knowledge_base' => [
            'name' => 'قاعدة المعرفة',
            'description' => 'إدارة المعرفة والبحث المدعوم بالذكاء الاصطناعي',
        ],
        'social_listening' => [
            'name' => 'الاستماع الاجتماعي',
            'description' => 'مراقبة ذكر العلامة التجارية والمشاعر عبر وسائل التواصل الاجتماعي',
        ],

        // Automation Apps
        'automation' => [
            'name' => 'الأتمتة',
            'description' => 'أتمتة المهام المتكررة وسير العمل',
        ],
        'workflows' => [
            'name' => 'سير العمل',
            'description' => 'إنشاء سير عمل مخصص للموافقة والمحتوى',
        ],
        'alerts' => [
            'name' => 'التنبيهات',
            'description' => 'إعداد إشعارات للأحداث المهمة',
        ],

        // System Apps
        'exports' => [
            'name' => 'تصدير البيانات',
            'description' => 'تصدير بياناتك بتنسيقات مختلفة',
        ],
        'dashboard_builder' => [
            'name' => 'منشئ اللوحات',
            'description' => 'إنشاء لوحات مخصصة بأدوات السحب والإفلات',
        ],
        'products' => [
            'name' => 'المنتجات',
            'description' => 'إدارة كتالوج المنتجات والمخزون',
        ],
        'creative_assets' => [
            'name' => 'الأصول الإبداعية',
            'description' => 'تخزين وإدارة مكتبة الوسائط الإبداعية',
        ],
    ],

];
