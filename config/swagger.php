<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Documentation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for auto-generated API documentation using OpenAPI/Swagger.
    |
    */

    'enabled' => env('SWAGGER_ENABLED', true),

    'version' => '1.0.0',

    'title' => 'CMIS API Documentation',

    'description' => 'Cognitive Marketing Intelligence Suite (CMIS) - RESTful API for campaign management, platform integrations, and AI-powered content generation.',

    'terms_of_service' => env('APP_URL') . '/terms',

    'contact' => [
        'name' => 'CMIS Support',
        'email' => 'support@cmis.marketing',
        'url' => env('APP_URL') . '/support',
    ],

    'license' => [
        'name' => 'Proprietary',
        'url' => env('APP_URL') . '/license',
    ],

    'servers' => [
        [
            'url' => env('APP_URL') . '/api',
            'description' => 'API Server',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    |
    | Define authentication methods available in the API.
    |
    */

    'security_schemes' => [
        'sanctum' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'Laravel Sanctum token authentication. Include token in Authorization header as: Bearer {token}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-scan Settings
    |--------------------------------------------------------------------------
    |
    | Directories to scan for API route annotations.
    |
    */

    'scan_paths' => [
        app_path('Http/Controllers'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Output
    |--------------------------------------------------------------------------
    |
    | Where to store generated OpenAPI specification files.
    |
    */

    'output_path' => storage_path('api-docs'),

    'output_filename' => 'openapi.json',

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the Swagger UI appearance.
    |
    */

    'ui' => [
        'display_request_duration' => true,
        'doc_expansion' => 'list', // 'none', 'list', 'full'
        'filter' => true, // Enable search filter
        'show_extensions' => false,
        'show_common_extensions' => false,
        'default_models_expand_depth' => 1,
        'default_model_expand_depth' => 1,
        'persist_authorization' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Routes for accessing API documentation.
    |
    */

    'routes' => [
        'api_docs' => '/api/documentation',
        'api_json' => '/api/openapi.json',
        'api_yaml' => '/api/openapi.yaml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits per Endpoint Type
    |--------------------------------------------------------------------------
    |
    | Document rate limits for different endpoint categories.
    |
    */

    'rate_limits' => [
        'ai' => [
            'starter' => '10 requests per minute',
            'professional' => '30 requests per minute',
            'enterprise' => '100 requests per minute',
        ],
        'standard' => [
            'all_plans' => '60 requests per minute',
        ],
        'webhooks' => [
            'all_plans' => 'Unlimited (platform-initiated)',
        ],
    ],
];
