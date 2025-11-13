<?php

namespace App\Http\Controllers;

use App\Services\PerformanceOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * PerformanceController
 *
 * Handles performance monitoring and optimization
 * Implements Sprint 6.1: Performance Optimization
 */
class PerformanceController extends Controller
{
    protected PerformanceOptimizationService $performanceService;

    public function __construct(PerformanceOptimizationService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Get performance metrics
     * GET /api/orgs/{org_id}/performance/metrics?start_date=2025-01-01&end_date=2025-01-31
     */
    public function getMetrics(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->performanceService->getPerformanceMetrics($orgId, $request->all());
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to get metrics', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear cache
     * POST /api/orgs/{org_id}/performance/cache/clear
     */
    public function clearCache(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tags' => 'nullable|array',
            'keys' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->performanceService->clearCache($request->all());
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to clear cache', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Warm up cache
     * POST /api/orgs/{org_id}/performance/cache/warmup
     */
    public function warmupCache(string $orgId): JsonResponse
    {
        try {
            $result = $this->performanceService->warmupCache($orgId);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to warm up cache', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Optimize database
     * POST /api/orgs/{org_id}/performance/optimize-database
     */
    public function optimizeDatabase(string $orgId): JsonResponse
    {
        try {
            $result = $this->performanceService->optimizeDatabase($orgId);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to optimize database', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get slow queries
     * GET /api/orgs/{org_id}/performance/slow-queries?threshold=500
     */
    public function getSlowQueries(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'threshold' => 'nullable|integer|min:100|max:10000'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $threshold = $request->input('threshold', 500);
            $result = $this->performanceService->getSlowQueries($orgId, $threshold);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to get slow queries', 'error' => $e->getMessage()], 500);
        }
    }
}
