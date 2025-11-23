<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Optimization\PerformanceProfiler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Performance Monitoring Controller
 *
 * Manages performance profiling, metrics, and monitoring
 */
class PerformanceMonitoringController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PerformanceProfiler $profiler
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get recent performance profiles
     *
     * GET /api/optimization/performance/profiles
     */
    public function profiles(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $limit = $validated['limit'] ?? 20;
            $result = $this->profiler->getRecentProfiles($limit);

            return $this->success($result, 'Performance profiles retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get performance summary statistics
     *
     * GET /api/optimization/performance/summary
     */
    public function summary(): JsonResponse
    {
        try {
            $result = $this->profiler->getSummaryStatistics();

            return $this->success($result, 'Performance summary retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get system resources
     *
     * GET /api/optimization/performance/resources
     */
    public function resources(): JsonResponse
    {
        try {
            $result = $this->profiler->getSystemResources();

            return $this->success($result, 'System resources retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get slow queries
     *
     * GET /api/optimization/performance/slow-queries
     */
    public function slowQueries(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $limit = $validated['limit'] ?? 20;
            $result = $this->profiler->getSlowQueries($limit);

            return $this->success($result, 'Slow queries retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get application health metrics
     *
     * GET /api/optimization/performance/health-metrics
     */
    public function healthMetrics(): JsonResponse
    {
        try {
            $result = $this->profiler->getHealthMetrics();

            return $this->success($result, 'Health metrics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Clear performance data
     *
     * POST /api/optimization/performance/clear
     */
    public function clear(): JsonResponse
    {
        try {
            $success = $this->profiler->clearData();

            if ($success) {
                return $this->success(null, 'Performance data cleared successfully');
            }

            return $this->error('Failed to clear performance data');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
