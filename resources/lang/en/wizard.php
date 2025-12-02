<?php

/**
 * Platform Connections Wizard Translations (English)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Wizard Steps
    |--------------------------------------------------------------------------
    */
    'steps' => [
        'connect' => 'Connect',
        'assets' => 'Select Assets',
        'complete' => 'Complete',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'title' => 'Platform Connections',
        'subtitle' => 'Connect your social media and advertising accounts',
        'connected' => 'Connected',
        'not_connected' => 'Not Connected',
        'connect_now' => 'Connect Now',
        'manage' => 'Manage',
        'view_all' => 'View All Connections',

        // Summary stats
        'summary' => [
            'platforms_connected' => 'Platforms Connected',
            'total_assets' => 'Total Assets',
            'health_status' => 'Health Status',
            'healthy' => 'All Healthy',
            'warning' => 'Needs Attention',
            'error' => 'Issues Detected',
        ],

        // Platform status badges
        'status' => [
            'active' => 'Active',
            'warning' => 'Warning',
            'error' => 'Error',
            'disconnected' => 'Disconnected',
        ],

        // Connection counts
        'connections' => ':count connection|:count connections',
        'assets_count' => ':count asset|:count assets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Step 1: Connection Mode
    |--------------------------------------------------------------------------
    */
    'mode' => [
        'title' => 'Connect :platform',
        'subtitle' => 'Choose how you want to connect your :platform account',

        // Direct Connect (OAuth)
        'direct' => [
            'title' => 'Direct Connect',
            'description' => 'Recommended for most users. Quick and secure.',
            'button' => 'Connect with :platform',
            'connecting' => 'Connecting...',
        ],

        // Manual Connect (Tokens)
        'manual' => [
            'toggle' => 'Advanced Options',
            'title' => 'Manual Connect',
            'description' => 'For advanced users with API tokens or service accounts.',
            'button' => 'Enter Credentials Manually',
        ],

        // Back button
        'back_to_dashboard' => 'Back to Dashboard',

        // Existing connection warning
        'existing_connection' => 'You already have a connection to this platform',
        'existing_connection_help' => 'Connecting again will update your existing connection with new credentials.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Step 2: Asset Selection
    |--------------------------------------------------------------------------
    */
    'assets' => [
        'title' => 'Select Assets',
        'subtitle' => 'Choose which :platform assets to connect',

        // Smart defaults
        'smart_defaults' => [
            'applied' => 'Smart defaults applied',
            'description' => 'We\'ve pre-selected the most relevant assets based on your account.',
            'reset' => 'Reset to defaults',
        ],

        // Actions
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'save_continue' => 'Save & Continue',
        'skip' => 'Skip for now',
        'back' => 'Back',

        // States
        'loading' => 'Loading assets...',
        'no_assets' => 'No :type found',
        'no_assets_description' => 'We couldn\'t find any :type in your :platform account.',

        // Asset card
        'recommended' => 'Recommended',
        'selected' => 'Selected',

        // Validation
        'at_least_one' => 'Please select at least one asset to continue.',

        /*
        |----------------------------------------------------------------------
        | Meta Assets
        |----------------------------------------------------------------------
        */
        'meta' => [
            'pages' => 'Facebook Pages',
            'pages_help' => 'Select the Facebook Pages you want to manage',
            'instagram' => 'Instagram Accounts',
            'instagram_help' => 'Business or Creator accounts connected to your Pages',
            'threads' => 'Threads Accounts',
            'threads_help' => 'Threads profiles linked to your Instagram accounts',
            'ad_accounts' => 'Ad Accounts',
            'ad_accounts_help' => 'Advertising accounts for running paid campaigns',
            'pixels' => 'Pixels',
            'pixels_help' => 'Conversion tracking pixels for your websites',
            'catalogs' => 'Product Catalogs',
            'catalogs_help' => 'Product catalogs for shopping campaigns',
        ],

        /*
        |----------------------------------------------------------------------
        | Google Assets
        |----------------------------------------------------------------------
        */
        'google' => [
            'youtube_channels' => 'YouTube Channels',
            'youtube_channels_help' => 'Your YouTube channels for video content',
            'ads_accounts' => 'Google Ads Accounts',
            'ads_accounts_help' => 'Advertising accounts for search and display campaigns',
            'analytics' => 'Analytics Properties',
            'analytics_help' => 'Google Analytics 4 properties for website tracking',
        ],

        /*
        |----------------------------------------------------------------------
        | LinkedIn Assets
        |----------------------------------------------------------------------
        */
        'linkedin' => [
            'profile' => 'LinkedIn Profile',
            'profile_help' => 'Your personal LinkedIn profile',
            'pages' => 'Company Pages',
            'pages_help' => 'LinkedIn Company Pages you administer',
            'ad_accounts' => 'Ad Accounts',
            'ad_accounts_help' => 'LinkedIn Campaign Manager accounts',
        ],

        /*
        |----------------------------------------------------------------------
        | TikTok Assets
        |----------------------------------------------------------------------
        */
        'tiktok' => [
            'account' => 'TikTok Account',
            'account_help' => 'Your TikTok creator or business account',
            'ad_accounts' => 'Ad Accounts',
            'ad_accounts_help' => 'TikTok Ads Manager accounts',
        ],

        /*
        |----------------------------------------------------------------------
        | Twitter/X Assets
        |----------------------------------------------------------------------
        */
        'twitter' => [
            'account' => 'X Account',
            'account_help' => 'Your X (Twitter) account',
            'ad_accounts' => 'Ad Accounts',
            'ad_accounts_help' => 'X Ads accounts for promoted content',
        ],

        /*
        |----------------------------------------------------------------------
        | Snapchat Assets
        |----------------------------------------------------------------------
        */
        'snapchat' => [
            'account' => 'Snapchat Account',
            'account_help' => 'Your Snapchat Business account',
            'ad_accounts' => 'Ad Accounts',
            'ad_accounts_help' => 'Snapchat Ads Manager accounts',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Step 3: Success
    |--------------------------------------------------------------------------
    */
    'success' => [
        'title' => ':platform Connected!',
        'subtitle' => 'Your account has been successfully connected.',

        // Summary
        'summary' => [
            'title' => 'Connection Summary',
            'assets_synced' => ':count asset synced|:count assets synced',
        ],

        // Actions
        'connect_another' => 'Connect Another Platform',
        'done' => 'Done',
        'view_connections' => 'View All Connections',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Display Names & Descriptions
    |--------------------------------------------------------------------------
    */
    'platforms' => [
        'meta' => [
            'display_name' => 'Meta',
            'description' => 'Facebook, Instagram, and Threads',
            'manual_label' => 'System User Token',
            'manual_help' => 'Enter your Meta Business System User access token for advanced integrations.',
        ],
        'google' => [
            'display_name' => 'Google',
            'description' => 'YouTube, Google Ads, and Analytics',
            'manual_label' => 'Service Account',
            'manual_help' => 'Upload your Google Cloud service account JSON key file.',
        ],
        'linkedin' => [
            'display_name' => 'LinkedIn',
            'description' => 'Professional networking and B2B advertising',
        ],
        'tiktok' => [
            'display_name' => 'TikTok',
            'description' => 'Short-form video content and advertising',
        ],
        'twitter' => [
            'display_name' => 'X (Twitter)',
            'description' => 'Real-time conversations and promoted content',
        ],
        'snapchat' => [
            'display_name' => 'Snapchat',
            'description' => 'Visual messaging and AR advertising',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'platform_not_found' => 'Platform not found.',
        'connection_failed' => 'Connection failed. Please try again.',
        'oauth_cancelled' => 'Connection was cancelled.',
        'oauth_error' => 'Authentication error: :message',
        'invalid_token' => 'Invalid or expired token.',
        'fetch_assets_failed' => 'Failed to load assets. Please try again.',
        'save_assets_failed' => 'Failed to save assets. Please try again.',
        'session_expired' => 'Your session has expired. Please start over.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Actions
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'cancel' => 'Cancel',
        'close' => 'Close',
        'retry' => 'Retry',
        'continue' => 'Continue',
        'back' => 'Back',
        'save' => 'Save',
        'connect' => 'Connect',
        'disconnect' => 'Disconnect',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tips
    |--------------------------------------------------------------------------
    */
    'tips' => [
        'sync_data' => 'Your data will begin syncing automatically',
        'manage_assets' => 'You can add or remove assets anytime from the connections page',
        'token_refresh' => 'Access tokens are refreshed automatically when needed',
    ],

];
