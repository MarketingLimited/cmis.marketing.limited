<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * PerformanceOptimizationService
 *
 * Handles performance monitoring and optimization
 * Implements Sprint 6.1: Performance Optimization
 *
 * Features:
 * - Query performance monitoring
 * - Cache management
 * - Database optimization
 * - Performance metrics tracking
 */
class PerformanceOptimizationService
{
    /**
     * Get performance metrics
     *
     * @param string $orgId
     * @param array $filters
     * @return array
     */
    public function getPerformanceMetrics(string $orgId, array $filters = []): array
    {
        try {
            $startDate = $filters['start_date'] ?? now()->subDays(7)->toDateString();
            $endDate = $filters['end_date'] ?? now()->toDateString();

            // API response times
            $apiMetrics = $this->getAPIMetrics($orgId, $startDate, $endDate);

            // Database query performance
            $dbMetrics = $this->getDatabaseMetrics($orgId, $startDate, $endDate);

            // Cache hit rates
            $cacheMetrics = $this->getCacheMetrics($orgId);

            // System resource usage
            $resourceMetrics = $this->getResourceMetrics();

            return [
                'success' => true,
                'data' => [
                    'api_performance' => $apiMetrics,
                    'database_performance' => $dbMetrics,
                    'cache_performance' => $cacheMetrics,
                    'resource_usage' => $resourceMetrics,
                    'recommendations' => $this->generateOptimizationRecommendations($apiMetrics, $dbMetrics, $cacheMetrics)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get performance metrics',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get API performance metrics
     *
     * @param string $orgId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function getAPIMetrics(string $orgId, string $startDate, string $endDate): array
    {
        // Get API request logs
        $requests = DB::table('cmis.api_logs')
            ->where('org_id', $orgId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('MAX(response_time_ms) as max_response_time'),
                DB::raw('MIN(response_time_ms) as min_response_time'),
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(CASE WHEN response_time_ms > 1000 THEN 1 ELSE 0 END) as slow_requests')
            )
            ->first();

        // Get endpoint breakdown
        $endpointBreakdown = DB::table('cmis.api_logs')
            ->where('org_id', $orgId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'endpoint',
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(*) as request_count')
            )
            ->groupBy('endpoint')
            ->orderBy('avg_response_time', 'desc')
            ->limit(10)
            ->get();

        return [
            'average_response_time_ms' => round($requests->avg_response_time ?? 0, 2),
            'max_response_time_ms' => round($requests->max_response_time ?? 0, 2),
            'min_response_time_ms' => round($requests->min_response_time ?? 0, 2),
            'total_requests' => $requests->total_requests ?? 0,
            'slow_requests' => $requests->slow_requests ?? 0,
            'slow_request_percentage' => $requests->total_requests > 0
                ? round(($requests->slow_requests / $requests->total_requests) * 100, 2)
                : 0,
            'slowest_endpoints' => $endpointBreakdown->map(function ($endpoint) {
                return [
                    'endpoint' => $endpoint->endpoint,
                    'avg_response_time_ms' => round($endpoint->avg_response_time, 2),
                    'request_count' => $endpoint->request_count
                ];
            })->toArray()
        ];
    }

    /**
     * Get database performance metrics
     *
     * @param string $orgId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function getDatabaseMetrics(string $orgId, string $startDate, string $endDate): array
    {
        // Get slow query logs
        $slowQueries = DB::table('cmis.slow_query_log')
            ->where('org_id', $orgId)
            ->whereBetween('logged_at', [$startDate, $endDate])
            ->select(
                'query_type',
                DB::raw('AVG(execution_time_ms) as avg_execution_time'),
                DB::raw('COUNT(*) as occurrence_count')
            )
            ->groupBy('query_type')
            ->orderBy('avg_execution_time', 'desc')
            ->limit(10)
            ->get();

        // Connection pool stats
        $connectionStats = [
            'active_connections' => DB::select("SELECT COUNT(*) as count FROM pg_stat_activity WHERE datname = current_database()")[0]->count ?? 0,
            'idle_connections' => DB::select("SELECT COUNT(*) as count FROM pg_stat_activity WHERE datname = current_database() AND state = 'idle'")[0]->count ?? 0
        ];

        return [
            'slow_queries' => $slowQueries->map(function ($query) {
                return [
                    'query_type' => $query->query_type,
                    'avg_execution_time_ms' => round($query->avg_execution_time, 2),
                    'occurrence_count' => $query->occurrence_count
                ];
            })->toArray(),
            'connection_pool' => $connectionStats,
            'recommendations' => $this->getDatabaseRecommendations($slowQueries)
        ];
    }

    /**
     * Get cache performance metrics
     *
     * @param string $orgId
     * @return array
     */
    protected function getCacheMetrics(string $orgId): array
    {
        try {
            // Redis info (if available)
            $info = Redis::info();

            $cacheHitRate = 0;
            $totalKeys = 0;
            $memoryUsage = 0;

            if (isset($info['keyspace_hits'], $info['keyspace_misses'])) {
                $hits = $info['keyspace_hits'];
                $misses = $info['keyspace_misses'];
                $total = $hits + $misses;
                $cacheHitRate = $total > 0 ? ($hits / $total) * 100 : 0;
            }

            if (isset($info['keys'])) {
                $totalKeys = $info['keys'];
            }

            if (isset($info['used_memory_human'])) {
                $memoryUsage = $info['used_memory_human'];
            }

            return [
                'cache_hit_rate' => round($cacheHitRate, 2),
                'total_keys' => $totalKeys,
                'memory_usage' => $memoryUsage,
                'eviction_policy' => $info['maxmemory_policy'] ?? 'unknown',
                'status' => 'healthy'
            ];

        } catch (\Exception $e) {
            return [
                'cache_hit_rate' => 0,
                'total_keys' => 0,
                'memory_usage' => 'unknown',
                'status' => 'unavailable',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get system resource metrics
     *
     * @return array
     */
    protected function getResourceMetrics(): array
    {
        $load = sys_getloadavg();

        return [
            'cpu_load_1min' => round($load[0], 2),
            'cpu_load_5min' => round($load[1], 2),
            'cpu_load_15min' => round($load[2], 2),
            'memory_usage' => [
                'total_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'current_mb' => round(memory_get_usage() / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
            ]
        ];
    }

    /**
     * Generate optimization recommendations
     *
     * @param array $apiMetrics
     * @param array $dbMetrics
     * @param array $cacheMetrics
     * @return array
     */
    protected function generateOptimizationRecommendations(array $apiMetrics, array $dbMetrics, array $cacheMetrics): array
    {
        $recommendations = [];

        // API performance recommendations
        if ($apiMetrics['slow_request_percentage'] > 10) {
            $recommendations[] = [
                'category' => 'API Performance',
                'priority' => 'high',
                'issue' => 'High percentage of slow requests',
                'recommendation' => 'Investigate and optimize slow endpoints. Consider implementing caching or pagination.'
            ];
        }

        // Database recommendations
        if (!empty($dbMetrics['slow_queries'])) {
            $recommendations[] = [
                'category' => 'Database Performance',
                'priority' => 'high',
                'issue' => 'Slow queries detected',
                'recommendation' => 'Add appropriate indexes and optimize query execution plans.'
            ];
        }

        // Cache recommendations
        if ($cacheMetrics['cache_hit_rate'] < 70) {
            $recommendations[] = [
                'category' => 'Cache Performance',
                'priority' => 'medium',
                'issue' => 'Low cache hit rate',
                'recommendation' => 'Review caching strategy and increase cache TTL for frequently accessed data.'
            ];
        }

        return $recommendations;
    }

    /**
     * Get database optimization recommendations
     *
     * @param \Illuminate\Support\Collection $slowQueries
     * @return array
     */
    protected function getDatabaseRecommendations($slowQueries): array
    {
        $recommendations = [];

        foreach ($slowQueries as $query) {
            if ($query->avg_execution_time > 500) {
                $recommendations[] = "Optimize {$query->query_type} queries - averaging {$query->avg_execution_time}ms";
            }
        }

        return $recommendations;
    }

    /**
     * Clear cache
     *
     * @param array $options
     * @return array
     */
    public function clearCache(array $options = []): array
    {
        try {
            if (!empty($options['tags'])) {
                // Clear specific cache tags
                foreach ($options['tags'] as $tag) {
                    Cache::tags($tag)->flush();
                }
                $message = 'Cache cleared for specified tags';
            } elseif (!empty($options['keys'])) {
                // Clear specific keys
                foreach ($options['keys'] as $key) {
                    Cache::forget($key);
                }
                $message = 'Specified cache keys cleared';
            } else {
                // Clear all cache
                Cache::flush();
                $message = 'All cache cleared';
            }

            return [
                'success' => true,
                'message' => $message
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Warm up cache
     *
     * @param string $orgId
     * @return array
     */
    public function warmupCache(string $orgId): array
    {
        try {
            $warmedItems = [];

            // Warm up organization data
            Cache::remember("org_data:{$orgId}", now()->addHours(24), function () use ($orgId) {
                return DB::table('cmis.orgs')->where('org_id', $orgId)->first();
            });
            $warmedItems[] = 'Organization data';

            // Warm up social accounts
            Cache::remember("org_accounts:{$orgId}", now()->addHours(12), function () use ($orgId) {
                return DB::table('cmis.social_accounts')->where('org_id', $orgId)->get();
            });
            $warmedItems[] = 'Social accounts';

            // Warm up team members
            Cache::remember("org_members:{$orgId}", now()->addMinutes(15), function () use ($orgId) {
                return DB::table('cmis.org_users')
                    ->where('org_id', $orgId)
                    ->get();
            });
            $warmedItems[] = 'Team members';

            return [
                'success' => true,
                'message' => 'Cache warmed up successfully',
                'data' => [
                    'items_warmed' => count($warmedItems),
                    'items' => $warmedItems
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to warm up cache',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimize database
     *
     * @param string $orgId
     * @return array
     */
    public function optimizeDatabase(string $orgId): array
    {
        try {
            $optimizations = [];

            // Analyze tables
            DB::statement("ANALYZE cmis.social_posts");
            DB::statement("ANALYZE cmis.social_accounts");
            DB::statement("ANALYZE cmis_ads.ad_campaigns");
            $optimizations[] = 'Table statistics updated';

            // Vacuum tables (only if needed)
            // Note: This should be run during maintenance windows
            // DB::statement("VACUUM ANALYZE cmis.social_posts");

            return [
                'success' => true,
                'message' => 'Database optimization completed',
                'data' => [
                    'optimizations_performed' => $optimizations
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to optimize database',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get slow queries
     *
     * @param string $orgId
     * @param int $threshold
     * @return array
     */
    public function getSlowQueries(string $orgId, int $threshold = 500): array
    {
        try {
            $slowQueries = DB::table('cmis.slow_query_log')
                ->where('org_id', $orgId)
                ->where('execution_time_ms', '>', $threshold)
                ->orderBy('execution_time_ms', 'desc')
                ->limit(50)
                ->get();

            return [
                'success' => true,
                'data' => $slowQueries->map(function ($query) {
                    return [
                        'query_id' => $query->query_id ?? null,
                        'query_type' => $query->query_type,
                        'execution_time_ms' => round($query->execution_time_ms, 2),
                        'query_text' => $query->query_text ?? 'N/A',
                        'logged_at' => $query->logged_at
                    ];
                }),
                'total' => $slowQueries->count()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get slow queries',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Log API request for performance tracking
     *
     * @param array $data
     * @return void
     */
    public function logAPIRequest(array $data): void
    {
        try {
            DB::table('cmis.api_logs')->insert([
                'log_id' => (string) Str::uuid(),
                'org_id' => $data['org_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'endpoint' => $data['endpoint'],
                'method' => $data['method'],
                'response_time_ms' => $data['response_time_ms'],
                'status_code' => $data['status_code'],
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            // Silently fail to not disrupt main operations
            \Log::error('Failed to log API request', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log slow query for analysis
     *
     * @param array $data
     * @return void
     */
    public function logSlowQuery(array $data): void
    {
        try {
            DB::table('cmis.slow_query_log')->insert([
                'query_id' => (string) Str::uuid(),
                'org_id' => $data['org_id'] ?? null,
                'query_type' => $data['query_type'],
                'query_text' => $data['query_text'] ?? null,
                'execution_time_ms' => $data['execution_time_ms'],
                'logged_at' => now()
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log slow query', ['error' => $e->getMessage()]);
        }
    }
}
