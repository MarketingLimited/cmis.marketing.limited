<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdPlatforms\TikTok\TikTokAdsPlatform;
use App\Models\Core\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ApiResponse;

/**
 * TikTok Ads API Controller
 *
 * Handles all TikTok Ads Management API endpoints
 */
class TikTokAdsController extends Controller
{
    use ApiResponse;

    /**
     * Get all TikTok Ads campaigns
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'integration_id' => 'required|uuid|exists:cmis.integrations,integration_id',
                'status' => 'nullable|string',
                'objective_type' => 'nullable|string',
                'page' => 'nullable|integer|min:1',
                'page_size' => 'nullable|integer|min:10|max:100',
            ]);

            // Set RLS context FIRST
            DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
                auth()->id(),
                auth()->user()->current_org_id,
            ]);

            // Get integration (RLS will filter by org)
            $integration = Integration::where('integration_id', $validated['integration_id'])
                ->where('platform', 'tiktok')
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                return $this->notFound('TikTok Ads integration not found or inactive');
            }

            // Initialize TikTok platform service
            $tiktokService = new TikTokAdsPlatform($integration);

            // Fetch campaigns from TikTok
            $filters = array_filter([
                'status' => $validated['status'] ?? null,
                'objective_type' => $validated['objective_type'] ?? null,
                'page' => $validated['page'] ?? 1,
                'page_size' => $validated['page_size'] ?? 50,
            ]);

            $result = $tiktokService->fetchCampaigns($filters);

            if (!$result['success']) {
                return $this->error($result['error'] ?? 'Failed to fetch campaigns', 500);
            }

            return $this->success([
                'campaigns' => $result['campaigns'],
                'pagination' => $result['pagination'],
            ], 'Campaigns retrieved successfully');

        } catch (\InvalidArgumentException $e) {
            Log::warning('TikTok integration validation failed', [
                'integration_id' => $validated['integration_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 400);

        } catch (\Exception $e) {
            Log::error('Failed to fetch TikTok campaigns', [
                'integration_id' => $validated['integration_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverError('Failed to fetch TikTok Ads campaigns');
        }
    }

    /**
     * Get specific campaign details
     */
    public function getCampaignDetails(Request $request, string $campaignId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'integration_id' => 'required|uuid|exists:cmis.integrations,integration_id',
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today',
            ]);

            // Set RLS context
            DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
                auth()->id(),
                auth()->user()->current_org_id,
            ]);

            $integration = Integration::where('integration_id', $validated['integration_id'])
                ->where('platform', 'tiktok')
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                return $this->notFound('TikTok Ads integration not found');
            }

            $tiktokService = new TikTokAdsPlatform($integration);

            $campaign = $tiktokService->getCampaign($campaignId);

            if (!$campaign['success']) {
                return $this->error($campaign['error'] ?? 'Campaign not found', 404);
            }

            // Get metrics if date range provided
            if ($validated['start_date'] ?? null) {
                $metrics = $tiktokService->getCampaignMetrics(
                    $campaignId,
                    $validated['start_date'],
                    $validated['end_date'] ?? now()->format('Y-m-d')
                );

                if ($metrics['success']) {
                    $campaign['data']['metrics'] = $metrics['metrics'];
                }
            }

            return $this->success($campaign['data'], 'Campaign details retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to fetch TikTok campaign details', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Failed to fetch campaign details');
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
            $validated = $request->validate([
                'integration_id' => 'required|uuid|exists:cmis.integrations,integration_id',
                'name' => 'required|string|min:1|max:512',
                'objective' => 'required|in:REACH,TRAFFIC,APP_PROMOTION,VIDEO_VIEWS,CONVERSIONS,LEAD_GENERATION,ENGAGEMENT,PRODUCT_SALES',
                'budget_mode' => 'required|in:BUDGET_MODE_DAY,BUDGET_MODE_TOTAL',
                'daily_budget' => 'required_if:budget_mode,BUDGET_MODE_DAY|nullable|numeric|min:20',
                'lifetime_budget' => 'required_if:budget_mode,BUDGET_MODE_TOTAL|nullable|numeric|min:20',
                'status' => 'nullable|in:ENABLE,DISABLE',
            ]);

            // Set RLS context
            DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
                auth()->id(),
                auth()->user()->current_org_id,
            ]);

            $integration = Integration::where('integration_id', $validated['integration_id'])
                ->where('platform', 'tiktok')
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                return $this->notFound('TikTok Ads integration not found');
            }

            $tiktokService = new TikTokAdsPlatform($integration);

            // Prepare campaign data
            $campaignData = [
                'name' => $validated['name'],
                'objective' => $validated['objective'],
                'budget_mode' => $validated['budget_mode'],
                'daily_budget' => $validated['daily_budget'] ?? null,
                'lifetime_budget' => $validated['lifetime_budget'] ?? null,
                'status' => $validated['status'] ?? 'DISABLE',
            ];

            $result = $tiktokService->createCampaign($campaignData);

            if (!$result['success']) {
                return $this->error($result['error'] ?? 'Failed to create campaign', 500);
            }

            return $this->created($result['data'], 'TikTok campaign created successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create TikTok campaign', [
                'integration_id' => $validated['integration_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Failed to create TikTok Ads campaign');
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
