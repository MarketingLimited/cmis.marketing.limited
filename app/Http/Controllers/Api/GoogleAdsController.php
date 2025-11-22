<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platform\GoogleAdsService;
use App\Models\Platform\PlatformIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class GoogleAdsController extends Controller
{
    use ApiResponse;

    private GoogleAdsService $googleAdsService;

    public function __construct(GoogleAdsService $googleAdsService)
    {
        $this->googleAdsService = $googleAdsService;
    }

    /**
     * Get all Google Ads campaigns for the organization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'limit' => 'nullable|integer|min:10|max:100'
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
                ->where('platform', 'google_ads')
                ->first();

            if (!$integration) {
                return $this->error('Google Ads integration not found', 404);
            }

            $campaigns = $this->googleAdsService->fetchCampaigns(
                $integration->platform_account_id,
                $integration->access_token,
                $integration->refresh_token,
                $request->input('limit', 50)
            );

            return response()->json([
                'success' => true,
                'campaigns' => $campaigns,
                'count' => count($campaigns)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Google Ads campaigns',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific campaign details
     *
     * @param Request $request
     * @param string $campaignId
     * @return JsonResponse
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
                ->where('platform', 'google_ads')
                ->first();

            if (!$integration) {
                return $this->error('Google Ads integration not found', 404);
            }

            $campaign = $this->googleAdsService->getCampaignDetails(
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
     *
     * @param Request $request
     * @param string $campaignId
     * @return JsonResponse
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
                ->where('platform', 'google_ads')
                ->first();

            if (!$integration) {
                return $this->error('Google Ads integration not found', 404);
            }

            $adGroups = $this->googleAdsService->fetchAdGroups(
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
     *
     * @param Request $request
     * @param string $adGroupId
     * @return JsonResponse
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
                ->where('platform', 'google_ads')
                ->first();

            if (!$integration) {
                return $this->error('Google Ads integration not found', 404);
            }

            $ads = $this->googleAdsService->fetchAds(
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
     * Create a new Google Ads campaign
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'name' => 'required|string|min:3|max:255',
                'status' => 'nullable|in:ENABLED,PAUSED',
                'channel_type' => 'nullable|in:SEARCH,DISPLAY,SHOPPING,VIDEO,MULTI_CHANNEL,SMART',
                'bidding_strategy' => 'nullable|in:MAXIMIZE_CLICKS,MAXIMIZE_CONVERSIONS,TARGET_CPA,TARGET_ROAS,MANUAL_CPC',
                'budget_amount' => 'required|numeric|min:1',
                'budget_delivery' => 'nullable|in:STANDARD,ACCELERATED'
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
                ->where('platform', 'google_ads')
                ->first();

            if (!$integration) {
                return $this->error('Google Ads integration not found', 404);
            }

            // Step 1: Create campaign budget
            $budgetName = $request->input('name') . ' Budget';
            $budgetAmountMicros = $request->input('budget_amount') * 1000000;
            $budgetResourceName = $this->googleAdsService->createCampaignBudget(
                $integration->platform_account_id,
                $integration->access_token,
                $budgetName,
                $budgetAmountMicros,
                $request->input('budget_delivery', 'STANDARD')
            );

            // Step 2: Create campaign
            $campaignData = [
                'name' => $request->input('name'),
                'status' => $request->input('status', 'PAUSED'),
                'channel_type' => $request->input('channel_type', 'SEARCH'),
                'bidding_strategy' => $request->input('bidding_strategy', 'MAXIMIZE_CLICKS'),
                'budget_resource_name' => $budgetResourceName
            ];

            $result = $this->googleAdsService->createCampaign(
                $integration->platform_account_id,
                $integration->access_token,
                $campaignData
            );

            return response()->json([
                'success' => true,
                'message' => 'Google Ads campaign created successfully',
                'campaign' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Google Ads campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign performance metrics
     *
     * @param Request $request
     * @param string $campaignId
     * @return JsonResponse
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
                ->where('platform', 'google_ads')
                ->first();

            if (!$integration) {
                return $this->error('Google Ads integration not found', 404);
            }

            $metrics = $this->googleAdsService->getCampaignMetrics(
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
     * Refresh Google Ads cache
     *
     * @param Request $request
     * @return JsonResponse
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
                ->where('platform', 'google_ads')
                ->first();

            if (!$integration) {
                return $this->error('Google Ads integration not found', 404);
            }

            $this->googleAdsService->clearCache($integration->platform_account_id);

            return response()->json([
                'success' => true,
                'message' => 'Google Ads cache cleared successfully'
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
