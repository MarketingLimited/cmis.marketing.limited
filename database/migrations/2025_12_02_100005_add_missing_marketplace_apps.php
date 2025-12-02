<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add all missing marketplace apps discovered from codebase analysis.
     * Analysis covered both web routes (routes/web.php) and API routes (routes/api.php).
     */
    public function up(): void
    {
        $apps = [
            // ========================================
            // MARKETING CATEGORY APPS
            // ========================================
            [
                'slug' => 'keywords',
                'name_key' => 'marketplace.apps.keywords.name',
                'description_key' => 'marketplace.apps.keywords.description',
                'category' => 'marketing',
                'icon' => 'fa-key',
                'route_prefix' => 'keywords',
                'is_core' => false,
                'sort_order' => 50,
            ],
            [
                'slug' => 'catalogs',
                'name_key' => 'marketplace.apps.catalogs.name',
                'description_key' => 'marketplace.apps.catalogs.description',
                'category' => 'marketing',
                'icon' => 'fa-boxes',
                'route_prefix' => 'catalogs',
                'is_core' => false,
                'sort_order' => 51,
            ],
            [
                'slug' => 'ad-accounts',
                'name_key' => 'marketplace.apps.ad_accounts.name',
                'description_key' => 'marketplace.apps.ad_accounts.description',
                'category' => 'marketing',
                'icon' => 'fa-wallet',
                'route_prefix' => 'settings/ad-accounts',
                'is_core' => false,
                'sort_order' => 52,
            ],
            [
                'slug' => 'leads',
                'name_key' => 'marketplace.apps.leads.name',
                'description_key' => 'marketplace.apps.leads.description',
                'category' => 'marketing',
                'icon' => 'fa-user-tag',
                'route_prefix' => 'leads',
                'is_core' => false,
                'sort_order' => 53,
            ],

            // ========================================
            // SOCIAL CATEGORY APPS (NEW)
            // ========================================
            [
                'slug' => 'link-shortener',
                'name_key' => 'marketplace.apps.link_shortener.name',
                'description_key' => 'marketplace.apps.link_shortener.description',
                'category' => 'social',
                'icon' => 'fa-link',
                'route_prefix' => 'social/links',
                'is_core' => false,
                'sort_order' => 60,
            ],
            [
                'slug' => 'media-library',
                'name_key' => 'marketplace.apps.media_library.name',
                'description_key' => 'marketplace.apps.media_library.description',
                'category' => 'social',
                'icon' => 'fa-photo-video',
                'route_prefix' => 'social/media',
                'is_core' => false,
                'sort_order' => 61,
            ],
            [
                'slug' => 'queue-settings',
                'name_key' => 'marketplace.apps.queue_settings.name',
                'description_key' => 'marketplace.apps.queue_settings.description',
                'category' => 'social',
                'icon' => 'fa-clock',
                'route_prefix' => 'settings/queue-labels',
                'is_core' => false,
                'sort_order' => 62,
            ],
            [
                'slug' => 'scheduling',
                'name_key' => 'marketplace.apps.scheduling.name',
                'description_key' => 'marketplace.apps.scheduling.description',
                'category' => 'social',
                'icon' => 'fa-calendar-check',
                'route_prefix' => 'scheduling',
                'is_core' => false,
                'sort_order' => 63,
            ],

            // ========================================
            // CONTENT CATEGORY APPS (NEW)
            // ========================================
            [
                'slug' => 'brand-voices',
                'name_key' => 'marketplace.apps.brand_voices.name',
                'description_key' => 'marketplace.apps.brand_voices.description',
                'category' => 'content',
                'icon' => 'fa-microphone',
                'route_prefix' => 'settings/brand-voices',
                'is_core' => false,
                'sort_order' => 70,
            ],
            [
                'slug' => 'channels',
                'name_key' => 'marketplace.apps.channels.name',
                'description_key' => 'marketplace.apps.channels.description',
                'category' => 'content',
                'icon' => 'fa-broadcast-tower',
                'route_prefix' => 'channels',
                'is_core' => false,
                'sort_order' => 71,
            ],
            [
                'slug' => 'content-library',
                'name_key' => 'marketplace.apps.content_library.name',
                'description_key' => 'marketplace.apps.content_library.description',
                'category' => 'content',
                'icon' => 'fa-folder-open',
                'route_prefix' => 'content-library',
                'is_core' => false,
                'sort_order' => 72,
            ],
            [
                'slug' => 'content-briefs',
                'name_key' => 'marketplace.apps.content_briefs.name',
                'description_key' => 'marketplace.apps.content_briefs.description',
                'category' => 'content',
                'icon' => 'fa-clipboard-list',
                'route_prefix' => 'briefs',
                'is_core' => false,
                'sort_order' => 73,
            ],

            // ========================================
            // AUTOMATION CATEGORY APPS
            // ========================================
            [
                'slug' => 'approval-workflows',
                'name_key' => 'marketplace.apps.approval_workflows.name',
                'description_key' => 'marketplace.apps.approval_workflows.description',
                'category' => 'automation',
                'icon' => 'fa-check-double',
                'route_prefix' => 'settings/approval-workflows',
                'is_core' => false,
                'sort_order' => 80,
            ],
            [
                'slug' => 'boost-rules',
                'name_key' => 'marketplace.apps.boost_rules.name',
                'description_key' => 'marketplace.apps.boost_rules.description',
                'category' => 'automation',
                'icon' => 'fa-rocket',
                'route_prefix' => 'settings/boost-rules',
                'is_core' => false,
                'sort_order' => 81,
            ],

            // ========================================
            // COMPLIANCE CATEGORY APPS (NEW)
            // ========================================
            [
                'slug' => 'brand-safety',
                'name_key' => 'marketplace.apps.brand_safety.name',
                'description_key' => 'marketplace.apps.brand_safety.description',
                'category' => 'compliance',
                'icon' => 'fa-shield-alt',
                'route_prefix' => 'settings/brand-safety',
                'is_core' => false,
                'sort_order' => 90,
            ],
            [
                'slug' => 'audit-logs',
                'name_key' => 'marketplace.apps.audit_logs.name',
                'description_key' => 'marketplace.apps.audit_logs.description',
                'category' => 'compliance',
                'icon' => 'fa-clipboard-check',
                'route_prefix' => 'audit',
                'is_core' => false,
                'sort_order' => 91,
            ],

            // ========================================
            // ANALYTICS CATEGORY APPS
            // ========================================
            [
                'slug' => 'reports-builder',
                'name_key' => 'marketplace.apps.reports_builder.name',
                'description_key' => 'marketplace.apps.reports_builder.description',
                'category' => 'analytics',
                'icon' => 'fa-chart-bar',
                'route_prefix' => 'reports',
                'is_core' => false,
                'sort_order' => 100,
            ],

            // ========================================
            // FINANCE CATEGORY APPS (NEW)
            // ========================================
            [
                'slug' => 'budget-manager',
                'name_key' => 'marketplace.apps.budget_manager.name',
                'description_key' => 'marketplace.apps.budget_manager.description',
                'category' => 'finance',
                'icon' => 'fa-money-bill-wave',
                'route_prefix' => 'budget',
                'is_core' => false,
                'sort_order' => 110,
            ],

            // ========================================
            // SYSTEM CATEGORY APPS
            // ========================================
            [
                'slug' => 'platform-connections',
                'name_key' => 'marketplace.apps.platform_connections.name',
                'description_key' => 'marketplace.apps.platform_connections.description',
                'category' => 'system',
                'icon' => 'fa-plug',
                'route_prefix' => 'settings/platform-connections',
                'is_core' => false,
                'sort_order' => 120,
            ],
            [
                'slug' => 'feature-flags',
                'name_key' => 'marketplace.apps.feature_flags.name',
                'description_key' => 'marketplace.apps.feature_flags.description',
                'category' => 'system',
                'icon' => 'fa-flag',
                'route_prefix' => 'feature-flags',
                'is_core' => false,
                'sort_order' => 121,
            ],
            [
                'slug' => 'notifications',
                'name_key' => 'marketplace.apps.notifications.name',
                'description_key' => 'marketplace.apps.notifications.description',
                'category' => 'system',
                'icon' => 'fa-bell',
                'route_prefix' => 'notifications',
                'is_core' => false,
                'sort_order' => 122,
            ],
            [
                'slug' => 'api-management',
                'name_key' => 'marketplace.apps.api_management.name',
                'description_key' => 'marketplace.apps.api_management.description',
                'category' => 'system',
                'icon' => 'fa-code',
                'route_prefix' => 'api-keys',
                'is_core' => false,
                'sort_order' => 123,
            ],
        ];

        foreach ($apps as $app) {
            // Check if app exists
            $exists = DB::table('cmis.marketplace_apps')->where('slug', $app['slug'])->exists();

            if ($exists) {
                // Update existing app (don't change app_id)
                DB::table('cmis.marketplace_apps')
                    ->where('slug', $app['slug'])
                    ->update(array_merge($app, ['updated_at' => now()]));
            } else {
                // Insert new app with generated UUID
                DB::table('cmis.marketplace_apps')->insert(array_merge($app, [
                    'app_id' => \Illuminate\Support\Str::uuid()->toString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugsToRemove = [
            'keywords', 'catalogs', 'ad-accounts', 'leads',
            'link-shortener', 'media-library', 'queue-settings', 'scheduling',
            'brand-voices', 'channels', 'content-library', 'content-briefs',
            'approval-workflows', 'boost-rules',
            'brand-safety', 'audit-logs',
            'reports-builder',
            'budget-manager',
            'platform-connections', 'feature-flags', 'notifications', 'api-management',
        ];

        DB::table('cmis.marketplace_apps')
            ->whereIn('slug', $slugsToRemove)
            ->delete();
    }
};
