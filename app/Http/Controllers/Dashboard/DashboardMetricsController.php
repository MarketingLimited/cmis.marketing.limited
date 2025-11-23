<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Campaign;
use App\Models\CreativeAsset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Metrics Controller
 *
 * Handles statistics, metrics, and performance data for dashboard
 */
class DashboardMetricsController extends Controller
{
    use ApiResponse;

    /**
     * Get dashboard statistics (automatically filtered by RLS)
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = [
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::where('status', 'active')->count(),
            'total_content' => DB::table('cmis.content_items')->count(),
            'total_assets' => CreativeAsset::count(),
        ];

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get recent activity
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $activities = $this->getRecentActivity($orgId);

        return $this->success($activities, 'Recent activity retrieved successfully');
    }

    /**
     * Get campaigns summary (automatically filtered by RLS)
     */
    public function campaignsSummary(Request $request): JsonResponse
    {
        $summary = [
            'total' => Campaign::count(),
            'active' => Campaign::where('status', 'active')->count(),
            'completed' => Campaign::where('status', 'completed')->count(),
            'draft' => Campaign::where('status', 'draft')->count(),
        ];

        return $this->success($summary, 'Campaigns summary retrieved successfully');
    }

    /**
     * Get analytics overview
     */
    public function analyticsOverview(Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $overview = [
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'ctr' => 0,
        ];

        return $this->success($overview, 'Analytics overview retrieved successfully');
    }

    /**
     * Get upcoming social media posts (automatically filtered by RLS)
     */
    public function upcomingPosts(Request $request): JsonResponse
    {
        $posts = DB::table('cmis.scheduled_social_posts')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        return $this->success($posts, 'Upcoming posts retrieved successfully');
    }

    /**
     * Get campaigns performance chart data
     */
    public function campaignsPerformance(Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $performance = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Active Campaigns',
                    'data' => [5, 8, 12, 10, 15, 18],
                ],
            ],
        ];

        return $this->success($performance, 'Campaigns performance retrieved successfully');
    }

    /**
     * Get engagement chart data
     */
    public function engagement(Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $engagement = [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'datasets' => [
                [
                    'label' => 'Engagement',
                    'data' => [120, 150, 180, 170, 200, 190, 160],
                ],
            ],
        ];

        return $this->success($engagement, 'Engagement data retrieved successfully');
    }

    /**
     * Get top performing campaigns (automatically filtered by RLS)
     */
    public function topCampaigns(Request $request): JsonResponse
    {
        $campaigns = Campaign::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['campaign_id', 'name', 'status', 'budget', 'start_date', 'end_date']);

        return $this->success($campaigns, 'Top campaigns retrieved successfully');
    }

    /**
     * Get budget summary (automatically filtered by RLS)
     */
    public function budgetSummary(Request $request): JsonResponse
    {
        $totalBudget = Campaign::sum('budget') ?? 0;

        $summary = [
            'total_budget' => $totalBudget,
            'spent' => 0,
            'remaining' => $totalBudget,
            'allocated' => $totalBudget,
        ];

        return $this->success($summary, 'Budget summary retrieved successfully');
    }

    /**
     * Resolve organization ID from request
     */
    private function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        if ($user->active_org_id ?? null) {
            return $user->active_org_id;
        }

        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }

    /**
     * Get recent activity for organization
     */
    private function getRecentActivity(string $orgId): array
    {
        return [];
    }
}
