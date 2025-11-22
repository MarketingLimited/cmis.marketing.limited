<?php

namespace App\Http\Controllers;

use App\Services\AdCampaignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AdCampaignController
 *
 * Handles ad campaign management across platforms
 * Implements Sprint 4.1: Campaign Management
 *
 * Features:
 * - Multi-platform campaign CRUD
 * - Campaign lifecycle management
 * - Status updates and bulk operations
 * - Campaign duplication
 * - Platform synchronization
 */
class AdCampaignController extends Controller
{
    use ApiResponse;

    protected AdCampaignService $campaignService;

    public function __construct(AdCampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Create new ad campaign
     *
     * POST /api/orgs/{org_id}/ad-campaigns
     *
     * Request body:
     * {
     *   "ad_account_id": "uuid",
     *   "platform": "meta|google|linkedin|twitter|tiktok",
     *   "campaign_name": "string",
     *   "objective": "awareness|traffic|engagement|leads|sales",
     *   "budget_type": "daily|lifetime",
     *   "daily_budget": 100.00,
     *   "bid_strategy": "lowest_cost|cost_cap|bid_cap",
     *   "start_time": "2025-01-01T00:00:00Z",
     *   "targeting": {},
     *   "placements": [],
     *   "sync_to_platform": true
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function create(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ad_account_id' => 'required|uuid',
            'platform' => 'required|in:meta,google,linkedin,twitter,tiktok',
            'campaign_name' => 'required|string|max:255',
            'objective' => 'required|in:awareness,traffic,engagement,leads,sales,app_installs,video_views',
            'campaign_status' => 'nullable|in:draft,active,paused,completed',
            'budget_type' => 'nullable|in:daily,lifetime',
            'daily_budget' => 'nullable|numeric|min:1',
            'lifetime_budget' => 'nullable|numeric|min:1',
            'bid_strategy' => 'nullable|in:lowest_cost,cost_cap,bid_cap,target_cost',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'targeting' => 'nullable|array',
            'placements' => 'nullable|array',
            'optimization_goal' => 'nullable|string',
            'metadata' => 'nullable|array',
            'sync_to_platform' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->campaignService->createCampaign($request->all());

            if (!$result['success']) {
                return $this->serverError($result['error'] ?? 'Failed to create campaign');
            }

            return $this->created($result, 'Campaign created successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create campaign: ' . $e->getMessage());
        }
    }

    /**
     * Get campaign details
     *
     * GET /api/orgs/{org_id}/ad-campaigns/{campaign_id}?include_metrics=true
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        try {
            $includeMetrics = $request->boolean('include_metrics', false);
            $result = $this->campaignService->getCampaign($campaignId, $includeMetrics);

            if (!$result['success']) {
                return $this->notFound($result['error'] ?? 'Campaign not found');
            }

            return $this->success($result, 'Campaign retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get campaign: ' . $e->getMessage());
        }
    }

    /**
     * List campaigns with filters
     *
     * GET /api/orgs/{org_id}/ad-campaigns?platform=meta&status=active&search=summer&per_page=20
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ad_account_id' => 'nullable|uuid',
            'platform' => 'nullable|in:meta,google,linkedin,twitter,tiktok',
            'campaign_status' => 'nullable|in:draft,active,paused,completed',
            'objective' => 'nullable|string',
            'search' => 'nullable|string',
            'start_date_from' => 'nullable|date',
            'start_date_to' => 'nullable|date',
            'sort_by' => 'nullable|in:created_at,campaign_name,start_time,daily_budget',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->campaignService->listCampaigns($request->all());

            return $this->success($result, 'Campaigns retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to list campaigns: ' . $e->getMessage());
        }
    }

    /**
     * Update campaign
     *
     * PUT /api/orgs/{org_id}/ad-campaigns/{campaign_id}
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_name' => 'nullable|string|max:255',
            'campaign_status' => 'nullable|in:draft,active,paused,completed',
            'objective' => 'nullable|in:awareness,traffic,engagement,leads,sales,app_installs,video_views',
            'budget_type' => 'nullable|in:daily,lifetime',
            'daily_budget' => 'nullable|numeric|min:1',
            'lifetime_budget' => 'nullable|numeric|min:1',
            'bid_strategy' => 'nullable|in:lowest_cost,cost_cap,bid_cap,target_cost',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'targeting' => 'nullable|array',
            'placements' => 'nullable|array',
            'optimization_goal' => 'nullable|string',
            'metadata' => 'nullable|array',
            'sync_to_platform' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->campaignService->updateCampaign($campaignId, $request->all());

            if (!$result['success']) {
                return $this->serverError($result['error'] ?? 'Failed to update campaign');
            }

            return $this->success($result, 'Campaign updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update campaign: ' . $e->getMessage());
        }
    }

    /**
     * Update campaign status
     *
     * PATCH /api/orgs/{org_id}/ad-campaigns/{campaign_id}/status
     *
     * Request body:
     * {
     *   "status": "active|paused|completed"
     * }
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,active,paused,completed'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->campaignService->updateCampaignStatus($campaignId, $request->input('status'));

            if (!$result['success']) {
                return $this->serverError($result['error'] ?? 'Failed to update campaign status');
            }

            return $this->success($result, 'Campaign status updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update campaign status: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate campaign
     *
     * POST /api/orgs/{org_id}/ad-campaigns/{campaign_id}/duplicate
     *
     * Request body:
     * {
     *   "campaign_name": "New Campaign Name",
     *   "start_time": "2025-02-01T00:00:00Z",
     *   "end_time": "2025-02-28T23:59:59Z"
     * }
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function duplicate(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->campaignService->duplicateCampaign($campaignId, $request->all());

            if (!$result['success']) {
                return $this->serverError($result['error'] ?? 'Failed to duplicate campaign');
            }

            return $this->created($result, 'Campaign duplicated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to duplicate campaign: ' . $e->getMessage());
        }
    }

    /**
     * Delete campaign
     *
     * DELETE /api/orgs/{org_id}/ad-campaigns/{campaign_id}?permanent=false
     *
     * @param string $orgId
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        try {
            $permanent = $request->boolean('permanent', false);
            $success = $this->campaignService->deleteCampaign($campaignId, $permanent);

            if ($success) {
                return $this->deleted('Campaign deleted successfully');
            }

            return $this->serverError('Failed to delete campaign');

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete campaign: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update campaign statuses
     *
     * PATCH /api/orgs/{org_id}/ad-campaigns/bulk/status
     *
     * Request body:
     * {
     *   "campaign_ids": ["uuid1", "uuid2", "uuid3"],
     *   "status": "paused"
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdateStatus(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_ids' => 'required|array|min:1',
            'campaign_ids.*' => 'uuid',
            'status' => 'required|in:draft,active,paused,completed'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->campaignService->bulkUpdateStatus(
                $request->input('campaign_ids'),
                $request->input('status')
            );

            return $this->success($result, 'Campaign statuses updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to bulk update campaign statuses: ' . $e->getMessage());
        }
    }

    /**
     * Get campaign statistics summary
     *
     * GET /api/orgs/{org_id}/ad-campaigns/statistics
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(string $orgId, Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['ad_account_id', 'platform']);
            $result = $this->campaignService->listCampaigns($filters);

            if (!$result['success']) {
                return $this->serverError('Failed to retrieve campaigns for statistics');
            }

            $campaigns = collect($result['data']);

            $statistics = [
                'total_campaigns' => $campaigns->count(),
                'by_status' => [
                    'draft' => $campaigns->where('campaign_status', 'draft')->count(),
                    'active' => $campaigns->where('campaign_status', 'active')->count(),
                    'paused' => $campaigns->where('campaign_status', 'paused')->count(),
                    'completed' => $campaigns->where('campaign_status', 'completed')->count(),
                ],
                'by_platform' => [
                    'meta' => $campaigns->where('platform', 'meta')->count(),
                    'google' => $campaigns->where('platform', 'google')->count(),
                    'linkedin' => $campaigns->where('platform', 'linkedin')->count(),
                    'twitter' => $campaigns->where('platform', 'twitter')->count(),
                    'tiktok' => $campaigns->where('platform', 'tiktok')->count(),
                ],
                'by_objective' => $campaigns->groupBy('objective')->map->count(),
                'total_budget' => [
                    'daily' => round($campaigns->where('budget_type', 'daily')->sum('daily_budget'), 2),
                    'lifetime' => round($campaigns->where('budget_type', 'lifetime')->sum('lifetime_budget'), 2)
                ]
            ];

            return $this->success($statistics, 'Campaign statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get campaign statistics: ' . $e->getMessage());
        }
    }
}
