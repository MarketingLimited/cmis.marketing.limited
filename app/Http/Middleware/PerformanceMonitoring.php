<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Performance Monitoring Middleware
 *
 * Tracks request timing, memory usage, and slow queries
 */
class PerformanceMonitoring
{
    protected float $startTime;
    protected int $startMemory;
    protected int $queryCount = 0;
    protected float $queryTime = 0.0;

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start monitoring
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();

        // Enable query logging in development/staging
        if (!app()->isProduction()) {
            \DB::enableQueryLog();
        }

        // Process request
        $response = $next($request);

        // Calculate metrics
        $duration = (microtime(true) - $this->startTime) * 1000; // milliseconds
        $memoryUsed = (memory_get_usage() - $this->startMemory) / 1024 / 1024; // MB

        // Get query information
        if (!app()->isProduction()) {
            $queries = \DB::getQueryLog();
            $this->queryCount = count($queries);
            $this->queryTime = array_sum(array_column($queries, 'time'));
        }

        // Add performance headers
        $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', round($memoryUsed, 2) . 'MB');

        if (!app()->isProduction()) {
            $response->headers->set('X-Query-Count', $this->queryCount);
            $response->headers->set('X-Query-Time', round($this->queryTime, 2) . 'ms');
        }

        // Log slow requests
        $slowThreshold = config('monitoring.slow_request_threshold', 1000); // 1 second
        if ($duration > $slowThreshold) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round($memoryUsed, 2),
                'query_count' => $this->queryCount,
                'user_id' => auth()->id(),
                'org_id' => session('current_org_id'),
            ]);
        }

        // Log excessive queries
        $queryThreshold = config('monitoring.query_count_threshold', 50);
        if ($this->queryCount > $queryThreshold) {
            Log::warning('Excessive database queries', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'query_count' => $this->queryCount,
                'query_time_ms' => round($this->queryTime, 2),
            ]);
        }

        return $response;
    }
}
