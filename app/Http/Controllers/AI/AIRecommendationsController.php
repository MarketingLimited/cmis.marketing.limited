<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\AI\AIRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AI Recommendations Controller (Phase 3 - Advanced AI Analytics)
 *
 * Provides API endpoints for AI-powered recommendations:
 * - Content recommendations
 * - Campaign strategy suggestions
 * - Audience targeting recommendations
 * - Optimal posting time suggestions
 * - Best performing content discovery
 */
class AIRecommendationsController extends Controller
{
    use ApiResponse;

    protected AIRecommendationService $recommendationService;

    public function __construct(AIRecommendationService $recommendationService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('throttle.ai');
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get similar high-performing content
     *
     * POST /api/ai/recommendations/similar
     *
     * Request body:
     * {
     *   "reference_type": "content|campaign|creative",
     *   "reference_id": "uuid",
     *   "limit": 10
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSimilarContent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference_type' => 'required|in:content,campaign,creative',
            'reference_id' => 'required|uuid',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $orgId = $request->user()->org_id;

        $result = $this->recommendationService->getSimilarHighPerformingContent(
            $orgId,
            $request->input('reference_type'),
            $request->input('reference_id'),
            $request->input('limit', 10)
        );

        return response()->json($result);
    }

    /**
     * Get content recommendations for a campaign
     *
     * GET /api/ai/recommendations/campaign/{campaign_id}/content
     *
     * Query params:
     * - content_type: string
     * - platform: string
     * - limit: int
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaignContentRecommendations(
        string $campaignId,
        Request $request
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'content_type' => 'nullable|string|max:50',
            'platform' => 'nullable|string|max:50',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->recommendationService->getContentRecommendationsForCampaign(
            $campaignId,
            $request->only(['content_type', 'platform', 'limit'])
        );

        return response()->json($result);
    }

    /**
     * Get best performing content
     *
     * GET /api/orgs/{org_id}/ai/recommendations/best-performing
     *
     * Query params:
     * - content_type: string
     * - platform: string
     * - date_range: date (ISO format)
     * - limit: int
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function getBestPerformingContent(
        string $orgId,
        Request $request
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'content_type' => 'nullable|string|max:50',
            'platform' => 'nullable|string|max:50',
            'date_range' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->recommendationService->getBestPerformingContent(
            $orgId,
            $request->only(['content_type', 'platform', 'date_range']),
            $request->input('limit', 20)
        );

        return response()->json($result);
    }

    /**
     * Get optimal posting times
     *
     * GET /api/orgs/{org_id}/ai/recommendations/optimal-times
     *
     * Query params:
     * - platform: string (optional)
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function getOptimalPostingTimes(
        string $orgId,
        Request $request
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'platform' => 'nullable|string|in:facebook,instagram,twitter,linkedin,tiktok',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->recommendationService->getOptimalPostingTimes(
            $orgId,
            $request->input('platform')
        );

        return response()->json($result);
    }

    /**
     * Get audience targeting recommendations
     *
     * GET /api/ai/recommendations/campaign/{campaign_id}/audience
     *
     * @param string $campaignId
     * @return JsonResponse
     */
    public function getAudienceRecommendations(string $campaignId): JsonResponse
    {
        $result = $this->recommendationService->getAudienceTargetingRecommendations($campaignId);

        return response()->json($result);
    }
}
