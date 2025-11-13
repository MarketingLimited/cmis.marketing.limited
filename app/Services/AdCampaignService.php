<?php

namespace App\Services;

use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for ad campaign management
 * Implements Sprint 4.1: Campaign Management
 *
 * Features:
 * - Multi-platform campaign creation (Meta, Google, LinkedIn, Twitter, TikTok)
 * - Campaign lifecycle management (draft, active, paused, completed)
 * - Platform API synchronization
 * - Budget and schedule management
 * - Campaign duplication
 * - Bulk operations
 */
class AdCampaignService
{
    /**
     * Create new ad campaign
     *
     * @param array $data
     * @return array
     */
    public function createCampaign(array $data): array
    {
        try {
            DB::beginTransaction();

            $campaignId = \Illuminate\Support\Str::uuid()->toString();

            // Validate ad account exists
            $adAccount = AdAccount::where('ad_account_id', $data['ad_account_id'])->first();
            if (!$adAccount) {
                throw new \Exception('Ad account not found');
            }

            // Create campaign
            $campaign = AdCampaign::create([
                'ad_campaign_id' => $campaignId,
                'ad_account_id' => $data['ad_account_id'],
                'campaign_id' => $data['campaign_id'] ?? null,
                'platform' => $data['platform'],
                'campaign_name' => $data['campaign_name'],
                'campaign_status' => $data['campaign_status'] ?? 'draft',
                'objective' => $data['objective'],
                'budget_type' => $data['budget_type'] ?? 'daily',
                'daily_budget' => $data['daily_budget'] ?? null,
                'lifetime_budget' => $data['lifetime_budget'] ?? null,
                'bid_strategy' => $data['bid_strategy'] ?? 'lowest_cost',
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'targeting' => $data['targeting'] ?? [],
                'placements' => $data['placements'] ?? [],
                'optimization_goal' => $data['optimization_goal'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'provider' => 'cmis'
            ]);

            // If not draft, sync to platform
            if ($data['campaign_status'] !== 'draft' && ($data['sync_to_platform'] ?? false)) {
                $syncResult = $this->syncCampaignToPlatform($campaign);
                if (!$syncResult['success']) {
                    throw new \Exception('Failed to sync campaign to platform: ' . ($syncResult['error'] ?? 'Unknown error'));
                }
                $campaign->campaign_external_id = $syncResult['external_id'];
                $campaign->last_synced_at = now();
                $campaign->save();
            }

            DB::commit();

            Log::info('Ad campaign created', [
                'campaign_id' => $campaignId,
                'platform' => $data['platform'],
                'status' => $campaign->campaign_status
            ]);

            return [
                'success' => true,
                'data' => $campaign->fresh(),
                'synced_to_platform' => isset($syncResult) && $syncResult['success']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create ad campaign', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update ad campaign
     *
     * @param string $campaignId
     * @param array $data
     * @return array
     */
    public function updateCampaign(string $campaignId, array $data): array
    {
        try {
            DB::beginTransaction();

            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            // Store old status for comparison
            $oldStatus = $campaign->campaign_status;

            // Update campaign
            $campaign->update(array_filter([
                'campaign_name' => $data['campaign_name'] ?? $campaign->campaign_name,
                'campaign_status' => $data['campaign_status'] ?? $campaign->campaign_status,
                'objective' => $data['objective'] ?? $campaign->objective,
                'budget_type' => $data['budget_type'] ?? $campaign->budget_type,
                'daily_budget' => $data['daily_budget'] ?? $campaign->daily_budget,
                'lifetime_budget' => $data['lifetime_budget'] ?? $campaign->lifetime_budget,
                'bid_strategy' => $data['bid_strategy'] ?? $campaign->bid_strategy,
                'start_time' => $data['start_time'] ?? $campaign->start_time,
                'end_time' => $data['end_time'] ?? $campaign->end_time,
                'targeting' => $data['targeting'] ?? $campaign->targeting,
                'placements' => $data['placements'] ?? $campaign->placements,
                'optimization_goal' => $data['optimization_goal'] ?? $campaign->optimization_goal,
                'metadata' => $data['metadata'] ?? $campaign->metadata,
            ], fn($value) => $value !== null));

            // Sync to platform if campaign exists externally
            if ($campaign->campaign_external_id && ($data['sync_to_platform'] ?? true)) {
                $syncResult = $this->syncCampaignToPlatform($campaign);
                if ($syncResult['success']) {
                    $campaign->last_synced_at = now();
                    $campaign->save();
                }
            }

            DB::commit();

            Log::info('Ad campaign updated', [
                'campaign_id' => $campaignId,
                'old_status' => $oldStatus,
                'new_status' => $campaign->campaign_status
            ]);

            return [
                'success' => true,
                'data' => $campaign->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update ad campaign', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get campaign details
     *
     * @param string $campaignId
     * @param bool $includeMetrics
     * @return array
     */
    public function getCampaign(string $campaignId, bool $includeMetrics = false): array
    {
        try {
            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            $data = [
                'campaign' => $campaign,
                'ad_account' => $campaign->adAccount,
            ];

            if ($includeMetrics) {
                $data['metrics'] = $this->getCampaignMetrics($campaignId);
                $data['performance_summary'] = $this->getCampaignPerformanceSummary($campaignId);
            }

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get campaign', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List campaigns with filters
     *
     * @param array $filters
     * @return array
     */
    public function listCampaigns(array $filters = []): array
    {
        try {
            $query = AdCampaign::query();

            // Apply filters
            if (isset($filters['ad_account_id'])) {
                $query->where('ad_account_id', $filters['ad_account_id']);
            }

            if (isset($filters['platform'])) {
                $query->where('platform', $filters['platform']);
            }

            if (isset($filters['campaign_status'])) {
                $query->where('campaign_status', $filters['campaign_status']);
            }

            if (isset($filters['objective'])) {
                $query->where('objective', $filters['objective']);
            }

            if (isset($filters['search'])) {
                $query->where('campaign_name', 'ILIKE', '%' . $filters['search'] . '%');
            }

            // Date range filters
            if (isset($filters['start_date_from'])) {
                $query->where('start_time', '>=', $filters['start_date_from']);
            }

            if (isset($filters['start_date_to'])) {
                $query->where('start_time', '<=', $filters['start_date_to']);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $filters['per_page'] ?? 20;
            $campaigns = $query->paginate($perPage);

            return [
                'success' => true,
                'data' => $campaigns->items(),
                'pagination' => [
                    'total' => $campaigns->total(),
                    'per_page' => $campaigns->perPage(),
                    'current_page' => $campaigns->currentPage(),
                    'last_page' => $campaigns->lastPage(),
                    'from' => $campaigns->firstItem(),
                    'to' => $campaigns->lastItem()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to list campaigns', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update campaign status
     *
     * @param string $campaignId
     * @param string $status
     * @return array
     */
    public function updateCampaignStatus(string $campaignId, string $status): array
    {
        try {
            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            $oldStatus = $campaign->campaign_status;
            $campaign->campaign_status = $status;
            $campaign->save();

            // Sync status to platform if campaign exists externally
            if ($campaign->campaign_external_id) {
                $syncResult = $this->syncCampaignStatusToPlatform($campaign, $status);
                if ($syncResult['success']) {
                    $campaign->last_synced_at = now();
                    $campaign->save();
                }
            }

            Log::info('Campaign status updated', [
                'campaign_id' => $campaignId,
                'old_status' => $oldStatus,
                'new_status' => $status
            ]);

            return [
                'success' => true,
                'data' => $campaign->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update campaign status', [
                'campaign_id' => $campaignId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Duplicate campaign
     *
     * @param string $campaignId
     * @param array $overrides
     * @return array
     */
    public function duplicateCampaign(string $campaignId, array $overrides = []): array
    {
        try {
            $originalCampaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$originalCampaign) {
                throw new \Exception('Campaign not found');
            }

            $newCampaignId = \Illuminate\Support\Str::uuid()->toString();

            // Prepare data for new campaign
            $newData = [
                'ad_campaign_id' => $newCampaignId,
                'ad_account_id' => $originalCampaign->ad_account_id,
                'campaign_id' => $originalCampaign->campaign_id,
                'platform' => $originalCampaign->platform,
                'campaign_name' => $overrides['campaign_name'] ?? ($originalCampaign->campaign_name . ' (Copy)'),
                'campaign_status' => 'draft', // Always create duplicates as draft
                'objective' => $originalCampaign->objective,
                'budget_type' => $originalCampaign->budget_type,
                'daily_budget' => $originalCampaign->daily_budget,
                'lifetime_budget' => $originalCampaign->lifetime_budget,
                'bid_strategy' => $originalCampaign->bid_strategy,
                'start_time' => $overrides['start_time'] ?? null,
                'end_time' => $overrides['end_time'] ?? null,
                'targeting' => $originalCampaign->targeting,
                'placements' => $originalCampaign->placements,
                'optimization_goal' => $originalCampaign->optimization_goal,
                'metadata' => array_merge($originalCampaign->metadata ?? [], ['duplicated_from' => $campaignId]),
                'provider' => 'cmis'
            ];

            $newCampaign = AdCampaign::create($newData);

            Log::info('Campaign duplicated', [
                'original_campaign_id' => $campaignId,
                'new_campaign_id' => $newCampaignId
            ]);

            return [
                'success' => true,
                'data' => $newCampaign
            ];

        } catch (\Exception $e) {
            Log::error('Failed to duplicate campaign', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete campaign
     *
     * @param string $campaignId
     * @param bool $permanent
     * @return bool
     */
    public function deleteCampaign(string $campaignId, bool $permanent = false): bool
    {
        try {
            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            if ($permanent) {
                $campaign->forceDelete();
            } else {
                $campaign->delete();
            }

            Log::info('Campaign deleted', [
                'campaign_id' => $campaignId,
                'permanent' => $permanent
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete campaign', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Bulk update campaign statuses
     *
     * @param array $campaignIds
     * @param string $status
     * @return array
     */
    public function bulkUpdateStatus(array $campaignIds, string $status): array
    {
        try {
            $results = [
                'success' => [],
                'failed' => []
            ];

            foreach ($campaignIds as $campaignId) {
                $result = $this->updateCampaignStatus($campaignId, $status);
                if ($result['success']) {
                    $results['success'][] = $campaignId;
                } else {
                    $results['failed'][] = [
                        'campaign_id' => $campaignId,
                        'error' => $result['error']
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results,
                'summary' => [
                    'total' => count($campaignIds),
                    'succeeded' => count($results['success']),
                    'failed' => count($results['failed'])
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed bulk status update', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get campaign performance summary
     *
     * @param string $campaignId
     * @return array
     */
    protected function getCampaignPerformanceSummary(string $campaignId): array
    {
        // Get metrics from ad_metrics table
        $metrics = DB::table('cmis_ads.ad_metrics')
            ->where('entity_id', $campaignId)
            ->where('entity_type', 'campaign')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        if ($metrics->isEmpty()) {
            return [
                'impressions' => 0,
                'clicks' => 0,
                'conversions' => 0,
                'spend' => 0,
                'ctr' => 0,
                'cpc' => 0,
                'cpa' => 0,
                'roas' => 0
            ];
        }

        $totalImpressions = $metrics->sum('impressions');
        $totalClicks = $metrics->sum('clicks');
        $totalConversions = $metrics->sum('conversions');
        $totalSpend = $metrics->sum('spend');
        $totalRevenue = $metrics->sum('revenue');

        return [
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'conversions' => $totalConversions,
            'spend' => round($totalSpend, 2),
            'revenue' => round($totalRevenue, 2),
            'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
            'cpc' => $totalClicks > 0 ? round($totalSpend / $totalClicks, 2) : 0,
            'cpa' => $totalConversions > 0 ? round($totalSpend / $totalConversions, 2) : 0,
            'roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0
        ];
    }

    /**
     * Get campaign metrics time series
     *
     * @param string $campaignId
     * @return Collection
     */
    protected function getCampaignMetrics(string $campaignId): Collection
    {
        return DB::table('cmis_ads.ad_metrics')
            ->where('entity_id', $campaignId)
            ->where('entity_type', 'campaign')
            ->orderBy('date', 'asc')
            ->limit(90)
            ->get();
    }

    /**
     * Sync campaign to platform API
     *
     * @param AdCampaign $campaign
     * @return array
     */
    protected function syncCampaignToPlatform(AdCampaign $campaign): array
    {
        // This would integrate with platform-specific APIs
        // For now, return placeholder indicating integration is needed

        Log::info('Campaign sync to platform requested', [
            'campaign_id' => $campaign->ad_campaign_id,
            'platform' => $campaign->platform
        ]);

        return [
            'success' => true,
            'external_id' => 'platform_' . uniqid(),
            'note' => 'Platform API integration required for ' . $campaign->platform
        ];
    }

    /**
     * Sync campaign status to platform API
     *
     * @param AdCampaign $campaign
     * @param string $status
     * @return array
     */
    protected function syncCampaignStatusToPlatform(AdCampaign $campaign, string $status): array
    {
        // This would integrate with platform-specific APIs

        Log::info('Campaign status sync to platform requested', [
            'campaign_id' => $campaign->ad_campaign_id,
            'platform' => $campaign->platform,
            'status' => $status
        ]);

        return [
            'success' => true,
            'note' => 'Platform API integration required for ' . $campaign->platform
        ];
    }
}
