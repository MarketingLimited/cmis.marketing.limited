<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use App\Services\Dashboard\UnifiedDashboardService;
use Illuminate\Http\JsonResponse;

/**
 * @group Dashboard
 *
 * APIs for accessing unified organization dashboard with aggregated metrics, campaigns, and content.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UnifiedDashboardService $dashboardService
    ) {}

    /**
     * Get unified dashboard
     *
     * Retrieves comprehensive dashboard data for an organization including:
     * - Overview metrics (advertising & content)
     * - KPIs (targets vs actual)
     * - Active campaigns (top 5)
     * - Scheduled content (next 10)
     * - Recent posts (last 10)
     * - Connected accounts
     * - Alerts (budget, token expiry, sync failures)
     * - Sync status summary
     *
     * Data is cached for 15 minutes for optimal performance.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000",
     *   "org_name": "ACME Inc",
     *   "overview": {
     *     "period": "Last 30 days",
     *     "advertising": {
     *       "total_spend": 5000,
     *       "total_impressions": 100000,
     *       "total_clicks": 5000,
     *       "total_conversions": 120,
     *       "avg_ctr": 5.0,
     *       "avg_cpc": 1.0,
     *       "roi": 250
     *     },
     *     "content": {
     *       "posts_published": 45,
     *       "engagement_rate": 4.5
     *     }
     *   },
     *   "kpis": [
     *     {"name": "ROI", "target": 300, "actual": 250, "status": "in_progress"}
     *   ],
     *   "active_campaigns": [
     *     {
     *       "id": "uuid",
     *       "name": "Summer Campaign",
     *       "platform": "google",
     *       "budget": 10000,
     *       "spend": 7500,
     *       "budget_used_pct": 75,
     *       "impressions": 50000,
     *       "clicks": 2500,
     *       "ctr": 5.0
     *     }
     *   ],
     *   "scheduled_content": [],
     *   "recent_posts": [],
     *   "connected_accounts": {
     *     "total": 8,
     *     "by_platform": {
     *       "google": 2,
     *       "meta": 3
     *     }
     *   },
     *   "alerts": [],
     *   "sync_status": {
     *     "total": 8,
     *     "syncing": 0,
     *     "success": 7,
     *     "failed": 1
     *   },
     *   "updated_at": "2024-01-15T15:00:00Z"
     * }
     *
     * @authenticated
     */
    public function index(Org $org): JsonResponse
    {
        $dashboard = $this->dashboardService->getOrgDashboard($org);

        return $this->success($dashboard, 'Retrieved successfully');
    }

    /**
     * Refresh dashboard cache
     *
     * Forces a refresh of the dashboard cache and returns updated data.
     * Use this endpoint when you need real-time data instead of cached data.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "message": "Dashboard refreshed",
     *   "data": {
     *     "org_id": "550e8400-e29b-41d4-a716-446655440000",
     *     "org_name": "ACME Inc",
     *     "overview": {},
     *     "updated_at": "2024-01-15T15:00:00Z"
     *   }
     * }
     *
     * @authenticated
     */
    public function refresh(Org $org): JsonResponse
    {
        $this->dashboardService->clearCache($org);
        $dashboard = $this->dashboardService->getOrgDashboard($org);

        return $this->success($dashboard, 'Dashboard refreshed');
    }
}
