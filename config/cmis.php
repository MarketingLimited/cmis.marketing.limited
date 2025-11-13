<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMIS System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Content Marketing Intelligence System
    |
    */

    /**
     * System user ID for automated operations (Artisan commands, scheduled tasks)
     * This user must exist in cmis.users table with email: system@cmis.app
     */
    'system_user_id' => env('CMIS_SYSTEM_USER_ID', '00000000-0000-0000-0000-000000000000'),

    /**
     * Default organization ID for system operations
     */
    'default_org_id' => env('CMIS_DEFAULT_ORG_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queues' => [
        'social-sync' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'retry_after' => 300, // 5 minutes
            'timeout' => 300,
        ],
        'ads-sync' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'retry_after' => 600, // 10 minutes
            'timeout' => 600,
        ],
        'embeddings' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'retry_after' => 1800, // 30 minutes
            'timeout' => 1800,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media API Configuration
    |--------------------------------------------------------------------------
    */

    'social' => [
        'instagram' => [
            'api_version' => env('INSTAGRAM_API_VERSION', 'v18.0'),
            'rate_limit' => env('INSTAGRAM_RATE_LIMIT', 200), // Requests per hour
        ],
        'facebook' => [
            'api_version' => env('FACEBOOK_API_VERSION', 'v18.0'),
            'rate_limit' => env('FACEBOOK_RATE_LIMIT', 200),
        ],
        'meta_ads' => [
            'api_version' => env('META_ADS_API_VERSION', 'v18.0'),
            'rate_limit' => env('META_ADS_RATE_LIMIT', 200),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI & Embeddings Configuration
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'text-embedding-004'),
            'rate_limit' => env('GEMINI_RATE_LIMIT', 60), // Requests per minute
        ],
        'embeddings' => [
            'dimension' => env('EMBEDDING_DIMENSION', 768),
            'batch_size' => env('EMBEDDING_BATCH_SIZE', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'default_lookback_days' => env('SYNC_LOOKBACK_DAYS', 30),
        'max_posts_per_sync' => env('SYNC_MAX_POSTS', 100),
        'retry_failed_after_hours' => env('SYNC_RETRY_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Row Level Security
    |--------------------------------------------------------------------------
    */

    'rls' => [
        'enabled' => env('RLS_ENABLED', true),
        'enforce_in_console' => env('RLS_ENFORCE_CONSOLE', true),
    ],

];
