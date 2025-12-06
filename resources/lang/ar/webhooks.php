<?php

return [
    // Page titles
    'title' => 'إعداد الويب هوك',
    'subtitle' => 'إعداد الويب هوك لتلقي الإشعارات الفورية',
    'create_title' => 'إنشاء ويب هوك',
    'edit_title' => 'تعديل الويب هوك',
    'details_title' => 'تفاصيل الويب هوك',

    // Actions
    'create' => 'إنشاء ويب هوك',
    'edit' => 'تعديل',
    'delete' => 'حذف',
    'verify' => 'التحقق من النقطة النهائية',
    'test' => 'إرسال اختبار',
    'activate' => 'تفعيل',
    'deactivate' => 'إلغاء التفعيل',
    'regenerate_token' => 'إعادة إنشاء الرمز',
    'regenerate_secret' => 'إعادة إنشاء المفتاح السري',
    'view_logs' => 'عرض السجلات',
    'retry' => 'إعادة المحاولة',
    'copy' => 'نسخ',
    'copied' => 'تم النسخ!',

    // Form fields
    'name' => 'اسم الويب هوك',
    'name_placeholder' => 'مثال: ويب هوك نظام CRM',
    'name_help' => 'اسم سهل لتحديد هذا الويب هوك',
    'callback_url' => 'رابط الاستدعاء',
    'callback_url_placeholder' => 'https://your-server.com/webhook',
    'callback_url_help' => 'الرابط الذي سيتم إرسال الأحداث إليه',
    'verify_token' => 'رمز التحقق',
    'verify_token_help' => 'استخدم هذا الرمز للتحقق من طلبات الويب هوك من CMIS',
    'secret_key' => 'المفتاح السري',
    'secret_key_help' => 'استخدم هذا المفتاح للتحقق من توقيعات الويب هوك (HMAC-SHA256)',
    'platform' => 'تصفية المنصة',
    'platform_help' => 'تلقي الأحداث من منصة محددة فقط',
    'all_platforms' => 'جميع المنصات',
    'subscribed_events' => 'الأحداث المشترك بها',
    'subscribed_events_help' => 'اختر الأحداث التي تريد تلقيها',
    'all_events' => 'جميع الأحداث',
    'timeout' => 'مهلة الطلب',
    'timeout_help' => 'الوقت الأقصى لانتظار استجابة النقطة النهائية',
    'max_retries' => 'أقصى محاولات',
    'max_retries_help' => 'عدد محاولات إعادة المحاولة للتسليمات الفاشلة',
    'custom_headers' => 'ترويسات مخصصة',
    'custom_headers_help' => 'ترويسات إضافية لتضمينها مع طلبات الويب هوك',

    // Status
    'status' => 'الحالة',
    'active' => 'نشط',
    'inactive' => 'غير نشط',
    'verified' => 'تم التحقق',
    'unverified' => 'لم يتم التحقق',
    'pending' => 'قيد الانتظار',
    'success' => 'نجاح',
    'failed' => 'فشل',
    'retrying' => 'إعادة المحاولة',

    // Statistics
    'statistics' => 'الإحصائيات',
    'total_deliveries' => 'إجمالي التسليمات',
    'success_rate' => 'معدل النجاح',
    'last_triggered' => 'آخر تشغيل',
    'last_success' => 'آخر نجاح',
    'last_failure' => 'آخر فشل',
    'last_error' => 'آخر خطأ',
    'last_24h' => 'آخر 24 ساعة',
    'last_7d' => 'آخر 7 أيام',

    // Logs
    'delivery_logs' => 'سجلات التسليم',
    'event_type' => 'نوع الحدث',
    'response_status' => 'حالة الاستجابة',
    'response_time' => 'وقت الاستجابة',
    'attempt' => 'المحاولة',
    'timestamp' => 'الوقت',
    'payload' => 'البيانات',
    'response' => 'الاستجابة',
    'error_message' => 'رسالة الخطأ',
    'no_logs' => 'لا توجد سجلات تسليم بعد',

    // Event types
    'events' => [
        'message.received' => 'رسالة مستلمة',
        'message.sent' => 'رسالة مرسلة',
        'message.delivered' => 'رسالة تم تسليمها',
        'message.read' => 'رسالة مقروءة',
        'message.failed' => 'فشل الرسالة',
        'status.changed' => 'تغيير الحالة',
        'webhook.meta' => 'أحداث ميتا',
        'webhook.whatsapp' => 'أحداث واتساب',
        'webhook.tiktok' => 'أحداث تيك توك',
        'webhook.twitter' => 'أحداث تويتر/X',
        'webhook.linkedin' => 'أحداث لينكد إن',
        'webhook.snapchat' => 'أحداث سناب شات',
        'webhook.google' => 'أحداث إعلانات جوجل',
        'lead.created' => 'عميل محتمل جديد',
        'lead.updated' => 'تحديث عميل محتمل',
        'campaign.status_changed' => 'تغيير حالة الحملة',
        'campaign.budget_alert' => 'تنبيه ميزانية الحملة',
        'post.published' => 'تم نشر المنشور',
        'post.failed' => 'فشل نشر المنشور',
        'post.engagement' => 'تفاعل المنشور',
    ],

    // Messages
    'created' => 'تم إنشاء الويب هوك بنجاح. يرجى التحقق من النقطة النهائية.',
    'updated' => 'تم تحديث الويب هوك بنجاح.',
    'deleted' => 'تم حذف الويب هوك بنجاح.',
    'activated' => 'تم تفعيل الويب هوك بنجاح.',
    'deactivated' => 'تم إلغاء تفعيل الويب هوك بنجاح.',
    'token_regenerated' => 'تم إعادة إنشاء رمز التحقق. يرجى التحقق من النقطة النهائية مرة أخرى.',
    'secret_regenerated' => 'تم إعادة إنشاء المفتاح السري. قم بتحديث معالج الويب هوك.',
    'verified' => 'تم التحقق من النقطة النهائية بنجاح.',
    'verification_failed' => 'فشل التحقق من النقطة النهائية.',
    'test_sent' => 'تم إرسال اختبار الويب هوك بنجاح.',
    'test_failed' => 'فشل اختبار الويب هوك.',
    'retry_queued' => 'تم إضافة التسليم لإعادة المحاولة.',
    'logs_retrieved' => 'تم استرداد سجلات التسليم.',

    // Errors
    'must_verify_first' => 'يجب التحقق من النقطة النهائية قبل تفعيل الويب هوك.',
    'cannot_retry' => 'لا يمكن إعادة محاولة هذا التسليم.',
    'max_limit_reached' => 'لقد وصلت إلى الحد الأقصى من الويب هوك (10).',
    'invalid_url' => 'يرجى إدخال رابط صحيح.',
    'url_not_reachable' => 'لا يمكن الوصول إلى رابط الاستدعاء.',

    // Verification instructions
    'verification_instructions' => 'تعليمات التحقق',
    'verification_step1' => 'عند التحقق من النقطة النهائية، نرسل طلب GET إلى رابط الاستدعاء مع هذه المعلمات:',
    'verification_step2' => 'يجب على الخادم:',
    'verification_step2a' => 'التحقق من أن hub.verify_token يطابق رمز التحقق',
    'verification_step2b' => 'الاستجابة بقيمة hub.challenge كنص عادي',
    'verification_example' => 'مثال على الاستجابة',

    // Signature verification
    'signature_verification' => 'التحقق من التوقيع',
    'signature_instructions' => 'للتحقق من صحة الويب هوك، تحقق من ترويسة X-CMIS-Signature:',
    'signature_step1' => 'حلل ترويسة التوقيع للحصول على الطابع الزمني (t) والتوقيع (v1)',
    'signature_step2' => 'احسب التوقيع المتوقع: HMAC-SHA256(timestamp.payload, secret_key)',
    'signature_step3' => 'قارن التوقيعات باستخدام مقارنة ثابتة الوقت',
    'signature_step4' => 'ارفض الطلبات الأقدم من 5 دقائق (300 ثانية)',

    // Empty state
    'no_webhooks' => 'لا توجد ويب هوك مُعدة',
    'no_webhooks_description' => 'أنشئ ويب هوك لتلقي إشعارات الأحداث الفورية.',

    // Confirmation
    'confirm_delete' => 'هل أنت متأكد من حذف هذا الويب هوك؟',
    'confirm_regenerate_token' => 'إعادة إنشاء رمز التحقق ستتطلب إعادة التحقق. استمرار؟',
    'confirm_regenerate_secret' => 'إعادة إنشاء المفتاح السري ستتطلب تحديث معالج الويب هوك. استمرار؟',
];
