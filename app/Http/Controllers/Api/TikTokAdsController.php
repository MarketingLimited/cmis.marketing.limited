<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platform\TikTokAdsService;
use App\Models\Platform\PlatformIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class TikTokAdsController extends Controller
{
    use ApiResponse;

    private TikTokAdsService $tiktokAdsService;

    public function __construct(TikTokAdsService $tiktokAdsService)
    {
        $this->tiktokAdsService = $tiktokAdsService;
    }

    /**
     * Get all TikTok Ads campaigns
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'page' => 'nullable|integer|min:1',
                'page_size' => 'nullable|integer|min:10|max:100'
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
                ->where('platform', 'tiktok')
                ->first();

            if (!$integration) {
                return $this->error('TikTok Ads integration not found', 404);
            }

            $result = $this->tiktokAdsService->fetchCampaigns(
                $integration->platform_account_id,
                $integration->access_token,
                $request->input('page', 1),
                $request->input('page_size', 50)
            );

            return response()->json([
                'success' => true,
                'campaigns' => $result['campaigns'],
                'page_info' => $result['page_info'],
                'count' => count($result['campaigns'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch TikTok Ads campaigns',
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
                ->where('platform', 'tiktok')
                ->first();

            if (!$integration) {
                return $this->error('TikTok Ads integration not found', 404);
            }

            $campaign = $this->tiktokAdsService->getCampaignDetails(
                $integration->platform_account_id,
                $campaignId,
                $integration->access_token,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'campaign' => $campaign
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
     * Get ad groups for a campaign
     */
    public function getAdGroups(Request $request, string $campaignId): JsonResponse
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
                ->where('platform', 'tiktok')
                ->first();

            if (!$integration) {
                return $this->error('TikTok Ads integration not found', 404);
            }

            $adGroups = $this->tiktokAdsService->fetchAdGroups(
                $integration->platform_account_id,
                $campaignId,
                $integration->access_token
            );

            return response()->json([
                'success' => true,
                'ad_groups' => $adGroups,
                'count' => count($adGroups)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ad groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ads for an ad group
     */
    public function getAds(Request $request, string $adGroupId): JsonResponse
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
                ->where('platform', 'tiktok')
                ->first();

            if (!$integration) {
                return $this->error('TikTok Ads integration not found', 404);
            }

            $ads = $this->tiktokAdsService->fetchAds(
                $integration->platform_account_id,
                $adGroupId,
                $integration->access_token
            );

            return response()->json([
                'success' => true,
                'ads' => $ads,
                'count' => count($ads)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new TikTok Ads campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'name' => 'required|string|min:1|max:512',
                'objective' => 'required|in:TRAFFIC,CONVERSIONS,APP_INSTALL,VIDEO_VIEWS,REACH,ENGAGEMENT',
                'budget_mode' => 'nullable|in:BUDGET_MODE_DAY,BUDGET_MODE_TOTAL,BUDGET_MODE_INFINITE',
                'budget' => 'required_if:budget_mode,BUDGET_MODE_DAY,BUDGET_MODE_TOTAL|nullable|numeric|min:20',
                'status' => 'nullable|in:ENABLE,DISABLE'
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
                ->where('platform', 'tiktok')
                ->first();

            if (!$integration) {
                return $this->error('TikTok Ads integration not found', 404);
            }

            $campaignData = [
                'name' => $request->input('name'),
                'objective' => $request->input('objective'),
                'budget_mode' => $request->input('budget_mode', 'BUDGET_MODE_INFINITE'),
                'budget' => $request->input('budget'),
                'status' => $request->input('status', 'DISABLE')
            ];

            $result = $this->tiktokAdsService->createCampaign(
                $integration->platform_account_id,
                $integration->access_token,
                $campaignData
            );

            return response()->json([
                'success' => true,
                'message' => 'TikTok Ads campaign created successfully',
                'campaign' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create TikTok Ads campaign',
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
                ->where('platform', 'tiktok')
                ->first();

            if (!$integration) {
                return $this->error('TikTok Ads integration not found', 404);
            }

            $metrics = $this->tiktokAdsService->getCampaignMetrics(
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
     * Refresh TikTok Ads cache
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
                ->where('platform', 'tiktok')
                ->first();

            if (!$integration) {
                return $this->error('TikTok Ads integration not found', 404);
            }

            $this->tiktokAdsService->clearCache($integration->platform_account_id);

            return response()->json([
                'success' => true,
                'message' => 'TikTok Ads cache cleared successfully'
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
