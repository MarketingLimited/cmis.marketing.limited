<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Products Language Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | Arabic translations for product management pages
    |
    */

    // Page Titles
    'products' => 'المنتجات',
    'product' => 'منتج',
    'product_catalog' => 'كتالوج المنتجات',
    'product_management' => 'إدارة المنتجات',
    'all_products' => 'جميع المنتجات',

    // Actions
    'add_product' => 'إضافة منتج',
    'create_product' => 'إنشاء منتج',
    'edit_product' => 'تعديل المنتج',
    'delete_product' => 'حذف المنتج',
    'duplicate_product' => 'نسخ المنتج',
    'import_products' => 'استيراد المنتجات',
    'export_products' => 'تصدير المنتجات',
    'bulk_edit' => 'تعديل مجمع',
    'bulk_delete' => 'حذف مجمع',

    // Product Details
    'product_name' => 'اسم المنتج',
    'product_description' => 'وصف المنتج',
    'product_sku' => 'رمز المنتج (SKU)',
    'product_price' => 'السعر',
    'product_category' => 'التصنيف',
    'product_brand' => 'العلامة التجارية',
    'product_status' => 'الحالة',
    'product_image' => 'صورة المنتج',
    'product_images' => 'صور المنتج',
    'product_url' => 'رابط المنتج',

    // Product Status
    'status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'out_of_stock' => 'نفذت الكمية',
        'discontinued' => 'متوقف',
        'draft' => 'مسودة',
    ],

    // Pricing
    'price' => 'السعر',
    'regular_price' => 'السعر العادي',
    'sale_price' => 'سعر الخصم',
    'discount' => 'الخصم',
    'discount_percentage' => 'نسبة الخصم',
    'cost_price' => 'سعر التكلفة',
    'profit_margin' => 'هامش الربح',
    'currency' => 'العملة',

    // Inventory
    'inventory' => 'المخزون',
    'stock_quantity' => 'كمية المخزون',
    'in_stock' => 'متوفر',
    'out_of_stock' => 'نفذت الكمية',
    'low_stock' => 'مخزون منخفض',
    'restock' => 'إعادة التخزين',
    'track_inventory' => 'تتبع المخزون',
    'sku' => 'رمز المنتج',
    'barcode' => 'الباركود',

    // Categories
    'category' => 'التصنيف',
    'categories' => 'التصنيفات',
    'select_category' => 'اختر التصنيف',
    'add_category' => 'إضافة تصنيف',
    'uncategorized' => 'غير مصنف',

    // Variants
    'variants' => 'الأشكال',
    'variant' => 'شكل',
    'add_variant' => 'إضافة شكل',
    'size' => 'الحجم',
    'color' => 'اللون',
    'material' => 'المادة',
    'variant_options' => 'خيارات الأشكال',

    // Media
    'images' => 'الصور',
    'add_image' => 'إضافة صورة',
    'upload_image' => 'رفع صورة',
    'image_gallery' => 'معرض الصور',
    'primary_image' => 'الصورة الرئيسية',
    'additional_images' => 'صور إضافية',

    // Product Information
    'details' => 'التفاصيل',
    'basic_information' => 'المعلومات الأساسية',
    'pricing_inventory' => 'السعر والمخزون',
    'images_media' => 'الصور والوسائط',
    'seo' => 'تحسين محركات البحث',
    'meta_title' => 'العنوان التعريفي',
    'meta_description' => 'الوصف التعريفي',
    'meta_keywords' => 'الكلمات المفتاحية',

    // Filters & Search
    'search_products' => 'بحث في المنتجات',
    'filter_by_category' => 'تصفية حسب التصنيف',
    'filter_by_status' => 'تصفية حسب الحالة',
    'filter_by_price' => 'تصفية حسب السعر',
    'sort_by' => 'ترتيب حسب',
    'sort_options' => [
        'name_asc' => 'الاسم (أ-ي)',
        'name_desc' => 'الاسم (ي-أ)',
        'price_low_high' => 'السعر (منخفض إلى مرتفع)',
        'price_high_low' => 'السعر (مرتفع إلى منخفض)',
        'newest' => 'الأحدث',
        'oldest' => 'الأقدم',
    ],

    // Product Feeds
    'product_feeds' => 'تغذية المنتجات',
    'facebook_catalog' => 'كتالوج فيسبوك',
    'google_shopping' => 'جوجل شوبينج',
    'sync_products' => 'مزامنة المنتجات',
    'last_sync' => 'آخر مزامنة',
    'sync_now' => 'مزامنة الآن',
    'sync_status' => 'حالة المزامنة',

    // Analytics
    'analytics' => 'التحليلات',
    'total_products' => 'إجمالي المنتجات',
    'active_products' => 'المنتجات النشطة',
    'out_of_stock_products' => 'المنتجات النافذة',
    'low_stock_products' => 'المنتجات ذات المخزون المنخفض',
    'total_value' => 'القيمة الإجمالية',
    'average_price' => 'متوسط السعر',
    'best_sellers' => 'الأكثر مبيعاً',
    'worst_sellers' => 'الأقل مبيعاً',

    // Messages
    'product_created' => 'تم إنشاء المنتج بنجاح',
    'product_updated' => 'تم تحديث المنتج بنجاح',
    'product_deleted' => 'تم حذف المنتج بنجاح',
    'products_imported' => 'تم استيراد المنتجات بنجاح',
    'products_exported' => 'تم تصدير المنتجات بنجاح',
    'products_synced' => 'تم مزامنة المنتجات بنجاح',

    // Errors
    'product_not_found' => 'المنتج غير موجود',
    'cannot_delete_product' => 'لا يمكن حذف المنتج',
    'sku_already_exists' => 'رمز المنتج موجود مسبقاً',
    'invalid_price' => 'سعر غير صالح',
    'image_upload_failed' => 'فشل رفع الصورة',

    // Empty States
    'no_products' => 'لا توجد منتجات',
    'no_products_found' => 'لم يتم العثور على منتجات',
    'create_first_product' => 'إنشاء أول منتج',

    // Help Text
    'help' => [
        'product_name' => 'أدخل اسماً وصفياً للمنتج',
        'sku' => 'رمز تعريف فريد للمنتج (اختياري)',
        'price' => 'السعر بالعملة المحلية',
        'stock' => 'الكمية المتوفرة في المخزون',
        'category' => 'اختر تصنيف المنتج لتنظيم أفضل',
    ],

];
