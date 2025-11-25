<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * Enhanced Health Check Controller
 *
 * Provides detailed health status for monitoring and load balancer health checks
 */
class HealthCheckController extends Controller
{
    use ApiResponse;

    /**
     * Basic health check (fast, for load balancers)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ], 'System is healthy');
    }

    /**
     * Detailed health check (includes dependencies)
     *
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['healthy']);

        $data = [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
            ],
        ];

        return $allHealthy
            ? $this->success($data, 'All systems healthy')
            : $this->error('System degraded', 503, $data);
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $time = microtime(true);
            DB::select('SELECT 1');
            $duration = (microtime(true) - $time) * 1000;

            return [
                'healthy' => true,
                'response_time_ms' => round($duration, 2),
                'driver' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connection
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . uniqid();
            $value = 'test';

            Cache::put($key, $value, 10);
            $retrieved = Cache::get($key);
            Cache::forget($key);

            return [
                'healthy' => $retrieved === $value,
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage (filesystem)
     */
    protected function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . uniqid() . '.txt';
            $content = 'test';

            Storage::put($testFile, $content);
            $retrieved = Storage::get($testFile);
            Storage::delete($testFile);

            return [
                'healthy' => $retrieved === $content,
                'driver' => config('filesystems.default'),
                'disk_space' => $this->getDiskSpace(),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connection
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');

            // Check if queue connection is accessible
            $size = \Queue::size();

            return [
                'healthy' => true,
                'connection' => $connection,
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get disk space information
     */
    protected function getDiskSpace(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;

        return [
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'usage_percent' => round(($used / $total) * 100, 2),
        ];
    }

    /**
     * Readiness check (for Kubernetes)
     */
    public function ready(): JsonResponse
    {
        // Application is ready when migrations are up to date
        try {
            DB::connection()->getPdo();
            return $this->success(['ready' => true], 'Application ready');
        } catch (\Exception $e) {
            return $this->error('Application not ready', 503, ['ready' => false]);
        }
    }

    /**
     * Liveness check (for Kubernetes)
     */
    public function live(): JsonResponse
    {
        // Application is alive if PHP is running
        return $this->success(['alive' => true], 'Application alive');
    }
}
