<?php

/**
 * Organization Backup & Restore Configuration
 *
 * Supports local and cloud storage (Google Drive, OneDrive, Dropbox)
 * with plan-based limits and encryption options.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure backup storage locations. Local storage is always available.
    | Cloud storage requires additional configuration per organization.
    |
    */

    'storage' => [
        'default' => env('BACKUP_STORAGE_DISK', 'local'),

        'disks' => [
            'local' => [
                'driver' => 'local',
                'path' => storage_path('app/backups'),
                'visibility' => 'private',
            ],

            'google' => [
                'driver' => 'google',
                'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
                'refresh_token' => null, // Set per organization
                'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
            ],

            'onedrive' => [
                'driver' => 'onedrive',
                'client_id' => env('ONEDRIVE_CLIENT_ID'),
                'client_secret' => env('ONEDRIVE_CLIENT_SECRET'),
                'refresh_token' => null, // Set per organization
                'folder_path' => '/CMIS Backups',
            ],

            'dropbox' => [
                'driver' => 'dropbox',
                'app_key' => env('DROPBOX_APP_KEY'),
                'app_secret' => env('DROPBOX_APP_SECRET'),
                'access_token' => null, // Set per organization
                'folder_path' => '/CMIS Backups',
            ],
        ],

        // Temp directory for backup creation
        'temp_path' => storage_path('app/temp/backups'),

        // Clean temp files older than (hours)
        'temp_cleanup_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Configuration
    |--------------------------------------------------------------------------
    |
    | Default encryption settings for backups. Organizations can use
    | custom keys (Enterprise feature) or the system master key.
    |
    */

    'encryption' => [
        // Default encryption algorithm
        'algorithm' => 'aes-256-gcm',

        // Master key for system encryption (generate with: openssl rand -base64 32)
        'master_key' => env('BACKUP_MASTER_KEY'),

        // IV/Nonce length for AES-GCM
        'iv_length' => 12,

        // Authentication tag length
        'tag_length' => 16,

        // Custom keys feature (Enterprise only)
        'custom_keys_enabled' => env('BACKUP_CUSTOM_KEYS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Limits
    |--------------------------------------------------------------------------
    |
    | Backup limits per pricing plan. -1 means unlimited.
    | Plans: free, basic, pro, enterprise
    |
    */

    'plans' => [
        'free' => [
            'monthly_limit' => 2,
            'max_size_mb' => 500,
            'retention_days' => 7,
            'allowed_schedules' => [], // No scheduled backups
            'allowed_storage' => ['local'],
            'encryption_available' => false,
            'custom_keys' => false,
            'api_access' => false,
            'restore_types' => ['selective'],
        ],

        'basic' => [
            'monthly_limit' => 10,
            'max_size_mb' => 5120, // 5GB
            'retention_days' => 30,
            'allowed_schedules' => ['weekly', 'monthly'],
            'allowed_storage' => ['local'],
            'encryption_available' => true,
            'custom_keys' => false,
            'api_access' => false,
            'restore_types' => ['selective', 'merge'],
        ],

        'pro' => [
            'monthly_limit' => -1, // Unlimited
            'max_size_mb' => 51200, // 50GB
            'retention_days' => 90,
            'allowed_schedules' => ['daily', 'weekly', 'monthly'],
            'allowed_storage' => ['local', 'google', 'onedrive', 'dropbox'],
            'encryption_available' => true,
            'custom_keys' => false,
            'api_access' => true,
            'restore_types' => ['selective', 'merge', 'full'],
        ],

        'enterprise' => [
            'monthly_limit' => -1, // Unlimited
            'max_size_mb' => 512000, // 500GB
            'retention_days' => 365,
            'allowed_schedules' => ['hourly', 'daily', 'weekly', 'monthly'],
            'allowed_storage' => ['local', 'google', 'onedrive', 'dropbox'],
            'encryption_available' => true,
            'custom_keys' => true,
            'api_access' => true,
            'restore_types' => ['selective', 'merge', 'full'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Plan
    |--------------------------------------------------------------------------
    |
    | Default plan for organizations without a specific plan assigned.
    |
    */

    'default_plan' => env('BACKUP_DEFAULT_PLAN', 'free'),

    /*
    |--------------------------------------------------------------------------
    | Category Mapping
    |--------------------------------------------------------------------------
    |
    | Map database tables to user-friendly categories. Tables not listed
    | here will be auto-discovered and categorized by the Discovery Engine.
    |
    | Format: 'category_key' => ['schema.table1', 'schema.table2', ...]
    |
    */

    'category_mapping' => [
        'campaigns' => [
            'cmis.campaigns',
            'cmis.campaign_objectives',
            'cmis.campaign_budgets',
            'cmis.campaign_schedules',
            'cmis.campaign_targeting',
        ],

        'ad_content' => [
            'cmis.ad_creatives',
            'cmis.ad_copies',
            'cmis.ad_media',
            'cmis.creative_assets',
        ],

        'audiences' => [
            'cmis.audiences',
            'cmis.audience_segments',
            'cmis.audience_rules',
            'cmis.custom_audiences',
        ],

        'analytics' => [
            'cmis.unified_metrics',
            'cmis.analytics_reports',
            'cmis.performance_summaries',
        ],

        'social_posts' => [
            'cmis.social_posts',
            'cmis.post_schedules',
            'cmis.post_media',
            'cmis.post_comments',
        ],

        'content_plans' => [
            'cmis_creative.content_plans',
            'cmis_creative.content_plan_items',
            'cmis_creative.content_calendars',
        ],

        'integrations' => [
            'cmis_platform.platform_connections',
            'cmis_platform.platform_credentials',
            'cmis_platform.ad_accounts',
        ],

        'team_settings' => [
            'cmis.org_settings',
            'cmis.org_members',
            'cmis.org_roles',
            'cmis.org_permissions',
        ],

        'automations' => [
            'cmis.automation_rules',
            'cmis.automation_triggers',
            'cmis.automation_actions',
            'cmis.automation_logs',
        ],

        'reports' => [
            'cmis.saved_reports',
            'cmis.report_schedules',
            'cmis.report_templates',
            'cmis.dashboards',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Labels
    |--------------------------------------------------------------------------
    |
    | Translation keys for category labels (used in UI)
    |
    */

    'category_labels' => [
        'campaigns' => 'backup.categories.campaigns',
        'ad_content' => 'backup.categories.ad_content',
        'audiences' => 'backup.categories.audiences',
        'analytics' => 'backup.categories.analytics',
        'social_posts' => 'backup.categories.social_posts',
        'content_plans' => 'backup.categories.content_plans',
        'integrations' => 'backup.categories.integrations',
        'team_settings' => 'backup.categories.team_settings',
        'automations' => 'backup.categories.automations',
        'reports' => 'backup.categories.reports',
        'other' => 'backup.categories.other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic table discovery from information_schema.
    |
    */

    'discovery' => [
        // Schemas to scan for org_id tables
        'schemas' => [
            'cmis',
            'cmis_ai',
            'cmis_analytics',
            'cmis_creative',
            'cmis_platform',
            'cmis_google',
            'cmis_meta',
            'cmis_tiktok',
            'cmis_linkedin',
            'cmis_twitter',
            'cmis_snapchat',
        ],

        // Tables to always exclude from backups
        'excluded_tables' => [
            'cmis.backup_audit_logs',
            'cmis.backup_encryption_keys',
            'cmis.backup_restores',
            'cmis.backup_schedules',
            'cmis.backup_settings',
            'cmis.organization_backups',
            'cmis.migrations',
            'cmis.failed_jobs',
            'cmis.jobs',
            'cmis.sessions',
            'cmis.cache',
            'cmis.password_reset_tokens',
        ],

        // Pattern matching for auto-categorization
        'category_patterns' => [
            'campaigns' => ['campaign', 'ad_set', 'ad_group', 'ad_'],
            'social_posts' => ['social_post', 'post_media', 'post_comment'],
            'analytics' => ['metric', 'analytics', 'report', 'performance'],
            'audiences' => ['audience', 'segment', 'targeting'],
            'integrations' => ['integration', 'connection', 'credential', 'platform_'],
            'automations' => ['automation', 'trigger', 'action', 'rule'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extraction Settings
    |--------------------------------------------------------------------------
    |
    | Settings for data extraction during backup creation.
    |
    */

    'extraction' => [
        // Chunk size for large table extraction
        'chunk_size' => 1000,

        // Memory limit for extraction (MB)
        'memory_limit' => 512,

        // Timeout per table (seconds)
        'table_timeout' => 300,

        // File columns detection patterns
        'file_column_patterns' => [
            'file_path',
            'file_url',
            'image_url',
            'media_url',
            'attachment',
            'thumbnail',
            'avatar',
            'logo',
            'document',
            'asset_url',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Restore Settings
    |--------------------------------------------------------------------------
    |
    | Settings for restore operations.
    |
    */

    'restore' => [
        // Create safety backup before restore
        'create_safety_backup' => true,

        // Rollback window (hours) after restore completion
        'rollback_window_hours' => 24,

        // Batch size for restore operations
        'batch_size' => 500,

        // Timeout for full restore (seconds)
        'full_restore_timeout' => 3600,

        // Default conflict resolution strategy
        'default_conflict_strategy' => 'skip',

        // Confirmation methods by restore type
        'confirmation_methods' => [
            'selective' => 'simple',      // Just click confirm
            'merge' => 'org_name',        // Type organization name
            'full' => 'email_code',       // Verify via email code
        ],

        // Email code expiry (minutes)
        'email_code_expiry' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling Settings
    |--------------------------------------------------------------------------
    |
    | Settings for scheduled backups.
    |
    */

    'scheduling' => [
        // Default timezone for schedules
        'default_timezone' => 'UTC',

        // Max concurrent scheduled backups
        'max_concurrent' => 3,

        // Retry failed scheduled backups
        'retry_on_failure' => true,
        'max_retries' => 3,
        'retry_delay_minutes' => 30,

        // Grace period before marking overdue (minutes)
        'overdue_threshold_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Default notification settings (can be overridden per organization).
    |
    */

    'notifications' => [
        'email' => [
            'from_address' => env('BACKUP_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
            'from_name' => env('BACKUP_EMAIL_FROM_NAME', env('MAIL_FROM_NAME', 'CMIS Backup')),
        ],

        'defaults' => [
            'backup_complete' => true,
            'backup_failed' => true,
            'restore_started' => true,
            'restore_complete' => true,
            'restore_failed' => true,
            'backup_expiring' => true,
            'storage_warning' => true,
        ],

        // Days before expiry to send notification
        'expiry_warning_days' => 3,

        // Storage warning threshold (percentage)
        'storage_warning_percent' => 80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Settings
    |--------------------------------------------------------------------------
    |
    | Queue and job configuration for backup operations.
    |
    */

    'jobs' => [
        // Queue name for backup jobs
        'queue' => env('BACKUP_QUEUE', 'backups'),

        // Job timeout (seconds)
        'timeout' => 1800, // 30 minutes

        // Max attempts before failure
        'tries' => 3,

        // Backoff delays between retries (seconds)
        'backoff' => [60, 300, 900], // 1min, 5min, 15min

        // Cleanup expired backups (run daily)
        'cleanup_enabled' => true,
        'cleanup_batch_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Settings for backup API endpoints (Enterprise/Pro).
    |
    */

    'api' => [
        // Rate limiting
        'rate_limit' => [
            'requests_per_minute' => 10,
            'backup_requests_per_hour' => 5,
        ],

        // API response pagination
        'pagination' => [
            'default_per_page' => 15,
            'max_per_page' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration.
    |
    */

    'security' => [
        // Log all backup downloads
        'log_downloads' => true,

        // Require re-authentication for restore
        'require_reauth_for_restore' => false,

        // IP whitelist for API access (empty = allow all)
        'api_ip_whitelist' => [],

        // Checksum algorithm
        'checksum_algorithm' => 'sha256',
    ],

];
