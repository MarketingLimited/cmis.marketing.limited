<?php

/**
 * Platform Batch Optimization Configuration
 *
 * This configuration controls the Collect & Batch strategy
 * for reducing API calls across all ad platforms.
 *
 * Expected Impact:
 * - Meta: 90-95% reduction (Field Expansion + Batch API)
 * - Google: 70-80% reduction (SearchStream)
 * - TikTok: 50-70% reduction (Bulk endpoints)
 * - LinkedIn: 40-60% reduction (Batch decoration)
 * - Twitter: 40-60% reduction (Batch user lookup)
 * - Snapchat: 50-70% reduction (Org-level fetch)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Batch Optimization
    |--------------------------------------------------------------------------
    |
    | Master switch to enable or disable the batch optimization system.
    | When disabled, API calls are made immediately without queuing.
    |
    */
    'enabled' => env('PLATFORM_BATCH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Platform-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Each platform has its own batcher class and optimization settings.
    |
    */
    'platforms' => [
        'meta' => [
            'enabled' => env('PLATFORM_BATCH_META_ENABLED', true),
            'batcher' => \App\Services\Platform\Batchers\MetaBatcher::class,
            'max_batch_size' => 50,           // Meta Batch API limit
            'flush_interval' => 300,          // 5 minutes
            'priority_queue' => 'platform-batch',
            'rate_limit' => [
                'requests_per_hour' => 200,   // Graph API limit
                'burst_limit' => 50,
            ],
            'optimizations' => [
                'field_expansion' => true,    // Get related entities in one call
                'batch_api' => true,          // Combine up to 50 requests
                'edge_pagination' => true,    // Efficient pagination
            ],
        ],

        'google' => [
            'enabled' => env('PLATFORM_BATCH_GOOGLE_ENABLED', true),
            'batcher' => \App\Services\Platform\Batchers\GoogleBatcher::class,
            'max_batch_size' => 100,
            'flush_interval' => 600,          // 10 minutes
            'priority_queue' => 'platform-batch',
            'rate_limit' => [
                'requests_per_day' => 15000,  // Google Ads API limit
                'operations_per_request' => 10000,
            ],
            'optimizations' => [
                'search_stream' => true,      // Single streaming request for unlimited data
                'gaql_batching' => true,      // Combine GAQL queries
                'customer_batching' => true,  // Cross-customer queries
            ],
        ],

        'tiktok' => [
            'enabled' => env('PLATFORM_BATCH_TIKTOK_ENABLED', true),
            'batcher' => \App\Services\Platform\Batchers\TikTokBatcher::class,
            'max_batch_size' => 100,
            'flush_interval' => 600,          // 10 minutes
            'priority_queue' => 'platform-batch',
            'rate_limit' => [
                'requests_per_minute' => 10,  // TikTok rate limit
                'requests_per_day' => 10000,
            ],
            'optimizations' => [
                'bulk_advertiser' => true,    // Up to 100 advertisers per request
                'bulk_events' => true,        // Up to 2000 events per request
                'batch_reports' => true,
            ],
        ],

        'linkedin' => [
            'enabled' => env('PLATFORM_BATCH_LINKEDIN_ENABLED', true),
            'batcher' => \App\Services\Platform\Batchers\LinkedInBatcher::class,
            'max_batch_size' => 50,           // Conservative due to strict limits
            'flush_interval' => 1800,         // 30 minutes (very conservative)
            'priority_queue' => 'platform-batch',
            'rate_limit' => [
                'requests_per_day' => 100,    // LinkedIn Marketing API is very strict
                'requests_per_minute' => 10,
            ],
            'optimizations' => [
                'batch_decoration' => true,   // Multiple resources per call
                'analytics_pivot' => true,    // Combined analytics
                'leads_batch' => true,
            ],
        ],

        'twitter' => [
            'enabled' => env('PLATFORM_BATCH_TWITTER_ENABLED', true),
            'batcher' => \App\Services\Platform\Batchers\TwitterBatcher::class,
            'max_batch_size' => 100,
            'flush_interval' => 300,          // 5 minutes
            'priority_queue' => 'platform-batch',
            'rate_limit' => [
                'requests_per_15_min' => 300, // Twitter rate window
                'tweet_lookup_per_request' => 100,
            ],
            'optimizations' => [
                'batch_user_lookup' => true,  // Up to 100 users per request
                'combined_stats' => true,     // Multiple entities in one call
                'async_analytics' => true,    // Async reports for large data
            ],
        ],

        'snapchat' => [
            'enabled' => env('PLATFORM_BATCH_SNAPCHAT_ENABLED', true),
            'batcher' => \App\Services\Platform\Batchers\SnapchatBatcher::class,
            'max_batch_size' => 200,
            'flush_interval' => 600,          // 10 minutes
            'priority_queue' => 'platform-batch',
            'rate_limit' => [
                'requests_per_minute' => 1000, // Snapchat is generous
                'bulk_limit' => 2000,
            ],
            'optimizations' => [
                'org_level_fetch' => true,    // Get everything at org level
                'includes' => true,           // Include related entities
                'combined_stats' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Event Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for storing and processing webhook events.
    |
    */
    'webhook' => [
        // Store raw payload for debugging
        'store_raw_payload' => env('WEBHOOK_STORE_RAW', true),

        // Maximum retry attempts for failed webhook processing
        'max_attempts' => 3,

        // Retry delays in seconds (exponential backoff)
        'retry_delays' => [60, 300, 900], // 1min, 5min, 15min

        // Days to retain webhook events before cleanup
        'retention_days' => env('WEBHOOK_RETENTION_DAYS', 30),

        // Hash algorithm for signature verification
        'signature_algorithm' => 'sha256',

        // Platforms that send webhooks
        'platforms' => [
            'meta' => [
                'enabled' => true,
                'verify_signature' => true,
                'event_types' => ['page', 'instagram', 'lead', 'ads_insights'],
            ],
            'google' => [
                'enabled' => true,
                'verify_signature' => true,
                'event_types' => ['campaign_status', 'budget_alert', 'conversion'],
            ],
            'tiktok' => [
                'enabled' => true,
                'verify_signature' => true,
                'event_types' => ['campaign', 'ad', 'pixel'],
            ],
            'linkedin' => [
                'enabled' => true,
                'verify_signature' => true,
                'event_types' => ['lead_gen', 'conversion'],
            ],
            'twitter' => [
                'enabled' => true,
                'verify_signature' => true,
                'event_types' => ['account_activity', 'ads_api'],
            ],
            'snapchat' => [
                'enabled' => true,
                'verify_signature' => true,
                'event_types' => ['campaign', 'pixel', 'conversion'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for batch processing jobs.
    |
    */
    'queue' => [
        // Queue connection to use
        'connection' => env('PLATFORM_BATCH_QUEUE_CONNECTION', 'database'),

        // Queue names for different job types
        'queues' => [
            'batch' => 'platform-batch',      // For FlushBatchRequestsJob
            'webhook' => 'webhooks',          // For ProcessWebhookEventsJob
            'priority' => 'high',             // For urgent operations
        ],

        // Job timeout in seconds
        'timeout' => 300,

        // Maximum number of retries
        'tries' => 3,

        // Backoff times in seconds
        'backoff' => [30, 60, 120],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deduplication Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for request deduplication to prevent duplicate API calls.
    |
    */
    'deduplication' => [
        // Enable request deduplication
        'enabled' => true,

        // Time window for considering requests as duplicates (in seconds)
        'window' => 300, // 5 minutes

        // Hash algorithm for request keys
        'algorithm' => 'xxh64', // Fast non-cryptographic hash

        // Include these fields in deduplication key
        'key_fields' => ['platform', 'request_type', 'connection_id', 'request_params'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Logging
    |--------------------------------------------------------------------------
    |
    | Settings for monitoring batch performance.
    |
    */
    'monitoring' => [
        // Log level for batch operations
        'log_level' => env('PLATFORM_BATCH_LOG_LEVEL', 'info'),

        // Log individual request results
        'log_requests' => env('PLATFORM_BATCH_LOG_REQUESTS', false),

        // Track execution metrics
        'track_metrics' => true,

        // Alert thresholds
        'alerts' => [
            'queue_size' => 1000,            // Alert if queue exceeds this
            'failure_rate' => 0.1,           // Alert if 10% failure rate
            'processing_time_ms' => 30000,   // Alert if batch takes > 30s
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    |
    | Priority configuration for request ordering.
    | Lower number = higher priority.
    |
    */
    'priorities' => [
        'critical' => 1,   // Immediate processing (token refresh, webhooks)
        'high' => 3,       // User-initiated actions
        'normal' => 5,     // Standard sync operations
        'low' => 7,        // Background refresh
        'bulk' => 9,       // Bulk imports, reports
    ],
];
