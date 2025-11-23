<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platform\SnapchatAdsService;
use App\Models\Platform\PlatformIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class SnapchatAdsController extends Controller
{
    use ApiResponse;

    private SnapchatAdsService $snapchatAdsService;

    public function __construct(SnapchatAdsService $snapchatAdsService)
    {
        $this->snapchatAdsService = $snapchatAdsService;
    }

    /**
     * Get all Snapchat Ads campaigns
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return $this->error('Snapchat Ads integration not found', 404);
            }

            $result = $this->snapchatAdsService->fetchCampaigns(
                $integration->platform_account_id,
                $integration->access_token,
                $request->input('limit', 50)
            );

            return $this->success([
                'campaigns' => $result['campaigns'],
                'paging' => $result['paging'],
                'count' => count($result['campaigns'])
            ], 'Snapchat Ads campaigns retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch Snapchat Ads campaigns: ' . $e->getMessage());
        }
    }

    /**
     * Create a new Snapchat Ads campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'name' => 'required|string|min:1|max:100',
                'objective' => 'required|in:AWARENESS,APP_INSTALLS,DRIVE_TRAFFIC,ENGAGEMENT,VIDEO_VIEWS,LEAD_GENERATION,SALES',
                'daily_budget' => 'required_without:lifetime_budget|nullable|numeric|min:5',
                'lifetime_budget' => 'required_without:daily_budget|nullable|numeric|min:50',
                'start_time' => 'nullable|date',
                'status' => 'nullable|in:ACTIVE,PAUSED'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return $this->notFound('Snapchat Ads integration not found');
            }

            $campaignData = [
                'name' => $request->input('name'),
                'objective' => $request->input('objective'),
                'daily_budget' => $request->input('daily_budget'),
                'lifetime_budget' => $request->input('lifetime_budget'),
                'start_time' => $request->input('start_time'),
                'status' => $request->input('status', 'PAUSED')
            ];

            $result = $this->snapchatAdsService->createCampaign(
                $integration->platform_account_id,
                $integration->access_token,
                $campaignData
            );

            return $this->created($result, 'Snapchat Ads campaign created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create Snapchat Ads campaign: ' . $e->getMessage());
        }
    }

    /**
     * Get specific campaign details
     */
    public function getCampaignDetails(Request $request, string $campaignId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return $this->notFound('Snapchat Ads integration not found');
            }

            $result = $this->snapchatAdsService->getCampaignDetails(
                $integration->platform_account_id,
                $campaignId,
                $integration->access_token,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success($result, 'Campaign details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch campaign details: ' . $e->getMessage());
        }
    }

    /**
     * Get campaign performance metrics
     */
    public function getCampaignMetrics(Request $request, string $campaignId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return $this->notFound('Snapchat Ads integration not found');
            }

            $metrics = $this->snapchatAdsService->getCampaignMetrics(
                $integration->platform_account_id,
                $campaignId,
                $integration->access_token,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success([
                'metrics' => $metrics,
                'period' => [
                    'start_date' => $request->input('start_date'),
                    'end_date' => $request->input('end_date')
                ]
            ], 'Campaign metrics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch campaign metrics: ' . $e->getMessage());
        }
    }

    /**
     * Refresh Snapchat Ads cache
     */
    public function refreshCache(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return $this->notFound('Snapchat Ads integration not found');
            }

            $this->snapchatAdsService->clearCache($integration->platform_account_id);

            return $this->success([], 'Snapchat Ads cache cleared successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to refresh cache: ' . $e->getMessage());
        }
    }
}
