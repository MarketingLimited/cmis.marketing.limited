<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use App\Services\Cache\CacheService;
use Illuminate\Http\{Request, JsonResponse};

/**
 * @group Cache Management
 *
 * APIs for managing application cache
 */
class CacheController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Get cache statistics
     *
     * Returns Redis cache statistics including hit rate, memory usage, and connections.
     *
     * @response 200 {
     *   "redis_version": "7.0.5",
     *   "used_memory_human": "2.5M",
     *   "connected_clients": 5,
     *   "total_commands_processed": 15420,
     *   "keyspace_hits": 12500,
     *   "keyspace_misses": 1200,
     *   "hit_rate": 91.23
     * }
     *
     * @authenticated
     */
    public function stats(): JsonResponse
    {
        $stats = $this->cacheService->getStats();

        return response()->json($stats);
    }

    /**
     * Clear organization cache
     *
     * Clears all cached data for a specific organization including dashboard,
     * metrics, campaigns, and sync status.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "message": "Organization cache cleared successfully",
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000"
     * }
     *
     * @authenticated
     */
    public function clearOrg(Org $org): JsonResponse
    {
        $this->cacheService->clearOrg($org->org_id);

        return response()->json([
            'message' => 'Organization cache cleared successfully',
            'org_id' => $org->org_id,
        ]);
    }

    /**
     * Clear dashboard cache
     *
     * Clears only the dashboard cache for an organization.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "message": "Dashboard cache cleared",
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000"
     * }
     *
     * @authenticated
     */
    public function clearDashboard(Org $org): JsonResponse
    {
        $this->cacheService->clearDashboard($org->org_id);

        return response()->json([
            'message' => 'Dashboard cache cleared',
            'org_id' => $org->org_id,
        ]);
    }

    /**
     * Clear campaigns cache
     *
     * Clears campaigns list cache for an organization.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "message": "Campaigns cache cleared",
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000"
     * }
     *
     * @authenticated
     */
    public function clearCampaigns(Org $org): JsonResponse
    {
        $this->cacheService->clearCampaigns($org->org_id);

        return response()->json([
            'message' => 'Campaigns cache cleared',
            'org_id' => $org->org_id,
        ]);
    }

    /**
     * Warm cache
     *
     * Proactively populates cache with frequently accessed data.
     * Useful to run during low-traffic periods.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @bodyParam services array Services to warm cache for. Default: all. Example: ["dashboard", "campaigns"]
     *
     * @response 200 {
     *   "message": "Cache warmed successfully",
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000",
     *   "services": ["dashboard"]
     * }
     *
     * @authenticated
     */
    public function warmCache(Request $request, Org $org): JsonResponse
    {
        $services = $request->input('services', []);

        $this->cacheService->warmCache($org, $services);

        return response()->json([
            'message' => 'Cache warmed successfully',
            'org_id' => $org->org_id,
            'services' => empty($services) ? ['all'] : $services,
        ]);
    }
}
