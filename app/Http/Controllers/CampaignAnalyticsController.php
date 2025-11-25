<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
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
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->analyticsService->getCampaignAnalytics($campaignId, $request->all());

            if (!isset($result['success']) || !$result['success']) {
                return $this->notFound('Campaign analytics not found');
            }

            return $this->success($result, 'Campaign analytics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get campaign analytics: ' . $e->getMessage());
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
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->analyticsService->compareCampaigns(
                $request->input('campaign_ids'),
                $request->only(['start_date', 'end_date'])
            );

            return $this->success($result, 'Campaigns compared successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to compare campaigns: ' . $e->getMessage());
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
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->analyticsService->getFunnelAnalytics($campaignId, $request->all());

            if (!isset($result['success']) || !$result['success']) {
                return $this->notFound('Funnel analytics not found');
            }

            return $this->success($result, 'Funnel analytics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get funnel analytics: ' . $e->getMessage());
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
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->analyticsService->getAttributionAnalysis($campaignId, $request->all());

            if (!isset($result['success']) || !$result['success']) {
                return $this->notFound('Attribution analysis not found');
            }

            return $this->success($result, 'Attribution analysis retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get attribution analysis: ' . $e->getMessage());
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
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->analyticsService->getAdSetBreakdown($campaignId, $request->all());

            if (!isset($result['success']) || !$result['success']) {
                return $this->notFound('Ad set breakdown not found');
            }

            return $this->success($result, 'Ad set breakdown retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get ad set breakdown: ' . $e->getMessage());
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
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->analyticsService->getCreativeBreakdown($campaignId, $request->all());

            if (!isset($result['success']) || !$result['success']) {
                return $this->notFound('Creative breakdown not found');
            }

            return $this->success($result, 'Creative breakdown retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get creative breakdown: ' . $e->getMessage());
        }
    }
}
