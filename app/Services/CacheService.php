<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    const TTL_SHORT = 300;      // 5 minutes
    const TTL_MEDIUM = 3600;    // 1 hour
    const TTL_LONG = 86400;     // 24 hours
    const TTL_WEEK = 604800;    // 7 days

    /**
     * Cache key prefixes
     */
    const PREFIX_USER = 'user';
    const PREFIX_ORG = 'org';
    const PREFIX_CAMPAIGN = 'campaign';
    const PREFIX_CONTENT = 'content';
    const PREFIX_KNOWLEDGE = 'knowledge';
    const PREFIX_ANALYTICS = 'analytics';
    const PREFIX_AD = 'ad';

    /**
     * Remember a value in cache or retrieve from callback.
     *
     * @param string $key
     * @param int $ttl Time to live in seconds
     * @param callable $callback
     * @return mixed
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Remember a value in cache forever or retrieve from callback.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return Cache::rememberForever($key, $callback);
    }

    /**
     * Store a value in cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, int $ttl = self::TTL_MEDIUM): bool
    {
        return Cache::put($key, $value, $ttl);
    }

    /**
     * Get a value from cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Check if key exists in cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Remove a value from cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Invalidate cache by pattern.
     *
     * @param string $pattern Pattern to match (e.g., "org:123:*")
     * @return int Number of keys deleted
     */
    public function invalidate(string $pattern): int
    {
        try {
            // Get Redis connection
            $redis = Redis::connection();

            // Find all keys matching pattern
            $keys = $redis->keys(config('cache.prefix') . ':' . $pattern);

            if (empty($keys)) {
                return 0;
            }

            // Remove cache prefix from keys
            $prefix = config('cache.prefix') . ':';
            $keysWithoutPrefix = array_map(function($key) use ($prefix) {
                return str_replace($prefix, '', $key);
            }, $keys);

            // Delete all matching keys
            foreach ($keysWithoutPrefix as $key) {
                Cache::forget($key);
            }

            return count($keys);
        } catch (\Exception $e) {
            \Log::warning('Cache invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Cache with tags (for easier bulk invalidation).
     *
     * @param array $tags
     * @return \Illuminate\Cache\TaggedCache
     */
    public function tags(array $tags): \Illuminate\Cache\TaggedCache
    {
        return Cache::tags($tags);
    }

    /**
     * Flush all cache.
     *
     * ⚠️ Use with caution in production
     *
     * @return bool
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Build a cache key for a user.
     *
     * @param string $userId
     * @param string $suffix
     * @return string
     */
    public function userKey(string $userId, string $suffix): string
    {
        return self::PREFIX_USER . ":{$userId}:{$suffix}";
    }

    /**
     * Build a cache key for an organization.
     *
     * @param string $orgId
     * @param string $suffix
     * @return string
     */
    public function orgKey(string $orgId, string $suffix): string
    {
        return self::PREFIX_ORG . ":{$orgId}:{$suffix}";
    }

    /**
     * Build a cache key for a campaign.
     *
     * @param string $campaignId
     * @param string $suffix
     * @return string
     */
    public function campaignKey(string $campaignId, string $suffix): string
    {
        return self::PREFIX_CAMPAIGN . ":{$campaignId}:{$suffix}";
    }

    /**
     * Build a cache key for content.
     *
     * @param string $contentId
     * @param string $suffix
     * @return string
     */
    public function contentKey(string $contentId, string $suffix): string
    {
        return self::PREFIX_CONTENT . ":{$contentId}:{$suffix}";
    }

    /**
     * Build a cache key for knowledge base.
     *
     * @param string $knowledgeId
     * @param string $suffix
     * @return string
     */
    public function knowledgeKey(string $knowledgeId, string $suffix): string
    {
        return self::PREFIX_KNOWLEDGE . ":{$knowledgeId}:{$suffix}";
    }

    /**
     * Build a cache key for analytics.
     *
     * @param string $resourceId
     * @param string $suffix
     * @return string
     */
    public function analyticsKey(string $resourceId, string $suffix): string
    {
        return self::PREFIX_ANALYTICS . ":{$resourceId}:{$suffix}";
    }

    /**
     * Build a cache key for ads.
     *
     * @param string $adId
     * @param string $suffix
     * @return string
     */
    public function adKey(string $adId, string $suffix): string
    {
        return self::PREFIX_AD . ":{$adId}:{$suffix}";
    }

    /**
     * Invalidate all cache for an organization.
     *
     * @param string $orgId
     * @return int
     */
    public function invalidateOrg(string $orgId): int
    {
        return $this->invalidate(self::PREFIX_ORG . ":{$orgId}:*");
    }

    /**
     * Invalidate all cache for a user.
     *
     * @param string $userId
     * @return int
     */
    public function invalidateUser(string $userId): int
    {
        return $this->invalidate(self::PREFIX_USER . ":{$userId}:*");
    }

    /**
     * Invalidate all cache for a campaign.
     *
     * @param string $campaignId
     * @return int
     */
    public function invalidateCampaign(string $campaignId): int
    {
        return $this->invalidate(self::PREFIX_CAMPAIGN . ":{$campaignId}:*");
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function stats(): array
    {
        try {
            $redis = Redis::connection();

            $info = $redis->info('stats');

            return [
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
                'keys_count' => $redis->dbSize(),
                'memory_used' => $redis->info('memory')['used_memory_human'] ?? 'N/A',
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Could not retrieve cache stats',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate cache hit rate.
     *
     * @param array $info
     * @return float
     */
    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;

        $total = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return round(($hits / $total) * 100, 2);
    }
}
