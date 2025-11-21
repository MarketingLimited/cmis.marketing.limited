<?php

namespace App\Services\Optimization;

use Illuminate\Support\Facades\{DB, Cache, Redis, Log, Http};
use Carbon\Carbon;

/**
 * Health Check & Readiness Probe Service (Phase 6)
 *
 * Kubernetes-compatible health checks for production deployment
 */
class HealthCheckService
{
    // Health check timeouts (milliseconds)
    const CHECK_TIMEOUT = 5000;
    const EXTERNAL_API_TIMEOUT = 10000;

    // Health status levels
    const STATUS_HEALTHY = 'healthy';
    const STATUS_DEGRADED = 'degraded';
    const STATUS_UNHEALTHY = 'unhealthy';

    /**
     * Liveness probe - checks if application is alive
     * Returns 200 if alive, 503 if dead
     *
     * @return array
     */
    public function liveness(): array
    {
        try {
            // Basic PHP execution check
            $uptime = $this->getUptime();

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Application is alive',
                'uptime_seconds' => $uptime,
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_UNHEALTHY,
                'message' => 'Liveness check failed',
                'error' => $e->getMessage(),
                'timestamp' => Carbon::now()->toIso8601String()
            ];
        }
    }

    /**
     * Readiness probe - checks if application is ready to serve traffic
     * Returns 200 if ready, 503 if not ready
     *
     * @return array
     */
    public function readiness(): array
    {
        $checks = [];
        $overallStatus = self::STATUS_HEALTHY;

        // Check database connectivity
        $dbCheck = $this->checkDatabase();
        $checks['database'] = $dbCheck;
        if ($dbCheck['status'] !== self::STATUS_HEALTHY) {
            $overallStatus = self::STATUS_UNHEALTHY;
        }

        // Check Redis connectivity
        $redisCheck = $this->checkRedis();
        $checks['redis'] = $redisCheck;
        if ($redisCheck['status'] !== self::STATUS_HEALTHY) {
            $overallStatus = $overallStatus === self::STATUS_UNHEALTHY
                ? self::STATUS_UNHEALTHY
                : self::STATUS_DEGRADED;
        }

        // Check filesystem
        $fsCheck = $this->checkFilesystem();
        $checks['filesystem'] = $fsCheck;
        if ($fsCheck['status'] !== self::STATUS_HEALTHY) {
            $overallStatus = self::STATUS_UNHEALTHY;
        }

        return [
            'status' => $overallStatus,
            'message' => $this->getReadinessMessage($overallStatus),
            'checks' => $checks,
            'timestamp' => Carbon::now()->toIso8601String()
        ];
    }

    /**
     * Comprehensive health check with all dependencies
     *
     * @return array
     */
    public function health(): array
    {
        $checks = [];
        $overallStatus = self::STATUS_HEALTHY;
        $failedChecks = [];

        // Core dependencies
        $dbCheck = $this->checkDatabase();
        $checks['database'] = $dbCheck;
        if ($dbCheck['status'] !== self::STATUS_HEALTHY) {
            $overallStatus = self::STATUS_UNHEALTHY;
            $failedChecks[] = 'database';
        }

        $redisCheck = $this->checkRedis();
        $checks['redis'] = $redisCheck;
        if ($redisCheck['status'] !== self::STATUS_HEALTHY) {
            $overallStatus = $overallStatus === self::STATUS_UNHEALTHY
                ? self::STATUS_UNHEALTHY
                : self::STATUS_DEGRADED;
            $failedChecks[] = 'redis';
        }

        $fsCheck = $this->checkFilesystem();
        $checks['filesystem'] = $fsCheck;
        if ($fsCheck['status'] !== self::STATUS_HEALTHY) {
            $overallStatus = self::STATUS_UNHEALTHY;
            $failedChecks[] = 'filesystem';
        }

        // Optional dependencies (non-critical)
        $checks['external_apis'] = $this->checkExternalAPIs();

        // System resources
        $checks['system'] = $this->checkSystemResources();

        // Application metrics
        $checks['application'] = $this->checkApplicationHealth();

        return [
            'status' => $overallStatus,
            'message' => $this->getHealthMessage($overallStatus, $failedChecks),
            'checks' => $checks,
            'failed_checks' => $failedChecks,
            'timestamp' => Carbon::now()->toIso8601String()
        ];
    }

    /**
     * Check database connectivity and health
     *
     * @return array
     */
    protected function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);

            // Test database connection
            DB::connection()->getPdo();

            // Test simple query
            $result = DB::selectOne('SELECT 1 as test');

            // Get connection stats
            $activeConnections = DB::selectOne("
                SELECT count(*) as count
                FROM pg_stat_activity
                WHERE datname = current_database()
                  AND state = 'active'
            ");

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Database connection OK',
                'response_time_ms' => $responseTime,
                'active_connections' => $activeConnections->count ?? 0,
                'driver' => config('database.default')
            ];

        } catch (\Exception $e) {
            Log::error('Database health check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => self::STATUS_UNHEALTHY,
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check Redis connectivity and health
     *
     * @return array
     */
    protected function checkRedis(): array
    {
        try {
            $startTime = microtime(true);

            // Test Redis connection
            $redis = Redis::connection();
            $pong = $redis->ping();

            // Get Redis info
            $info = $redis->info();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $usedMemory = $info['used_memory_human'] ?? 'N/A';
            $connectedClients = $info['connected_clients'] ?? 0;

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Redis connection OK',
                'response_time_ms' => $responseTime,
                'used_memory' => $usedMemory,
                'connected_clients' => $connectedClients
            ];

        } catch (\Exception $e) {
            Log::error('Redis health check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => self::STATUS_UNHEALTHY,
                'message' => 'Redis connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check filesystem health
     *
     * @return array
     */
    protected function checkFilesystem(): array
    {
        try {
            $storagePath = storage_path();

            // Check if storage directory is writable
            if (!is_writable($storagePath)) {
                return [
                    'status' => self::STATUS_UNHEALTHY,
                    'message' => 'Storage directory not writable',
                    'path' => $storagePath
                ];
            }

            // Test file write
            $testFile = storage_path('framework/cache/health_check_' . time());
            $testContent = 'health_check_' . time();

            if (!file_put_contents($testFile, $testContent)) {
                return [
                    'status' => self::STATUS_UNHEALTHY,
                    'message' => 'Cannot write to storage directory'
                ];
            }

            // Test file read
            $readContent = file_get_contents($testFile);

            // Clean up
            @unlink($testFile);

            if ($readContent !== $testContent) {
                return [
                    'status' => self::STATUS_UNHEALTHY,
                    'message' => 'File content mismatch'
                ];
            }

            // Get disk space
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercentage = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);

            $status = $usedPercentage > 95
                ? self::STATUS_UNHEALTHY
                : ($usedPercentage > 85 ? self::STATUS_DEGRADED : self::STATUS_HEALTHY);

            return [
                'status' => $status,
                'message' => 'Filesystem OK',
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_percentage' => $usedPercentage
            ];

        } catch (\Exception $e) {
            Log::error('Filesystem health check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => self::STATUS_UNHEALTHY,
                'message' => 'Filesystem check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check external API availability
     *
     * @return array
     */
    protected function checkExternalAPIs(): array
    {
        $apis = [
            'google_gemini' => config('services.google_gemini.endpoint', 'https://generativelanguage.googleapis.com'),
            'meta_graph' => 'https://graph.facebook.com',
            'google_ads' => 'https://googleads.googleapis.com',
        ];

        $results = [];
        $healthyCount = 0;

        foreach ($apis as $name => $endpoint) {
            try {
                $startTime = microtime(true);

                // Simple HEAD request to check availability
                $response = Http::timeout(5)->head($endpoint);

                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                $status = $response->successful() || $response->status() === 403 // 403 is OK for API endpoints
                    ? self::STATUS_HEALTHY
                    : self::STATUS_DEGRADED;

                if ($status === self::STATUS_HEALTHY) {
                    $healthyCount++;
                }

                $results[$name] = [
                    'status' => $status,
                    'response_time_ms' => $responseTime,
                    'http_status' => $response->status()
                ];

            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => self::STATUS_DEGRADED,
                    'error' => 'Connection timeout or failed'
                ];
            }
        }

        $overallStatus = $healthyCount === count($apis)
            ? self::STATUS_HEALTHY
            : ($healthyCount > 0 ? self::STATUS_DEGRADED : self::STATUS_UNHEALTHY);

        return [
            'status' => $overallStatus,
            'message' => "{$healthyCount} of " . count($apis) . " APIs accessible",
            'apis' => $results
        ];
    }

    /**
     * Check system resources
     *
     * @return array
     */
    protected function checkSystemResources(): array
    {
        try {
            $cpuLoad = sys_getloadavg();
            $memoryUsage = memory_get_usage(true);
            $memoryPeak = memory_get_peak_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

            $memoryUsagePercentage = $memoryLimit > 0
                ? round(($memoryPeak / $memoryLimit) * 100, 2)
                : 0;

            $status = match(true) {
                $memoryUsagePercentage > 90 => self::STATUS_UNHEALTHY,
                $memoryUsagePercentage > 75 => self::STATUS_DEGRADED,
                $cpuLoad[0] > 8.0 => self::STATUS_DEGRADED,
                default => self::STATUS_HEALTHY
            };

            return [
                'status' => $status,
                'message' => 'System resources OK',
                'cpu_load' => [
                    '1min' => $cpuLoad[0] ?? null,
                    '5min' => $cpuLoad[1] ?? null,
                    '15min' => $cpuLoad[2] ?? null
                ],
                'memory' => [
                    'current_mb' => round($memoryUsage / 1024 / 1024, 2),
                    'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
                    'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                    'usage_percentage' => $memoryUsagePercentage
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_DEGRADED,
                'message' => 'Could not retrieve system resources',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check application-specific health
     *
     * @return array
     */
    protected function checkApplicationHealth(): array
    {
        try {
            // Check Laravel configuration
            $appDebug = config('app.debug');
            $appEnv = config('app.env');

            // Production safety checks
            $warnings = [];

            if ($appEnv === 'production' && $appDebug === true) {
                $warnings[] = 'Debug mode enabled in production';
            }

            $status = empty($warnings) ? self::STATUS_HEALTHY : self::STATUS_DEGRADED;

            return [
                'status' => $status,
                'message' => 'Application health OK',
                'environment' => $appEnv,
                'debug_mode' => $appDebug,
                'warnings' => $warnings,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version()
            ];

        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_DEGRADED,
                'message' => 'Application health check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get application uptime in seconds
     *
     * @return int
     */
    protected function getUptime(): int
    {
        $startFile = storage_path('framework/cache/app_start_time');

        if (!file_exists($startFile)) {
            file_put_contents($startFile, time());
            return 0;
        }

        $startTime = (int) file_get_contents($startFile);
        return time() - $startTime;
    }

    /**
     * Parse PHP memory limit string to bytes
     *
     * @param string $limit
     * @return int
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $unit = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }

    /**
     * Get readiness message based on status
     *
     * @param string $status
     * @return string
     */
    protected function getReadinessMessage(string $status): string
    {
        return match($status) {
            self::STATUS_HEALTHY => 'Application is ready to serve traffic',
            self::STATUS_DEGRADED => 'Application is running with degraded performance',
            self::STATUS_UNHEALTHY => 'Application is not ready to serve traffic',
            default => 'Unknown readiness status'
        };
    }

    /**
     * Get health message based on status and failed checks
     *
     * @param string $status
     * @param array $failedChecks
     * @return string
     */
    protected function getHealthMessage(string $status, array $failedChecks): string
    {
        if (empty($failedChecks)) {
            return 'All health checks passed';
        }

        $count = count($failedChecks);
        $checks = implode(', ', $failedChecks);

        return match($status) {
            self::STATUS_UNHEALTHY => "{$count} critical check(s) failed: {$checks}",
            self::STATUS_DEGRADED => "{$count} non-critical check(s) degraded: {$checks}",
            default => 'Health status unknown'
        };
    }

    /**
     * Get detailed diagnostics for troubleshooting
     *
     * @return array
     */
    public function diagnostics(): array
    {
        return [
            'timestamp' => Carbon::now()->toIso8601String(),
            'health' => $this->health(),
            'database' => $this->getDatabaseDiagnostics(),
            'cache' => $this->getCacheDiagnostics(),
            'configuration' => $this->getConfigurationInfo(),
            'environment' => $this->getEnvironmentInfo()
        ];
    }

    /**
     * Get database diagnostics
     *
     * @return array
     */
    protected function getDatabaseDiagnostics(): array
    {
        try {
            $version = DB::selectOne('SELECT version()');
            $size = DB::selectOne("SELECT pg_size_pretty(pg_database_size(current_database())) as size");

            return [
                'version' => $version->version ?? 'Unknown',
                'size' => $size->size ?? 'Unknown',
                'driver' => config('database.default'),
                'host' => config('database.connections.pgsql.host')
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get cache diagnostics
     *
     * @return array
     */
    protected function getCacheDiagnostics(): array
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();

            return [
                'driver' => config('cache.default'),
                'key_count' => $redis->dbsize(),
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'uptime_days' => isset($info['uptime_in_seconds'])
                    ? round($info['uptime_in_seconds'] / 86400, 1)
                    : 0
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get configuration info
     *
     * @return array
     */
    protected function getConfigurationInfo(): array
    {
        return [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'session_driver' => config('session.driver')
        ];
    }

    /**
     * Get environment info
     *
     * @return array
     */
    protected function getEnvironmentInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit')
        ];
    }
}
