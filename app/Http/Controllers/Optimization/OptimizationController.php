<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use App\Services\Optimization\DatabaseQueryOptimizer;
use App\Services\Optimization\MultiLayerCacheService;
use App\Services\Optimization\PerformanceProfiler;
use App\Services\Optimization\HealthCheckService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;

/**
 * Optimization Controller (Phase 6)
 *
 * Unified API for performance optimization, caching, and health monitoring
 */
class OptimizationController extends Controller
{
    protected DatabaseQueryOptimizer $queryOptimizer;
    protected MultiLayerCacheService $cacheService;
    protected PerformanceProfiler $profiler;
    protected HealthCheckService $healthCheck;

    public function __construct(
        DatabaseQueryOptimizer $queryOptimizer,
        MultiLayerCacheService $cacheService,
        PerformanceProfiler $profiler,
        HealthCheckService $healthCheck
    ) {
        // Health check endpoints are public for Kubernetes probes
        $this->middleware('auth:sanctum')->except([
            'liveness',
            'readiness',
            'health'
        ]);

        $this->queryOptimizer = $queryOptimizer;
        $this->cacheService = $cacheService;
        $this->profiler = $profiler;
        $this->healthCheck = $healthCheck;
    }

    // =========================================================================
    // HEALTH CHECK ENDPOINTS (Public - Kubernetes Probes)
    // =========================================================================

    /**
     * Liveness probe endpoint
     *
     * GET /api/health/live
     */
    public function liveness(): JsonResponse
    {
        $result = $this->healthCheck->liveness();
        $status = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $status);
    }

    /**
     * Readiness probe endpoint
     *
     * GET /api/health/ready
     */
    public function readiness(): JsonResponse
    {
        $result = $this->healthCheck->readiness();
        $status = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $status);
    }

    /**
     * Comprehensive health check
     *
     * GET /api/health
     */
    public function health(): JsonResponse
    {
        $result = $this->healthCheck->health();
        $status = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $status);
    }

    /**
     * Detailed diagnostics (authenticated)
     *
     * GET /api/optimization/diagnostics
     */
    public function diagnostics(): JsonResponse
    {
        $result = $this->healthCheck->diagnostics();

        return response()->json($result);
    }

    // =========================================================================
    // DATABASE OPTIMIZATION ENDPOINTS
    // =========================================================================

    /**
     * Analyze query performance
     *
     * POST /api/optimization/analyze-query
     */
    public function analyzeQuery(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string',
            'bindings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->queryOptimizer->analyzeQuery(
                $request->input('query'),
                $request->input('bindings', [])
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get missing indexes for a table
     *
     * GET /api/optimization/missing-indexes/{table}
     */
    public function getMissingIndexes(string $table): JsonResponse
    {
        try {
            $result = $this->queryOptimizer->getMissingIndexes($table);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get database statistics
     *
     * GET /api/optimization/database-stats
     */
    public function getDatabaseStatistics(): JsonResponse
    {
        try {
            $result = $this->queryOptimizer->getDatabaseStatistics();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize a table
     *
     * POST /api/optimization/optimize-table/{table}
     */
    public function optimizeTable(string $table): JsonResponse
    {
        try {
            $result = $this->queryOptimizer->optimizeTable($table);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // CACHE MANAGEMENT ENDPOINTS
    // =========================================================================

    /**
     * Get cache statistics
     *
     * GET /api/optimization/cache/statistics
     */
    public function getCacheStatistics(): JsonResponse
    {
        try {
            $result = $this->cacheService->getStatistics();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate cache for organization
     *
     * POST /api/optimization/cache/invalidate/organization/{org_id}
     */
    public function invalidateOrganizationCache(string $orgId): JsonResponse
    {
        try {
            $deleted = $this->cacheService->invalidateOrganization($orgId);

            return response()->json([
                'success' => true,
                'message' => 'Organization cache invalidated',
                'keys_deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate cache for campaign
     *
     * POST /api/optimization/cache/invalidate/campaign/{campaign_id}
     */
    public function invalidateCampaignCache(string $campaignId): JsonResponse
    {
        try {
            $deleted = $this->cacheService->invalidateCampaign($campaignId);

            return response()->json([
                'success' => true,
                'message' => 'Campaign cache invalidated',
                'keys_deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate cache by pattern
     *
     * POST /api/optimization/cache/invalidate-pattern
     */
    public function invalidateCachePattern(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pattern' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $deleted = $this->cacheService->invalidatePattern($request->input('pattern'));

            return response()->json([
                'success' => true,
                'message' => 'Cache pattern invalidated',
                'keys_deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Warm up cache for organization
     *
     * POST /api/optimization/cache/warmup/{org_id}
     */
    public function warmupCache(string $orgId): JsonResponse
    {
        try {
            $result = $this->cacheService->warmup($orgId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cache health status
     *
     * GET /api/optimization/cache/health
     */
    public function getCacheHealth(): JsonResponse
    {
        try {
            $result = $this->cacheService->getHealthStatus();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top cached keys
     *
     * GET /api/optimization/cache/top-keys
     */
    public function getTopCachedKeys(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $limit = $request->input('limit', 20);
            $result = $this->cacheService->getTopKeys($limit);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Flush all caches (DANGER)
     *
     * POST /api/optimization/cache/flush
     */
    public function flushCache(): JsonResponse
    {
        try {
            $success = $this->cacheService->flushAll();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'All caches flushed successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Cache flush failed'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // PERFORMANCE PROFILING ENDPOINTS
    // =========================================================================

    /**
     * Get recent performance profiles
     *
     * GET /api/optimization/performance/profiles
     */
    public function getPerformanceProfiles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $limit = $request->input('limit', 20);
            $result = $this->profiler->getRecentProfiles($limit);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance summary statistics
     *
     * GET /api/optimization/performance/summary
     */
    public function getPerformanceSummary(): JsonResponse
    {
        try {
            $result = $this->profiler->getSummaryStatistics();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system resources
     *
     * GET /api/optimization/performance/resources
     */
    public function getSystemResources(): JsonResponse
    {
        try {
            $result = $this->profiler->getSystemResources();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get slow queries
     *
     * GET /api/optimization/performance/slow-queries
     */
    public function getSlowQueries(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $limit = $request->input('limit', 20);
            $result = $this->profiler->getSlowQueries($limit);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get application health metrics
     *
     * GET /api/optimization/performance/health-metrics
     */
    public function getHealthMetrics(): JsonResponse
    {
        try {
            $result = $this->profiler->getHealthMetrics();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear performance data
     *
     * POST /api/optimization/performance/clear
     */
    public function clearPerformanceData(): JsonResponse
    {
        try {
            $success = $this->profiler->clearData();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Performance data cleared successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear performance data'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
