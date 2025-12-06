<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catalogs Language Lines (Arabic)
    |--------------------------------------------------------------------------
    | Translation keys for multi-platform catalog/product feed management
    */

    // General
    'catalogs' => 'الكتالوجات',
    'product_catalogs' => 'كتالوجات المنتجات',
    'manage_catalogs' => 'إدارة كتالوجات المنتجات عبر جميع منصات الإعلانات',
    'all_catalogs' => 'جميع الكتالوجات',
    'create_catalog' => 'إنشاء كتالوج',
    'import_catalog' => 'استيراد كتالوج',
    'sync_catalogs' => 'مزامنة الكتالوجات',

    // Stats
    'total_products' => 'إجمالي المنتجات',
    'active_products' => 'المنتجات النشطة',
    'synced_products' => 'المنتجات المتزامنة',
    'pending_sync' => 'في انتظار المزامنة',
    'sync_errors' => 'أخطاء المزامنة',

    // Platform Catalogs
    'meta_catalog' => 'كتالوج ميتا',
    'meta_catalog_desc' => 'كتالوج منتجات فيسبوك وإنستغرام',
    'google_merchant' => 'مركز جوجل التجاري',
    'google_merchant_desc' => 'موجز منتجات جوجل للتسوق',
    'tiktok_catalog' => 'كتالوج تيك توك',
    'tiktok_catalog_desc' => 'كتالوج منتجات متجر تيك توك',
    'snapchat_catalog' => 'كتالوج سناب شات',
    'snapchat_catalog_desc' => 'كتالوج منتجات سناب شات',
    'twitter_catalog' => 'كتالوج X/تويتر',
    'twitter_catalog_desc' => 'كتالوج منتجات X للتسوق',
    'linkedin_catalog' => 'منتجات لينكد إن',
    'linkedin_catalog_desc' => 'صفحات منتجات لينكد إن',

    // Empty State
    'no_catalogs' => 'لا توجد كتالوجات متصلة',
    'no_catalogs_description' => 'اربط كتالوجات منتجاتك لمزامنة المنتجات عبر جميع منصات الإعلانات.',
    'connect_first_catalog' => 'اربط أول كتالوج لك',
    'go_to_connections' => 'انتقل إلى اتصالات المنصات',

    // Catalog Details
    'catalog_name' => 'اسم الكتالوج',
    'catalog_id' => 'معرف الكتالوج',
    'platform' => 'المنصة',
    'products_count' => 'عدد المنتجات',
    'last_sync' => 'آخر مزامنة',
    'sync_status' => 'حالة المزامنة',
    'feed_url' => 'رابط الموجز',
    'feed_type' => 'نوع الموجز',

    // Feed Types
    'manual' => 'رفع يدوي',
    'scheduled' => 'موجز مجدول',
    'realtime' => 'API في الوقت الفعلي',
    'csv' => 'رفع CSV',
    'xml' => 'موجز XML',

    // Sync Status
    'synced' => 'متزامن',
    'syncing' => 'جاري المزامنة...',
    'sync_failed' => 'فشلت المزامنة',
    'never_synced' => 'لم تتم المزامنة',
    'pending' => 'قيد الانتظار',

    // Actions
    'view_catalog' => 'عرض الكتالوج',
    'edit_catalog' => 'تعديل الكتالوج',
    'sync_now' => 'مزامنة الآن',
    'view_products' => 'عرض المنتجات',
    'manage_feed' => 'إدارة الموجز',
    'delete_catalog' => 'حذف الكتالوج',

    // Product Fields
    'product_id' => 'معرف المنتج',
    'product_name' => 'اسم المنتج',
    'product_price' => 'السعر',
    'product_image' => 'الصورة',
    'product_category' => 'الفئة',
    'product_availability' => 'التوفر',
    'in_stock' => 'متوفر',
    'out_of_stock' => 'غير متوفر',

    // Import
    'import_title' => 'استيراد المنتجات',
    'import_description' => 'استيراد المنتجات من ملف أو ربط رابط موجز',
    'upload_file' => 'رفع ملف',
    'supported_formats' => 'الصيغ المدعومة: CSV، XML، JSON',
    'or_connect_feed' => 'أو اربط رابط موجز',
    'feed_url_placeholder' => 'https://yourstore.com/products/feed.xml',
    'validate_feed' => 'التحقق من الموجز',
    'import_now' => 'استيراد الآن',

    // Messages
    'catalog_created' => 'تم إنشاء الكتالوج بنجاح',
    'catalog_updated' => 'تم تحديث الكتالوج بنجاح',
    'catalog_deleted' => 'تم حذف الكتالوج بنجاح',
    'sync_started' => 'بدأت مزامنة الكتالوج',
    'sync_completed' => 'تمت مزامنة :count منتج إلى :platform',
    'sync_error' => 'فشلت مزامنة الكتالوج: :error',
    'sync_failed' => 'فشلت المزامنة إلى :platform: :error',
    'import_success' => 'تم استيراد :count منتج بنجاح',
    'confirm_delete' => 'هل أنت متأكد من حذف هذا الكتالوج؟',

];
