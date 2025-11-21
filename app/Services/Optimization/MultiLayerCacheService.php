<?php

namespace App\Services\Optimization;

use Illuminate\Support\Facades\{Cache, Redis, Log};
use Carbon\Carbon;

/**
 * Multi-Layer Caching Service (Phase 6)
 *
 * Implements comprehensive caching strategies with multiple cache layers
 */
class MultiLayerCacheService
{
    // Cache layers
    const LAYER_MEMORY = 'memory';      // In-memory (APCu/Array)
    const LAYER_REDIS = 'redis';        // Redis cache
    const LAYER_DATABASE = 'database';  // Database cache fallback

    // Cache groups with TTLs (seconds)
    const CACHE_GROUPS = [
        'api_response' => 300,           // 5 minutes
        'query_result' => 600,           // 10 minutes
        'model_data' => 900,             // 15 minutes
        'analytics' => 1800,             // 30 minutes
        'platform_data' => 3600,         // 1 hour
        'user_session' => 7200,          // 2 hours
        'embeddings' => 86400,           // 24 hours
        'static_data' => 604800,         // 7 days
    ];

    // Cache key prefixes
    const PREFIX_API = 'api';
    const PREFIX_QUERY = 'qry';
    const PREFIX_MODEL = 'mdl';
    const PREFIX_ANALYTICS = 'anl';
    const PREFIX_PLATFORM = 'plt';
    const PREFIX_EMBEDDING = 'emb';

    protected array $memoryCache = [];
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0
    ];

    /**
     * Get cached value with multi-layer fallback
     *
     * @param string $key
     * @param string $group
     * @param callable|null $callback
     * @return mixed
     */
    public function get(string $key, string $group = 'query_result', ?callable $callback = null): mixed
    {
        $fullKey = $this->buildKey($key, $group);

        // Layer 1: Memory cache (fastest)
        if (isset($this->memoryCache[$fullKey])) {
            $this->stats['hits']++;
            Log::debug('Cache hit (memory)', ['key' => $fullKey]);
            return $this->memoryCache[$fullKey];
        }

        // Layer 2: Redis cache
        $value = Cache::get($fullKey);

        if ($value !== null) {
            // Store in memory cache for faster subsequent access
            $this->memoryCache[$fullKey] = $value;
            $this->stats['hits']++;
            Log::debug('Cache hit (redis)', ['key' => $fullKey]);
            return $value;
        }

        // Cache miss
        $this->stats['misses']++;
        Log::debug('Cache miss', ['key' => $fullKey]);

        // Generate value if callback provided
        if ($callback !== null) {
            $value = $callback();
            $this->put($key, $value, $group);
            return $value;
        }

        return null;
    }

    /**
     * Store value in cache with multi-layer strategy
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, string $group = 'query_result', ?int $ttl = null): bool
    {
        $fullKey = $this->buildKey($key, $group);
        $ttl = $ttl ?? self::CACHE_GROUPS[$group] ?? 600;

        try {
            // Store in memory cache
            $this->memoryCache[$fullKey] = $value;

            // Store in Redis with TTL
            Cache::put($fullKey, $value, $ttl);

            $this->stats['writes']++;
            Log::debug('Cache write', ['key' => $fullKey, 'ttl' => $ttl]);

            return true;

        } catch (\Exception $e) {
            Log::error('Cache write failed', [
                'key' => $fullKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Remember value with automatic caching
     *
     * @param string $key
     * @param callable $callback
     * @param string $group
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, callable $callback, string $group = 'query_result', ?int $ttl = null): mixed
    {
        $fullKey = $this->buildKey($key, $group);
        $ttl = $ttl ?? self::CACHE_GROUPS[$group] ?? 600;

        // Check memory cache first
        if (isset($this->memoryCache[$fullKey])) {
            $this->stats['hits']++;
            return $this->memoryCache[$fullKey];
        }

        // Use Laravel's remember for Redis layer
        $value = Cache::remember($fullKey, $ttl, $callback);

        // Store in memory cache
        $this->memoryCache[$fullKey] = $value;

        if (Cache::has($fullKey)) {
            $this->stats['hits']++;
        } else {
            $this->stats['writes']++;
        }

        return $value;
    }

    /**
     * Delete cached value from all layers
     *
     * @param string $key
     * @param string $group
     * @return bool
     */
    public function forget(string $key, string $group = 'query_result'): bool
    {
        $fullKey = $this->buildKey($key, $group);

        try {
            // Remove from memory cache
            unset($this->memoryCache[$fullKey]);

            // Remove from Redis
            Cache::forget($fullKey);

            $this->stats['deletes']++;
            Log::debug('Cache delete', ['key' => $fullKey]);

            return true;

        } catch (\Exception $e) {
            Log::error('Cache delete failed', [
                'key' => $fullKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Invalidate all cache keys matching a pattern
     *
     * @param string $pattern
     * @return int Number of keys deleted
     */
    public function invalidatePattern(string $pattern): int
    {
        try {
            $redis = Redis::connection();
            $keys = $redis->keys($pattern);

            if (empty($keys)) {
                return 0;
            }

            // Delete matching keys
            $deleted = $redis->del($keys);

            // Clear from memory cache
            foreach ($keys as $key) {
                unset($this->memoryCache[$key]);
            }

            Log::info('Cache pattern invalidated', [
                'pattern' => $pattern,
                'deleted' => $deleted
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Pattern invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Invalidate cache for an organization
     *
     * @param string $orgId
     * @return int
     */
    public function invalidateOrganization(string $orgId): int
    {
        $patterns = [
            "*:org:{$orgId}:*",
            "*:campaigns:org:{$orgId}",
            "*:analytics:org:{$orgId}",
            "*:platform:org:{$orgId}"
        ];

        $totalDeleted = 0;

        foreach ($patterns as $pattern) {
            $totalDeleted += $this->invalidatePattern($pattern);
        }

        Log::info('Organization cache invalidated', [
            'org_id' => $orgId,
            'keys_deleted' => $totalDeleted
        ]);

        return $totalDeleted;
    }

    /**
     * Invalidate cache for a campaign
     *
     * @param string $campaignId
     * @return int
     */
    public function invalidateCampaign(string $campaignId): int
    {
        $patterns = [
            "*:campaign:{$campaignId}:*",
            "*:analytics:campaign:{$campaignId}",
            "*:performance:campaign:{$campaignId}"
        ];

        $totalDeleted = 0;

        foreach ($patterns as $pattern) {
            $totalDeleted += $this->invalidatePattern($pattern);
        }

        Log::info('Campaign cache invalidated', [
            'campaign_id' => $campaignId,
            'keys_deleted' => $totalDeleted
        ]);

        return $totalDeleted;
    }

    /**
     * Build cache key with prefix and group
     *
     * @param string $key
     * @param string $group
     * @return string
     */
    protected function buildKey(string $key, string $group): string
    {
        $prefix = $this->getPrefix($group);
        return "{$prefix}:{$group}:{$key}";
    }

    /**
     * Get prefix for cache group
     *
     * @param string $group
     * @return string
     */
    protected function getPrefix(string $group): string
    {
        return match($group) {
            'api_response' => self::PREFIX_API,
            'query_result' => self::PREFIX_QUERY,
            'model_data' => self::PREFIX_MODEL,
            'analytics' => self::PREFIX_ANALYTICS,
            'platform_data' => self::PREFIX_PLATFORM,
            'embeddings' => self::PREFIX_EMBEDDING,
            default => 'cache'
        };
    }

    /**
     * Warm up cache with frequently accessed data
     *
     * @param string $orgId
     * @return array
     */
    public function warmup(string $orgId): array
    {
        $startTime = microtime(true);
        $warmedKeys = [];

        try {
            // Warmup organization data
            $this->remember("org:{$orgId}", function() use ($orgId) {
                return \DB::table('cmis.organizations')
                    ->where('org_id', $orgId)
                    ->first();
            }, 'model_data');
            $warmedKeys[] = "org:{$orgId}";

            // Warmup active campaigns
            $this->remember("campaigns:org:{$orgId}:active", function() use ($orgId) {
                return \DB::table('cmis.campaigns')
                    ->where('org_id', $orgId)
                    ->where('status', 'active')
                    ->get();
            }, 'query_result');
            $warmedKeys[] = "campaigns:org:{$orgId}:active";

            // Warmup analytics summary
            $this->remember("analytics:org:{$orgId}:summary", function() use ($orgId) {
                return \DB::table('cmis_analytics.campaign_performance')
                    ->where('org_id', $orgId)
                    ->whereDate('date', '>=', Carbon::now()->subDays(30))
                    ->get();
            }, 'analytics');
            $warmedKeys[] = "analytics:org:{$orgId}:summary";

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'org_id' => $orgId,
                'keys_warmed' => count($warmedKeys),
                'keys' => $warmedKeys,
                'execution_time_ms' => $executionTime
            ];

        } catch (\Exception $e) {
            Log::error('Cache warmup failed', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        try {
            // Get Redis info
            $redis = Redis::connection();
            $info = $redis->info();

            // Calculate hit rate
            $totalOperations = $this->stats['hits'] + $this->stats['misses'];
            $hitRate = $totalOperations > 0
                ? round(($this->stats['hits'] / $totalOperations) * 100, 2)
                : 0;

            // Get memory usage
            $usedMemory = $info['used_memory_human'] ?? 'N/A';
            $maxMemory = $info['maxmemory_human'] ?? 'N/A';

            // Get key count
            $keyCount = $redis->dbsize();

            // Memory cache size
            $memoryCacheSize = count($this->memoryCache);

            return [
                'success' => true,
                'statistics' => [
                    'hits' => $this->stats['hits'],
                    'misses' => $this->stats['misses'],
                    'writes' => $this->stats['writes'],
                    'deletes' => $this->stats['deletes'],
                    'hit_rate' => $hitRate,
                    'total_operations' => $totalOperations
                ],
                'redis' => [
                    'used_memory' => $usedMemory,
                    'max_memory' => $maxMemory,
                    'key_count' => $keyCount,
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'uptime_days' => isset($info['uptime_in_seconds'])
                        ? round($info['uptime_in_seconds'] / 86400, 1)
                        : 0
                ],
                'memory_cache' => [
                    'size' => $memoryCacheSize,
                    'keys' => array_keys($this->memoryCache)
                ],
                'generated_at' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Cache statistics retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'statistics' => $this->stats
            ];
        }
    }

    /**
     * Flush all cache layers
     *
     * @return bool
     */
    public function flushAll(): bool
    {
        try {
            // Clear memory cache
            $this->memoryCache = [];

            // Clear Redis cache
            Cache::flush();

            Log::warning('All caches flushed');

            return true;

        } catch (\Exception $e) {
            Log::error('Cache flush failed', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get cache health status
     *
     * @return array
     */
    public function getHealthStatus(): array
    {
        try {
            // Test Redis connection
            $redis = Redis::connection();
            $redis->ping();

            // Test cache operations
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            $isHealthy = ($value === 'test');

            return [
                'success' => true,
                'healthy' => $isHealthy,
                'redis_connected' => true,
                'cache_working' => $isHealthy,
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'healthy' => false,
                'redis_connected' => false,
                'error' => $e->getMessage(),
                'timestamp' => Carbon::now()->toIso8601String()
            ];
        }
    }

    /**
     * Get top cached keys by access frequency
     *
     * @param int $limit
     * @return array
     */
    public function getTopKeys(int $limit = 20): array
    {
        try {
            $redis = Redis::connection();
            $keys = $redis->keys('*');

            $keyStats = [];

            foreach (array_slice($keys, 0, 100) as $key) {
                $ttl = $redis->ttl($key);
                $type = $redis->type($key);

                $keyStats[] = [
                    'key' => $key,
                    'type' => $type,
                    'ttl' => $ttl,
                    'ttl_human' => $ttl > 0 ? $this->formatTTL($ttl) : 'No expiry'
                ];
            }

            // Sort by TTL (most recently accessed)
            usort($keyStats, function($a, $b) {
                return $b['ttl'] <=> $a['ttl'];
            });

            return [
                'success' => true,
                'keys' => array_slice($keyStats, 0, $limit),
                'total_keys' => count($keys)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format TTL in human-readable format
     *
     * @param int $seconds
     * @return string
     */
    protected function formatTTL(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes}m";
        }

        if ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            return "{$hours}h";
        }

        $days = floor($seconds / 86400);
        return "{$days}d";
    }

    /**
     * Reset statistics
     *
     * @return void
     */
    public function resetStatistics(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0
        ];
    }
}
