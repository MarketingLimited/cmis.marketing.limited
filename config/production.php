<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Environment Optimizations
    |--------------------------------------------------------------------------
    |
    | These settings are optimized for production deployment
    |
    */

    'optimizations' => [
        // Enable route caching
        'route_cache' => true,

        // Enable config caching
        'config_cache' => true,

        // Enable view caching
        'view_cache' => true,

        // Enable OPcache
        'opcache' => [
            'enabled' => true,
            'validate_timestamps' => false, // Disable in production
            'revalidate_freq' => 0,
            'max_accelerated_files' => 10000,
        ],

        // Database connection pooling
        'database' => [
            'persistent' => true,
            'pool_size' => env('DB_POOL_SIZE', 10),
        ],

        // Queue optimization
        'queue' => [
            'after_commit' => true, // Wait for DB transaction commit
            'max_tries' => 3,
            'timeout' => 300, // 5 minutes
            'retry_after' => 90,
        ],
    ],

    // Asset optimization
    'assets' => [
        'minify' => true,
        'combine' => true,
        'cdn_url' => env('CDN_URL'),
        'version' => env('ASSET_VERSION', time()),
    ],

    // Session optimization
    'session' => [
        'driver' => 'redis',
        'lifetime' => 120,
        'encrypt' => true,
        'secure' => true, // HTTPS only
        'same_site' => 'strict',
    ],

    // Database query optimization
    'database_query' => [
        'lazy_loading' => false, // Prevent N+1
        'strict_mode' => true,
        'default_timeout' => 5000, // 5 seconds
    ],

    // Logging optimization
    'logging' => [
        'driver' => 'daily',
        'days' => 14,
        'level' => 'warning', // Only warnings and errors in production
        'bubble' => true,
        'permission' => 0644,
    ],

    // Security
    'security' => [
        'https_only' => true,
        'hsts' => true,
        'csrf_protection' => true,
        'sql_injection_prevention' => true,
        'xss_protection' => true,
    ],

    // Performance targets
    'performance_targets' => [
        'response_time_ms' => 200,
        'query_time_ms' => 50,
        'memory_limit_mb' => 256,
        'max_queries_per_request' => 30,
    ],

    // Monitoring thresholds
    'monitoring' => [
        'alert_on_slow_query' => 100, // ms
        'alert_on_high_memory' => 200, // MB
        'alert_on_error_rate' => 1, // % of requests
        'alert_on_queue_delay' => 300, // seconds
    ],
];
