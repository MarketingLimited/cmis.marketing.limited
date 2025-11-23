<?php

namespace App\Http\Controllers\API\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Social Analytics Controller
 *
 * Handles social media analytics including posts, engagement, and content performance
 */
class SocialAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get post performance analytics
     */
    public function posts(Request $request): JsonResponse
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

            return $this->success([
                'posts' => $posts,
                'total' => $posts->count(),
            ], 'Post performance retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get post performance: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve post performance');
        }
    }

    /**
     * Get engagement analytics (comments, messages)
     */
    public function engagement(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            $commentsByPlatform = DB::table('cmis_social.social_comments')
                ->where('org_id', $orgId)
                ->where('created_at', '>=', $startDate)
                ->select('platform', DB::raw('count(*) as count'))
                ->groupBy('platform')
                ->get();

            $messagesByPlatform = DB::table('cmis_social.social_messages')
                ->where('org_id', $orgId)
                ->where('received_at', '>=', $startDate)
                ->select('platform', DB::raw('count(*) as count'))
                ->groupBy('platform')
                ->get();

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

            return $this->success([
                'period_days' => $period,
                'comments_by_platform' => $commentsByPlatform,
                'messages_by_platform' => $messagesByPlatform,
                'daily_comments_trend' => $dailyComments,
                'daily_messages_trend' => $dailyMessages,
            ], 'Engagement analytics retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get engagement analytics: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve engagement analytics');
        }
    }

    /**
     * Get content performance analytics
     */
    public function content(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            $contentMetrics = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->select(
                    'post_id',
                    'platform',
                    'content',
                    'published_at',
                    DB::raw("COALESCE((metadata->>'likes')::int, 0) as likes"),
                    DB::raw("COALESCE((metadata->>'comments')::int, 0) as comments"),
                    DB::raw("COALESCE((metadata->>'shares')::int, 0) as shares"),
                    DB::raw("COALESCE((metadata->>'reach')::int, 0) as reach")
                )
                ->orderByRaw("(COALESCE((metadata->>'likes')::int, 0) + COALESCE((metadata->>'comments')::int, 0) + COALESCE((metadata->>'shares')::int, 0)) DESC")
                ->limit(20)
                ->get();

            return $this->success([
                'data' => $contentMetrics,
                'period_days' => $period,
            ], 'Content performance retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get content performance: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve content performance');
        }
    }

    /**
     * Get social media analytics
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            $postsByPlatform = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->select('platform', DB::raw('COUNT(*) as count'))
                ->groupBy('platform')
                ->get();

            $totalEngagement = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->select(
                    DB::raw("SUM(COALESCE((metadata->>'likes')::int, 0)) as total_likes"),
                    DB::raw("SUM(COALESCE((metadata->>'comments')::int, 0)) as total_comments"),
                    DB::raw("SUM(COALESCE((metadata->>'shares')::int, 0)) as total_shares"),
                    DB::raw("SUM(COALESCE((metadata->>'reach')::int, 0)) as total_reach")
                )
                ->first();

            $dailyTrends = DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(published_at) as date'),
                    DB::raw('COUNT(*) as post_count'),
                    DB::raw("SUM(COALESCE((metadata->>'likes')::int, 0)) as likes"),
                    DB::raw("SUM(COALESCE((metadata->>'comments')::int, 0)) as comments")
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return $this->success([
                'data' => [
                    'posts_by_platform' => $postsByPlatform,
                    'total_engagement' => $totalEngagement,
                    'daily_trends' => $dailyTrends,
                ],
                'period_days' => $period,
            ], 'Social analytics retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get social analytics: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve social analytics');
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
