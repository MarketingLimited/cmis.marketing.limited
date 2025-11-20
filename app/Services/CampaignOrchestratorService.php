<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\AdCampaign;
use App\Models\Platform\PlatformConnection;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Repositories\Analytics\AnalyticsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for multi-platform campaign orchestration (AdEspresso-style)
 * Implements Sprint 4.1: Unified Campaign Builder
 */
class CampaignOrchestratorService
{
    protected CampaignRepositoryInterface $campaignRepo;
    protected AnalyticsRepository $analyticsRepo;

    public function __construct(
        CampaignRepositoryInterface $campaignRepo,
        AnalyticsRepository $analyticsRepo
    ) {
        $this->campaignRepo = $campaignRepo;
        $this->analyticsRepo = $analyticsRepo;
    }

    /**
     * Create campaign across multiple platforms
     *
     * @param string $orgId
     * @param array $platforms  ['meta', 'google', 'linkedin', etc.]
     * @param array $campaignData
     * @return array
     */
    public function createMultiPlatformCampaign(string $orgId, array $platforms, array $campaignData): array
    {
        $results = [];

        // First, create the main campaign in CMIS database
        try {
            $campaign = Campaign::create([
                'org_id' => $orgId,
                'name' => $campaignData['name'] ?? 'Unnamed Campaign',
                'objective' => $campaignData['objective'] ?? 'awareness',
                'status' => 'draft',
                'start_date' => $campaignData['start_date'] ?? now(),
                'end_date' => $campaignData['end_date'] ?? null,
                'budget' => $campaignData['budget'] ?? 0,
                'currency' => $campaignData['currency'] ?? 'USD',
                'description' => $campaignData['description'] ?? null,
                'created_by' => $campaignData['created_by'] ?? null,
            ]);

            $results['cmis_campaign'] = [
                'success' => true,
                'campaign_id' => $campaign->campaign_id,
            ];

            // Create platform-specific ad campaigns
            foreach ($platforms as $platform) {
                try {
                    // Check if organization has active connection to platform
                    $connection = PlatformConnection::where('org_id', $orgId)
                        ->where('platform', $platform)
                        ->where('is_active', true)
                        ->first();

                    if (!$connection) {
                        $results[$platform] = [
                            'success' => false,
                            'error' => "No active connection found for platform: {$platform}",
                        ];
                        continue;
                    }

                    // Create ad campaign record
                    $adCampaign = AdCampaign::create([
                        'org_id' => $orgId,
                        'name' => $campaignData['name'] . " ({$platform})",
                        'status' => 'draft',
                    ]);

                    $results[$platform] = [
                        'success' => true,
                        'ad_campaign_id' => $adCampaign->ad_campaign_id,
                        'message' => 'Ad campaign created. Platform-specific sync pending.',
                    ];

                    Log::info("Created ad campaign on {$platform}", [
                        'org_id' => $orgId,
                        'campaign_id' => $campaign->campaign_id,
                        'ad_campaign_id' => $adCampaign->ad_campaign_id,
                    ]);

                } catch (\Exception $e) {
                    Log::error("Failed to create campaign on {$platform}", [
                        'error' => $e->getMessage(),
                        'org_id' => $orgId,
                        'platform' => $platform,
                    ]);

                    $results[$platform] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to create main campaign", [
                'error' => $e->getMessage(),
                'org_id' => $orgId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create main campaign: ' . $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Sync campaign status from all platforms
     *
     * @param string $campaignId
     * @return array
     */
    public function syncCampaignStatus(string $campaignId): array
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return [
                'success' => false,
                'error' => 'Campaign not found',
            ];
        }

        $results = [];
        $platformStatuses = [];

        // Get all ad campaigns for this campaign
        $adCampaigns = AdCampaign::where('org_id', $campaign->org_id)->get();

        foreach ($adCampaigns as $adCampaign) {
            $platformStatuses[] = $adCampaign->status;
            $results[] = [
                'ad_campaign_id' => $adCampaign->ad_campaign_id,
                'name' => $adCampaign->name,
                'status' => $adCampaign->status,
            ];
        }

        // Determine overall campaign status based on platform statuses
        $overallStatus = $this->determineOverallStatus($platformStatuses);

        // Update campaign status if it changed
        if ($campaign->status !== $overallStatus) {
            $campaign->update(['status' => $overallStatus]);
        }

        return [
            'success' => true,
            'campaign_id' => $campaignId,
            'overall_status' => $overallStatus,
            'platforms' => $results,
        ];
    }

    /**
     * Pause campaign across all platforms
     *
     * @param string $campaignId
     * @return array
     */
    public function pauseCampaign(string $campaignId): array
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return [
                'success' => false,
                'error' => 'Campaign not found',
            ];
        }

        $results = [];

        try {
            DB::beginTransaction();

            // Update main campaign status
            $campaign->update(['status' => 'paused']);

            // Pause all associated ad campaigns
            $adCampaigns = AdCampaign::where('org_id', $campaign->org_id)->get();

            foreach ($adCampaigns as $adCampaign) {
                $adCampaign->update(['status' => 'paused']);
                $results[] = [
                    'ad_campaign_id' => $adCampaign->ad_campaign_id,
                    'status' => 'paused',
                    'success' => true,
                ];
            }

            DB::commit();

            Log::info("Campaign paused successfully", [
                'campaign_id' => $campaignId,
                'org_id' => $campaign->org_id,
            ]);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'status' => 'paused',
                'platforms' => $results,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to pause campaign", [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resume campaign across all platforms
     *
     * @param string $campaignId
     * @return array
     */
    public function resumeCampaign(string $campaignId): array
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return [
                'success' => false,
                'error' => 'Campaign not found',
            ];
        }

        $results = [];

        try {
            DB::beginTransaction();

            // Update main campaign status
            $campaign->update(['status' => 'active']);

            // Resume all associated ad campaigns
            $adCampaigns = AdCampaign::where('org_id', $campaign->org_id)
                ->where('status', 'paused')
                ->get();

            foreach ($adCampaigns as $adCampaign) {
                $adCampaign->update(['status' => 'active']);
                $results[] = [
                    'ad_campaign_id' => $adCampaign->ad_campaign_id,
                    'status' => 'active',
                    'success' => true,
                ];
            }

            DB::commit();

            Log::info("Campaign resumed successfully", [
                'campaign_id' => $campaignId,
                'org_id' => $campaign->org_id,
            ]);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'status' => 'active',
                'platforms' => $results,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to resume campaign", [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a campaign
     *
     * @param string $orgId
     * @param array $data
     * @return array
     */
    public function createCampaign(string $orgId, array $data): array
    {
        try {
            $campaign = Campaign::create([
                'org_id' => $orgId,
                'name' => $data['name'] ?? 'Unnamed Campaign',
                'objective' => $data['objective'] ?? 'awareness',
                'status' => $data['status'] ?? 'draft',
                'start_date' => $data['start_date'] ?? now(),
                'end_date' => $data['end_date'] ?? null,
                'budget' => $data['budget'] ?? 0,
                'currency' => $data['currency'] ?? 'USD',
                'description' => $data['description'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'context_id' => $data['context_id'] ?? null,
                'creative_id' => $data['creative_id'] ?? null,
                'value_id' => $data['value_id'] ?? null,
            ]);

            Log::info("Campaign created successfully", [
                'campaign_id' => $campaign->campaign_id,
                'org_id' => $orgId,
            ]);

            return [
                'success' => true,
                'campaign_id' => $campaign->campaign_id,
                'campaign' => $campaign->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to create campaign", [
                'error' => $e->getMessage(),
                'org_id' => $orgId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get campaign details
     *
     * @param string $campaignId
     * @return array|null
     */
    public function getCampaign(string $campaignId): ?array
    {
        $campaign = Campaign::with(['org', 'creator', 'performanceMetrics', 'adCampaigns'])
            ->find($campaignId);

        if (!$campaign) {
            return null;
        }

        return [
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $campaign->org_id,
            'name' => $campaign->name,
            'objective' => $campaign->objective,
            'status' => $campaign->status,
            'start_date' => $campaign->start_date?->toDateString(),
            'end_date' => $campaign->end_date?->toDateString(),
            'budget' => $campaign->budget,
            'currency' => $campaign->currency,
            'description' => $campaign->description,
            'created_by' => $campaign->created_by,
            'created_at' => $campaign->created_at?->toIso8601String(),
            'updated_at' => $campaign->updated_at?->toIso8601String(),
            'ad_campaigns_count' => $campaign->adCampaigns->count(),
            'metrics_count' => $campaign->performanceMetrics->count(),
        ];
    }

    /**
     * Activate a campaign
     *
     * @param string $campaignId
     * @return bool
     */
    public function activateCampaign(string $campaignId): bool
    {
        try {
            $campaign = Campaign::find($campaignId);

            if (!$campaign) {
                Log::warning("Campaign not found for activation", ['campaign_id' => $campaignId]);
                return false;
            }

            $campaign->update(['status' => 'active']);

            // Activate associated ad campaigns
            AdCampaign::where('org_id', $campaign->org_id)
                ->update(['status' => 'active']);

            Log::info("Campaign activated", [
                'campaign_id' => $campaignId,
                'org_id' => $campaign->org_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to activate campaign", [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Complete a campaign
     *
     * @param string $campaignId
     * @return bool
     */
    public function completeCampaign(string $campaignId): bool
    {
        try {
            $campaign = Campaign::find($campaignId);

            if (!$campaign) {
                Log::warning("Campaign not found for completion", ['campaign_id' => $campaignId]);
                return false;
            }

            $campaign->update([
                'status' => 'completed',
                'end_date' => now(),
            ]);

            // Complete associated ad campaigns
            AdCampaign::where('org_id', $campaign->org_id)
                ->update(['status' => 'completed']);

            Log::info("Campaign completed", [
                'campaign_id' => $campaignId,
                'org_id' => $campaign->org_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to complete campaign", [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Duplicate a campaign
     *
     * @param string $campaignId
     * @return array
     */
    public function duplicateCampaign(string $campaignId): array
    {
        try {
            $originalCampaign = Campaign::find($campaignId);

            if (!$originalCampaign) {
                return [
                    'success' => false,
                    'error' => 'Campaign not found',
                ];
            }

            // Create duplicate with modified name
            $newCampaign = $originalCampaign->replicate();
            $newCampaign->campaign_id = (string) Str::uuid();
            $newCampaign->name = $originalCampaign->name . ' (Copy)';
            $newCampaign->status = 'draft';
            $newCampaign->created_at = now();
            $newCampaign->updated_at = now();
            $newCampaign->save();

            Log::info("Campaign duplicated", [
                'original_campaign_id' => $campaignId,
                'new_campaign_id' => $newCampaign->campaign_id,
                'org_id' => $originalCampaign->org_id,
            ]);

            return [
                'success' => true,
                'new_campaign_id' => $newCampaign->campaign_id,
                'campaign' => $newCampaign->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to duplicate campaign", [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update campaign metrics
     *
     * @param string $campaignId
     * @return bool
     */
    public function updateCampaignMetrics(string $campaignId): bool
    {
        try {
            $campaign = Campaign::find($campaignId);

            if (!$campaign) {
                Log::warning("Campaign not found for metrics update", ['campaign_id' => $campaignId]);
                return false;
            }

            // Fetch latest metrics from analytics repository
            $analytics = $this->analyticsRepo->getCampaignAnalytics($campaign->org_id, $campaignId);

            // Update campaign with aggregated metrics (if you have such fields)
            // For now, we just log that metrics were refreshed
            Log::info("Campaign metrics updated", [
                'campaign_id' => $campaignId,
                'org_id' => $campaign->org_id,
                'metrics_retrieved' => !$analytics->isEmpty(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to update campaign metrics", [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate campaign insights
     *
     * @param string $campaignId
     * @return array
     */
    public function generateCampaignInsights(string $campaignId): array
    {
        try {
            $campaign = Campaign::find($campaignId);

            if (!$campaign) {
                return [
                    'success' => false,
                    'error' => 'Campaign not found',
                ];
            }

            // Get analytics data from repository
            $analytics = $this->analyticsRepo->getCampaignAnalytics($campaign->org_id, $campaignId);

            if ($analytics->isEmpty()) {
                return [
                    'success' => true,
                    'campaign_id' => $campaignId,
                    'message' => 'No analytics data available yet',
                    'insights' => [
                        'impressions' => 0,
                        'clicks' => 0,
                        'conversions' => 0,
                        'spend' => 0,
                        'ctr' => 0,
                        'cpc' => 0,
                        'roi' => 0,
                    ],
                ];
            }

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'insights' => $analytics->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to generate campaign insights", [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Determine overall campaign status based on platform statuses
     *
     * @param array $platformStatuses
     * @return string
     */
    protected function determineOverallStatus(array $platformStatuses): string
    {
        if (empty($platformStatuses)) {
            return 'draft';
        }

        // If any platform is active, campaign is active
        if (in_array('active', $platformStatuses)) {
            return 'active';
        }

        // If all platforms are paused, campaign is paused
        if (count(array_unique($platformStatuses)) === 1 && $platformStatuses[0] === 'paused') {
            return 'paused';
        }

        // If all platforms are completed, campaign is completed
        if (count(array_unique($platformStatuses)) === 1 && $platformStatuses[0] === 'completed') {
            return 'completed';
        }

        // Mixed statuses default to draft
        return 'draft';
    }
}
