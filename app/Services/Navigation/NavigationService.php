<?php

namespace App\Services\Navigation;

use App\Models\Marketplace\AppCategory;
use App\Models\Marketplace\MarketplaceApp;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

/**
 * Navigation Service
 *
 * Provides dynamic sidebar navigation based on enabled marketplace apps.
 * Handles navigation structure, active route detection, and caching.
 */
class NavigationService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    protected const CACHE_TTL = 300;

    protected MarketplaceService $marketplace;

    public function __construct(MarketplaceService $marketplace)
    {
        $this->marketplace = $marketplace;
    }

    /**
     * Get sidebar navigation items for an organization.
     * Returns a flat array of enabled apps for the sidebar.
     */
    public function getSidebarItems(string $orgId): array
    {
        $cacheKey = "org_{$orgId}_sidebar_navigation";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($orgId) {
            $enabledApps = $this->marketplace->getEnabledApps($orgId);

            return $enabledApps->map(function ($app) {
                return [
                    'slug' => $app->slug,
                    'name' => $app->name,
                    'name_key' => $app->name_key,
                    'icon' => $app->icon,
                    'route_name' => $app->route_name,
                    'route_prefix' => $app->route_prefix,
                    'category' => $app->category,
                    'is_core' => $app->is_core,
                    'is_premium' => $app->is_premium,
                    'metadata' => $app->metadata,
                ];
            })->toArray();
        });
    }

    /**
     * Get structured navigation grouped by category.
     */
    public function getStructuredNavigation(string $orgId): array
    {
        $items = $this->getSidebarItems($orgId);
        $categories = AppCategory::active()->ordered()->get();

        $structured = [];

        foreach ($categories as $category) {
            $categoryItems = array_filter($items, function ($item) use ($category) {
                return $item['category'] === $category->slug;
            });

            if (!empty($categoryItems)) {
                $structured[] = [
                    'category' => [
                        'slug' => $category->slug,
                        'name' => $category->name,
                        'name_key' => $category->name_key,
                        'icon' => $category->icon,
                    ],
                    'items' => array_values($categoryItems),
                    'is_core' => $category->slug === 'core',
                ];
            }
        }

        return $structured;
    }

    /**
     * Get only core navigation items (always visible).
     */
    public function getCoreNavigation(): array
    {
        return MarketplaceApp::active()
            ->core()
            ->ordered()
            ->get()
            ->map(function ($app) {
                return [
                    'slug' => $app->slug,
                    'name' => $app->name,
                    'name_key' => $app->name_key,
                    'icon' => $app->icon,
                    'route_name' => $app->route_name,
                    'route_prefix' => $app->route_prefix,
                    'is_core' => true,
                ];
            })
            ->toArray();
    }

    /**
     * Check if the current route matches a navigation item.
     */
    public function isRouteActive(string $routePrefix): bool
    {
        $currentRoute = Request::route();

        if (!$currentRoute) {
            return false;
        }

        $currentName = $currentRoute->getName() ?? '';
        $currentUri = $currentRoute->uri() ?? '';

        // Check route name prefix
        if (str_starts_with($currentName, "orgs.{$routePrefix}")) {
            return true;
        }

        // Check URI prefix
        if (str_contains($currentUri, "/{$routePrefix}")) {
            return true;
        }

        return false;
    }

    /**
     * Check if any of the given route prefixes match the current route.
     */
    public function isAnyRouteActive(array $routePrefixes): bool
    {
        foreach ($routePrefixes as $prefix) {
            if ($this->isRouteActive($prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get navigation item by route name.
     */
    public function getItemByRoute(string $routeName, string $orgId): ?array
    {
        $items = $this->getSidebarItems($orgId);

        foreach ($items as $item) {
            if ($item['route_name'] === $routeName) {
                return $item;
            }

            // Check sub-routes in metadata
            $subRoutes = $item['metadata']['sub_routes'] ?? [];
            if (in_array($routeName, $subRoutes)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Clear navigation cache for an organization.
     */
    public function clearCache(string $orgId): void
    {
        Cache::forget("org_{$orgId}_sidebar_navigation");
    }

    /**
     * Get navigation items for a specific category.
     */
    public function getItemsForCategory(string $orgId, string $category): array
    {
        $items = $this->getSidebarItems($orgId);

        return array_values(array_filter($items, function ($item) use ($category) {
            return $item['category'] === $category;
        }));
    }

    /**
     * Check if an organization has any enabled apps in a category.
     */
    public function hasCategoryItems(string $orgId, string $category): bool
    {
        return !empty($this->getItemsForCategory($orgId, $category));
    }
}
