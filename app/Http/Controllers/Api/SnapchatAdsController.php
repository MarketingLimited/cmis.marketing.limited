<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platform\SnapchatAdsService;
use App\Models\Platform\PlatformIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SnapchatAdsController extends Controller
{
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
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads integration not found'
                ], 404);
            }

            $result = $this->snapchatAdsService->fetchCampaigns(
                $integration->platform_account_id,
                $integration->access_token,
                $request->input('limit', 50)
            );

            return response()->json([
                'success' => true,
                'campaigns' => $result['campaigns'],
                'paging' => $result['paging'],
                'count' => count($result['campaigns'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Snapchat Ads campaigns',
                'error' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads integration not found'
                ], 404);
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

            return response()->json([
                'success' => true,
                'message' => 'Snapchat Ads campaign created successfully',
                'campaign' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Snapchat Ads campaign',
                'error' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads integration not found'
                ], 404);
            }

            $result = $this->snapchatAdsService->getCampaignDetails(
                $integration->platform_account_id,
                $campaignId,
                $integration->access_token,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'campaign' => $result['campaign'],
                'metrics' => $result['metrics']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaign details',
                'error' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads integration not found'
                ], 404);
            }

            $metrics = $this->snapchatAdsService->getCampaignMetrics(
                $integration->platform_account_id,
                $campaignId,
                $integration->access_token,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'period' => [
                    'start_date' => $request->input('start_date'),
                    'end_date' => $request->input('end_date')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaign metrics',
                'error' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads integration not found'
                ], 404);
            }

            $this->snapchatAdsService->clearCache($integration->platform_account_id);

            return response()->json([
                'success' => true,
                'message' => 'Snapchat Ads cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
