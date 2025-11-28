<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Language Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | Arabic translations for email templates
    |
    */

    // Alert Notification Email
    'alert_notification' => [
        'subject' => 'تنبيه CMIS: :name',
        'alert' => 'تنبيه',
        'severity' => [
            'critical' => 'حرج',
            'high' => 'عالي',
            'medium' => 'متوسط',
            'low' => 'منخفض',
        ],
        'triggered_at' => 'تم التفعيل في',
        'actual_value' => 'القيمة الفعلية',
        'threshold' => 'الحد المسموح',
        'alert_rule' => 'قاعدة التنبيه',
        'metric' => 'المقياس',
        'entity_type' => 'نوع الكيان',
        'entity_id' => 'معرف الكيان',
        'condition' => 'الشرط',
        'severity_label' => 'الخطورة',
        'status' => 'الحالة',
        'view_in_dashboard' => 'عرض في لوحة التحكم',
        'recommended_actions' => 'الإجراءات الموصى بها',
        'immediate_action' => 'مطلوب إجراء فوري - راجع أداء الكيان',
        'check_issues' => 'تحقق من أي مشاكل أو حالات شاذة في النظام',
        'consider_pausing' => 'فكر في إيقاف الحملات المتأثرة مؤقتاً إذا لزم الأمر',
        'review_24h' => 'راجع مقاييس الكيان خلال الـ 24 ساعة القادمة',
        'analyze_changes' => 'حلل التغييرات الأخيرة التي قد تكون سببت هذا التنبيه',
        'prepare_plan' => 'أعد خطة إجراءات تصحيحية',
        'review_scheduled' => 'راجع خلال الفحص المجدول التالي',
        'monitor_trend' => 'راقب استمرار الاتجاه',
        'document_findings' => 'وثق النتائج للرجوع إليها مستقبلاً',
        'automated_alert' => 'هذا تنبيه تلقائي من CMIS Analytics.',
        'manage_alert_settings' => 'إدارة إعدادات التنبيهات',
        'view_all_alerts' => 'عرض جميع التنبيهات',
        'copyright' => 'CMIS - نظام المعلومات التسويقية الإدراكية',
    ],

    // One-Time Report Email
    'one_time_report' => [
        'subject' => 'تقرير CMIS Analytics',
        'title' => 'تقرير التحليلات',
        'report_type' => 'تقرير :type',
        'hello' => 'مرحباً،',
        'ready_message' => 'تم إنشاء تقرير التحليلات المطلوب وهو جاهز للتحميل.',
        'report_type_label' => 'نوع التقرير',
        'generated' => 'تم الإنشاء',
        'expires' => 'ينتهي في',
        'click_to_download' => 'انقر على الزر أدناه لتحميل تقريرك:',
        'download_button' => 'تحميل التقرير',
        'expires_note' => 'سينتهي رابط التحميل هذا خلال 7 أيام لأسباب أمنية.',
        'note' => 'ملاحظة:',
        'additional_reports' => 'إذا كنت بحاجة إلى إنشاء تقارير إضافية أو جدولة التسليم التلقائي، يرجى زيارة لوحة تحكم التحليلات.',
        'automated_message' => 'تم إنشاء هذا التقرير بناءً على طلبك من CMIS Analytics.',
        'analytics_dashboard' => 'لوحة تحكم التحليلات',
        'help_center' => 'مركز المساعدة',
        'copyright' => 'CMIS - نظام المعلومات التسويقية الإدراكية',
    ],

    // Scheduled Report Email
    'scheduled_report' => [
        'subject' => ':name - تقرير CMIS Analytics',
        'frequency' => [
            'daily' => 'يومي',
            'weekly' => 'أسبوعي',
            'monthly' => 'شهري',
        ],
        'report' => 'تقرير',
        'hello' => 'مرحباً،',
        'ready_message' => 'تم إنشاء تقرير التحليلات المجدول وهو جاهز للمراجعة.',
        'report_name' => 'اسم التقرير',
        'report_type' => 'نوع التقرير',
        'frequency_label' => 'التكرار',
        'generated' => 'تم الإنشاء',
        'expires' => 'ينتهي في',
        'can_download' => 'يمكنك تحميل تقريرك باستخدام الزر أدناه:',
        'download_button' => 'تحميل التقرير',
        'auto_generated' => 'تم إنشاء هذا التقرير تلقائياً بناءً على إعدادات الجدولة الخاصة بك. إذا كنت بحاجة إلى تعديل الجدولة أو إعدادات التقرير، يرجى زيارة لوحة تحكم التحليلات.',
        'automated_message' => 'هذه رسالة تلقائية من CMIS Analytics.',
        'manage_schedules' => 'إدارة جداول التقارير',
        'analytics_dashboard' => 'لوحة تحكم التحليلات',
        'copyright' => 'CMIS - نظام المعلومات التسويقية الإدراكية',
    ],

];
