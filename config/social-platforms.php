<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Social Media Platform Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for all supported social media publishing platforms.
    | Each platform includes OAuth settings, API endpoints, capabilities,
    | and publishing requirements.
    |
    */

    'meta' => [
        'name' => 'Meta (Facebook/Instagram)',
        'enabled' => true,
        'oauth_version' => '2.0',
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'redirect_uri' => env('META_REDIRECT_URI'),
        'api_version' => env('META_API_VERSION', 'v21.0'),
        'rate_limit' => env('META_RATE_LIMIT', 200),
        'base_url' => 'https://graph.facebook.com',
        'authorize_url' => 'https://www.facebook.com/v21.0/dialog/oauth',
        'token_url' => 'https://graph.facebook.com/v21.0/oauth/access_token',
        'scopes' => [
            'ads_management',
            'ads_read',
            'business_management',
            'catalog_management',
            'pages_manage_posts',
            'pages_read_engagement',
            'instagram_basic',
            'instagram_content_publish',
        ],
        'platforms' => ['facebook', 'instagram'],
        'post_types' => [
            'facebook' => ['text', 'image', 'video', 'carousel', 'link', 'poll'],
            'instagram' => ['image', 'video', 'carousel', 'reels', 'stories'],
        ],
    ],

    'threads' => [
        'name' => 'Threads',
        'enabled' => true,
        'oauth_version' => '2.0',
        'app_id' => env('THREADS_APP_ID', env('META_APP_ID')),
        'app_secret' => env('THREADS_APP_SECRET', env('META_APP_SECRET')),
        'redirect_uri' => env('APP_URL') . '/integrations/threads/callback',
        'api_version' => env('THREADS_API_VERSION', 'v1.0'),
        'rate_limit' => env('META_RATE_LIMIT', 200),
        'base_url' => 'https://graph.threads.net',
        'authorize_url' => 'https://www.threads.net/oauth/authorize',
        'token_url' => 'https://graph.threads.net/oauth/access_token',
        'scopes' => [
            'threads_basic',
            'threads_content_publish',
            'threads_manage_insights',
            'threads_manage_replies',
        ],
        'post_types' => ['post', 'media', 'carousel', 'poll'],
        'features' => [
            'auto_publish_text' => true, // July 2025 feature
            'polls' => true, // July 2025 feature
            'location_tagging' => true,
            'topic_tags' => true,
            'reply_restrictions' => true,
        ],
        'text_limits' => [
            'post' => 500,
            'poll_question' => 500,
            'poll_option' => 25,
        ],
        'media_requirements' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'video' => ['mp4', 'mov'],
            'max_file_size_mb' => 100,
        ],
    ],

    'youtube' => [
        'name' => 'YouTube',
        'enabled' => true,
        'oauth_version' => '2.0',
        'client_id' => env('YOUTUBE_CLIENT_ID', env('GOOGLE_CLIENT_ID')),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET')),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', env('APP_URL') . '/integrations/youtube/callback'),
        'api_version' => 'v3',
        'rate_limit' => 10000, // Daily quota units
        'base_url' => 'https://www.googleapis.com/youtube/v3',
        'upload_url' => 'https://www.googleapis.com/upload/youtube/v3',
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'scopes' => [
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube.force-ssl',
            'https://www.googleapis.com/auth/youtube',
        ],
        'post_types' => ['video', 'short'],
        'features' => [
            'thumbnails' => true,
            'captions' => true,
            'playlists' => true,
            'scheduling' => true,
            'chapters' => true,
            'end_screens' => true,
        ],
        'video_requirements' => [
            'formats' => ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm'],
            'max_file_size_gb' => 256,
            'max_duration_seconds' => 43200, // 12 hours
            'shorts_max_duration' => 60, // 60 seconds for Shorts
            'resolutions' => ['360p', '480p', '720p', '1080p', '1440p', '2160p', '4320p'],
        ],
        'text_limits' => [
            'title' => 100,
            'description' => 5000,
            'tags' => 500,
        ],
    ],

    'linkedin' => [
        'name' => 'LinkedIn',
        'enabled' => true,
        'oauth_version' => '2.0',
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect_uri' => env('LINKEDIN_REDIRECT_URI', env('APP_URL') . '/integrations/linkedin/callback'),
        'api_version' => env('LINKEDIN_API_VERSION', '202401'),
        'rate_limit' => env('LINKEDIN_RATE_LIMIT', 100),
        'base_url' => 'https://api.linkedin.com/rest',
        'authorize_url' => 'https://www.linkedin.com/oauth/v2/authorization',
        'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
        'scopes' => [
            'openid',
            'profile',
            'email',
            'w_member_social',
            'r_organization_social',
            'w_organization_social',
        ],
        'post_types' => ['text', 'image', 'carousel', 'video', 'article', 'poll'],
        'features' => [
            'carousel' => true, // Nov 2025 feature
            'polls' => true, // Nov 2025 feature
            'mentions' => true,
            'hashtags' => true,
        ],
        'text_limits' => [
            'post' => 3000,
            'poll_question' => 140,
            'poll_option' => 30,
        ],
        'media_requirements' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'video' => ['mp4', 'mov', 'avi'],
            'max_images_carousel' => 9,
            'min_images_carousel' => 2,
            'max_file_size_mb' => 200,
            'max_video_duration_seconds' => 600,
        ],
        'poll_settings' => [
            'min_options' => 2,
            'max_options' => 4,
            'min_duration_hours' => 24,
            'max_duration_hours' => 336, // 14 days
        ],
    ],

    'twitter' => [
        'name' => 'X (Twitter)',
        'enabled' => true,
        'oauth_version' => '2.0',
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'api_key' => env('TWITTER_API_KEY'),
        'api_secret' => env('TWITTER_API_SECRET'),
        'bearer_token' => env('TWITTER_BEARER_TOKEN'),
        'redirect_uri' => env('TWITTER_REDIRECT_URI', env('APP_URL') . '/integrations/twitter/callback'),
        'api_version' => env('TWITTER_API_VERSION', '2'),
        'rate_limit' => env('TWITTER_RATE_LIMIT', 300),
        'base_url' => 'https://api.twitter.com/2',
        'upload_url' => 'https://upload.twitter.com/1.1',
        'authorize_url' => 'https://twitter.com/i/oauth2/authorize',
        'token_url' => 'https://api.twitter.com/2/oauth2/token',
        'scopes' => [
            'tweet.read',
            'tweet.write',
            'tweet.moderate.write',
            'users.read',
            'follows.read',
            'follows.write',
            'offline.access',
            'space.read',
            'mute.read',
            'mute.write',
            'like.read',
            'like.write',
            'list.read',
            'list.write',
            'block.read',
            'block.write',
        ],
        'post_types' => ['tweet', 'thread', 'media', 'poll', 'quote'],
        'features' => [
            'threads' => true,
            'polls' => true,
            'quote_tweets' => true,
            'reply_controls' => true,
            'mentions' => true,
            'hashtags' => true,
        ],
        'text_limits' => [
            'tweet' => 280,
            'poll_option' => 25,
        ],
        'media_requirements' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'mov'],
            'max_images' => 4,
            'max_file_size_image_mb' => 5,
            'max_file_size_video_mb' => 512,
            'max_video_duration_seconds' => 140,
            'gif_max_size_mb' => 15,
        ],
        'poll_settings' => [
            'min_options' => 2,
            'max_options' => 4,
            'min_duration_minutes' => 5,
            'max_duration_hours' => 168, // 7 days
        ],
    ],

    'pinterest' => [
        'name' => 'Pinterest',
        'enabled' => true,
        'oauth_version' => '2.0',
        'app_id' => env('PINTEREST_APP_ID'),
        'app_secret' => env('PINTEREST_APP_SECRET'),
        'redirect_uri' => env('PINTEREST_REDIRECT_URI', env('APP_URL') . '/integrations/pinterest/callback'),
        'api_version' => env('PINTEREST_API_VERSION', 'v5'),
        'rate_limit' => env('PINTEREST_RATE_LIMIT', 10),
        'base_url' => 'https://api.pinterest.com/v5',
        'authorize_url' => 'https://www.pinterest.com/oauth/',
        'token_url' => 'https://api.pinterest.com/v5/oauth/token',
        'scopes' => [
            'pins:read',
            'pins:write',
            'boards:read',
            'boards:write',
            'user_accounts:read',
        ],
        'post_types' => ['pin', 'video_pin', 'idea_pin'],
        'features' => [
            'boards' => true,
            'scheduling' => true,
            'idea_pins' => true, // Multi-page stories
            'shopping_tags' => true,
        ],
        'text_limits' => [
            'title' => 100,
            'description' => 500,
            'alt_text' => 500,
        ],
        'media_requirements' => [
            'image' => ['jpg', 'jpeg', 'png'],
            'video' => ['mp4', 'mov', 'm4v'],
            'max_file_size_image_mb' => 32,
            'max_file_size_video_mb' => 2048,
            'max_video_duration_minutes' => 15,
            'aspect_ratios' => ['2:3', '1:1', '9:16'],
            'idea_pin_min_pages' => 2,
            'idea_pin_max_pages' => 20,
        ],
    ],

    'tiktok' => [
        'name' => 'TikTok',
        'enabled' => true,
        'oauth_version' => '2.0',
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect_uri' => env('TIKTOK_REDIRECT_URI', env('APP_URL') . '/integrations/tiktok/callback'),
        'api_version' => env('TIKTOK_API_VERSION', 'v1.3'),
        'rate_limit' => env('TIKTOK_RATE_LIMIT', 100),
        'base_url' => 'https://open.tiktokapis.com/v2',
        'authorize_url' => 'https://www.tiktok.com/v2/auth/authorize/',
        'token_url' => 'https://open.tiktokapis.com/v2/oauth/token/',
        'scopes' => [
            'user.info.basic',
            'user.info.profile',
            'user.info.stats',
            'video.upload',
            'video.publish',
            'video.list',
        ],
        'post_types' => ['video', 'photo_carousel'],
        'features' => [
            'photo_carousel' => true, // NEW 2025
            'duet' => true,
            'stitch' => true,
            'comments' => true,
            'privacy_controls' => true,
        ],
        'text_limits' => [
            'caption' => 2200,
            'hashtag_max' => 150,
        ],
        'media_requirements' => [
            'video' => ['mp4', 'mov', 'webm'],
            'image' => ['jpg', 'jpeg', 'webp'], // For photo carousel
            'max_file_size_video_mb' => 287,
            'max_file_size_image_mb' => 10,
            'min_video_duration_seconds' => 3,
            'max_video_duration_seconds' => 600,
            'aspect_ratios' => ['9:16', '1:1', '16:9'],
            'photo_carousel_min' => 2,
            'photo_carousel_max' => 35,
        ],
        'notes' => [
            'audit_required' => 'Public posting requires TikTok audit approval',
            'unaudited_limit' => 'PRIVATE or FOLLOWER_OF_CREATOR only',
        ],
    ],

    'tiktok_ads' => [
        'name' => 'TikTok Business',
        'enabled' => true,
        'oauth_version' => '2.0',
        'app_id' => env('TIKTOK_ADS_APP_ID'),
        'app_secret' => env('TIKTOK_ADS_APP_SECRET', env('TIKTOK_ADS_SECRET')),
        'redirect_uri' => env('TIKTOK_ADS_REDIRECT_URI', env('APP_URL') . '/integrations/tiktok-business/callback'),
        'api_version' => env('TIKTOK_ADS_API_VERSION', 'v1.3'),
        'rate_limit' => env('TIKTOK_ADS_RATE_LIMIT', 100),
        'base_url' => 'https://business-api.tiktok.com/open_api/v1.3',
        'authorize_url' => 'https://business-api.tiktok.com/portal/auth',
        'token_url' => 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/',
        'advertiser_url' => 'https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/',
        // Note: TikTok Marketing API (Business API) does NOT use OAuth scopes in the authorization URL.
        // Permissions are configured at the app level in TikTok Developer Portal.
        'features' => [
            'campaigns' => true,
            'ad_groups' => true,
            'ads' => true,
            'pixels' => true,
            'catalogs' => true,
            'audiences' => true,
            'reporting' => true,
            'creative_management' => true,
            'lead_management' => true,
            'spark_ads' => true,
            'automated_rules' => true,
        ],
        'notes' => [
            'token_expiry' => 'Access tokens do not expire unless revoked',
            'advertiser_ids' => 'Returns list of advertiser_ids that can be managed',
        ],
    ],

    'tumblr' => [
        'name' => 'Tumblr',
        'enabled' => true,
        'oauth_version' => '1.0a',
        'consumer_key' => env('TUMBLR_CONSUMER_KEY'),
        'consumer_secret' => env('TUMBLR_CONSUMER_SECRET'),
        'redirect_uri' => env('TUMBLR_REDIRECT_URI', env('APP_URL') . '/integrations/tumblr/callback'),
        'rate_limit' => env('TUMBLR_RATE_LIMIT', 300),
        'base_url' => 'https://api.tumblr.com/v2',
        'authorize_url' => 'https://www.tumblr.com/oauth/authorize',
        'request_token_url' => 'https://www.tumblr.com/oauth/request_token',
        'access_token_url' => 'https://www.tumblr.com/oauth/access_token',
        'post_types' => ['text', 'photo', 'video', 'link', 'quote', 'chat', 'audio'],
        'features' => [
            'npf' => true, // Neue Post Format
            'queue' => true,
            'drafts' => true,
            'scheduling' => true,
            'tags' => true,
            'custom_urls' => true,
            'reblog' => true,
        ],
        'text_limits' => [
            'title' => 250,
            'body' => 4096,
            'tags_max' => 30,
            'tag_length' => 140,
        ],
        'media_requirements' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp'],
            'video' => ['mp4', 'mov'],
            'max_file_size_image_mb' => 10,
            'max_file_size_video_mb' => 500,
            'max_photos_per_post' => 10,
        ],
    ],

    'reddit' => [
        'name' => 'Reddit',
        'enabled' => true,
        'oauth_version' => '2.0',
        'client_id' => env('REDDIT_CLIENT_ID'),
        'client_secret' => env('REDDIT_CLIENT_SECRET'),
        'redirect_uri' => env('REDDIT_REDIRECT_URI', env('APP_URL') . '/integrations/reddit/callback'),
        'user_agent' => env('REDDIT_USER_AGENT', 'CMIS Marketing Platform'),
        'rate_limit' => env('REDDIT_RATE_LIMIT', 60),
        'base_url' => 'https://oauth.reddit.com',
        'authorize_url' => 'https://www.reddit.com/api/v1/authorize',
        'token_url' => 'https://www.reddit.com/api/v1/access_token',
        'scopes' => [
            'identity',
            'read',
            'submit',
            'edit',
            'flair',
            'modposts',
            'privatemessages',
            'save',
            'vote',
        ],
        'post_types' => ['text', 'link', 'image', 'video', 'poll', 'crosspost'],
        'features' => [
            'flair' => true,
            'nsfw_tagging' => true,
            'spoiler_tagging' => true,
            'crossposting' => true,
            'subreddit_validation' => true,
        ],
        'text_limits' => [
            'title' => 300,
            'selftext' => 40000,
        ],
        'media_requirements' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'video' => ['mp4', 'mov'],
            'max_file_size_image_mb' => 20,
            'max_file_size_video_mb' => 1024,
            'max_video_duration_minutes' => 15,
        ],
    ],

    'google' => [
        'name' => 'Google Services',
        'enabled' => true,
        'oauth_version' => '2.0',
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/integrations/google/callback'),
        'rate_limit' => 1000,
        'base_url' => 'https://www.googleapis.com',
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
        'scopes' => [
            // Core profile
            'openid',
            'email',
            'profile',
            // Google Ads
            'https://www.googleapis.com/auth/adwords',
            // Analytics
            'https://www.googleapis.com/auth/analytics.readonly',
            'https://www.googleapis.com/auth/analytics.edit',
            // Business Profile
            'https://www.googleapis.com/auth/business.manage',
            // Tag Manager
            'https://www.googleapis.com/auth/tagmanager.readonly',
            'https://www.googleapis.com/auth/tagmanager.edit.containers',
            // Search Console
            'https://www.googleapis.com/auth/webmasters.readonly',
            // Calendar
            'https://www.googleapis.com/auth/calendar.readonly',
            // Drive
            'https://www.googleapis.com/auth/drive.readonly',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
            // Merchant Center (Content API for Shopping)
            'https://www.googleapis.com/auth/content',
            // NOTE: YouTube scopes moved to 'youtube_scopes' for incremental authorization
        ],
        // YouTube scopes for incremental authorization (requested separately on Assets page)
        'youtube_scopes' => [
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube',
        ],
        'services' => [
            'youtube' => [
                'name' => 'YouTube',
                'base_url' => 'https://www.googleapis.com/youtube/v3',
                'icon' => 'fab fa-youtube',
                'color' => 'red',
            ],
            'google_ads' => [
                'name' => 'Google Ads',
                'base_url' => 'https://googleads.googleapis.com',
                'icon' => 'fas fa-ad',
                'color' => 'green',
            ],
            'analytics' => [
                'name' => 'Google Analytics',
                'base_url' => 'https://analyticsadmin.googleapis.com/v1beta',
                'icon' => 'fas fa-chart-line',
                'color' => 'orange',
            ],
            'business_profile' => [
                'name' => 'Google Business Profile',
                'base_url' => 'https://mybusinessbusinessinformation.googleapis.com/v1',
                'icon' => 'fas fa-store',
                'color' => 'blue',
            ],
            'tag_manager' => [
                'name' => 'Tag Manager',
                'base_url' => 'https://www.googleapis.com/tagmanager/v2',
                'icon' => 'fas fa-code',
                'color' => 'purple',
            ],
            'merchant_center' => [
                'name' => 'Merchant Center',
                'base_url' => 'https://shoppingcontent.googleapis.com/content/v2.1',
                'icon' => 'fas fa-shopping-cart',
                'color' => 'teal',
            ],
            'search_console' => [
                'name' => 'Search Console',
                'base_url' => 'https://searchconsole.googleapis.com',
                'icon' => 'fas fa-search',
                'color' => 'indigo',
            ],
            'calendar' => [
                'name' => 'Google Calendar',
                'base_url' => 'https://www.googleapis.com/calendar/v3',
                'icon' => 'fas fa-calendar',
                'color' => 'cyan',
            ],
            'drive' => [
                'name' => 'Google Drive',
                'base_url' => 'https://www.googleapis.com/drive/v3',
                'icon' => 'fas fa-folder',
                'color' => 'yellow',
            ],
        ],
    ],

    'google_business' => [
        'name' => 'Google Business Profile',
        'enabled' => true,
        'oauth_version' => '2.0',
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('APP_URL') . '/integrations/google-business/callback',
        'rate_limit' => 100,
        'base_url' => 'https://mybusiness.googleapis.com/v4',
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'scopes' => [
            'https://www.googleapis.com/auth/business.manage',
        ],
        'post_types' => ['whats_new', 'event', 'offer', 'cta'],
        'features' => [
            'multi_location' => true, // NEW Nov 25, 2025
            'scheduling' => true, // NEW Nov 25, 2025
            'events' => true,
            'offers' => true,
            'call_to_action' => true,
        ],
        'text_limits' => [
            'summary' => 1500,
            'event_title' => 58,
            'offer_title' => 58,
            'offer_coupon_code' => 50,
        ],
        'media_requirements' => [
            'image' => ['jpg', 'jpeg', 'png'],
            'max_file_size_mb' => 5,
            'min_resolution' => [250, 250],
            'max_resolution' => [2048, 2048],
        ],
        'cta_types' => ['BOOK', 'ORDER', 'SHOP', 'LEARN_MORE', 'SIGN_UP', 'CALL'],
    ],

    'snapchat' => [
        'name' => 'Snapchat',
        'enabled' => true,
        'oauth_version' => '2.0',
        'client_id' => env('SNAPCHAT_CLIENT_ID'),
        'client_secret' => env('SNAPCHAT_CLIENT_SECRET'),
        'redirect_uri' => env('SNAPCHAT_REDIRECT_URI', env('APP_URL') . '/integrations/snapchat/callback'),
        'rate_limit' => env('SNAPCHAT_RATE_LIMIT', 100),
        'base_url' => 'https://adsapi.snapchat.com/v1',
        'authorize_url' => 'https://accounts.snapchat.com/login/oauth2/authorize',
        'token_url' => 'https://accounts.snapchat.com/login/oauth2/access_token',
        'scopes' => [
            'snapchat-marketing-api',
        ],
        'post_types' => ['snap_ad', 'story_ad', 'collection_ad'],
        'features' => [
            'ar_lenses' => true,
            'geofilters' => true,
            'snap_pixel' => true,
            'instant_forms' => true,
        ],
        'media_requirements' => [
            'image' => ['jpg', 'jpeg', 'png'],
            'video' => ['mp4', 'mov'],
            'max_file_size_image_mb' => 5,
            'max_file_size_video_mb' => 1024,
            'aspect_ratio' => '9:16', // Vertical only
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    */

    'retry' => [
        'max_attempts' => 3,
        'delay_seconds' => 5,
        'backoff_multiplier' => 2,
    ],

    'token_refresh' => [
        'before_expiry_hours' => 24,
        'auto_refresh' => true,
    ],

    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'redis'),
        'queue_name' => 'social-publishing',
        'timeout_seconds' => 300,
    ],

    'analytics' => [
        'sync_interval_minutes' => 60,
        'retention_days' => 90,
    ],
];
