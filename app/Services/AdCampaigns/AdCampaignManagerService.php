<?php

namespace App\Services\AdCampaigns;

use App\Models\AdPlatform\AdCampaign;
use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Unified Ad Campaign Manager Service
 * Manages ad campaigns across all platforms using the Connector pattern
 */
class AdCampaignManagerService
{
    /**
     * Create ad campaign on any platform
     *
     * @param Integration $integration The platform integration
     * @param array $campaignData Campaign configuration data
     * @return array Result with success status and data
     */
    public function createCampaign(Integration $integration, array $campaignData): array
    {
        try {
            DB::beginTransaction();

            // Get the appropriate connector for this platform
            $connector = ConnectorFactory::make($integration->platform);

            // Create campaign via connector
            $result = $connector->createAdCampaign($integration, $campaignData);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'فشل إنشاء الحملة الإعلانية');
            }

            // Store in database using Model
            $adCampaign = AdCampaign::create([
                'ad_account_id' => $integration->account_id,
                'campaign_id' => $campaignData['campaign_id'] ?? null,
                'platform' => $integration->platform,
                'campaign_name' => $campaignData['campaign_name'],
                'campaign_external_id' => $result['campaign_id'],
                'campaign_status' => $campaignData['status'] ?? 'PAUSED',
                'objective' => $campaignData['objective'],
                'budget_type' => $campaignData['budget_type'] ?? 'daily',
                'daily_budget' => $campaignData['daily_budget'] ?? null,
                'lifetime_budget' => $campaignData['lifetime_budget'] ?? null,
                'bid_strategy' => $campaignData['bid_strategy'] ?? null,
                'start_time' => $campaignData['start_date'] ?? null,
                'end_time' => $campaignData['end_date'] ?? null,
                'targeting' => $campaignData['targeting'] ?? [],
                'placements' => $campaignData['placements'] ?? [],
                'optimization_goal' => $campaignData['optimization_goal'] ?? null,
                'metadata' => $result['metadata'] ?? [],
                'last_synced_at' => now(),
            ]);

            DB::commit();

            Log::info('Ad campaign created successfully', [
                'platform' => $integration->platform,
                'campaign_id' => $adCampaign->ad_campaign_id,
                'external_id' => $result['campaign_id'],
            ]);

            return [
                'success' => true,
                'campaign' => $adCampaign,
                'external_id' => $result['campaign_id'],
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create ad campaign', [
                'platform' => $integration->platform,
                'error' => $e->getMessage(),
                'data' => $campaignData,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update ad campaign
     *
     * @param AdCampaign $adCampaign The campaign to update
     * @param Integration $integration The platform integration
     * @param array $updates Fields to update
     * @return array Result with success status
     */
    public function updateCampaign(AdCampaign $adCampaign, Integration $integration, array $updates): array
    {
        try {
            DB::beginTransaction();

            // Get connector
            $connector = ConnectorFactory::make($integration->platform);

            // Update on platform
            $result = $connector->updateAdCampaign(
                $integration,
                $adCampaign->campaign_external_id,
                $updates
            );

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'فشل تحديث الحملة الإعلانية');
            }

            // Update in database
            $adCampaign->update(array_merge($updates, [
                'last_synced_at' => now(),
            ]));

            DB::commit();

            Log::info('Ad campaign updated successfully', [
                'platform' => $integration->platform,
                'campaign_id' => $adCampaign->ad_campaign_id,
            ]);

            return [
                'success' => true,
                'campaign' => $adCampaign->fresh(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update ad campaign', [
                'campaign_id' => $adCampaign->ad_campaign_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get campaign metrics
     *
     * @param AdCampaign $adCampaign The campaign
     * @param Integration $integration The platform integration
     * @param array $options Date range and metric options
     * @return Collection Campaign metrics
     */
    public function getCampaignMetrics(
        AdCampaign $adCampaign,
        Integration $integration,
        array $options = []
    ): Collection {
        try {
            $connector = ConnectorFactory::make($integration->platform);

            return $connector->getAdCampaignMetrics(
                $integration,
                $adCampaign->campaign_external_id,
                $options
            );

        } catch (\Exception $e) {
            Log::error('Failed to get campaign metrics', [
                'campaign_id' => $adCampaign->ad_campaign_id,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Sync campaigns from platform
     *
     * @param Integration $integration The platform integration
     * @param array $options Sync options
     * @return array Result with synced campaigns count
     */
    public function syncCampaigns(Integration $integration, array $options = []): array
    {
        try {
            $connector = ConnectorFactory::make($integration->platform);

            $campaigns = $connector->syncCampaigns($integration, $options);

            $syncedCount = 0;

            foreach ($campaigns as $campaignData) {
                // Upsert campaign
                AdCampaign::updateOrCreate(
                    [
                        'campaign_external_id' => $campaignData['id'],
                        'platform' => $integration->platform,
                    ],
                    [
                        'ad_account_id' => $integration->account_id,
                        'campaign_name' => $campaignData['name'],
                        'campaign_status' => $campaignData['status'],
                        'objective' => $campaignData['objective'] ?? null,
                        'daily_budget' => $campaignData['daily_budget'] ?? null,
                        'lifetime_budget' => $campaignData['lifetime_budget'] ?? null,
                        'start_time' => $campaignData['start_time'] ?? null,
                        'end_time' => $campaignData['end_time'] ?? null,
                        'metadata' => $campaignData['metadata'] ?? [],
                        'last_synced_at' => now(),
                    ]
                );

                $syncedCount++;
            }

            Log::info('Campaigns synced successfully', [
                'platform' => $integration->platform,
                'count' => $syncedCount,
            ]);

            return [
                'success' => true,
                'synced_count' => $syncedCount,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to sync campaigns', [
                'platform' => $integration->platform,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get campaigns for an integration
     *
     * @param Integration $integration
     * @return Collection
     */
    public function getCampaigns(Integration $integration): Collection
    {
        return AdCampaign::where('platform', $integration->platform)
            ->where('ad_account_id', $integration->account_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active campaigns
     *
     * @param Integration $integration
     * @return Collection
     */
    public function getActiveCampaigns(Integration $integration): Collection
    {
        return AdCampaign::active()
            ->byPlatform($integration->platform)
            ->where('ad_account_id', $integration->account_id)
            ->get();
    }

    /**
     * Get campaign by external ID
     *
     * @param string $externalId
     * @param string $platform
     * @return AdCampaign|null
     */
    public function getCampaignByExternalId(string $externalId, string $platform): ?AdCampaign
    {
        return AdCampaign::where('campaign_external_id', $externalId)
            ->where('platform', $platform)
            ->first();
    }
}
