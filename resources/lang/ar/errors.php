<?php

return [
    // Error Page Titles
    '403_title' => 'محظور',
    '404_title' => 'الصفحة غير موجودة',
    '500_title' => 'خطأ في الخادم',
    '503_title' => 'الخدمة غير متاحة',

    // Error Messages
    '403_message' => 'ليس لديك إذن للوصول إلى هذه الصفحة.',
    '404_message' => 'الصفحة التي تبحث عنها غير موجودة.',
    '500_message' => 'حدث خطأ من جانبنا. يرجى المحاولة مرة أخرى لاحقاً.',
    '503_message' => 'الخدمة غير متاحة مؤقتاً. يرجى المحاولة مرة أخرى لاحقاً.',

    // Actions
    'go_home' => 'الذهاب إلى الصفحة الرئيسية',
    'go_back' => 'العودة',
    'contact_support' => 'اتصل بالدعم',
    'try_again' => 'حاول مرة أخرى',

    // General Errors
    'error_occurred' => 'حدث خطأ',
    'something_went_wrong' => 'حدث خطأ ما',
    'unexpected_error' => 'حدث خطأ غير متوقع',
    'please_try_again' => 'يرجى المحاولة مرة أخرى',

    // Validation Errors
    'validation_failed' => 'فشل التحقق',
    'required_field' => 'هذا الحقل مطلوب',
    'invalid_email' => 'يرجى إدخال عنوان بريد إلكتروني صالح',
    'invalid_format' => 'تنسيق غير صالح',
    'min_length' => 'الحد الأدنى للطول هو :min حرفاً',
    'max_length' => 'الحد الأقصى للطول هو :max حرفاً',

    // Authentication Errors
    'unauthorized' => 'وصول غير مصرح به',
    'unauthenticated' => 'يجب تسجيل الدخول للوصول إلى هذه الصفحة',
    'forbidden' => 'ليس لديك إذن لتنفيذ هذا الإجراء',
    'invalid_credentials' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
    'account_suspended' => 'تم تعليق حسابك',
    'session_expired' => 'انتهت صلاحية جلستك. يرجى تسجيل الدخول مرة أخرى.',

    // Database Errors
    'database_error' => 'حدث خطأ في قاعدة البيانات',
    'connection_failed' => 'تعذر الاتصال بقاعدة البيانات',
    'query_failed' => 'فشل تنفيذ الاستعلام',

    // API Errors
    'api_error' => 'حدث خطأ في واجهة برمجة التطبيقات',
    'rate_limit_exceeded' => 'طلبات كثيرة جداً. يرجى المحاولة مرة أخرى لاحقاً.',
    'invalid_request' => 'طلب غير صالح',
    'timeout' => 'انتهى وقت الطلب',

    // File Upload Errors
    'upload_failed' => 'فشل تحميل الملف',
    'file_too_large' => 'حجم الملف يتجاوز الحد الأقصى',
    'invalid_file_type' => 'نوع ملف غير صالح',
    'upload_error' => 'حدث خطأ أثناء تحميل الملف',

    // Network Errors
    'network_error' => 'حدث خطأ في الشبكة',
    'no_internet' => 'لا يوجد اتصال بالإنترنت',
    'connection_timeout' => 'انتهى وقت الاتصال',

    // Campaign Errors
    'campaign_not_found' => 'الحملة غير موجودة',
    'campaign_create_failed' => 'فشل إنشاء الحملة',
    'campaign_update_failed' => 'فشل تحديث الحملة',
    'campaign_delete_failed' => 'فشل حذف الحملة',

    // Platform Errors
    'platform_connection_failed' => 'فشل الاتصال بالمنصة',
    'platform_authentication_failed' => 'فشلت المصادقة على المنصة',
    'platform_api_error' => 'خطأ في واجهة برمجة تطبيقات المنصة',
];
