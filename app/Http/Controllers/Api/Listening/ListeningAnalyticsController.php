<?php

namespace App\Http\Controllers\Api\Listening;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Listening\SocialListeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Listening Analytics Controller
 *
 * Provides analytics and insights for social listening data
 */
class ListeningAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SocialListeningService $listeningService
    ) {}

    /**
     * Get listening statistics
     *
     * GET /api/listening/analytics/statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $keywordId = $request->keyword_id ?? null;
        $days = $request->days ?? 30;

        $stats = $this->listeningService->getStatistics($orgId, $keywordId, $days);

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get sentiment timeline
     *
     * GET /api/listening/analytics/sentiment-timeline
     */
    public function sentimentTimeline(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $keywordId = $request->keyword_id ?? null;
        $days = $request->days ?? 30;

        $timeline = $this->listeningService->getSentimentTimeline($orgId, $keywordId, $days);

        return $this->success($timeline, 'Sentiment timeline retrieved successfully');
    }

    /**
     * Get top authors
     *
     * GET /api/listening/analytics/top-authors
     */
    public function topAuthors(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $keywordId = $request->keyword_id ?? null;
        $limit = $request->limit ?? 10;

        $authors = $this->listeningService->getTopAuthors($orgId, $keywordId, $limit);

        return $this->success($authors, 'Top authors retrieved successfully');
    }
}
