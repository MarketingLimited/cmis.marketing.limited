<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\ApiResponse;

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
    use ApiResponse;

    protected PerformanceOptimizationService $performanceService;

    public function __construct(PerformanceOptimizationService $performanceService)
    {
        $this->middleware('auth:sanctum');
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
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->performanceService->getPerformanceMetrics($orgId, $request->all());
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get metrics: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->performanceService->clearCache($request->all());
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to clear cache: ' . $e->getMessage());
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
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to warm up cache: ' . $e->getMessage());
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
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to optimize database: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $threshold = $request->input('threshold', 500);
            $result = $this->performanceService->getSlowQueries($orgId, $threshold);
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get slow queries' . ': ' . $e->getMessage());
        }
    }
}
