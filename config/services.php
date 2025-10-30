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

    'instagram' => [
        'base_url' => env('INSTAGRAM_GRAPH_BASE_URL', 'https://graph.facebook.com/v21.0/'),
        'timeout' => env('INSTAGRAM_TIMEOUT', 30),
        'retry_times' => env('INSTAGRAM_RETRY_TIMES', 3),
        'retry_sleep' => env('INSTAGRAM_RETRY_SLEEP', 500),
        'account_fields' => [
            'id',
            'username',
            'name',
            'profile_picture_url',
            'biography',
            'website',
            'followers_count',
            'follows_count',
            'media_count',
            'category_name',
            'is_verified',
        ],
        'account_metrics' => ['impressions', 'reach', 'profile_views'],
        'media_fields' => [
            'id',
            'caption',
            'media_type',
            'media_url',
            'permalink',
            'thumbnail_url',
            'timestamp',
            'like_count',
            'comments_count',
        ],
        'post_insight_metrics' => ['impressions', 'reach', 'saved'],
        'media_page_size' => env('INSTAGRAM_MEDIA_PAGE_SIZE', 50),
        'media_max_pages' => env('INSTAGRAM_MEDIA_MAX_PAGES', 5),
    ],

];
