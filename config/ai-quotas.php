<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Usage Quotas Configuration
    |--------------------------------------------------------------------------
    |
    | Define default quotas for different subscription tiers
    | to control AI API costs and prevent abuse.
    |
    | Created: 2025-11-21 as part of weakness remediation plan
    |
    */

    /**
     * Enable/disable quota enforcement
     */
    'enabled' => env('AI_QUOTA_ENABLED', true),

    /**
     * Default tier quotas
     * These are used as templates when creating org-specific quotas
     */
    'tiers' => [
        'free' => [
            'gpt' => [
                'daily_limit' => 5,
                'monthly_limit' => 100,
                'cost_limit_monthly' => 10.00, // USD
                'description' => 'Free tier - 5 AI generations per day',
            ],
            'embeddings' => [
                'daily_limit' => 20,
                'monthly_limit' => 500,
                'cost_limit_monthly' => 5.00,
                'description' => 'Free tier embeddings',
            ],
        ],

        'pro' => [
            'gpt' => [
                'daily_limit' => 50,
                'monthly_limit' => 1000,
                'cost_limit_monthly' => 100.00,
                'description' => 'Pro tier - 50 AI generations per day',
            ],
            'embeddings' => [
                'daily_limit' => 100,
                'monthly_limit' => 2500,
                'cost_limit_monthly' => 20.00,
                'description' => 'Pro tier embeddings',
            ],
        ],

        'enterprise' => [
            'gpt' => [
                'daily_limit' => 999999, // Essentially unlimited
                'monthly_limit' => 999999,
                'cost_limit_monthly' => 1000.00,
                'description' => 'Enterprise tier - Unlimited (fair use)',
            ],
            'embeddings' => [
                'daily_limit' => 999999,
                'monthly_limit' => 999999,
                'cost_limit_monthly' => 100.00,
                'description' => 'Enterprise embeddings',
            ],
        ],
    ],

    /**
     * Cost per 1K tokens (USD) for different models
     * Source: OpenAI pricing as of 2025-01
     */
    'costs' => [
        'gpt-4' => [
            'input' => 0.03,
            'output' => 0.06,
        ],
        'gpt-4-turbo' => [
            'input' => 0.01,
            'output' => 0.03,
        ],
        'gpt-4-turbo-preview' => [
            'input' => 0.01,
            'output' => 0.03,
        ],
        'gpt-3.5-turbo' => [
            'input' => 0.0005,
            'output' => 0.0015,
        ],
        'text-embedding-004' => [
            'input' => 0.0001,
            'output' => 0,
        ],
        'text-embedding-3-small' => [
            'input' => 0.00002,
            'output' => 0,
        ],
        'text-embedding-3-large' => [
            'input' => 0.00013,
            'output' => 0,
        ],
    ],

    /**
     * Alert thresholds
     * Send notifications when usage reaches these percentages
     */
    'alerts' => [
        'warning' => 80, // 80% of quota used
        'critical' => 95, // 95% of quota used
    ],

    /**
     * Cache settings for quota checks
     */
    'cache' => [
        'enabled' => true,
        'ttl' => 60, // seconds - balance between performance and accuracy
    ],

    /**
     * Rate limiting
     * Additional protection beyond quotas
     */
    'rate_limits' => [
        'gpt' => [
            'per_minute' => 10,
            'per_hour' => 100,
        ],
        'embeddings' => [
            'per_minute' => 30,
            'per_hour' => 500,
        ],
    ],

    /**
     * Monitoring & Analytics
     */
    'monitoring' => [
        'enabled' => true,
        'log_all_requests' => env('AI_LOG_ALL_REQUESTS', true),
        'slow_query_threshold' => 5000, // ms - log if AI request takes longer
    ],

    /**
     * Automatic quota upgrade suggestions
     * When user hits limits, suggest appropriate tier
     */
    'upgrade_suggestions' => [
        'enabled' => true,
        'show_after_hits' => 3, // Show upgrade prompt after 3 quota hits
    ],

    /**
     * Quota reset schedule
     */
    'reset_schedule' => [
        'daily' => '00:00', // Midnight UTC
        'monthly' => 1, // Day of month
    ],

    /**
     * Feature-specific quotas
     * Override general quotas for specific features
     */
    'features' => [
        'campaign_generation' => [
            'free' => 3, // Only 3 AI campaigns per day on free tier
            'pro' => 30,
            'enterprise' => 999999,
        ],
        'content_rewriting' => [
            'free' => 5,
            'pro' => 50,
            'enterprise' => 999999,
        ],
        'audience_analysis' => [
            'free' => 2,
            'pro' => 20,
            'enterprise' => 999999,
        ],
    ],

];
