<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace Language Lines (English)
    |--------------------------------------------------------------------------
    | Translation keys for the Apps Marketplace feature
    */

    // Page titles and headers
    'title' => 'Apps Marketplace',
    'subtitle' => 'Customize your CMIS experience by enabling the apps you need',

    // Search and filter
    'search_placeholder' => 'Search apps...',
    'all_categories' => 'All',

    // App states
    'enable' => 'Enable',
    'disable' => 'Disable',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',
    'premium' => 'Premium',
    'requires' => 'Requires',
    'core_feature' => 'Core Feature',
    'apps_count' => 'apps',

    // Bulk actions
    'bulk_select' => 'Bulk Select',
    'selected' => 'selected',
    'enable_all' => 'Enable All',
    'disable_all' => 'Disable All',
    'bulk_enabled' => ':count apps have been enabled successfully',
    'bulk_disabled' => ':count apps have been disabled successfully',

    // Messages
    'app_enabled' => ':app has been enabled successfully',
    'app_enabled_with_dependencies' => ':app has been enabled. Also enabled: :dependencies',
    'app_disabled' => ':app has been disabled successfully',
    'app_not_found' => 'App not found',
    'app_not_enabled' => 'This feature is not enabled for your organization. Enable it from the Apps Marketplace.',
    'cannot_modify_core_app' => 'Core apps cannot be modified',
    'cannot_disable_has_dependents' => 'Cannot disable this app. It is required by: :apps',
    'premium_required' => 'This app requires a Premium subscription',

    // Premium info
    'premium_info_title' => 'Unlock Premium Apps',
    'premium_info_description' => 'Upgrade to Premium to access advanced features like AI Assistant, Predictive Analytics, and more.',
    'upgrade_now' => 'Upgrade Now',
    'premium_required' => 'Premium Required',

    // Empty states
    'no_results_title' => 'No apps found',
    'no_results_description' => 'Try adjusting your search or filter criteria',

    // Categories
    'categories' => [
        'core' => 'Core',
        'core_description' => 'Essential features always available',
        'marketing' => 'Marketing',
        'marketing_description' => 'Campaign management and audience tools',
        'analytics' => 'Analytics',
        'analytics_description' => 'Performance tracking and insights',
        'ai' => 'AI & Intelligence',
        'ai_description' => 'Smart features powered by artificial intelligence',
        'automation' => 'Automation',
        'automation_description' => 'Workflow automation and alerts',
        'system' => 'System Tools',
        'system_description' => 'Data management and administration tools',
    ],

    // App names and descriptions
    'apps' => [
        // Core Apps
        'dashboard' => [
            'name' => 'Dashboard',
            'description' => 'Your central hub for monitoring all activities and metrics',
        ],
        'social_media' => [
            'name' => 'Social Media',
            'description' => 'Compose, schedule, and manage social media posts',
        ],
        'profile_groups' => [
            'name' => 'Profile Groups',
            'description' => 'Organize your social profiles into manageable groups',
        ],
        'inbox' => [
            'name' => 'Inbox',
            'description' => 'Unified inbox for all your social media messages',
        ],
        'settings' => [
            'name' => 'Settings',
            'description' => 'Configure your organization and platform connections',
        ],
        'marketplace' => [
            'name' => 'Apps Marketplace',
            'description' => 'Browse and manage available apps for your organization',
        ],
        'historical_content' => [
            'name' => 'Historical Content',
            'description' => 'View and analyze your past social media posts and performance',
        ],

        // Marketing Apps
        'campaigns' => [
            'name' => 'Campaigns',
            'description' => 'Create and manage advertising campaigns across platforms',
        ],
        'audiences' => [
            'name' => 'Audiences',
            'description' => 'Build and manage target audiences for your campaigns',
        ],
        'influencers' => [
            'name' => 'Influencer Marketing',
            'description' => 'Discover and manage influencer partnerships',
        ],
        'orchestration' => [
            'name' => 'Campaign Orchestration',
            'description' => 'Advanced campaign coordination across channels',
        ],

        // Analytics Apps
        'analytics' => [
            'name' => 'Analytics',
            'description' => 'Track performance metrics and generate insights',
        ],
        'predictive' => [
            'name' => 'Predictive Analytics',
            'description' => 'AI-powered forecasting and trend predictions',
        ],
        'ab_testing' => [
            'name' => 'A/B Testing',
            'description' => 'Test and optimize your campaigns with experiments',
        ],
        'optimization' => [
            'name' => 'Optimization Engine',
            'description' => 'Automated campaign optimization and recommendations',
        ],

        // AI Apps
        'ai_assistant' => [
            'name' => 'AI Assistant',
            'description' => 'Intelligent assistant for content creation and insights',
        ],
        'knowledge_base' => [
            'name' => 'Knowledge Base',
            'description' => 'AI-powered knowledge management and search',
        ],
        'social_listening' => [
            'name' => 'Social Listening',
            'description' => 'Monitor brand mentions and sentiment across social media',
        ],

        // Automation Apps
        'automation' => [
            'name' => 'Automation',
            'description' => 'Automate repetitive tasks and workflows',
        ],
        'workflows' => [
            'name' => 'Workflows',
            'description' => 'Create custom approval and content workflows',
        ],
        'alerts' => [
            'name' => 'Alerts',
            'description' => 'Set up notifications for important events',
        ],

        // System Apps
        'exports' => [
            'name' => 'Data Exports',
            'description' => 'Export your data in various formats',
        ],
        'dashboard_builder' => [
            'name' => 'Dashboard Builder',
            'description' => 'Create custom dashboards with drag-and-drop widgets',
        ],
        'products' => [
            'name' => 'Products',
            'description' => 'Manage your product catalog and inventory',
        ],
        'creative_assets' => [
            'name' => 'Creative Assets',
            'description' => 'Store and manage your creative media library',
        ],
    ],

];
