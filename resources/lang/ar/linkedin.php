<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LinkedIn Integration Language Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | سطور اللغة التالية تستخدم لتكامل منصة LinkedIn Ads
    | استخدم هذه الترجمات لأهداف الحملات، وأشكال الإعلانات،
    | والمواضع، والمصطلحات الأخرى الخاصة بـ LinkedIn.
    |
    */

    'objectives' => [
        'BRAND_AWARENESS' => 'الوعي بالعلامة التجارية',
        'WEBSITE_VISITS' => 'زيارات الموقع',
        'ENGAGEMENT' => 'التفاعل',
        'VIDEO_VIEWS' => 'مشاهدات الفيديو',
        'LEAD_GENERATION' => 'جذب العملاء المحتملين',
        'WEBSITE_CONVERSIONS' => 'تحويلات الموقع',
        'JOB_APPLICANTS' => 'المتقدمين للوظائف',
    ],

    'placements' => [
        'linkedin_feed' => 'آخر الأخبار في LinkedIn',
        'linkedin_right_rail' => 'الشريط الجانبي الأيمن في LinkedIn',
        'linkedin_messaging' => 'رسائل LinkedIn (InMail)',
    ],

    'ad_formats' => [
        'SPONSORED_STATUS_UPDATE' => 'المحتوى المدعوم (صورة واحدة)',
        'SPONSORED_VIDEO' => 'المحتوى المدعوم (فيديو)',
        'SPONSORED_CAROUSEL' => 'المحتوى المدعوم (كاروسيل)',
        'SPONSORED_INMAILS' => 'إعلانات الرسائل (InMail)',
        'TEXT_AD' => 'إعلانات نصية',
        'DYNAMIC_AD_FOLLOWER' => 'إعلانات ديناميكية (متابع)',
        'DYNAMIC_AD_SPOTLIGHT' => 'إعلانات ديناميكية (تسليط الضوء)',
    ],

    'cost_types' => [
        'CPC' => 'تكلفة النقرة',
        'CPM' => 'تكلفة الظهور',
        'CPS' => 'تكلفة الإرسال',
    ],

    'statuses' => [
        'ACTIVE' => 'نشط',
        'PAUSED' => 'متوقف مؤقتًا',
        'ARCHIVED' => 'مؤرشف',
        'DRAFT' => 'مسودة',
    ],

    'targeting' => [
        'locations' => 'المواقع',
        'company_sizes' => 'أحجام الشركات',
        'industries' => 'الصناعات',
        'job_titles' => 'المسميات الوظيفية',
        'job_functions' => 'الوظائف',
        'seniorities' => 'مستويات الأقدمية',
        'skills' => 'المهارات',
        'companies' => 'الشركات',
        'age_ranges' => 'الفئات العمرية',
        'genders' => 'الجنس',
        'matched_audiences' => 'الجماهير المطابقة',
    ],

    'seniority_levels' => [
        'entry' => 'مستوى مبتدئ',
        'mid' => 'مستوى متوسط',
        'senior' => 'مستوى أول',
        'manager' => 'مدير',
        'director' => 'مدير عام',
        'executive' => 'تنفيذي (المستوى C)',
        'owner' => 'مالك/شريك',
    ],

    'form_fields' => [
        'FIRST_NAME' => 'الاسم الأول',
        'LAST_NAME' => 'اسم العائلة',
        'EMAIL' => 'البريد الإلكتروني',
        'PHONE' => 'رقم الهاتف',
        'COMPANY' => 'اسم الشركة',
        'JOB_TITLE' => 'المسمى الوظيفي',
        'SENIORITY' => 'مستوى الأقدمية',
        'INDUSTRY' => 'الصناعة',
        'COUNTRY' => 'البلد',
        'STATE' => 'الولاية/المحافظة',
        'CITY' => 'المدينة',
    ],

    'webhooks' => [
        'lead_gen_form_response' => 'تقديم نموذج جذب العملاء المحتملين',
        'campaign_notification' => 'إشعار الحملة',
        'signature_invalid' => 'توقيع webhook غير صالح',
        'signature_missing' => 'توقيع webhook مفقود',
        'processing_failed' => 'فشلت معالجة webhook',
        'success' => 'تمت معالجة webhook بنجاح',
    ],

    'errors' => [
        'auth_failed' => 'فشلت المصادقة مع LinkedIn',
        'token_refresh_failed' => 'فشل تحديث رمز الوصول',
        'no_refresh_token' => 'لا يوجد رمز تحديث متاح',
        'api_error' => 'خطأ في API LinkedIn',
        'campaign_creation_failed' => 'فشل إنشاء الحملة',
        'form_creation_failed' => 'فشل إنشاء نموذج جذب العملاء المحتملين',
        'invalid_objective' => 'هدف الحملة غير صالح',
        'invalid_targeting' => 'معايير الاستهداف غير صالحة',
        'rate_limit_exceeded' => 'تم تجاوز حد معدل API LinkedIn',
    ],

    'success' => [
        'campaign_created' => 'تم إنشاء الحملة بنجاح',
        'campaign_updated' => 'تم تحديث الحملة بنجاح',
        'campaign_deleted' => 'تمت أرشفة الحملة بنجاح',
        'form_created' => 'تم إنشاء نموذج جذب العملاء المحتملين بنجاح',
        'token_refreshed' => 'تم تحديث رمز الوصول بنجاح',
        'lead_processed' => 'تمت معالجة العميل المحتمل بنجاح',
    ],
];
