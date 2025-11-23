<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platform\TwitterAdsService;
use App\Models\Platform\PlatformIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class TwitterAdsController extends Controller
{
    use ApiResponse;

    private TwitterAdsService $twitterAdsService;

    public function __construct(TwitterAdsService $twitterAdsService)
    {
        $this->twitterAdsService = $twitterAdsService;
    }

    /**
     * Get all Twitter Ads campaigns
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'count' => 'nullable|integer|min:1|max:200',
                'cursor' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'twitter')
                ->first();

            if (!$integration) {
                return $this->error('Twitter Ads integration not found', 404);
            }

            $result = $this->twitterAdsService->fetchCampaigns(
                $integration->platform_account_id,
                $integration->access_token,
                $request->input('count', 50),
                $request->input('cursor')
            );

            return response()->json([
                'success' => true,
                'campaigns' => $result['campaigns'],
                'next_cursor' => $result['next_cursor'],
                'total_count' => $result['total_count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Twitter Ads campaigns',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new Twitter Ads campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'name' => 'required|string|min:1|max:280',
                'funding_instrument_id' => 'required|string',
                'daily_budget' => 'required_without:total_budget|nullable|numeric|min:5',
                'total_budget' => 'required_without:daily_budget|nullable|numeric|min:50',
                'currency' => 'nullable|in:USD,EUR,GBP,JPY,CAD,AUD',
                'start_time' => 'nullable|date',
                'status' => 'nullable|in:ACTIVE,PAUSED'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'twitter')
                ->first();

            if (!$integration) {
                return $this->error('Twitter Ads integration not found', 404);
            }

            $campaignData = [
                'name' => $request->input('name'),
                'funding_instrument_id' => $request->input('funding_instrument_id'),
                'daily_budget' => $request->input('daily_budget'),
                'total_budget' => $request->input('total_budget'),
                'currency' => $request->input('currency', 'USD'),
                'start_time' => $request->input('start_time'),
                'status' => $request->input('status', 'PAUSED')
            ];

            $result = $this->twitterAdsService->createCampaign(
                $integration->platform_account_id,
                $integration->access_token,
                $campaignData
            );

            return response()->json([
                'success' => true,
                'message' => 'Twitter Ads campaign created successfully',
                'campaign' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Twitter Ads campaign',
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'twitter')
                ->first();

            if (!$integration) {
                return $this->error('Twitter Ads integration not found', 404);
            }

            $result = $this->twitterAdsService->getCampaignDetails(
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'twitter')
                ->first();

            if (!$integration) {
                return $this->error('Twitter Ads integration not found', 404);
            }

            $metrics = $this->twitterAdsService->getCampaignMetrics(
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
     * Refresh Twitter Ads cache
     */
    public function refreshCache(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'twitter')
                ->first();

            if (!$integration) {
                return $this->error('Twitter Ads integration not found', 404);
            }

            $this->twitterAdsService->clearCache($integration->platform_account_id);

            return $this->success(null, 'Twitter Ads cache cleared successfully');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
