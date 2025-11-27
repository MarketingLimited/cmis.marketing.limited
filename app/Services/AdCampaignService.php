<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Integration;
use App\Services\AdPlatforms\AdPlatformFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AdCampaignService
 *
 * Service for managing ad campaigns across platforms.
 * Implements Sprint 4.1: Campaign Management
 *
 * @package App\Services
 */
class AdCampaignService
{
    /**
     * Create a new ad campaign
     *
     * @param string $orgId Organization ID
     * @param array $data Campaign data
     * @return Campaign|null
     */
    public function createCampaign(string $orgId, array $data): ?Campaign
    {
        try {
            return DB::transaction(function () use ($orgId, $data) {
                // Set defaults
                $campaignData = array_merge([
                    'campaign_id' => Str::uuid()->toString(),
                    'org_id' => $orgId,
                    'status' => 'draft',
                    'currency' => $data['currency'] ?? 'USD',
                    'sync_status' => 'pending',
                ], $data);

                // Create the campaign
                $campaign = Campaign::create($campaignData);

                Log::info('Campaign created', [
                    'campaign_id' => $campaign->campaign_id,
                    'org_id' => $orgId,
                    'name' => $campaign->name,
                ]);

                return $campaign;
            });
        } catch (\Exception $e) {
            Log::error('Failed to create campaign', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get campaigns for an organization
     *
     * @param string $orgId Organization ID
     * @param array $filters Optional filters
     * @return Collection
     */
    public function getCampaigns(string $orgId, array $filters = []): Collection
    {
        return Campaign::where('org_id', $orgId)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['platform']), fn($q) => $q->where('platform', $filters['platform']))
            ->when(isset($filters['objective']), fn($q) => $q->where('objective', $filters['objective']))
            ->when(isset($filters['search']), fn($q) => $q->where('name', 'ILIKE', '%' . $filters['search'] . '%'))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a single campaign
     *
     * @param string $campaignId Campaign ID
     * @return Campaign|null
     */
    public function getCampaign(string $campaignId): ?Campaign
    {
        return Campaign::find($campaignId);
    }

    /**
     * Update a campaign
     *
     * @param string $campaignId Campaign ID
     * @param array $data Update data
     * @return Campaign|null
     */
    public function updateCampaign(string $campaignId, array $data): ?Campaign
    {
        $campaign = Campaign::find($campaignId);
        if ($campaign) {
            $campaign->update($data);

            // If campaign is synced and we're updating key fields, mark for re-sync
            if ($campaign->isSynced() && $this->requiresResync($data)) {
                $campaign->update(['sync_status' => 'pending_update']);
            }
        }
        return $campaign;
    }

    /**
     * Delete a campaign
     *
     * @param string $campaignId Campaign ID
     * @return bool
     */
    public function deleteCampaign(string $campaignId): bool
    {
        $campaign = Campaign::find($campaignId);
        return $campaign ? $campaign->delete() : false;
    }

    /**
     * Update campaign status
     *
     * @param string $campaignId Campaign ID
     * @param string $status New status
     * @return Campaign|null
     */
    public function updateStatus(string $campaignId, string $status): ?Campaign
    {
        $campaign = Campaign::find($campaignId);
        if ($campaign) {
            $campaign->update(['status' => $status]);
        }
        return $campaign;
    }

    /**
     * Duplicate a campaign
     *
     * @param string $campaignId Campaign ID
     * @param string|null $newName Optional new name
     * @return Campaign|null
     */
    public function duplicateCampaign(string $campaignId, ?string $newName = null): ?Campaign
    {
        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            return null;
        }

        $replica = $campaign->replicate([
            'external_campaign_id',
            'last_synced_at',
            'sync_status',
        ]);
        $replica->campaign_id = Str::uuid()->toString();
        $replica->name = $newName ?? $campaign->name . ' (Copy)';
        $replica->status = 'draft';
        $replica->sync_status = 'pending';
        $replica->save();

        return $replica;
    }

    /**
     * Bulk update campaign statuses
     *
     * @param array $campaignIds Array of campaign IDs
     * @param string $status New status
     * @return int Number of updated campaigns
     */
    public function bulkUpdateStatus(array $campaignIds, string $status): int
    {
        return Campaign::whereIn('campaign_id', $campaignIds)
            ->update(['status' => $status]);
    }

    /**
     * Sync campaign with platform
     *
     * @param string $campaignId Campaign ID
     * @return bool
     */
    public function syncWithPlatform(string $campaignId): bool
    {
        try {
            $campaign = Campaign::find($campaignId);
            if (!$campaign || !$campaign->platform) {
                Log::warning('Cannot sync campaign: campaign not found or platform not set', [
                    'campaign_id' => $campaignId,
                ]);
                return false;
            }

            // Get the integration for this org and platform
            $integration = Integration::where('org_id', $campaign->org_id)
                ->where('platform', $campaign->platform)
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                Log::warning('Cannot sync campaign: no active integration found', [
                    'campaign_id' => $campaignId,
                    'platform' => $campaign->platform,
                ]);
                return false;
            }

            // Use the platform service to sync
            $platformService = AdPlatformFactory::make($integration);

            // If campaign already has external ID, update; otherwise create
            if ($campaign->external_campaign_id) {
                $result = $platformService->updateCampaign($campaign->external_campaign_id, [
                    'name' => $campaign->name,
                    'status' => $this->mapStatusToPlatform($campaign->status, $campaign->platform),
                    'budget' => $campaign->getEffectiveBudget(),
                    'objective' => $campaign->objective,
                ]);
            } else {
                $result = $platformService->createCampaign([
                    'name' => $campaign->name,
                    'objective' => $campaign->objective,
                    'status' => $this->mapStatusToPlatform($campaign->status, $campaign->platform),
                    'budget' => $campaign->getEffectiveBudget(),
                    'budget_type' => $campaign->budget_type,
                    'bid_strategy' => $campaign->bid_strategy,
                ]);
            }

            if ($result['success']) {
                $campaign->update([
                    'external_campaign_id' => $result['data']['id'] ?? $campaign->external_campaign_id,
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                ]);

                Log::info('Campaign synced successfully', [
                    'campaign_id' => $campaignId,
                    'external_id' => $result['data']['id'] ?? null,
                ]);

                return true;
            }

            Log::error('Campaign sync failed', [
                'campaign_id' => $campaignId,
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            $campaign->update(['sync_status' => 'error']);
            return false;

        } catch (\Exception $e) {
            Log::error('Exception during campaign sync', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get campaign metrics
     *
     * @param string $campaignId Campaign ID
     * @param string $dateRange Date range (e.g., '7d', '30d', 'all')
     * @return array
     */
    public function getCampaignMetrics(string $campaignId, string $dateRange = '30d'): array
    {
        try {
            $campaign = Campaign::find($campaignId);
            if (!$campaign) {
                return $this->getEmptyMetrics();
            }

            // Calculate date range
            $startDate = match($dateRange) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                '90d' => now()->subDays(90),
                'all' => null,
                default => now()->subDays(30),
            };

            // Get metrics from performance metrics table
            $query = DB::table('cmis.campaign_performance_metrics')
                ->where('campaign_id', $campaignId);

            if ($startDate) {
                $query->where('metric_date', '>=', $startDate->toDateString());
            }

            $metrics = $query->selectRaw("
                COALESCE(SUM(impressions), 0) as impressions,
                COALESCE(SUM(clicks), 0) as clicks,
                COALESCE(SUM(conversions), 0) as conversions,
                COALESCE(SUM(spend), 0) as spend,
                COALESCE(SUM(reach), 0) as reach,
                COALESCE(SUM(video_views), 0) as video_views
            ")->first();

            // Calculate derived metrics
            $impressions = (int) ($metrics->impressions ?? 0);
            $clicks = (int) ($metrics->clicks ?? 0);
            $conversions = (int) ($metrics->conversions ?? 0);
            $spend = (float) ($metrics->spend ?? 0);

            return [
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversions' => $conversions,
                'spend' => round($spend, 2),
                'reach' => (int) ($metrics->reach ?? 0),
                'video_views' => (int) ($metrics->video_views ?? 0),
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                'cpm' => $impressions > 0 ? round(($spend / $impressions) * 1000, 2) : 0,
                'cpa' => $conversions > 0 ? round($spend / $conversions, 2) : 0,
                'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get campaign metrics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
            return $this->getEmptyMetrics();
        }
    }

    /**
     * Get empty metrics array
     */
    private function getEmptyMetrics(): array
    {
        return [
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'spend' => 0,
            'reach' => 0,
            'video_views' => 0,
            'ctr' => 0,
            'cpc' => 0,
            'cpm' => 0,
            'cpa' => 0,
            'conversion_rate' => 0,
        ];
    }

    /**
     * Check if update data requires re-sync with platform
     */
    private function requiresResync(array $data): bool
    {
        $syncRequiredFields = [
            'name',
            'status',
            'budget',
            'daily_budget',
            'lifetime_budget',
            'bid_strategy',
            'bid_amount',
            'objective',
        ];

        return !empty(array_intersect(array_keys($data), $syncRequiredFields));
    }

    /**
     * Map internal status to platform-specific status
     */
    private function mapStatusToPlatform(string $status, string $platform): string
    {
        $statusMap = [
            'meta' => [
                'draft' => 'PAUSED',
                'active' => 'ACTIVE',
                'paused' => 'PAUSED',
                'completed' => 'ARCHIVED',
            ],
            'google' => [
                'draft' => 'PAUSED',
                'active' => 'ENABLED',
                'paused' => 'PAUSED',
                'completed' => 'REMOVED',
            ],
            'tiktok' => [
                'draft' => 'DISABLE',
                'active' => 'ENABLE',
                'paused' => 'DISABLE',
                'completed' => 'DISABLE',
            ],
        ];

        return $statusMap[$platform][$status] ?? strtoupper($status);
    }
}
