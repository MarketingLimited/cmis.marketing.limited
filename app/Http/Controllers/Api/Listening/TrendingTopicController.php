<?php

namespace App\Http\Controllers\Api\Listening;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Listening\TrendingTopic;
use App\Services\Listening\TrendDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Trending Topic Controller
 *
 * Manages trending topics detection and analysis
 */
class TrendingTopicController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TrendDetectionService $trendService
    ) {}

    /**
     * Get trending topics
     *
     * GET /api/listening/trends
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $filters = $request->only([
            'status',
            'velocity',
            'min_relevance',
            'topic_type',
            'opportunities_only',
        ]);

        $trends = $this->trendService->getTrendingTopics($orgId, $filters);

        return $this->success($trends, 'Trending topics retrieved successfully');
    }

    /**
     * Get trend details
     *
     * GET /api/listening/trends/{id}
     */
    public function show(string $id): JsonResponse
    {
        $trend = TrendingTopic::findOrFail($id);

        $opportunity = $this->trendService->analyzeTrendOpportunity($trend);
        $timeline = $this->trendService->getTrendTimeline($trend, 7);

        return $this->success([
            'trend' => $trend,
            'opportunity_analysis' => $opportunity,
            'timeline' => $timeline,
        ], 'Trend details retrieved successfully');
    }

    /**
     * Detect emerging trends
     *
     * POST /api/listening/trends/detect
     */
    public function detect(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $hours = $request->hours ?? 24;

        $trends = $this->trendService->detectEmergingTrends($orgId, $hours);

        return $this->success([
            'trends' => $trends,
            'count' => $trends->count(),
        ], 'Emerging trends detected successfully');
    }
}
