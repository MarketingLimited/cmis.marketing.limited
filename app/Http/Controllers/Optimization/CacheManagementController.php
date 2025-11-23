<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Optimization\MultiLayerCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cache Management Controller
 *
 * Manages cache operations, invalidation, and health monitoring
 */
class CacheManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MultiLayerCacheService $cacheService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get cache statistics
     *
     * GET /api/optimization/cache/statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $result = $this->cacheService->getStatistics();

            return $this->success($result, 'Cache statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Invalidate cache for organization
     *
     * POST /api/optimization/cache/invalidate/organization/{orgId}
     */
    public function invalidateOrganization(string $orgId): JsonResponse
    {
        try {
            $deleted = $this->cacheService->invalidateOrganization($orgId);

            return $this->success([
                'keys_deleted' => $deleted
            ], 'Organization cache invalidated successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Invalidate cache for campaign
     *
     * POST /api/optimization/cache/invalidate/campaign/{campaignId}
     */
    public function invalidateCampaign(string $campaignId): JsonResponse
    {
        try {
            $deleted = $this->cacheService->invalidateCampaign($campaignId);

            return $this->success([
                'keys_deleted' => $deleted
            ], 'Campaign cache invalidated successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Invalidate cache by pattern
     *
     * POST /api/optimization/cache/invalidate-pattern
     */
    public function invalidatePattern(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pattern' => 'required|string'
        ]);

        try {
            $deleted = $this->cacheService->invalidatePattern($validated['pattern']);

            return $this->success([
                'keys_deleted' => $deleted
            ], 'Cache pattern invalidated successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Warm up cache for organization
     *
     * POST /api/optimization/cache/warmup/{orgId}
     */
    public function warmup(string $orgId): JsonResponse
    {
        try {
            $result = $this->cacheService->warmup($orgId);

            return $this->success($result, 'Cache warmup completed successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get cache health status
     *
     * GET /api/optimization/cache/health
     */
    public function health(): JsonResponse
    {
        try {
            $result = $this->cacheService->getHealthStatus();

            return $this->success($result, 'Cache health retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get top cached keys
     *
     * GET /api/optimization/cache/top-keys
     */
    public function topKeys(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $limit = $validated['limit'] ?? 20;
            $result = $this->cacheService->getTopKeys($limit);

            return $this->success($result, 'Top cache keys retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Flush all caches (DANGER)
     *
     * POST /api/optimization/cache/flush
     */
    public function flush(): JsonResponse
    {
        try {
            $success = $this->cacheService->flushAll();

            if ($success) {
                return $this->success(null, 'All caches flushed successfully');
            }

            return $this->error('Cache flush failed');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
