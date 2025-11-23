<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media & Marketing Platforms
    |--------------------------------------------------------------------------
    */

    // Meta (Facebook & Instagram)
    'meta' => [
        'client_id' => env('META_CLIENT_ID'),
        'client_secret' => env('META_CLIENT_SECRET'),
        'redirect_uri' => env('META_REDIRECT_URI'),
        'api_version' => env('META_API_VERSION', 'v19.0'),
        'rate_limit' => env('META_RATE_LIMIT', 200),
        // Webhook security (CRITICAL: Required for webhook signature verification)
        'app_secret' => env('META_APP_SECRET', env('META_CLIENT_SECRET')), // Fallback to client_secret
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'webhook_secret' => env('META_WEBHOOK_SECRET'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_APP_ID'),
        'client_secret' => env('FACEBOOK_APP_SECRET'),
        'api_version' => env('FACEBOOK_API_VERSION', 'v19.0'),
        'rate_limit' => env('FACEBOOK_RATE_LIMIT', 200),
    ],

    'instagram' => [
        'client_id' => env('INSTAGRAM_APP_ID'),
        'client_secret' => env('INSTAGRAM_APP_SECRET'),
        'api_version' => env('INSTAGRAM_API_VERSION', 'v19.0'),
        'rate_limit' => env('INSTAGRAM_RATE_LIMIT', 200),
        'base_url' => env('INSTAGRAM_GRAPH_BASE_URL', 'https://graph.facebook.com/v21.0/'),
        'timeout' => env('INSTAGRAM_TIMEOUT', 30),
        'account_fields' => ['id', 'username', 'name', 'profile_picture_url', 'biography', 'followers_count'],
        'media_page_size' => env('INSTAGRAM_MEDIA_PAGE_SIZE', 50),
    ],

    // Google Services
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),

        // Google AI APIs (Gemini & Veo)
        'ai_api_key' => env('GOOGLE_AI_API_KEY'),
        'project_id' => env('GOOGLE_CLOUD_PROJECT'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'storage_bucket' => env('GOOGLE_STORAGE_BUCKET', 'cmis-video-ads'),
        'use_org_keys' => env('GOOGLE_USE_ORG_KEYS', false),

        // Webhook security
        'webhook_secret' => env('GOOGLE_WEBHOOK_SECRET'),
    ],

    'google_ads' => [
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'redirect_uri' => env('GOOGLE_ADS_REDIRECT_URI'),
    ],

    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY'),
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI'),
    ],

    // TikTok
    'tiktok' => [
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect_uri' => env('TIKTOK_REDIRECT_URI'),
        'api_version' => env('TIKTOK_API_VERSION', 'v1.3'),
        'base_url' => env('TIKTOK_API_BASE_URL', 'https://business-api.tiktok.com'),
        'rate_limit' => env('TIKTOK_RATE_LIMIT', 100),
        // Webhook security
        'webhook_verify_token' => env('TIKTOK_WEBHOOK_VERIFY_TOKEN'),
        'webhook_secret' => env('TIKTOK_WEBHOOK_SECRET'),
    ],

    // Snapchat
    'snapchat' => [
        'client_id' => env('SNAPCHAT_CLIENT_ID'),
        'client_secret' => env('SNAPCHAT_CLIENT_SECRET'),
        'redirect_uri' => env('SNAPCHAT_REDIRECT_URI'),
        'rate_limit' => env('SNAPCHAT_RATE_LIMIT', 100),
        // Webhook security
        'webhook_secret' => env('SNAPCHAT_WEBHOOK_SECRET'),
    ],

    // Twitter/X
    'twitter' => [
        'api_key' => env('TWITTER_API_KEY'),
        'api_secret' => env('TWITTER_API_SECRET'),
        'bearer_token' => env('TWITTER_BEARER_TOKEN'),
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect_uri' => env('TWITTER_REDIRECT_URI'),
        'api_version' => env('TWITTER_API_VERSION', '2'),
        'rate_limit' => env('TWITTER_RATE_LIMIT', 300),
        // Webhook security
        'webhook_secret' => env('TWITTER_WEBHOOK_SECRET'),
    ],

    // LinkedIn
    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),
        'api_version' => env('LINKEDIN_API_VERSION', '202401'),
        'rate_limit' => env('LINKEDIN_RATE_LIMIT', 100),
        // Webhook security
        'webhook_secret' => env('LINKEDIN_WEBHOOK_SECRET'),
    ],

    // WhatsApp Business
    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],

    // WooCommerce
    'woocommerce' => [
        'url' => env('WOOCOMMERCE_URL'),
        'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),
        'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
        'api_version' => env('WOOCOMMERCE_API_VERSION', 'wc/v3'),
        'rate_limit' => env('WOOCOMMERCE_RATE_LIMIT', 50),
    ],

    // Microsoft Clarity
    'clarity' => [
        'project_id' => env('MICROSOFT_CLARITY_PROJECT_ID'),
        'api_key' => env('MICROSOFT_CLARITY_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Services
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'rate_limit' => env('AI_RATE_LIMIT', 10), // requests per minute per user
        'openai_key' => env('OPENAI_API_KEY'),
        'anthropic_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('AI_MODEL', 'gpt-4'),
        'max_tokens' => env('AI_MAX_TOKENS', 2000),
        'temperature' => env('AI_TEMPERATURE', 0.7),
    ],

    // Google Gemini AI (Legacy - deprecated in favor of google.ai_api_key)
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', env('GOOGLE_AI_API_KEY')),
        'model' => env('GEMINI_MODEL', 'gemini-3-pro-preview'),
        'temperature' => env('GEMINI_TEMPERATURE', 1.0),
        'max_tokens' => env('GEMINI_MAX_TOKENS', 2048),
        'rate_limit' => env('GEMINI_RATE_LIMIT', 30), // requests per minute
        'rate_limit_hour' => env('GEMINI_RATE_LIMIT_HOUR', 500), // requests per hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits by Service
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'gemini' => 30, // requests per minute
        'veo' => 10, // concurrent requests
        'gpt' => 10, // requests per minute
    ],

];
