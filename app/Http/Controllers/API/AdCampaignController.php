<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller for managing ad campaigns across all platforms
 * Supports: Meta, Google Ads, TikTok, Snapchat, Twitter, LinkedIn
 */
class AdCampaignController extends Controller
{
    /**
     * Create a new ad campaign
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'integration_id' => 'required|string|exists:cmis_integrations.integrations,integration_id',
                'campaign_name' => 'required|string',
                'objective' => 'required|string',
                'status' => 'nullable|string|in:ACTIVE,PAUSED,DRAFT',
                'daily_budget' => 'nullable|numeric',
                'lifetime_budget' => 'nullable|numeric',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'targeting' => 'nullable|array',
            ]);

            $orgId = $request->user()->org_id;

            $integration = Integration::where('integration_id', $validated['integration_id'])
                ->where('org_id', $orgId)
                ->where('is_active', true)
                ->firstOrFail();

            // Create campaign via connector
            $connector = ConnectorFactory::make($integration->platform);
            $result = $connector->createAdCampaign($integration, $validated);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to create campaign');
            }

            // Store in database
            $campaignId = \Illuminate\Support\Str::uuid();
            DB::table('cmis_ads.ad_campaigns')->insert([
                'campaign_id' => $campaignId,
                'org_id' => $orgId,
                'integration_id' => $validated['integration_id'],
                'platform' => $integration->platform,
                'platform_campaign_id' => $result['campaign_id'],
                'campaign_name' => $validated['campaign_name'],
                'objective' => $validated['objective'],
                'status' => $validated['status'] ?? 'PAUSED',
                'daily_budget' => $validated['daily_budget'] ?? null,
                'lifetime_budget' => $validated['lifetime_budget'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'targeting' => json_encode($validated['targeting'] ?? []),
                'created_by' => $request->user()->user_id,
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'campaign_id' => $campaignId,
                'platform_campaign_id' => $result['campaign_id'],
                'message' => 'Campaign created successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create campaign: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an ad campaign
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCampaign(string $campaignId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'campaign_name' => 'nullable|string',
                'status' => 'nullable|string|in:ACTIVE,PAUSED,DELETED',
                'daily_budget' => 'nullable|numeric',
                'lifetime_budget' => 'nullable|numeric',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'targeting' => 'nullable|array',
            ]);

            $orgId = $request->user()->org_id;

            $campaign = DB::table('cmis_ads.ad_campaigns')
                ->where('campaign_id', $campaignId)
                ->where('org_id', $orgId)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'error' => 'Campaign not found',
                ], 404);
            }

            $integration = Integration::where('integration_id', $campaign->integration_id)
                ->firstOrFail();

            // Update via connector
            $connector = ConnectorFactory::make($integration->platform);
            $result = $connector->updateAdCampaign($integration, $campaign->platform_campaign_id, $validated);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to update campaign');
            }

            // Update in database
            $updates = ['updated_at' => now()];
            foreach (['campaign_name', 'status', 'daily_budget', 'lifetime_budget', 'start_date', 'end_date'] as $field) {
                if (isset($validated[$field])) {
                    $updates[$field] = $validated[$field];
                }
            }
            if (isset($validated['targeting'])) {
                $updates['targeting'] = json_encode($validated['targeting']);
            }

            DB::table('cmis_ads.ad_campaigns')
                ->where('campaign_id', $campaignId)
                ->update($updates);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update campaign: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all campaigns for organization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $platform = $request->input('platform');
            $status = $request->input('status');

            $query = DB::table('cmis_ads.ad_campaigns as c')
                ->join('cmis_integrations.integrations as i', 'c.integration_id', '=', 'i.integration_id')
                ->where('c.org_id', $orgId)
                ->select(
                    'c.*',
                    'i.external_account_name',
                    'i.external_account_id'
                );

            if ($platform) {
                $query->where('c.platform', $platform);
            }

            if ($status) {
                $query->where('c.status', $status);
            }

            $campaigns = $query->orderBy('c.created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'campaigns' => $campaigns,
                'total' => $campaigns->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get campaigns: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign details
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaign(string $campaignId, Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;

            $campaign = DB::table('cmis_ads.ad_campaigns as c')
                ->join('cmis_integrations.integrations as i', 'c.integration_id', '=', 'i.integration_id')
                ->where('c.campaign_id', $campaignId)
                ->where('c.org_id', $orgId)
                ->select('c.*', 'i.external_account_name', 'i.external_account_id')
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'error' => 'Campaign not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'campaign' => $campaign,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get campaign: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign metrics/performance
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaignMetrics(string $campaignId, Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;

            $campaign = DB::table('cmis_ads.ad_campaigns')
                ->where('campaign_id', $campaignId)
                ->where('org_id', $orgId)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'error' => 'Campaign not found',
                ], 404);
            }

            $integration = Integration::where('integration_id', $campaign->integration_id)
                ->firstOrFail();

            // Get metrics from platform
            $connector = ConnectorFactory::make($integration->platform);
            $metrics = $connector->getAdCampaignMetrics(
                $integration,
                $campaign->platform_campaign_id,
                $request->all()
            );

            // Also get stored metrics
            $storedMetrics = DB::table('cmis_ads.ad_metrics')
                ->where('campaign_id', $campaignId)
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();

            return response()->json([
                'success' => true,
                'campaign_id' => $campaignId,
                'platform' => $campaign->platform,
                'live_metrics' => $metrics,
                'stored_metrics' => $storedMetrics,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get campaign metrics: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pause a campaign
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function pauseCampaign(string $campaignId, Request $request): JsonResponse
    {
        return $this->updateCampaignStatus($campaignId, 'PAUSED', $request);
    }

    /**
     * Activate a campaign
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function activateCampaign(string $campaignId, Request $request): JsonResponse
    {
        return $this->updateCampaignStatus($campaignId, 'ACTIVE', $request);
    }

    /**
     * Delete a campaign
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteCampaign(string $campaignId, Request $request): JsonResponse
    {
        return $this->updateCampaignStatus($campaignId, 'DELETED', $request);
    }

    /**
     * Update campaign status
     *
     * @param string $campaignId
     * @param string $status
     * @param Request $request
     * @return JsonResponse
     */
    protected function updateCampaignStatus(string $campaignId, string $status, Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;

            $campaign = DB::table('cmis_ads.ad_campaigns')
                ->where('campaign_id', $campaignId)
                ->where('org_id', $orgId)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'error' => 'Campaign not found',
                ], 404);
            }

            $integration = Integration::where('integration_id', $campaign->integration_id)
                ->firstOrFail();

            // Update via connector
            $connector = ConnectorFactory::make($integration->platform);
            $result = $connector->updateAdCampaign($integration, $campaign->platform_campaign_id, [
                'status' => $status,
            ]);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to update campaign status');
            }

            // Update in database
            DB::table('cmis_ads.ad_campaigns')
                ->where('campaign_id', $campaignId)
                ->update([
                    'status' => $status,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => "Campaign {$status} successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update campaign status: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign objectives for a platform
     *
     * @param string $platform
     * @return JsonResponse
     */
    public function getCampaignObjectives(string $platform): JsonResponse
    {
        $objectives = [
            'meta' => [
                'OUTCOME_AWARENESS' => 'Brand Awareness',
                'OUTCOME_ENGAGEMENT' => 'Engagement',
                'OUTCOME_LEADS' => 'Lead Generation',
                'OUTCOME_SALES' => 'Sales & Conversions',
                'OUTCOME_TRAFFIC' => 'Website Traffic',
                'OUTCOME_APP_PROMOTION' => 'App Promotion',
            ],
            'google' => [
                'SEARCH' => 'Search Campaign',
                'DISPLAY' => 'Display Campaign',
                'SHOPPING' => 'Shopping Campaign',
                'VIDEO' => 'Video Campaign',
                'SMART' => 'Smart Campaign',
                'PERFORMANCE_MAX' => 'Performance Max',
            ],
            'tiktok' => [
                'REACH' => 'Reach',
                'TRAFFIC' => 'Traffic',
                'VIDEO_VIEWS' => 'Video Views',
                'LEAD_GENERATION' => 'Lead Generation',
                'APP_PROMOTION' => 'App Promotion',
                'CONVERSIONS' => 'Conversions',
            ],
            'linkedin' => [
                'BRAND_AWARENESS' => 'Brand Awareness',
                'WEBSITE_VISITS' => 'Website Visits',
                'ENGAGEMENT' => 'Engagement',
                'VIDEO_VIEWS' => 'Video Views',
                'LEAD_GENERATION' => 'Lead Generation',
                'WEBSITE_CONVERSIONS' => 'Website Conversions',
            ],
            'twitter' => [
                'REACH' => 'Reach',
                'VIDEO_VIEWS' => 'Video Views',
                'WEBSITE_CLICKS' => 'Website Clicks',
                'FOLLOWERS' => 'Followers',
                'ENGAGEMENTS' => 'Engagements',
                'APP_INSTALLS' => 'App Installs',
            ],
            'snapchat' => [
                'AWARENESS' => 'Awareness',
                'APP_INSTALLS' => 'App Installs',
                'DRIVE_TRAFFIC' => 'Drive Traffic to Website',
                'ENGAGEMENT' => 'Engagement',
                'VIDEO_VIEWS' => 'Video Views',
                'CONVERSIONS' => 'Conversions',
            ],
        ];

        return response()->json([
            'success' => true,
            'platform' => $platform,
            'objectives' => $objectives[$platform] ?? [],
        ]);
    }
}
