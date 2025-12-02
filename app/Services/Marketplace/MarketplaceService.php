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
        // TODO: Implement actual subscription check
        // For now, return true to allow all apps during development
        $org = Org::find($orgId);

        if (!$org) {
            return false;
        }

        // Check for a subscription or premium flag
        // This would integrate with a billing/subscription system
        return $org->is_premium ?? true;
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
     * Clear cache for an organization.
     */
    public function clearCache(string $orgId): void
    {
        Cache::forget("org_{$orgId}_enabled_apps");
        Cache::forget("org_{$orgId}_sidebar_navigation");
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
