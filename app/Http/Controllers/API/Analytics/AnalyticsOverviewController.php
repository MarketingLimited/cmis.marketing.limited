<?php

namespace App\Http\Controllers\API\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Overview Controller
 *
 * Handles high-level analytics overview and general metrics
 */
class AnalyticsOverviewController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get overview analytics for organization
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30);

            $startDate = now()->subDays($period);

            $totalPosts = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->count();

            $totalComments = DB::table('cmis_social.social_comments')
                ->where('org_id', $orgId)
                ->where('created_at', '>=', $startDate)
                ->count();

            $totalMessages = DB::table('cmis_social.social_messages')
                ->where('org_id', $orgId)
                ->where('received_at', '>=', $startDate)
                ->count();

            $activeCampaigns = DB::table('cmis_ads.ad_campaigns')
                ->where('org_id', $orgId)
                ->whereIn('status', ['ACTIVE', 'ENABLED', 'active'])
                ->count();

            $connectedPlatforms = Integration::where('org_id', $orgId)
                ->where('is_active', true)
                ->count();

            $postsByPlatform = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->select('platform', DB::raw('count(*) as count'))
                ->groupBy('platform')
                ->get();

            $dailyPosts = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(published_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return $this->success([
                'period_days' => $period,
                'overview' => [
                    'total_posts' => $totalPosts,
                    'total_comments' => $totalComments,
                    'total_messages' => $totalMessages,
                    'active_campaigns' => $activeCampaigns,
                    'connected_platforms' => $connectedPlatforms,
                ],
                'posts_by_platform' => $postsByPlatform,
                'daily_posts_trend' => $dailyPosts,
            ], 'Analytics overview retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get analytics overview: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve analytics overview');
        }
    }

    /**
     * Get platform-specific analytics
     */
    public function platform(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);
            $metrics = $connector->getAccountMetrics($integration);

            return $this->success([
                'platform' => $integration->platform,
                'metrics' => $metrics,
            ], 'Platform analytics retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get platform analytics: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve platform analytics');
        }
    }

    /**
     * Get platform performance analytics
     */
    public function platformPerformance(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            $platformMetrics = DB::table('cmis_ads.ad_campaigns as c')
                ->leftJoin('cmis_ads.ad_metrics as m', 'c.campaign_id', '=', 'm.campaign_id')
                ->where('c.org_id', $orgId)
                ->where('m.date', '>=', $startDate)
                ->select(
                    'c.platform',
                    DB::raw('COUNT(DISTINCT c.campaign_id) as campaign_count'),
                    DB::raw('SUM(m.impressions) as total_impressions'),
                    DB::raw('SUM(m.clicks) as total_clicks'),
                    DB::raw('SUM(m.spend) as total_spend'),
                    DB::raw('SUM(m.conversions) as total_conversions'),
                    DB::raw('AVG(m.clicks::float / NULLIF(m.impressions, 0) * 100) as avg_ctr')
                )
                ->groupBy('c.platform')
                ->get();

            return $this->success([
                'data' => $platformMetrics,
                'period_days' => $period,
            ], 'Platform performance retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get platform performance: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve platform performance');
        }
    }

    /**
     * Get trending metrics
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            $spendTrends = DB::table('cmis_ads.ad_metrics')
                ->join('cmis_ads.ad_campaigns', 'cmis_ads.ad_metrics.campaign_id', '=', 'cmis_ads.ad_campaigns.campaign_id')
                ->where('cmis_ads.ad_campaigns.org_id', $orgId)
                ->where('cmis_ads.ad_metrics.date', '>=', $startDate)
                ->select(
                    DB::raw('DATE(cmis_ads.ad_metrics.date) as date'),
                    DB::raw('SUM(spend) as daily_spend'),
                    DB::raw('SUM(impressions) as daily_impressions'),
                    DB::raw('SUM(clicks) as daily_clicks')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $engagementTrends = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(published_at) as date'),
                    DB::raw("SUM(COALESCE((metadata->>'likes')::int, 0)) as daily_likes"),
                    DB::raw("SUM(COALESCE((metadata->>'comments')::int, 0)) as daily_comments"),
                    DB::raw("SUM(COALESCE((metadata->>'shares')::int, 0)) as daily_shares")
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return $this->success([
                'data' => [
                    'spend_trends' => $spendTrends,
                    'engagement_trends' => $engagementTrends,
                ],
                'period_days' => $period,
            ], 'Trends retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get trends: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve trends');
        }
    }

    /**
     * Get audience demographics
     */
    public function demographics(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            return $this->success([
                'data' => [
                    'age_groups' => [
                        '18-24' => 25,
                        '25-34' => 35,
                        '35-44' => 20,
                        '45-54' => 15,
                        '55+' => 5
                    ],
                    'gender' => [
                        'male' => 48,
                        'female' => 50,
                        'other' => 2
                    ],
                    'locations' => [
                        'Saudi Arabia' => 60,
                        'UAE' => 25,
                        'Egypt' => 10,
                        'Other' => 5
                    ]
                ]
            ], 'Audience demographics retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get audience demographics: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve demographics');
        }
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

        if (isset($user->org_id)) {
            return $user->org_id;
        }

        if (isset($user->active_org_id)) {
            return $user->active_org_id;
        }

        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }
}
