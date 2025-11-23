<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\{Cache, Redis};
use App\Models\Core\Org;

/**
 * Centralized caching service with Redis
 * Provides consistent caching strategy across the application
 */
class CacheService
{
    // Cache TTLs (in seconds)
    const TTL_DASHBOARD = 900;      // 15 minutes
    const TTL_METRICS = 1800;       // 30 minutes
    const TTL_CAMPAIGNS = 3600;     // 1 hour
    const TTL_SYNC_STATUS = 300;    // 5 minutes
    const TTL_ANALYTICS = 3600;     // 1 hour
    const TTL_USER_DATA = 1800;     // 30 minutes

    /**
     * Get cache key with prefix
     */
    private function key(string $key): string
    {
        return config('app.name', 'cmis') . ':' . $key;
    }

    /**
     * Remember with automatic invalidation tags
     */
    public function remember(string $key, int $ttl, callable $callback, array $tags = []): mixed
    {
        $fullKey = $this->key($key);

        // Try to get from cache
        $value = Cache::get($fullKey);

        if ($value !== null) {
            return $value;
        }

        // Execute callback and cache result
        $value = $callback();
        Cache::put($fullKey, $value, $ttl);

        // Store tags for invalidation
        if (!empty($tags)) {
            $this->storeTags($fullKey, $tags);
        }

        return $value;
    }

    /**
     * Store cache tags for invalidation
     */
    private function storeTags(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = $this->key("tag:{$tag}");
            Redis::sadd($tagKey, $key);
            Redis::expire($tagKey, 86400); // 24 hours
        }
    }

    /**
     * Invalidate by tags
     */
    public function invalidateTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = $this->key("tag:{$tag}");
            $keys = Redis::smembers($tagKey);

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            Redis::del($tagKey);
        }
    }

    /**
     * Clear all caches for an organization
     */
    public function clearOrg(string $orgId): void
    {
        $this->invalidateTags([
            "org:{$orgId}",
            "dashboard:org:{$orgId}",
            "metrics:org:{$orgId}",
            "campaigns:org:{$orgId}",
            "sync:org:{$orgId}",
        ]);
    }

    /**
     * Cache dashboard data
     */
    public function cacheDashboard(Org $org, callable $callback): self
    {
        return $this->remember(
            "dashboard:org:{$org->org_id}",
            self::TTL_DASHBOARD,
            $callback,
            ["org:{$org->org_id}", "dashboard"]
        );
    }

    /**
     * Clear dashboard cache
     */
    public function clearDashboard(string $orgId): void
    {
        Cache::forget($this->key("dashboard:org:{$orgId}"));
        $this->invalidateTags(["dashboard", "org:{$orgId}"]);
    }

    /**
     * Cache campaign metrics
     */
    public function cacheMetrics(string $campaignId, callable $callback): self
    {
        return $this->remember(
            "metrics:campaign:{$campaignId}",
            self::TTL_METRICS,
            $callback,
            ["metrics", "campaign:{$campaignId}"]
        );
    }

    /**
     * Clear metrics cache
     */
    public function clearMetrics(string $campaignId): void
    {
        Cache::forget($this->key("metrics:campaign:{$campaignId}"));
        $this->invalidateTags(["metrics", "campaign:{$campaignId}"]);
    }

    /**
     * Cache campaigns list
     */
    public function cacheCampaigns(string $orgId, array $filters, callable $callback): self
    {
        $filterHash = md5(json_encode($filters));
        return $this->remember(
            "campaigns:org:{$orgId}:filters:{$filterHash}",
            self::TTL_CAMPAIGNS,
            $callback,
            ["campaigns", "org:{$orgId}"]
        );
    }

    /**
     * Clear campaigns cache
     */
    public function clearCampaigns(string $orgId): void
    {
        $this->invalidateTags(["campaigns", "org:{$orgId}"]);
    }

    /**
     * Cache sync status
     */
    public function cacheSyncStatus(string $orgId, callable $callback): self
    {
        return $this->remember(
            "sync:org:{$orgId}",
            self::TTL_SYNC_STATUS,
            $callback,
            ["sync", "org:{$orgId}"]
        );
    }

    /**
     * Clear sync status cache
     */
    public function clearSyncStatus(string $orgId): void
    {
        Cache::forget($this->key("sync:org:{$orgId}"));
        $this->invalidateTags(["sync", "org:{$orgId}"]);
    }

    /**
     * Cache analytics data
     */
    public function cacheAnalytics(string $orgId, string $type, array $params, callable $callback): self
    {
        $paramsHash = md5(json_encode($params));
        return $this->remember(
            "analytics:org:{$orgId}:type:{$type}:params:{$paramsHash}",
            self::TTL_ANALYTICS,
            $callback,
            ["analytics", "org:{$orgId}"]
        );
    }

    /**
     * Clear analytics cache
     */
    public function clearAnalytics(string $orgId): void
    {
        $this->invalidateTags(["analytics", "org:{$orgId}"]);
    }

    /**
     * Warm cache proactively
     */
    public function warmCache(Org $org, array $services = []): void
    {
        // This can be called during low-traffic periods
        // to pre-populate caches

        if (empty($services) || in_array('dashboard', $services)) {
            // Warm dashboard
            app(\App\Services\Dashboard\UnifiedDashboardService::class)
                ->getOrgDashboard($org);
        }

        // Add more cache warming logic as needed
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $info = Redis::info();

        return [
            'redis_version' => $info['redis_version'] ?? 'unknown',
            'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
            'connected_clients' => $info['connected_clients'] ?? 0,
            'total_commands_processed' => $info['total_commands_processed'] ?? 0,
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
            'hit_rate' => $this->calculateHitRate($info),
        ];
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Flush all application caches (use with caution!)
     */
    public function flushAll(): void
    {
        $prefix = config('app.name', 'cmis');
        $keys = Redis::keys("{$prefix}:*");

        foreach ($keys as $key) {
            Redis::del($key);
        }
    }
}
