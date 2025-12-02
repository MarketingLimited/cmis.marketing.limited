<?php

/**
 * Platform Connections Wizard Configuration
 *
 * Defines platform-specific settings for the simplified connection wizard.
 * Each platform includes:
 * - Display info (name, icon, color)
 * - Connection modes (OAuth, manual)
 * - Asset types with smart default strategies
 *
 * @see App\Http\Controllers\Settings\PlatformConnectionsController
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Smart Default Strategies
    |--------------------------------------------------------------------------
    |
    | Available strategies for auto-selecting assets:
    | - most_followers: Select asset with highest follower/subscriber count
    | - first: Select first available asset
    | - all: Select all available assets
    | - none: No auto-selection (user must choose)
    | - active_only: Select only active/enabled assets
    | - linked_to_instagram: Select if linked Instagram account is selected
    | - linked_to_ad_accounts: Select if linked ad account is selected
    |
    */

    'smart_defaults' => [
        'most_followers' => 'Select asset with most followers',
        'first' => 'Select first available asset',
        'all' => 'Select all available assets',
        'none' => 'Do not auto-select',
        'active_only' => 'Select only active/enabled assets',
        'linked_to_instagram' => 'Select if linked Instagram is selected',
        'linked_to_ad_accounts' => 'Select if linked ad account is selected',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Configurations
    |--------------------------------------------------------------------------
    |
    | Each platform defines:
    | - name: Internal identifier
    | - display_name: User-facing name (translatable)
    | - icon: FontAwesome icon class
    | - color: Brand color (hex)
    | - supports_oauth: Direct Connect via OAuth available
    | - supports_manual: Manual token entry available
    | - oauth_route: Route name for OAuth authorization
    | - manual_route: Route name for manual token form (if supported)
    | - asset_types: Array of selectable assets
    |
    */

    'platforms' => [

        /*
        |----------------------------------------------------------------------
        | Meta (Facebook/Instagram/Threads)
        |----------------------------------------------------------------------
        */
        'meta' => [
            'name' => 'Meta',
            'display_name' => 'wizard.platforms.meta.display_name',
            'description' => 'wizard.platforms.meta.description',
            'icon' => 'fab fa-facebook',
            'color' => '#1877F2',
            'supports_oauth' => true,
            'supports_manual' => true,
            'oauth_route' => 'orgs.settings.platform-connections.meta.authorize',
            'manual_route' => 'orgs.settings.platform-connections.meta.create',
            'manual_label' => 'wizard.platforms.meta.manual_label',
            'manual_help' => 'wizard.platforms.meta.manual_help',
            'asset_types' => [
                'page' => [
                    'name' => 'wizard.assets.meta.pages',
                    'icon' => 'fab fa-facebook',
                    'multi_select' => true,
                    'smart_default' => 'most_followers',
                    'required' => false,
                    'help' => 'wizard.assets.meta.pages_help',
                ],
                'instagram_account' => [
                    'name' => 'wizard.assets.meta.instagram',
                    'icon' => 'fab fa-instagram',
                    'multi_select' => true,
                    'smart_default' => 'most_followers',
                    'required' => false,
                    'help' => 'wizard.assets.meta.instagram_help',
                ],
                'threads_account' => [
                    'name' => 'wizard.assets.meta.threads',
                    'icon' => 'fas fa-at',
                    'multi_select' => true,
                    'smart_default' => 'linked_to_instagram',
                    'required' => false,
                    'help' => 'wizard.assets.meta.threads_help',
                ],
                'ad_account' => [
                    'name' => 'wizard.assets.meta.ad_accounts',
                    'icon' => 'fas fa-ad',
                    'multi_select' => true,
                    'smart_default' => 'active_only',
                    'required' => false,
                    'help' => 'wizard.assets.meta.ad_accounts_help',
                ],
                'pixel' => [
                    'name' => 'wizard.assets.meta.pixels',
                    'icon' => 'fas fa-chart-bar',
                    'multi_select' => true,
                    'smart_default' => 'linked_to_ad_accounts',
                    'required' => false,
                    'help' => 'wizard.assets.meta.pixels_help',
                ],
                'catalog' => [
                    'name' => 'wizard.assets.meta.catalogs',
                    'icon' => 'fas fa-shopping-bag',
                    'multi_select' => true,
                    'smart_default' => 'none',
                    'required' => false,
                    'help' => 'wizard.assets.meta.catalogs_help',
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Google (YouTube/Ads/Analytics)
        |----------------------------------------------------------------------
        */
        'google' => [
            'name' => 'Google',
            'display_name' => 'wizard.platforms.google.display_name',
            'description' => 'wizard.platforms.google.description',
            'icon' => 'fab fa-google',
            'color' => '#4285F4',
            'supports_oauth' => true,
            'supports_manual' => true, // Service account JSON
            'oauth_route' => 'orgs.settings.platform-connections.google.authorize',
            'manual_route' => 'orgs.settings.platform-connections.google.create',
            'manual_label' => 'wizard.platforms.google.manual_label',
            'manual_help' => 'wizard.platforms.google.manual_help',
            'asset_types' => [
                'youtube_channel' => [
                    'name' => 'wizard.assets.google.youtube_channels',
                    'icon' => 'fab fa-youtube',
                    'multi_select' => true,
                    'smart_default' => 'all',
                    'required' => false,
                    'help' => 'wizard.assets.google.youtube_channels_help',
                ],
                'ads_account' => [
                    'name' => 'wizard.assets.google.ads_accounts',
                    'icon' => 'fas fa-ad',
                    'multi_select' => true,
                    'smart_default' => 'active_only',
                    'required' => false,
                    'help' => 'wizard.assets.google.ads_accounts_help',
                ],
                'analytics_property' => [
                    'name' => 'wizard.assets.google.analytics',
                    'icon' => 'fas fa-chart-line',
                    'multi_select' => true,
                    'smart_default' => 'first',
                    'required' => false,
                    'help' => 'wizard.assets.google.analytics_help',
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | LinkedIn
        |----------------------------------------------------------------------
        */
        'linkedin' => [
            'name' => 'LinkedIn',
            'display_name' => 'wizard.platforms.linkedin.display_name',
            'description' => 'wizard.platforms.linkedin.description',
            'icon' => 'fab fa-linkedin',
            'color' => '#0A66C2',
            'supports_oauth' => true,
            'supports_manual' => false,
            'oauth_route' => 'orgs.settings.platform-connections.linkedin.authorize',
            'asset_types' => [
                'profile' => [
                    'name' => 'wizard.assets.linkedin.profile',
                    'icon' => 'fas fa-user',
                    'multi_select' => false,
                    'smart_default' => 'first',
                    'required' => true,
                    'help' => 'wizard.assets.linkedin.profile_help',
                ],
                'page' => [
                    'name' => 'wizard.assets.linkedin.pages',
                    'icon' => 'fas fa-building',
                    'multi_select' => true,
                    'smart_default' => 'most_followers',
                    'required' => false,
                    'help' => 'wizard.assets.linkedin.pages_help',
                ],
                'ad_account' => [
                    'name' => 'wizard.assets.linkedin.ad_accounts',
                    'icon' => 'fas fa-ad',
                    'multi_select' => true,
                    'smart_default' => 'active_only',
                    'required' => false,
                    'help' => 'wizard.assets.linkedin.ad_accounts_help',
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | TikTok
        |----------------------------------------------------------------------
        */
        'tiktok' => [
            'name' => 'TikTok',
            'display_name' => 'wizard.platforms.tiktok.display_name',
            'description' => 'wizard.platforms.tiktok.description',
            'icon' => 'fab fa-tiktok',
            'color' => '#000000',
            'supports_oauth' => true,
            'supports_manual' => false,
            'oauth_route' => 'orgs.settings.platform-connections.tiktok.authorize',
            'asset_types' => [
                'account' => [
                    'name' => 'wizard.assets.tiktok.account',
                    'icon' => 'fab fa-tiktok',
                    'multi_select' => false,
                    'smart_default' => 'first',
                    'required' => true,
                    'help' => 'wizard.assets.tiktok.account_help',
                ],
                'ad_account' => [
                    'name' => 'wizard.assets.tiktok.ad_accounts',
                    'icon' => 'fas fa-ad',
                    'multi_select' => true,
                    'smart_default' => 'active_only',
                    'required' => false,
                    'help' => 'wizard.assets.tiktok.ad_accounts_help',
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | X (Twitter)
        |----------------------------------------------------------------------
        */
        'twitter' => [
            'name' => 'X',
            'display_name' => 'wizard.platforms.twitter.display_name',
            'description' => 'wizard.platforms.twitter.description',
            'icon' => 'fab fa-x-twitter',
            'color' => '#000000',
            'supports_oauth' => true,
            'supports_manual' => false,
            'oauth_route' => 'orgs.settings.platform-connections.twitter.authorize',
            'asset_types' => [
                'account' => [
                    'name' => 'wizard.assets.twitter.account',
                    'icon' => 'fab fa-x-twitter',
                    'multi_select' => false,
                    'smart_default' => 'first',
                    'required' => true,
                    'help' => 'wizard.assets.twitter.account_help',
                ],
                'ad_account' => [
                    'name' => 'wizard.assets.twitter.ad_accounts',
                    'icon' => 'fas fa-ad',
                    'multi_select' => true,
                    'smart_default' => 'active_only',
                    'required' => false,
                    'help' => 'wizard.assets.twitter.ad_accounts_help',
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Snapchat
        |----------------------------------------------------------------------
        */
        'snapchat' => [
            'name' => 'Snapchat',
            'display_name' => 'wizard.platforms.snapchat.display_name',
            'description' => 'wizard.platforms.snapchat.description',
            'icon' => 'fab fa-snapchat',
            'color' => '#FFFC00',
            'text_color' => '#000000', // Black text for yellow background
            'supports_oauth' => true,
            'supports_manual' => false,
            'oauth_route' => 'orgs.settings.platform-connections.snapchat.authorize',
            'asset_types' => [
                'account' => [
                    'name' => 'wizard.assets.snapchat.account',
                    'icon' => 'fab fa-snapchat',
                    'multi_select' => false,
                    'smart_default' => 'first',
                    'required' => true,
                    'help' => 'wizard.assets.snapchat.account_help',
                ],
                'ad_account' => [
                    'name' => 'wizard.assets.snapchat.ad_accounts',
                    'icon' => 'fas fa-ad',
                    'multi_select' => true,
                    'smart_default' => 'active_only',
                    'required' => false,
                    'help' => 'wizard.assets.snapchat.ad_accounts_help',
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Wizard Settings
    |--------------------------------------------------------------------------
    */

    'wizard' => [
        // Session key for storing wizard state
        'session_key' => 'platform_wizard_state',

        // Step names (used for progress indicator)
        'steps' => [
            1 => 'wizard.steps.connect',
            2 => 'wizard.steps.assets',
            3 => 'wizard.steps.complete',
        ],

        // Default timeout for wizard session (minutes)
        'timeout_minutes' => 30,
    ],

];
