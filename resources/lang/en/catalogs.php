<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catalogs Language Lines (English)
    |--------------------------------------------------------------------------
    | Translation keys for multi-platform catalog/product feed management
    */

    // General
    'catalogs' => 'Catalogs',
    'product_catalogs' => 'Product Catalogs',
    'manage_catalogs' => 'Manage product catalogs across all ad platforms',
    'all_catalogs' => 'All Catalogs',
    'create_catalog' => 'Create Catalog',
    'import_catalog' => 'Import Catalog',
    'sync_catalogs' => 'Sync Catalogs',

    // Stats
    'total_products' => 'Total Products',
    'active_products' => 'Active Products',
    'synced_products' => 'Synced Products',
    'pending_sync' => 'Pending Sync',
    'sync_errors' => 'Sync Errors',

    // Platform Catalogs
    'meta_catalog' => 'Meta Catalog',
    'meta_catalog_desc' => 'Facebook & Instagram Product Catalog',
    'google_merchant' => 'Google Merchant Center',
    'google_merchant_desc' => 'Google Shopping Product Feed',
    'tiktok_catalog' => 'TikTok Catalog',
    'tiktok_catalog_desc' => 'TikTok Shop Product Catalog',
    'snapchat_catalog' => 'Snapchat Catalog',
    'snapchat_catalog_desc' => 'Snapchat Product Catalog',
    'twitter_catalog' => 'X/Twitter Catalog',
    'twitter_catalog_desc' => 'X Shopping Product Catalog',
    'linkedin_catalog' => 'LinkedIn Products',
    'linkedin_catalog_desc' => 'LinkedIn Product Pages',

    // Empty State
    'no_catalogs' => 'No Catalogs Connected',
    'no_catalogs_description' => 'Connect your product catalogs to sync products across all ad platforms.',
    'connect_first_catalog' => 'Connect Your First Catalog',
    'go_to_connections' => 'Go to Platform Connections',

    // Catalog Details
    'catalog_name' => 'Catalog Name',
    'catalog_id' => 'Catalog ID',
    'platform' => 'Platform',
    'products_count' => 'Products Count',
    'last_sync' => 'Last Sync',
    'sync_status' => 'Sync Status',
    'feed_url' => 'Feed URL',
    'feed_type' => 'Feed Type',

    // Feed Types
    'manual' => 'Manual Upload',
    'scheduled' => 'Scheduled Feed',
    'realtime' => 'Real-time API',
    'csv' => 'CSV Upload',
    'xml' => 'XML Feed',

    // Sync Status
    'synced' => 'Synced',
    'syncing' => 'Syncing...',
    'sync_failed' => 'Sync Failed',
    'never_synced' => 'Never Synced',
    'pending' => 'Pending',

    // Actions
    'view_catalog' => 'View Catalog',
    'edit_catalog' => 'Edit Catalog',
    'sync_now' => 'Sync Now',
    'view_products' => 'View Products',
    'manage_feed' => 'Manage Feed',
    'delete_catalog' => 'Delete Catalog',

    // Product Fields
    'product_id' => 'Product ID',
    'product_name' => 'Product Name',
    'product_price' => 'Price',
    'product_image' => 'Image',
    'product_category' => 'Category',
    'product_availability' => 'Availability',
    'in_stock' => 'In Stock',
    'out_of_stock' => 'Out of Stock',

    // Import
    'import_title' => 'Import Products',
    'import_description' => 'Import products from a file or connect a feed URL',
    'upload_file' => 'Upload File',
    'supported_formats' => 'Supported formats: CSV, XML, JSON',
    'or_connect_feed' => 'Or Connect Feed URL',
    'feed_url_placeholder' => 'https://yourstore.com/products/feed.xml',
    'validate_feed' => 'Validate Feed',
    'import_now' => 'Import Now',

    // Messages
    'catalog_created' => 'Catalog created successfully',
    'catalog_updated' => 'Catalog updated successfully',
    'catalog_deleted' => 'Catalog deleted successfully',
    'sync_started' => 'Catalog sync started',
    'sync_completed' => ':count products synced to :platform',
    'sync_error' => 'Catalog sync failed: :error',
    'sync_failed' => 'Sync to :platform failed: :error',
    'import_success' => ':count products imported successfully',
    'confirm_delete' => 'Are you sure you want to delete this catalog?',

];
