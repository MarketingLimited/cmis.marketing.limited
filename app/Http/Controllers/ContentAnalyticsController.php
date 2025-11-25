<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\ContentAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ContentAnalyticsController
 *
 * Provides deep content performance analysis
 * Implements Sprint 3.2: Content Performance Analysis
 *
 * Features:
 * - Post-level detailed analytics
 * - Hashtag performance tracking
 * - Audience demographics
 * - Engagement patterns
 * - Content type comparison
 * - Top performing content
 */
class ContentAnalyticsController extends Controller
{
    use ApiResponse;

    protected ContentAnalyticsService $analyticsService;

    public function __construct(ContentAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get detailed analytics for a specific post
     *
     * GET /api/orgs/{org_id}/content/analytics/post/{post_id}
     *
     * @param string $orgId
     * @param string $postId
     * @return JsonResponse
     */
    public function postAnalytics(string $orgId, string $postId): JsonResponse
    {
        try {
            $analytics = $this->analyticsService->getPostAnalytics($postId);

            if (!$analytics['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $analytics['error'] ?? 'Failed to load post analytics'
                ], 404);
            }

            return response()->json($analytics);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load post analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get hashtag performance analysis
     *
     * GET /api/orgs/{org_id}/content/analytics/hashtags/{social_account_id}?start_date=2025-01-01&end_date=2025-01-31&limit=50
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function hashtagAnalytics(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'limit' => $request->input('limit', 50)
        ];

        try {
            $analytics = $this->analyticsService->getHashtagAnalytics($socialAccountId, $filters);
            return response()->json($analytics);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load hashtag analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audience demographics insights
     *
     * GET /api/orgs/{org_id}/content/analytics/demographics/{social_account_id}
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function audienceDemographics(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date']);
            $demographics = $this->analyticsService->getAudienceDemographics($socialAccountId, $filters);

            return response()->json($demographics);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load audience demographics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get engagement patterns analysis
     *
     * GET /api/orgs/{org_id}/content/analytics/patterns/{social_account_id}?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function engagementPatterns(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $patterns = $this->analyticsService->getEngagementPatterns($socialAccountId, $filters);
            return response()->json($patterns);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load engagement patterns',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content type performance comparison
     *
     * GET /api/orgs/{org_id}/content/analytics/content-types/{social_account_id}?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function contentTypePerformance(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $performance = $this->analyticsService->getContentTypePerformance($socialAccountId, $filters);
            return response()->json($performance);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load content type performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top performing posts
     *
     * GET /api/orgs/{org_id}/content/analytics/top-posts/{social_account_id}?metric=engagement_rate&limit=10&start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function topPosts(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'metric' => 'nullable|in:engagement_rate,likes,shares,reach,comments',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'metric' => $request->input('metric', 'engagement_rate'),
            'limit' => $request->input('limit', 10)
        ];

        try {
            $topPosts = $this->analyticsService->getTopPosts($socialAccountId, $filters);
            return response()->json($topPosts);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load top posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive content analysis
     *
     * GET /api/orgs/{org_id}/content/analytics/comprehensive/{social_account_id}?start_date=2025-01-01&end_date=2025-01-31
     *
     * Returns all analytics in one response for dashboard view
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function comprehensiveAnalysis(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            // Get all analytics data
            $hashtags = $this->analyticsService->getHashtagAnalytics($socialAccountId, array_merge($filters, ['limit' => 20]));
            $patterns = $this->analyticsService->getEngagementPatterns($socialAccountId, $filters);
            $contentTypes = $this->analyticsService->getContentTypePerformance($socialAccountId, $filters);
            $topPosts = $this->analyticsService->getTopPosts($socialAccountId, array_merge($filters, ['limit' => 10]));

            return response()->json([
                'success' => true,
                'data' => [
                    'hashtags' => $hashtags,
                    'engagement_patterns' => $patterns,
                    'content_types' => $contentTypes,
                    'top_posts' => $topPosts
                ],
                'period' => [
                    'start' => $filters['start_date'],
                    'end' => $filters['end_date']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load comprehensive analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
