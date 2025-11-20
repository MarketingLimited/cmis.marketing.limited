<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CMIS Caching Strategy
    |--------------------------------------------------------------------------
    |
    | Defines caching TTLs and strategies for different data types
    |
    */

    // Platform API response caching
    'platform_api' => [
        'meta' => [
            'account_data' => 900,      // 15 minutes
            'page_data' => 600,         // 10 minutes
            'post_data' => 300,         // 5 minutes
            'insights' => 1800,         // 30 minutes
        ],
        'google' => [
            'campaign_data' => 600,     // 10 minutes
            'ad_data' => 300,           // 5 minutes
            'performance' => 900,       // 15 minutes
        ],
        'tiktok' => [
            'account_data' => 900,      // 15 minutes
            'video_data' => 600,        // 10 minutes
            'analytics' => 1800,        // 30 minutes
        ],
    ],

    // Application data caching
    'application' => [
        'user_permissions' => 3600,     // 1 hour
        'organization_data' => 1800,    // 30 minutes
        'campaigns' => 300,             // 5 minutes
        'content_plans' => 600,         // 10 minutes
        'social_accounts' => 1800,      // 30 minutes
    ],

    // Analytics & reporting
    'analytics' => [
        'campaign_metrics' => 1800,     // 30 minutes
        'dashboard_stats' => 300,       // 5 minutes
        'reports' => 3600,              // 1 hour
    ],

    // AI & embeddings
    'ai' => [
        'embeddings' => 86400,          // 24 hours
        'ai_suggestions' => 3600,       // 1 hour
        'semantic_search' => 1800,      // 30 minutes
    ],

    // Tags for cache invalidation
    'tags' => [
        'campaigns' => 'campaigns',
        'content' => 'content',
        'users' => 'users',
        'platforms' => 'platforms',
        'analytics' => 'analytics',
    ],

    // Cache warming (pre-populate cache on deployment)
    'warming' => [
        'enabled' => env('CACHE_WARMING_ENABLED', false),
        'targets' => [
            'common_queries',
            'user_permissions',
            'platform_configs',
        ],
    ],
];
