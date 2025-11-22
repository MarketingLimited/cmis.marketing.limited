<?php

namespace App\Http\Controllers;

use App\Services\CampaignAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * CampaignAnalyticsController
 *
 * Handles campaign analytics and performance tracking
 * Implements Sprint 4.5: Campaign Analytics
 *
 * Features:
 * - Comprehensive campaign metrics
 * - Performance comparison
 * - Funnel analysis
 * - Attribution modeling
 * - Ad set and creative breakdowns
 */
class CampaignAnalyticsController extends Controller
{
    use ApiResponse;

    protected CampaignAnalyticsService $analyticsService;

    public function __construct(CampaignAnalyticsService $analyticsService)
    {
        $this->middleware('auth:sanctum');
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get comprehensive campaign analytics
     *
     * GET /api/orgs/{org_id}/campaign-analytics/{campaign_id}?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaignAnalytics(string $orgId, string $campaignId, Request $request): JsonResponse
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

        try {
            $result = $this->analyticsService->getCampaignAnalytics($campaignId, $request->all());

            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get campaign analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compare multiple campaigns
     *
     * POST /api/orgs/{org_id}/campaign-analytics/compare
     *
     * Request body:
     * {
     *   "campaign_ids": ["uuid1", "uuid2", "uuid3"],
     *   "start_date": "2025-01-01",
     *   "end_date": "2025-01-31"
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function compareCampaigns(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_ids' => 'required|array|min:2|max:10',
            'campaign_ids.*' => 'uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->analyticsService->compareCampaigns(
                $request->input('campaign_ids'),
                $request->only(['start_date', 'end_date'])
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to compare campaigns',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get funnel analytics
     *
     * GET /api/orgs/{org_id}/campaign-analytics/{campaign_id}/funnel?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getFunnelAnalytics(string $orgId, string $campaignId, Request $request): JsonResponse
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

        try {
            $result = $this->analyticsService->getFunnelAnalytics($campaignId, $request->all());

            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get funnel analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attribution analysis
     *
     * GET /api/orgs/{org_id}/campaign-analytics/{campaign_id}/attribution?attribution_model=last_click&conversion_window=7
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getAttributionAnalysis(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'attribution_model' => 'nullable|in:first_click,last_click,linear,time_decay,position_based',
            'conversion_window' => 'nullable|integer|min:1|max:90'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->analyticsService->getAttributionAnalysis($campaignId, $request->all());

            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get attribution analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ad set performance breakdown
     *
     * GET /api/orgs/{org_id}/campaign-analytics/{campaign_id}/ad-sets?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdSetBreakdown(string $orgId, string $campaignId, Request $request): JsonResponse
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

        try {
            $result = $this->analyticsService->getAdSetBreakdown($campaignId, $request->all());

            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get ad set breakdown',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get creative performance breakdown
     *
     * GET /api/orgs/{org_id}/campaign-analytics/{campaign_id}/creatives?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCreativeBreakdown(string $orgId, string $campaignId, Request $request): JsonResponse
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

        try {
            $result = $this->analyticsService->getCreativeBreakdown($campaignId, $request->all());

            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get creative breakdown',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
