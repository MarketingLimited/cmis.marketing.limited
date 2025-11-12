<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controller for analytics and reporting across all platforms
 */
class AnalyticsController extends Controller
{
    /**
     * Get overview analytics for organization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOverview(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30); // days

            $startDate = now()->subDays($period);

            // Total posts published
            $totalPosts = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->count();

            // Total comments
            $totalComments = DB::table('cmis_social.social_comments')
                ->where('org_id', $orgId)
                ->where('created_at', '>=', $startDate)
                ->count();

            // Total messages
            $totalMessages = DB::table('cmis_social.social_messages')
                ->where('org_id', $orgId)
                ->where('received_at', '>=', $startDate)
                ->count();

            // Active campaigns
            $activeCampaigns = DB::table('cmis_ads.ad_campaigns')
                ->where('org_id', $orgId)
                ->whereIn('status', ['ACTIVE', 'ENABLED', 'active'])
                ->count();

            // Connected platforms
            $connectedPlatforms = Integration::where('org_id', $orgId)
                ->where('is_active', true)
                ->count();

            // Posts by platform
            $postsByPlatform = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->select('platform', DB::raw('count(*) as count'))
                ->groupBy('platform')
                ->get();

            // Daily posts trend
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

            return response()->json([
                'success' => true,
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
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get analytics overview: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get platform-specific analytics
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlatformAnalytics(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);
            $metrics = $connector->getAccountMetrics($integration);

            return response()->json([
                'success' => true,
                'platform' => $integration->platform,
                'metrics' => $metrics,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get platform analytics: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get post performance analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPostPerformance(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $limit = $request->input('limit', 20);
            $platform = $request->input('platform');

            $query = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->orderBy('published_at', 'desc')
                ->limit($limit);

            if ($platform) {
                $query->where('platform', $platform);
            }

            $posts = $query->get();

            return response()->json([
                'success' => true,
                'posts' => $posts,
                'total' => $posts->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get post performance: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign performance analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaignPerformance(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            $campaigns = DB::table('cmis_ads.ad_campaigns')
                ->where('org_id', $orgId)
                ->where('created_at', '>=', $startDate)
                ->get();

            // Get metrics for each campaign
            $campaignMetrics = [];
            foreach ($campaigns as $campaign) {
                $metrics = DB::table('cmis_ads.ad_metrics')
                    ->where('campaign_id', $campaign->campaign_id)
                    ->where('date', '>=', $startDate)
                    ->select(
                        DB::raw('SUM(impressions) as total_impressions'),
                        DB::raw('SUM(clicks) as total_clicks'),
                        DB::raw('SUM(spend) as total_spend'),
                        DB::raw('SUM(conversions) as total_conversions')
                    )
                    ->first();

                $campaignMetrics[] = [
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'platform' => $campaign->platform,
                    'status' => $campaign->status,
                    'metrics' => $metrics,
                ];
            }

            return response()->json([
                'success' => true,
                'period_days' => $period,
                'campaigns' => $campaignMetrics,
                'total_campaigns' => count($campaignMetrics),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get campaign performance: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get engagement analytics (comments, messages)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEngagementAnalytics(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            // Comments by platform
            $commentsByPlatform = DB::table('cmis_social.social_comments')
                ->where('org_id', $orgId)
                ->where('created_at', '>=', $startDate)
                ->select('platform', DB::raw('count(*) as count'))
                ->groupBy('platform')
                ->get();

            // Messages by platform
            $messagesByPlatform = DB::table('cmis_social.social_messages')
                ->where('org_id', $orgId)
                ->where('received_at', '>=', $startDate)
                ->select('platform', DB::raw('count(*) as count'))
                ->groupBy('platform')
                ->get();

            // Daily engagement trend
            $dailyComments = DB::table('cmis_social.social_comments')
                ->where('org_id', $orgId)
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $dailyMessages = DB::table('cmis_social.social_messages')
                ->where('org_id', $orgId)
                ->where('received_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(received_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'period_days' => $period,
                'comments_by_platform' => $commentsByPlatform,
                'messages_by_platform' => $messagesByPlatform,
                'daily_comments_trend' => $dailyComments,
                'daily_messages_trend' => $dailyMessages,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get engagement analytics: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export analytics report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30);
            $format = $request->input('format', 'json'); // json, csv, pdf

            $startDate = now()->subDays($period);

            // Gather all data
            $report = [
                'generated_at' => now()->toIso8601String(),
                'period_days' => $period,
                'organization_id' => $orgId,
                'posts' => DB::table('cmis_social.social_posts')
                    ->where('org_id', $orgId)
                    ->where('published_at', '>=', $startDate)
                    ->get(),
                'comments' => DB::table('cmis_social.social_comments')
                    ->where('org_id', $orgId)
                    ->where('created_at', '>=', $startDate)
                    ->get(),
                'messages' => DB::table('cmis_social.social_messages')
                    ->where('org_id', $orgId)
                    ->where('received_at', '>=', $startDate)
                    ->get(),
                'campaigns' => DB::table('cmis_ads.ad_campaigns')
                    ->where('org_id', $orgId)
                    ->where('created_at', '>=', $startDate)
                    ->get(),
            ];

            // For now, return JSON (CSV/PDF export can be implemented later)
            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to export report: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
