<?php

namespace App\Services\Optimization;

use Illuminate\Support\Facades\{DB, Log, Cache};
use Carbon\Carbon;

/**
 * Performance Profiling Service (Phase 6)
 *
 * Monitors and profiles application performance metrics
 */
class PerformanceProfiler
{
    // Performance thresholds
    const SLOW_REQUEST_THRESHOLD = 2000;      // 2 seconds
    const WARNING_REQUEST_THRESHOLD = 1000;   // 1 second
    const MEMORY_WARNING_THRESHOLD = 128;     // 128 MB
    const MEMORY_CRITICAL_THRESHOLD = 256;    // 256 MB

    protected array $metrics = [];
    protected array $checkpoints = [];
    protected float $startTime;
    protected int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Start profiling a request
     *
     * @param string $requestId
     * @param array $context
     * @return void
     */
    public function startRequest(string $requestId, array $context = []): void
    {
        $this->metrics[$requestId] = [
            'request_id' => $requestId,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context,
            'checkpoints' => [],
            'queries' => [],
            'cache_hits' => 0,
            'cache_misses' => 0
        ];
    }

    /**
     * Add a checkpoint to track progress
     *
     * @param string $requestId
     * @param string $name
     * @param array $data
     * @return void
     */
    public function checkpoint(string $requestId, string $name, array $data = []): void
    {
        if (!isset($this->metrics[$requestId])) {
            return;
        }

        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);

        $this->metrics[$requestId]['checkpoints'][] = [
            'name' => $name,
            'time' => $currentTime,
            'elapsed_ms' => round(($currentTime - $this->metrics[$requestId]['start_time']) * 1000, 2),
            'memory_bytes' => $currentMemory,
            'memory_mb' => round($currentMemory / 1024 / 1024, 2),
            'data' => $data
        ];
    }

    /**
     * End profiling a request
     *
     * @param string $requestId
     * @return array
     */
    public function endRequest(string $requestId): array
    {
        if (!isset($this->metrics[$requestId])) {
            return ['error' => 'Request not found'];
        }

        $metric = $this->metrics[$requestId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $totalTime = ($endTime - $metric['start_time']) * 1000; // milliseconds
        $totalMemory = ($endMemory - $metric['start_memory']) / 1024 / 1024; // MB
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;

        $profile = [
            'request_id' => $requestId,
            'total_time_ms' => round($totalTime, 2),
            'memory_used_mb' => round($totalMemory, 2),
            'peak_memory_mb' => round($peakMemory, 2),
            'checkpoints' => $metric['checkpoints'],
            'query_count' => count($metric['queries']),
            'cache_hits' => $metric['cache_hits'],
            'cache_misses' => $metric['cache_misses'],
            'context' => $metric['context'],
            'performance_rating' => $this->ratePerformance($totalTime, $peakMemory),
            'timestamp' => Carbon::now()->toIso8601String()
        ];

        // Log slow requests
        if ($totalTime > self::SLOW_REQUEST_THRESHOLD) {
            Log::warning('Slow request detected', $profile);
        }

        // Store in cache for analysis
        $this->storeProfile($requestId, $profile);

        // Clean up
        unset($this->metrics[$requestId]);

        return $profile;
    }

    /**
     * Rate performance based on metrics
     *
     * @param float $timeMs
     * @param float $memoryMb
     * @return array
     */
    protected function ratePerformance(float $timeMs, float $memoryMb): array
    {
        $timeRating = match(true) {
            $timeMs < 100 => 'excellent',
            $timeMs < 500 => 'good',
            $timeMs < self::WARNING_REQUEST_THRESHOLD => 'fair',
            $timeMs < self::SLOW_REQUEST_THRESHOLD => 'poor',
            default => 'critical'
        };

        $memoryRating = match(true) {
            $memoryMb < 32 => 'excellent',
            $memoryMb < 64 => 'good',
            $memoryMb < self::MEMORY_WARNING_THRESHOLD => 'fair',
            $memoryMb < self::MEMORY_CRITICAL_THRESHOLD => 'poor',
            default => 'critical'
        };

        // Overall rating (worst of the two)
        $ratings = ['excellent' => 5, 'good' => 4, 'fair' => 3, 'poor' => 2, 'critical' => 1];
        $overallScore = min($ratings[$timeRating], $ratings[$memoryRating]);
        $overall = array_search($overallScore, $ratings);

        return [
            'time' => $timeRating,
            'memory' => $memoryRating,
            'overall' => $overall,
            'score' => $overallScore
        ];
    }

    /**
     * Store profile for later analysis
     *
     * @param string $requestId
     * @param array $profile
     * @return void
     */
    protected function storeProfile(string $requestId, array $profile): void
    {
        try {
            $key = "profile:{$requestId}";
            Cache::put($key, $profile, 3600); // 1 hour

            // Also add to recent profiles list
            $recentKey = 'profiles:recent';
            $recent = Cache::get($recentKey, []);
            array_unshift($recent, $requestId);
            $recent = array_slice($recent, 0, 100); // Keep last 100
            Cache::put($recentKey, $recent, 3600);

        } catch (\Exception $e) {
            Log::error('Failed to store profile', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get recent performance profiles
     *
     * @param int $limit
     * @return array
     */
    public function getRecentProfiles(int $limit = 20): array
    {
        try {
            $recentKey = 'profiles:recent';
            $recentIds = Cache::get($recentKey, []);
            $recentIds = array_slice($recentIds, 0, $limit);

            $profiles = [];

            foreach ($recentIds as $requestId) {
                $profile = Cache::get("profile:{$requestId}");
                if ($profile) {
                    $profiles[] = $profile;
                }
            }

            return [
                'success' => true,
                'profiles' => $profiles,
                'count' => count($profiles)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get performance summary statistics
     *
     * @return array
     */
    public function getSummaryStatistics(): array
    {
        try {
            $recentKey = 'profiles:recent';
            $recentIds = Cache::get($recentKey, []);

            $profiles = [];
            foreach ($recentIds as $requestId) {
                $profile = Cache::get("profile:{$requestId}");
                if ($profile) {
                    $profiles[] = $profile;
                }
            }

            if (empty($profiles)) {
                return [
                    'success' => true,
                    'message' => 'No performance data available',
                    'statistics' => []
                ];
            }

            // Calculate statistics
            $times = array_column($profiles, 'total_time_ms');
            $memories = array_column($profiles, 'peak_memory_mb');

            $stats = [
                'total_requests' => count($profiles),
                'time_stats' => [
                    'avg' => round(array_sum($times) / count($times), 2),
                    'min' => round(min($times), 2),
                    'max' => round(max($times), 2),
                    'median' => $this->calculateMedian($times),
                    'p95' => $this->calculatePercentile($times, 95),
                    'p99' => $this->calculatePercentile($times, 99)
                ],
                'memory_stats' => [
                    'avg' => round(array_sum($memories) / count($memories), 2),
                    'min' => round(min($memories), 2),
                    'max' => round(max($memories), 2),
                    'median' => $this->calculateMedian($memories)
                ],
                'slow_requests' => count(array_filter($times, fn($t) => $t > self::SLOW_REQUEST_THRESHOLD)),
                'warning_requests' => count(array_filter($times, fn($t) => $t > self::WARNING_REQUEST_THRESHOLD && $t <= self::SLOW_REQUEST_THRESHOLD)),
                'fast_requests' => count(array_filter($times, fn($t) => $t <= self::WARNING_REQUEST_THRESHOLD)),
                'generated_at' => Carbon::now()->toIso8601String()
            ];

            return [
                'success' => true,
                'statistics' => $stats
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate median value
     *
     * @param array $values
     * @return float
     */
    protected function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return 0;
        }

        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return round(($values[$middle - 1] + $values[$middle]) / 2, 2);
        }

        return round($values[$middle], 2);
    }

    /**
     * Calculate percentile value
     *
     * @param array $values
     * @param int $percentile
     * @return float
     */
    protected function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return 0;
        }

        $index = ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return round($values[$index], 2);
    }

    /**
     * Monitor system resources
     *
     * @return array
     */
    public function getSystemResources(): array
    {
        try {
            $cpuLoad = sys_getloadavg();

            return [
                'success' => true,
                'cpu' => [
                    'load_1min' => $cpuLoad[0] ?? null,
                    'load_5min' => $cpuLoad[1] ?? null,
                    'load_15min' => $cpuLoad[2] ?? null
                ],
                'memory' => [
                    'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'limit_mb' => ini_get('memory_limit')
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'max_execution_time' => ini_get('max_execution_time'),
                    'post_max_size' => ini_get('post_max_size'),
                    'upload_max_filesize' => ini_get('upload_max_filesize')
                ],
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get slow queries from logs
     *
     * @param int $limit
     * @return array
     */
    public function getSlowQueries(int $limit = 20): array
    {
        try {
            // Get slow queries from cache (populated by DatabaseQueryOptimizer)
            $slowQueries = Cache::get('slow_queries', []);

            // Sort by execution time (descending)
            usort($slowQueries, function($a, $b) {
                return ($b['time_ms'] ?? 0) <=> ($a['time_ms'] ?? 0);
            });

            $slowQueries = array_slice($slowQueries, 0, $limit);

            return [
                'success' => true,
                'queries' => $slowQueries,
                'count' => count($slowQueries)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Profile a specific operation
     *
     * @param string $name
     * @param callable $operation
     * @return array
     */
    public function profile(string $name, callable $operation): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $operation();

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $executionTime = ($endTime - $startTime) * 1000;
            $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

            $profile = [
                'success' => true,
                'operation' => $name,
                'execution_time_ms' => round($executionTime, 2),
                'memory_used_mb' => round($memoryUsed, 2),
                'result' => $result,
                'timestamp' => Carbon::now()->toIso8601String()
            ];

            // Log if slow
            if ($executionTime > self::WARNING_REQUEST_THRESHOLD) {
                Log::warning('Slow operation detected', [
                    'operation' => $name,
                    'time_ms' => $executionTime
                ]);
            }

            return $profile;

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            Log::error('Operation failed during profiling', [
                'operation' => $name,
                'error' => $e->getMessage(),
                'time_ms' => $executionTime
            ]);

            return [
                'success' => false,
                'operation' => $name,
                'execution_time_ms' => round($executionTime, 2),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get application health metrics
     *
     * @return array
     */
    public function getHealthMetrics(): array
    {
        try {
            // Get recent profiles for health assessment
            $recentProfiles = $this->getRecentProfiles(50);

            if (!$recentProfiles['success'] || empty($recentProfiles['profiles'])) {
                return [
                    'success' => true,
                    'health' => 'unknown',
                    'message' => 'Insufficient data for health assessment'
                ];
            }

            $profiles = $recentProfiles['profiles'];
            $times = array_column($profiles, 'total_time_ms');
            $avgTime = array_sum($times) / count($times);

            // Count critical issues
            $criticalCount = count(array_filter($profiles, function($p) {
                return $p['performance_rating']['overall'] === 'critical';
            }));

            $poorCount = count(array_filter($profiles, function($p) {
                return $p['performance_rating']['overall'] === 'poor';
            }));

            // Determine overall health
            $criticalPercentage = ($criticalCount / count($profiles)) * 100;
            $poorPercentage = ($poorCount / count($profiles)) * 100;

            $health = match(true) {
                $criticalPercentage > 10 => 'critical',
                $poorPercentage > 25 => 'degraded',
                $avgTime > self::WARNING_REQUEST_THRESHOLD => 'warning',
                default => 'healthy'
            };

            return [
                'success' => true,
                'health' => $health,
                'metrics' => [
                    'avg_response_time_ms' => round($avgTime, 2),
                    'critical_requests' => $criticalCount,
                    'poor_requests' => $poorCount,
                    'total_requests' => count($profiles),
                    'critical_percentage' => round($criticalPercentage, 2),
                    'poor_percentage' => round($poorPercentage, 2)
                ],
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'health' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear all performance data
     *
     * @return bool
     */
    public function clearData(): bool
    {
        try {
            $recentKey = 'profiles:recent';
            $recentIds = Cache::get($recentKey, []);

            foreach ($recentIds as $requestId) {
                Cache::forget("profile:{$requestId}");
            }

            Cache::forget($recentKey);
            Cache::forget('slow_queries');

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to clear performance data', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
