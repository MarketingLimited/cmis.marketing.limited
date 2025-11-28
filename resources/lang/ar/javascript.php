<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JavaScript Language Lines (Arabic)
    |--------------------------------------------------------------------------
    | Translation keys specifically for JavaScript components and Alpine.js
    */

    // Alert & Confirmation Messages
    'confirm_delete' => 'هل أنت متأكد من حذف هذا العنصر؟',
    'confirm_delete_scheduled_report' => 'هل أنت متأكد من حذف هذا التقرير المجدول؟',
    'confirm_delete_export_config' => 'حذف إعدادات التصدير؟',
    'confirm_delete_experiment' => 'حذف هذه التجربة؟ لا يمكن التراجع عن هذا الإجراء.',
    'confirm_start_experiment' => 'بدء هذه التجربة؟',
    'confirm_complete_experiment' => 'إكمال هذه التجربة؟ سيتم حساب النتائج وتحديد الفائز.',
    'confirm_accept_recommendation' => 'قبول هذه التوصية؟',
    'confirm_mark_false_positive' => 'وضع علامة على هذا الشذوذ كإيجابي خاطئ؟',
    'confirm_revoke_token' => 'إلغاء رمز API؟ لا يمكن التراجع عن هذا الإجراء.',
    'cannot_undo' => 'لا يمكن التراجع عن هذا الإجراء',

    // Success Messages
    'scheduled_report_created' => 'تم إنشاء التقرير المجدول بنجاح',
    'scheduled_report_deleted' => 'تم حذف التقرير المجدول',
    'schedule_updated' => 'تم تحديث الجدول بنجاح',
    'schedule_activated' => 'تم تفعيل الجدول',
    'schedule_deactivated' => 'تم إلغاء تفعيل الجدول',
    'experiment_created' => 'تم إنشاء التجربة',
    'experiment_started' => 'تم بدء التجربة',
    'experiment_paused' => 'تم إيقاف التجربة مؤقتاً',
    'experiment_resumed' => 'تم استئناف التجربة',
    'experiment_completed' => 'تم إكمال التجربة',
    'experiment_deleted' => 'تم حذف التجربة',
    'variant_added' => 'تمت إضافة المتغير',
    'export_config_created' => 'تم إنشاء إعدادات التصدير',
    'export_config_activated' => 'تم تفعيل الإعدادات',
    'export_config_deactivated' => 'تم إلغاء تفعيل الإعدادات',
    'export_config_deleted' => 'تم حذف الإعدادات',
    'export_queued' => 'تم إضافة التصدير إلى قائمة الانتظار',
    'export_completed' => 'تم إكمال التصدير',
    'api_token_created' => 'تم إنشاء رمز API',
    'token_revoked' => 'تم إلغاء الرمز',
    'copied_to_clipboard' => 'تم النسخ إلى الحافظة',
    'trend_analysis_completed' => 'تم إكمال تحليل الاتجاه',
    'forecasts_generated' => 'تم إنشاء :count توقعات بنجاح',
    'recommendations_generated' => 'تم إنشاء التوصيات بنجاح',
    'anomalies_detected' => 'تم اكتشاف الشذوذات بنجاح',

    // Error Messages
    'failed_to_load_schedules' => 'فشل تحميل التقارير المجدولة',
    'failed_to_load_templates' => 'فشل تحميل القوالب',
    'failed_to_create_schedule' => 'فشل إنشاء التقرير المجدول',
    'failed_to_update_schedule' => 'فشل تحديث الجدول',
    'failed_to_delete_schedule' => 'فشل حذف الجدول',
    'failed_to_load_history' => 'فشل تحميل سجل التنفيذ',
    'failed_to_load_experiments' => 'فشل تحميل التجارب',
    'failed_to_load_stats' => 'فشل تحميل الإحصائيات',
    'failed_to_create_experiment' => 'فشل إنشاء التجربة',
    'failed_to_load_experiment' => 'فشل تحميل التجربة',
    'failed_to_add_variant' => 'فشلت إضافة المتغير',
    'failed_to_start_experiment' => 'فشل بدء التجربة',
    'failed_to_pause_experiment' => 'فشل إيقاف التجربة',
    'failed_to_resume_experiment' => 'فشل استئناف التجربة',
    'failed_to_complete_experiment' => 'فشل إكمال التجربة',
    'failed_to_delete_experiment' => 'فشل حذف التجربة',
    'failed_to_load_results' => 'فشل تحميل النتائج',
    'failed_to_load_configs' => 'فشل تحميل الإعدادات',
    'failed_to_load_logs' => 'فشل تحميل السجلات',
    'failed_to_load_tokens' => 'فشل تحميل الرموز',
    'failed_to_create_config' => 'فشل إنشاء الإعدادات',
    'failed_to_update_config' => 'فشل تحديث الإعدادات',
    'failed_to_delete_config' => 'فشل حذف الإعدادات',
    'failed_to_execute_export' => 'فشل تنفيذ التصدير',
    'failed_to_create_token' => 'فشل إنشاء الرمز',
    'failed_to_revoke_token' => 'فشل إلغاء الرمز',
    'failed_to_copy' => 'فشل النسخ',
    'failed_to_load_forecasts' => 'فشل تحميل التوقعات',
    'failed_to_load_anomalies' => 'فشل تحميل الشذوذات',
    'failed_to_load_trends' => 'فشل تحميل الاتجاهات',
    'failed_to_load_recommendations' => 'فشل تحميل التوصيات',
    'no_significant_winner' => 'لم يتم العثور على فائز ذو دلالة إحصائية.',
    'experiment_winner_found' => 'الفائز: :name\nالتحسين: :improvement%',
    'failed_to_create' => 'فشل الإنشاء',
    'failed_to_update' => 'فشل التحديث',
    'failed_to_delete' => 'فشل الحذف',
    'failed_to_acknowledge' => 'فشل الإقرار',
    'failed_to_resolve' => 'فشل الحل',

    // Validation Messages
    'please_enter_valid_email' => 'الرجاء إدخال عنوان بريد إلكتروني صالح',
    'please_enter_report_name' => 'الرجاء إدخال اسم التقرير',
    'please_add_recipient' => 'الرجاء إضافة مستلم واحد على الأقل',

    // Prompt Messages
    'enter_acknowledgement_notes' => 'أدخل ملاحظات الإقرار (اختياري):',
    'enter_resolution_notes' => 'أدخل ملاحظات الحل:',
    'enter_rejection_reason' => 'أدخل سبب الرفض (اختياري):',
    'enter_implementation_notes' => 'أدخل ملاحظات التنفيذ (اختياري):',

    // Console/Debug Messages (keep for developers, not user-facing)
    'comparison_error' => 'خطأ في المقارنة:',
    'alerts_load_error' => 'خطأ في تحميل التنبيهات:',
    'acknowledge_error' => 'خطأ في الإقرار:',
    'resolve_error' => 'خطأ في الحل:',
    'kpi_load_error' => 'خطأ في تحميل مؤشرات الأداء:',
    'context_load_error' => 'خطأ في تحميل السياق:',
    'organizations_load_error' => 'خطأ في تحميل المنظمات:',
    'switch_organization_error' => 'خطأ في تبديل المنظمة:',
    'metrics_load_error' => 'خطأ في تحميل المقاييس:',
    'trends_load_error' => 'خطأ في تحميل الاتجاهات:',
    'top_performing_load_error' => 'خطأ في تحميل الأداء الأفضل:',
    'roi_load_error' => 'خطأ في تحميل العائد على الاستثمار:',
    'attribution_load_error' => 'خطأ في تحميل الإحالة:',
    'ltv_load_error' => 'خطأ في تحميل قيمة العميل:',
    'projection_load_error' => 'خطأ في تحميل التوقعات:',

    // General JavaScript UI
    'loading' => 'جاري التحميل...',
    'processing' => 'جاري المعالجة...',
    'saving' => 'جاري الحفظ...',
    'deleting' => 'جاري الحذف...',
    'error' => 'خطأ',
    'success' => 'نجاح',
    'warning' => 'تحذير',
    'info' => 'معلومة',

];
