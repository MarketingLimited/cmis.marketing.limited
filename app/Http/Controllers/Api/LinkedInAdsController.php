<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platform\LinkedInAdsService;
use App\Models\Platform\PlatformIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class LinkedInAdsController extends Controller
{
    use ApiResponse;

    private LinkedInAdsService $linkedInAdsService;

    public function __construct(LinkedInAdsService $linkedInAdsService)
    {
        $this->linkedInAdsService = $linkedInAdsService;
    }

    /**
     * Get all LinkedIn Ads campaigns
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'start' => 'nullable|integer|min:0',
                'count' => 'nullable|integer|min:10|max:100'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'linkedin')
                ->first();

            if (!$integration) {
                return $this->error('LinkedIn Ads integration not found', 404);
            }

            $result = $this->linkedInAdsService->fetchCampaigns(
                $integration->platform_account_id,
                $integration->access_token,
                $request->input('start', 0),
                $request->input('count', 50)
            );

            return response()->json([
                'success' => true,
                'campaigns' => $result['campaigns'],
                'paging' => $result['paging'],
                'count' => count($result['campaigns'])
            ]);
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch LinkedIn Ads campaigns',
                'error' => $e->getMessage()
            );
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
                ->where('platform', 'linkedin')
                ->first();

            if (!$integration) {
                return $this->error('LinkedIn Ads integration not found', 404);
            }

            $campaign = $this->linkedInAdsService->getCampaignDetails(
                $integration->platform_account_id,
                $campaignId,
                $integration->access_token,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success(['campaign' => $campaign], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch campaign details',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get creatives for a campaign
     */
    public function getCreatives(Request $request, string $campaignId): JsonResponse
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
                ->where('platform', 'linkedin')
                ->first();

            if (!$integration) {
                return $this->error('LinkedIn Ads integration not found', 404);
            }

            $creatives = $this->linkedInAdsService->fetchCreatives(
                $integration->platform_account_id,
                $campaignId,
                $integration->access_token
            );

            return $this->success(['creatives' => $creatives,
                'count' => count($creatives)], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch creatives',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Create a new LinkedIn Ads campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'integration_id' => 'required|uuid|exists:cmis_platform.platform_integrations,id',
                'name' => 'required|string|min:1|max:255',
                'type' => 'nullable|in:SPONSORED_UPDATES,TEXT_AD,SPONSORED_INMAILS,DYNAMIC',
                'objective' => 'required|in:BRAND_AWARENESS,WEBSITE_VISITS,ENGAGEMENT,VIDEO_VIEWS,LEAD_GENERATION,WEBSITE_CONVERSIONS,JOB_APPLICANTS',
                'cost_type' => 'nullable|in:CPM,CPC,CPV',
                'daily_budget' => 'required_without:total_budget|nullable|numeric|min:10',
                'total_budget' => 'required_without:daily_budget|nullable|numeric|min:100',
                'currency' => 'nullable|in:USD,EUR,GBP,CAD,AUD',
                'status' => 'nullable|in:ACTIVE,PAUSED,ARCHIVED,DRAFT'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $integration = PlatformIntegration::where('id', $request->input('integration_id'))
                ->where('org_id', $orgId)
                ->where('platform', 'linkedin')
                ->first();

            if (!$integration) {
                return $this->error('LinkedIn Ads integration not found', 404);
            }

            $campaignData = [
                'name' => $request->input('name'),
                'type' => $request->input('type', 'SPONSORED_UPDATES'),
                'objective' => $request->input('objective'),
                'cost_type' => $request->input('cost_type', 'CPM'),
                'daily_budget' => $request->input('daily_budget'),
                'total_budget' => $request->input('total_budget'),
                'currency' => $request->input('currency', 'USD'),
                'status' => $request->input('status', 'PAUSED')
            ];

            $result = $this->linkedInAdsService->createCampaign(
                $integration->platform_account_id,
                $integration->access_token,
                $campaignData
            );

            return response()->json([
                'success' => true,
                'message' => 'LinkedIn Ads campaign created successfully',
                'campaign' => $result
            ], 201);
        } catch (\Exception $e) {
            return $this->serverError('Failed to create LinkedIn Ads campaign',
                'error' => $e->getMessage()
            );
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
                ->where('platform', 'linkedin')
                ->first();

            if (!$integration) {
                return $this->error('LinkedIn Ads integration not found', 404);
            }

            $metrics = $this->linkedInAdsService->getCampaignMetrics(
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
            return $this->serverError('Failed to fetch campaign metrics',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Refresh LinkedIn Ads cache
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
                ->where('platform', 'linkedin')
                ->first();

            if (!$integration) {
                return $this->error('LinkedIn Ads integration not found', 404);
            }

            $this->linkedInAdsService->clearCache($integration->platform_account_id);

            return $this->success(null, 'LinkedIn Ads cache cleared successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to refresh cache',
                'error' => $e->getMessage()
            );
        }
    }
}
