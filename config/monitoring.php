<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for application performance monitoring and alerting
    |
    */

    // Slow request threshold in milliseconds
    'slow_request_threshold' => env('MONITORING_SLOW_REQUEST_MS', 1000),

    // Query count threshold for alerting
    'query_count_threshold' => env('MONITORING_QUERY_COUNT_THRESHOLD', 50),

    // Memory usage threshold in MB
    'memory_threshold_mb' => env('MONITORING_MEMORY_THRESHOLD_MB', 128),

    // Enable query logging (disable in production for performance)
    'enable_query_logging' => env('MONITORING_ENABLE_QUERY_LOG', !app()->isProduction()),

    // Performance headers in responses
    'add_performance_headers' => env('MONITORING_PERFORMANCE_HEADERS', true),

    // Log slow queries
    'log_slow_queries' => env('MONITORING_LOG_SLOW_QUERIES', true),
    'slow_query_threshold_ms' => env('MONITORING_SLOW_QUERY_MS', 100),

    // Error tracking
    'sentry' => [
        'enabled' => env('SENTRY_LARAVEL_DSN', false) !== false,
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'environment' => env('APP_ENV', 'production'),
        'release' => env('SENTRY_RELEASE', null),
        'sample_rate' => env('SENTRY_SAMPLE_RATE', 1.0),
        'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),
    ],

    // Health check endpoints
    'health_checks' => [
        'database' => true,
        'cache' => true,
        'queue' => true,
        'storage' => true,
    ],

    // Metrics collection
    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
        'driver' => env('METRICS_DRIVER', 'log'), // log, statsd, prometheus
        'statsd' => [
            'host' => env('STATSD_HOST', 'localhost'),
            'port' => env('STATSD_PORT', 8125),
            'namespace' => env('STATSD_NAMESPACE', 'cmis'),
        ],
    ],
];
