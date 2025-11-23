<?php

return [
    'database' => [
        'connection' => env('CMIS_DB_CONNECTION', 'pgsql'),
        'schema' => 'cmis_knowledge',
    ],
    
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model_name' => env('GEMINI_MODEL', 'models/text-embedding-004'),
        'embedding_dimension' => 768,
        'max_batch_size' => 100,
        'rate_limit_per_minute' => 30, // Google Gemini API limit: 30 requests/minute
        'retry_attempts' => 3,
        'timeout_seconds' => 30,
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta/',
    ],
    
    'processing' => [
        'default_batch_size' => 100,
        'chunk_size' => 1000,
        'max_content_length' => 10000,
        'parallel_workers' => 4,
        'queue_check_interval_seconds' => 60,
    ],
    
    'search' => [
        'default_limit' => 10,
        'default_threshold' => 0.7,
        'cache_ttl_seconds' => 3600,
        'max_results' => 100,
    ],
    
    'logging' => [
        'channel' => 'cmis_embeddings',
        'level' => env('CMIS_LOG_LEVEL', 'info'),
    ],
    
    'monitoring' => [
        'enabled' => env('CMIS_MONITORING_ENABLED', true),
        'health_check_interval_seconds' => 300,
        'alert_threshold_failed_percentage' => 10,
    ],
];