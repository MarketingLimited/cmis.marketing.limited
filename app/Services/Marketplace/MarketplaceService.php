<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\AppCategory;
use App\Models\Marketplace\MarketplaceApp;
use App\Models\Marketplace\OrganizationApp;
use App\Models\Core\Org;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Marketplace Service
 *
 * Handles all business logic for the Apps Marketplace including:
 * - Enabling/disabling apps per organization
 * - Managing app dependencies
 * - Checking app access permissions
 * - Premium subscription validation
 */
class MarketplaceService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    protected const CACHE_TTL = 300;

    /**
     * Get all available apps, optionally filtered by category.
     */
    public function getAvailableApps(?string $category = null): Collection
    {
        $query = MarketplaceApp::active()->ordered();

        if ($category) {
            $query->inCategory($category);
        }

        return $query->get();
    }

    /**
     * Get all app categories with their apps.
     */
    public function getCategoriesWithApps(): Collection
    {
        return AppCategory::active()
            ->ordered()
            ->with(['apps' => function ($query) {
                $query->active()->ordered();
            }])
            ->get();
    }

    /**
     * Get enabled apps for an organization (cached).
     */
    public function getEnabledApps(string $orgId): Collection
    {
        $cacheKey = "org_{$orgId}_enabled_apps";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($orgId) {
            // Core apps are always enabled
            $coreApps = MarketplaceApp::active()->core()->get();

            // Get explicitly enabled optional apps
            $enabledAppIds = OrganizationApp::withoutGlobalScopes()
                ->where('org_id', $orgId)
                ->enabled()
                ->pluck('app_id');

            $enabledOptionalApps = MarketplaceApp::active()
                ->optional()
                ->whereIn('app_id', $enabledAppIds)
                ->get();

            return $coreApps->merge($enabledOptionalApps)->sortBy('sort_order');
        });
    }

    /**
     * Get enabled app slugs for an organization.
     */
    public function getEnabledAppSlugs(string $orgId): array
    {
        return $this->getEnabledApps($orgId)->pluck('slug')->toArray();
    }

    /**
     * Check if an app is enabled for an organization.
     */
    public function isAppEnabled(string $orgId, string $appSlug): bool
    {
        // Get the app
        $app = MarketplaceApp::findBySlug($appSlug);

        if (!$app || !$app->is_active) {
            return false;
        }

        // Core apps are always enabled
        if ($app->is_core) {
            return true;
        }

        // Check organization_apps table
        return OrganizationApp::withoutGlobalScopes()
            ->where('org_id', $orgId)
            ->where('app_id', $app->app_id)
            ->enabled()
            ->exists();
    }

    /**
     * Enable an app for an organization.
     * Auto-enables dependencies.
     *
     * @return array ['success' => bool, 'enabled' => array, 'message' => string]
     */
    public function enableApp(string $orgId, string $appSlug, string $userId): array
    {
        $app = MarketplaceApp::findBySlug($appSlug);

        if (!$app) {
            return [
                'success' => false,
                'enabled' => [],
                'message' => __('marketplace.app_not_found'),
            ];
        }

        if ($app->is_core) {
            return [
                'success' => false,
                'enabled' => [],
                'message' => __('marketplace.cannot_modify_core_app'),
            ];
        }

        // Check premium access
        if ($app->is_premium && !$this->hasPremiumAccess($orgId)) {
            return [
                'success' => false,
                'enabled' => [],
                'message' => __('marketplace.premium_required'),
            ];
        }

        $enabledApps = [];

        DB::transaction(function () use ($orgId, $app, $userId, &$enabledApps) {
            // Get all dependencies (including nested)
            $allDependencies = $app->getAllDependencies();

            // Enable dependencies first
            foreach ($allDependencies as $depSlug) {
                $depApp = MarketplaceApp::findBySlug($depSlug);
                if ($depApp && !$this->isAppEnabled($orgId, $depSlug)) {
                    $this->enableSingleApp($orgId, $depApp->app_id, $userId);
                    $enabledApps[] = $depSlug;
                }
            }

            // Enable the requested app
            $this->enableSingleApp($orgId, $app->app_id, $userId);
            $enabledApps[] = $app->slug;
        });

        $this->clearCache($orgId);

        return [
            'success' => true,
            'enabled' => $enabledApps,
            'message' => count($enabledApps) > 1
                ? __('marketplace.app_enabled_with_dependencies', [
                    'app' => $app->name,
                    'dependencies' => implode(', ', array_slice($enabledApps, 0, -1))
                ])
                : __('marketplace.app_enabled', ['app' => $app->name]),
        ];
    }

    /**
     * Disable an app for an organization.
     * Prevents disabling if other enabled apps depend on it.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function disableApp(string $orgId, string $appSlug, string $userId): array
    {
        $app = MarketplaceApp::findBySlug($appSlug);

        if (!$app) {
            return [
                'success' => false,
                'message' => __('marketplace.app_not_found'),
            ];
        }

        if ($app->is_core) {
            return [
                'success' => false,
                'message' => __('marketplace.cannot_modify_core_app'),
            ];
        }

        // Check for dependent apps
        $dependents = $this->getEnabledDependents($orgId, $appSlug);

        if (!empty($dependents)) {
            return [
                'success' => false,
                'message' => __('marketplace.cannot_disable_has_dependents', [
                    'apps' => implode(', ', $dependents)
                ]),
            ];
        }

        // Disable the app
        $orgApp = OrganizationApp::withoutGlobalScopes()
            ->where('org_id', $orgId)
            ->where('app_id', $app->app_id)
            ->first();

        if ($orgApp) {
            $orgApp->disable($userId);
        }

        $this->clearCache($orgId);

        return [
            'success' => true,
            'message' => __('marketplace.app_disabled', ['app' => $app->name]),
        ];
    }

    /**
     * Get enabled apps that depend on a given app.
     */
    public function getEnabledDependents(string $orgId, string $appSlug): array
    {
        $enabledSlugs = $this->getEnabledAppSlugs($orgId);

        $dependents = [];

        foreach ($enabledSlugs as $slug) {
            $app = MarketplaceApp::findBySlug($slug);
            if ($app && in_array($appSlug, $app->dependencies ?? [])) {
                $dependents[] = $app->name;
            }
        }

        return $dependents;
    }

    /**
     * Resolve all dependencies for an app (recursive).
     */
    public function resolveDependencies(string $appSlug): array
    {
        $app = MarketplaceApp::findBySlug($appSlug);

        if (!$app) {
            return [];
        }

        return $app->getAllDependencies();
    }

    /**
     * Get apps that depend on a given app.
     */
    public function getDependentApps(string $appSlug): Collection
    {
        return MarketplaceApp::active()
            ->whereJsonContains('dependencies', $appSlug)
            ->get();
    }

    /**
     * Check if organization has premium subscription access.
     */
    public function hasPremiumAccess(string $orgId): bool
    {
        $org = Org::find($orgId);

        if (!$org) {
            return false;
        }

        // Get the active subscription
        $subscription = $org->subscription;

        if (!$subscription) {
            // No subscription - check if org has is_premium flag (legacy support)
            return $org->is_premium ?? false;
        }

        // Check if subscription is valid (active or on trial)
        if (!$subscription->isValid()) {
            return false;
        }

        // Get the plan and check for premium access
        $plan = $subscription->plan;

        if (!$plan) {
            return false;
        }

        // Check various indicators of premium access:
        // 1. Plan has 'premium' or 'premium_apps' feature enabled
        if ($plan->hasFeature('premium') || $plan->hasFeature('premium_apps')) {
            return true;
        }

        // 2. Plan code indicates premium level (professional, enterprise, business)
        $premiumPlanCodes = ['premium', 'professional', 'enterprise', 'business', 'unlimited'];
        if (in_array(strtolower($plan->code), $premiumPlanCodes)) {
            return true;
        }

        // 3. Plan name contains premium indicators
        $planNameLower = strtolower($plan->name);
        foreach ($premiumPlanCodes as $indicator) {
            if (str_contains($planNameLower, $indicator)) {
                return true;
            }
        }

        // 4. Check if plan price is above free tier (paid plans get premium access)
        if ($plan->price_monthly > 0) {
            return true;
        }

        return false;
    }

    /**
     * Initialize marketplace apps for a new organization.
     *
     * @param bool $detectUsage If true, enables apps based on existing data
     */
    public function initializeForOrg(string $orgId, string $userId, bool $detectUsage = true): void
    {
        if ($detectUsage) {
            $this->enableAppsBasedOnUsage($orgId, $userId);
        }
    }

    /**
     * Enable apps based on existing data usage.
     */
    protected function enableAppsBasedOnUsage(string $orgId, string $userId): void
    {
        // Define usage detection queries for each app
        // These check if the org has any data that would indicate they use this feature
        $usageChecks = [
            'campaigns' => function ($orgId) {
                return DB::table('cmis.campaigns')
                    ->where('org_id', $orgId)
                    ->exists();
            },
            'analytics' => function ($orgId) {
                return DB::table('cmis.analytics_reports')
                    ->where('org_id', $orgId)
                    ->exists();
            },
            'audiences' => function ($orgId) {
                return DB::table('cmis_meta.audiences')
                    ->where('org_id', $orgId)
                    ->exists();
            },
            'creative-assets' => function ($orgId) {
                return DB::table('cmis.creative_assets')
                    ->where('org_id', $orgId)
                    ->exists();
            },
            'products' => function ($orgId) {
                return DB::table('cmis.products')
                    ->where('org_id', $orgId)
                    ->exists();
            },
            'workflows' => function ($orgId) {
                return DB::table('cmis.workflows')
                    ->where('org_id', $orgId)
                    ->exists();
            },
            'alerts' => function ($orgId) {
                return DB::table('cmis.alerts')
                    ->where('org_id', $orgId)
                    ->exists();
            },
        ];

        foreach ($usageChecks as $appSlug => $check) {
            try {
                if ($check($orgId)) {
                    $this->enableApp($orgId, $appSlug, $userId);
                    Log::info("Marketplace: Auto-enabled {$appSlug} for org {$orgId} based on usage");
                }
            } catch (\Exception $e) {
                // Table might not exist, skip silently
                Log::debug("Marketplace: Could not check usage for {$appSlug}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get marketplace status for an organization.
     */
    public function getStatus(string $orgId): array
    {
        $allApps = $this->getAvailableApps();
        $enabledSlugs = $this->getEnabledAppSlugs($orgId);
        $hasPremium = $this->hasPremiumAccess($orgId);

        return [
            'total_apps' => $allApps->count(),
            'enabled_count' => count($enabledSlugs),
            'enabled_apps' => $enabledSlugs,
            'has_premium' => $hasPremium,
        ];
    }

    /**
     * Get usage stats for all apps in an organization.
     * Returns array keyed by app slug with enabled_at, enabled_by_name, settings.
     */
    public function getAppUsageStats(string $orgId): array
    {
        $orgApps = OrganizationApp::withoutGlobalScopes()
            ->where('org_id', $orgId)
            ->with(['enabledByUser', 'app'])
            ->get();

        $stats = [];
        foreach ($orgApps as $orgApp) {
            if ($orgApp->app) {
                $stats[$orgApp->app->slug] = [
                    'enabled_at' => $orgApp->enabled_at?->toIso8601String(),
                    'enabled_at_human' => $orgApp->enabled_at?->diffForHumans(),
                    'enabled_by_name' => $orgApp->enabledByUser?->name ?? __('common.system'),
                    'disabled_at' => $orgApp->disabled_at?->toIso8601String(),
                    'disabled_at_human' => $orgApp->disabled_at?->diffForHumans(),
                    'is_enabled' => $orgApp->is_enabled,
                    'settings' => $orgApp->settings ?? [],
                ];
            }
        }

        return $stats;
    }

    /**
     * Get settings for a specific app in an organization.
     */
    public function getAppSettings(string $orgId, string $appSlug): array
    {
        $app = MarketplaceApp::findBySlug($appSlug);
        if (!$app) {
            return [];
        }

        $orgApp = OrganizationApp::withoutGlobalScopes()
            ->where('org_id', $orgId)
            ->where('app_id', $app->app_id)
            ->first();

        return $orgApp?->settings ?? [];
    }

    /**
     * Update settings for a specific app in an organization.
     */
    public function updateAppSettings(string $orgId, string $appSlug, array $settings): array
    {
        $app = MarketplaceApp::findBySlug($appSlug);
        if (!$app) {
            return [
                'success' => false,
                'message' => __('marketplace.app_not_found'),
            ];
        }

        $orgApp = OrganizationApp::withoutGlobalScopes()
            ->where('org_id', $orgId)
            ->where('app_id', $app->app_id)
            ->first();

        if (!$orgApp) {
            return [
                'success' => false,
                'message' => __('marketplace.app_not_enabled'),
            ];
        }

        // Merge with existing settings
        $existingSettings = $orgApp->settings ?? [];
        $mergedSettings = array_merge($existingSettings, $settings);

        $orgApp->update(['settings' => $mergedSettings]);

        $this->clearCache($orgId);

        return [
            'success' => true,
            'settings' => $mergedSettings,
            'message' => __('marketplace.settings_updated'),
        ];
    }

    /**
     * Clear cache for an organization.
     */
    public function clearCache(string $orgId): void
    {
        Cache::forget("org_{$orgId}_enabled_apps");
        Cache::forget("org_{$orgId}_sidebar_navigation");
    }

    /**
     * Bulk enable multiple apps for an organization.
     *
     * @param array $slugs Array of app slugs to enable
     * @return array ['success' => bool, 'enabled' => int, 'message' => string]
     */
    public function bulkEnable(string $orgId, array $slugs, string $userId): array
    {
        $enabledCount = 0;
        $errors = [];

        foreach ($slugs as $slug) {
            $result = $this->enableApp($orgId, $slug, $userId);
            if ($result['success']) {
                $enabledCount++;
            } else {
                $errors[] = $slug . ': ' . $result['message'];
            }
        }

        $this->clearCache($orgId);

        return [
            'success' => $enabledCount > 0,
            'enabled' => $enabledCount,
            'errors' => $errors,
            'message' => __('marketplace.bulk_enabled', ['count' => $enabledCount]),
        ];
    }

    /**
     * Bulk disable multiple apps for an organization.
     *
     * @param array $slugs Array of app slugs to disable
     * @return array ['success' => bool, 'disabled' => int, 'message' => string]
     */
    public function bulkDisable(string $orgId, array $slugs, string $userId): array
    {
        $disabledCount = 0;
        $errors = [];

        // Sort by dependencies - disable dependent apps first
        $sorted = $this->sortByDependencies($slugs, true);

        foreach ($sorted as $slug) {
            $result = $this->disableApp($orgId, $slug, $userId);
            if ($result['success']) {
                $disabledCount++;
            } else {
                $errors[] = $slug . ': ' . $result['message'];
            }
        }

        $this->clearCache($orgId);

        return [
            'success' => $disabledCount > 0,
            'disabled' => $disabledCount,
            'errors' => $errors,
            'message' => __('marketplace.bulk_disabled', ['count' => $disabledCount]),
        ];
    }

    /**
     * Sort app slugs by their dependencies.
     *
     * @param bool $reverse If true, dependents come first (for disabling)
     */
    protected function sortByDependencies(array $slugs, bool $reverse = false): array
    {
        $sorted = [];
        $remaining = $slugs;

        while (!empty($remaining)) {
            foreach ($remaining as $key => $slug) {
                $app = MarketplaceApp::findBySlug($slug);
                if (!$app) {
                    unset($remaining[$key]);
                    continue;
                }

                $deps = $app->dependencies ?? [];
                $hasPendingDeps = !empty(array_intersect($deps, $remaining));

                if (!$hasPendingDeps || empty($deps)) {
                    $sorted[] = $slug;
                    unset($remaining[$key]);
                }
            }

            // Prevent infinite loop if circular dependency
            if (count($remaining) === count($slugs)) {
                $sorted = array_merge($sorted, $remaining);
                break;
            }
        }

        return $reverse ? array_reverse($sorted) : $sorted;
    }

    /**
     * Enable a single app (internal helper).
     */
    protected function enableSingleApp(string $orgId, string $appId, string $userId): void
    {
        OrganizationApp::withoutGlobalScopes()->updateOrCreate(
            [
                'org_id' => $orgId,
                'app_id' => $appId,
            ],
            [
                'is_enabled' => true,
                'enabled_at' => now(),
                'enabled_by' => $userId,
                'disabled_at' => null,
                'disabled_by' => null,
            ]
        );
    }
}
