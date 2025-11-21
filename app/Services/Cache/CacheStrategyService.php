<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache Strategy Service
 *
 * Provides intelligent caching strategies for different data types
 * with automatic invalidation and cache warming.
 *
 * Part of Phase 1B performance optimizations (2025-11-21)
 */
class CacheStrategyService
{
    /**
     * Cache TTL configurations by data type
     */
    protected array $ttlConfig = [
        'platform_data' => 900,      // 15 minutes
        'analytics' => 300,           // 5 minutes
        'user_preferences' => 3600,   // 1 hour
        'ai_embeddings' => 86400,     // 24 hours (permanent until updated)
        'campaign_stats' => 600,      // 10 minutes
        'feature_flags' => 300,       // 5 minutes
        'quota_status' => 60,         // 1 minute (real-time)
    ];

    /**
     * Remember with automatic cache key generation
     *
     * @param string $type Cache type from $ttlConfig
     * @param string $identifier Unique identifier
     * @param callable $callback Data fetching callback
     * @param int|null $customTtl Optional custom TTL
     * @return mixed
     */
    public function remember(string $type, string $identifier, callable $callback, ?int $customTtl = null): mixed
    {
        $key = $this->buildCacheKey($type, $identifier);
        $ttl = $customTtl ?? $this->ttlConfig[$type] ?? 300;

        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Cache remember failed', [
                'key' => $key,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            // Return fresh data on cache failure
            return $callback();
        }
    }

    /**
     * Put data in cache with automatic key generation
     *
     * @param string $type
     * @param string $identifier
     * @param mixed $value
     * @param int|null $customTtl
     * @return bool
     */
    public function put(string $type, string $identifier, mixed $value, ?int $customTtl = null): bool
    {
        $key = $this->buildCacheKey($type, $identifier);
        $ttl = $customTtl ?? $this->ttlConfig[$type] ?? 300;

        try {
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::error('Cache put failed', [
                'key' => $key,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get cached value
     *
     * @param string $type
     * @param string $identifier
     * @param mixed $default
     * @return mixed
     */
    public function get(string $type, string $identifier, mixed $default = null): mixed
    {
        $key = $this->buildCacheKey($type, $identifier);

        return Cache::get($key, $default);
    }

    /**
     * Forget (invalidate) cache
     *
     * @param string $type
     * @param string $identifier
     * @return bool
     */
    public function forget(string $type, string $identifier): bool
    {
        $key = $this->buildCacheKey($type, $identifier);

        return Cache::forget($key);
    }

    /**
     * Flush all cache for a specific type
     *
     * @param string $type
     * @return int Number of keys deleted
     */
    public function flushType(string $type): int
    {
        $pattern = "cmis:cache:{$type}:*";
        $deleted = 0;

        try {
            // Get all keys matching pattern
            $keys = Cache::getRedis()->keys($pattern);

            foreach ($keys as $key) {
                if (Cache::forget($key)) {
                    $deleted++;
                }
            }
        } catch (\Exception $e) {
            Log::error('Cache flush type failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }

        return $deleted;
    }

    /**
     * Cache with tags (for grouped invalidation)
     *
     * @param array $tags
     * @param string $key
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    public function tags(array $tags, string $key, callable $callback, int $ttl = 300): mixed
    {
        try {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Tagged cache failed', [
                'tags' => $tags,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * Flush all cache for specific tags
     *
     * @param array $tags
     * @return bool
     */
    public function flushTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            Log::error('Cache flush tags failed', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build standardized cache key
     *
     * @param string $type
     * @param string $identifier
     * @return string
     */
    protected function buildCacheKey(string $type, string $identifier): string
    {
        // Format: cmis:cache:{type}:{identifier}
        return sprintf('cmis:cache:%s:%s', $type, $identifier);
    }

    /**
     * Warm cache for frequently accessed data
     *
     * @param array $warmers Array of ['type' => 'identifier' => callback]
     * @return array Statistics
     */
    public function warmCache(array $warmers): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($warmers as $type => $items) {
            foreach ($items as $identifier => $callback) {
                $stats['total']++;

                try {
                    $this->remember($type, $identifier, $callback);
                    $stats['success']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    Log::warning('Cache warming failed', [
                        'type' => $type,
                        'identifier' => $identifier,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $stats;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        try {
            $redis = Cache::getRedis();

            return [
                'memory_used' => $redis->info('memory')['used_memory_human'] ?? 'Unknown',
                'total_keys' => $redis->dbSize() ?? 0,
                'hit_rate' => $this->calculateHitRate(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve cache stats',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate approximate cache hit rate
     *
     * @return float
     */
    protected function calculateHitRate(): float
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info('stats');

            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;

            return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}
