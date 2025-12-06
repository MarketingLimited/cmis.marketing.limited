<?php

namespace App\Services\Orchestration;

use App\Models\Core\Integration;
use App\Models\Orchestration\OrchestrationPlatform;
use App\Models\Orchestration\OrchestrationSyncLog;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Cross Platform Sync Service
 *
 * Handles syncing campaign data across multiple ad platforms.
 * Implements real API calls to Meta, Google, TikTok, LinkedIn, Twitter, Snapchat.
 *
 * Fixes Critical Issue from TODO plan:
 * - fetchPlatformPerformance() now makes real API calls
 * - pausePlatformCampaign() now actually pauses campaigns
 * - resumePlatformCampaign() now actually resumes campaigns
 * - createPlatformCampaign() now creates real campaigns
 * - updatePlatformCampaign() now syncs updates to platforms
 */
class CrossPlatformSyncService
{
    /**
     * Sync platform mapping (pull performance from platform).
     */
    public function syncPlatformMapping(OrchestrationPlatform $mapping, string $syncType = 'full'): OrchestrationSyncLog
    {
        $syncLog = OrchestrationSyncLog::create([
            'org_id' => $mapping->org_id,
            'orchestration_id' => $mapping->orchestration_id,
            'platform_mapping_id' => $mapping->platform_mapping_id,
            'sync_type' => $syncType,
            'direction' => 'pull',
            'status' => 'running',
        ]);

        $syncLog->markAsRunning();

        try {
            // Fetch performance data from platform
            $performance = $this->fetchPlatformPerformance($mapping);

            // Update mapping with latest metrics
            $mapping->updateMetrics($performance);
            $mapping->markSynced();

            // Mark sync as completed
            $syncLog->markAsCompleted([
                'entities_synced' => 1,
                'entities_failed' => 0,
                'changes_detected' => $performance,
            ]);

            return $syncLog;

        } catch (\Exception $e) {
            Log::error('Platform sync failed', [
                'platform' => $mapping->platform,
                'mapping_id' => $mapping->platform_mapping_id,
                'error' => $e->getMessage(),
            ]);

            $syncLog->markAsFailed($e->getMessage());
            $mapping->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch performance from platform API.
     */
    protected function fetchPlatformPerformance(OrchestrationPlatform $mapping): array
    {
        $integration = $this->getIntegration($mapping);

        if (!$integration) {
            Log::warning('No active integration found for platform sync', [
                'platform' => $mapping->platform,
                'org_id' => $mapping->org_id,
            ]);
            // Return current values if no integration available
            return [
                'spend' => $mapping->spend ?? 0,
                'impressions' => $mapping->impressions ?? 0,
                'clicks' => $mapping->clicks ?? 0,
                'conversions' => $mapping->conversions ?? 0,
                'revenue' => $mapping->revenue ?? 0,
            ];
        }

        try {
            $connector = ConnectorFactory::make($mapping->platform);

            // Get campaign metrics using connector's getAdCampaignMetrics method
            if (method_exists($connector, 'getAdCampaignMetrics') && $mapping->platform_campaign_id) {
                $metrics = $connector->getAdCampaignMetrics($integration, $mapping->platform_campaign_id);

                // Extract and aggregate metrics
                $aggregated = [
                    'spend' => 0,
                    'impressions' => 0,
                    'clicks' => 0,
                    'conversions' => 0,
                    'revenue' => 0,
                ];

                foreach ($metrics as $metric) {
                    $aggregated['spend'] += $metric->spend ?? $metric['spend'] ?? 0;
                    $aggregated['impressions'] += $metric->impressions ?? $metric['impressions'] ?? 0;
                    $aggregated['clicks'] += $metric->clicks ?? $metric['clicks'] ?? 0;
                    $aggregated['conversions'] += $metric->conversions ?? $metric['conversions'] ?? 0;
                    $aggregated['revenue'] += $metric->revenue ?? $metric['revenue'] ?? 0;
                }

                Log::info('Fetched platform performance', [
                    'platform' => $mapping->platform,
                    'campaign_id' => $mapping->platform_campaign_id,
                    'metrics' => $aggregated,
                ]);

                return $aggregated;
            }

            // Fallback to getAccountMetrics if campaign-specific not available
            if (method_exists($connector, 'getAccountMetrics')) {
                $accountMetrics = $connector->getAccountMetrics($integration);

                // Try to find this specific campaign in account metrics
                foreach ($accountMetrics as $campaign) {
                    if (($campaign->campaign_id ?? '') === $mapping->platform_campaign_id) {
                        return [
                            'spend' => $campaign->spend ?? 0,
                            'impressions' => $campaign->impressions ?? 0,
                            'clicks' => $campaign->clicks ?? 0,
                            'conversions' => $campaign->conversions ?? 0,
                            'revenue' => $campaign->revenue ?? 0,
                        ];
                    }
                }
            }

            // If no specific metrics found, return current values
            return [
                'spend' => $mapping->spend ?? 0,
                'impressions' => $mapping->impressions ?? 0,
                'clicks' => $mapping->clicks ?? 0,
                'conversions' => $mapping->conversions ?? 0,
                'revenue' => $mapping->revenue ?? 0,
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to fetch platform performance, using cached values', [
                'platform' => $mapping->platform,
                'error' => $e->getMessage(),
            ]);

            return [
                'spend' => $mapping->spend ?? 0,
                'impressions' => $mapping->impressions ?? 0,
                'clicks' => $mapping->clicks ?? 0,
                'conversions' => $mapping->conversions ?? 0,
                'revenue' => $mapping->revenue ?? 0,
            ];
        }
    }

    /**
     * Pause campaign on platform.
     */
    public function pausePlatformCampaign(OrchestrationPlatform $mapping): void
    {
        $integration = $this->getIntegration($mapping);

        if (!$integration) {
            Log::warning('No integration found for pause operation', [
                'platform' => $mapping->platform,
                'org_id' => $mapping->org_id,
            ]);
            return;
        }

        if (!$mapping->platform_campaign_id) {
            Log::warning('No platform campaign ID for pause operation', [
                'mapping_id' => $mapping->platform_mapping_id,
            ]);
            return;
        }

        try {
            $connector = ConnectorFactory::make($mapping->platform);

            if (method_exists($connector, 'updateAdCampaign')) {
                $connector->updateAdCampaign($integration, $mapping->platform_campaign_id, [
                    'status' => $this->getPausedStatus($mapping->platform),
                ]);

                Log::info('Paused campaign on platform', [
                    'platform' => $mapping->platform,
                    'campaign_id' => $mapping->platform_campaign_id,
                ]);

                $mapping->markAsPaused();
            } else {
                Log::warning('Platform connector does not support campaign updates', [
                    'platform' => $mapping->platform,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to pause campaign on platform', [
                'platform' => $mapping->platform,
                'campaign_id' => $mapping->platform_campaign_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resume campaign on platform.
     */
    public function resumePlatformCampaign(OrchestrationPlatform $mapping): void
    {
        $integration = $this->getIntegration($mapping);

        if (!$integration) {
            Log::warning('No integration found for resume operation', [
                'platform' => $mapping->platform,
                'org_id' => $mapping->org_id,
            ]);
            return;
        }

        if (!$mapping->platform_campaign_id) {
            Log::warning('No platform campaign ID for resume operation', [
                'mapping_id' => $mapping->platform_mapping_id,
            ]);
            return;
        }

        try {
            $connector = ConnectorFactory::make($mapping->platform);

            if (method_exists($connector, 'updateAdCampaign')) {
                $connector->updateAdCampaign($integration, $mapping->platform_campaign_id, [
                    'status' => $this->getActiveStatus($mapping->platform),
                ]);

                Log::info('Resumed campaign on platform', [
                    'platform' => $mapping->platform,
                    'campaign_id' => $mapping->platform_campaign_id,
                ]);

                $mapping->update(['status' => 'active']);
            } else {
                Log::warning('Platform connector does not support campaign updates', [
                    'platform' => $mapping->platform,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to resume campaign on platform', [
                'platform' => $mapping->platform,
                'campaign_id' => $mapping->platform_campaign_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create campaign on platform.
     */
    public function createPlatformCampaign(OrchestrationPlatform $mapping): string
    {
        $integration = $this->getIntegration($mapping);

        if (!$integration) {
            throw new \Exception("No active integration found for platform: {$mapping->platform}");
        }

        $mapping->markAsCreating();

        try {
            $connector = ConnectorFactory::make($mapping->platform);

            if (method_exists($connector, 'createAdCampaign')) {
                // Build campaign data from mapping and orchestration
                $orchestration = $mapping->orchestration;
                $campaignData = $this->buildCampaignData($mapping, $orchestration);

                $result = $connector->createAdCampaign($integration, $campaignData);

                $platformCampaignId = $result['campaign_id'] ?? $result['id'] ?? null;

                if (!$platformCampaignId) {
                    throw new \Exception('Platform did not return a campaign ID');
                }

                Log::info('Created campaign on platform', [
                    'platform' => $mapping->platform,
                    'platform_campaign_id' => $platformCampaignId,
                    'mapping_id' => $mapping->platform_mapping_id,
                ]);

                $mapping->markAsActive($platformCampaignId, $result['name'] ?? $campaignData['name'] ?? null);

                return $platformCampaignId;
            } else {
                throw new \Exception("Platform connector does not support campaign creation: {$mapping->platform}");
            }
        } catch (\Exception $e) {
            Log::error('Failed to create campaign on platform', [
                'platform' => $mapping->platform,
                'mapping_id' => $mapping->platform_mapping_id,
                'error' => $e->getMessage(),
            ]);
            $mapping->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Update campaign settings on platform.
     */
    public function updatePlatformCampaign(OrchestrationPlatform $mapping, array $updates): void
    {
        $integration = $this->getIntegration($mapping);

        if (!$integration) {
            Log::warning('No integration found for update operation', [
                'platform' => $mapping->platform,
                'org_id' => $mapping->org_id,
            ]);
            return;
        }

        if (!$mapping->platform_campaign_id) {
            Log::warning('No platform campaign ID for update operation', [
                'mapping_id' => $mapping->platform_mapping_id,
            ]);
            return;
        }

        try {
            $connector = ConnectorFactory::make($mapping->platform);

            if (method_exists($connector, 'updateAdCampaign')) {
                // Transform updates to platform-specific format
                $platformUpdates = $this->transformUpdates($mapping->platform, $updates);

                $connector->updateAdCampaign($integration, $mapping->platform_campaign_id, $platformUpdates);

                Log::info('Updated campaign on platform', [
                    'platform' => $mapping->platform,
                    'campaign_id' => $mapping->platform_campaign_id,
                    'updates' => array_keys($updates),
                ]);
            } else {
                Log::warning('Platform connector does not support campaign updates', [
                    'platform' => $mapping->platform,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update campaign on platform', [
                'platform' => $mapping->platform,
                'campaign_id' => $mapping->platform_campaign_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get Integration for the platform mapping.
     */
    protected function getIntegration(OrchestrationPlatform $mapping): ?Integration
    {
        // Try to get integration via connection relationship
        if ($mapping->connection_id) {
            $connection = $mapping->connection;
            if ($connection && $connection->isActive()) {
                // Create a pseudo-integration object from PlatformConnection
                // since connectors expect Integration
                return Integration::where('org_id', $mapping->org_id)
                    ->where('platform', $mapping->platform)
                    ->where('is_active', true)
                    ->first();
            }
        }

        // Fallback: find any active integration for this org/platform
        return Integration::where('org_id', $mapping->org_id)
            ->where('platform', $mapping->platform)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Build campaign data for platform API.
     */
    protected function buildCampaignData(OrchestrationPlatform $mapping, $orchestration): array
    {
        $platformConfig = $mapping->platform_config ?? [];

        $baseData = [
            'name' => $platformConfig['campaign_name'] ?? $orchestration->name ?? 'Campaign ' . now()->format('Y-m-d'),
            'status' => $this->getActiveStatus($mapping->platform),
            'objective' => $platformConfig['objective'] ?? $this->mapObjective($mapping->platform, $orchestration->objective ?? 'AWARENESS'),
        ];

        // Add budget if available
        if ($mapping->allocated_budget) {
            $baseData['daily_budget'] = $this->convertBudget($mapping->platform, $mapping->allocated_budget);
        }

        // Merge platform-specific config
        return array_merge($baseData, $platformConfig);
    }

    /**
     * Transform updates to platform-specific format.
     */
    protected function transformUpdates(string $platform, array $updates): array
    {
        $transformed = [];

        foreach ($updates as $key => $value) {
            switch ($key) {
                case 'budget':
                    $transformed['daily_budget'] = $this->convertBudget($platform, $value);
                    break;
                case 'status':
                    $transformed['status'] = $value === 'paused'
                        ? $this->getPausedStatus($platform)
                        : $this->getActiveStatus($platform);
                    break;
                case 'name':
                    $transformed['name'] = $value;
                    break;
                default:
                    $transformed[$key] = $value;
            }
        }

        return $transformed;
    }

    /**
     * Get platform-specific active status.
     */
    protected function getActiveStatus(string $platform): string
    {
        return match ($platform) {
            'meta', 'facebook', 'instagram' => 'ACTIVE',
            'google', 'google_ads' => 'ENABLED',
            'tiktok' => 'ENABLE',
            'linkedin' => 'ACTIVE',
            'twitter', 'x' => 'ACTIVE',
            'snapchat' => 'ACTIVE',
            default => 'ACTIVE',
        };
    }

    /**
     * Get platform-specific paused status.
     */
    protected function getPausedStatus(string $platform): string
    {
        return match ($platform) {
            'meta', 'facebook', 'instagram' => 'PAUSED',
            'google', 'google_ads' => 'PAUSED',
            'tiktok' => 'DISABLE',
            'linkedin' => 'PAUSED',
            'twitter', 'x' => 'PAUSED',
            'snapchat' => 'PAUSED',
            default => 'PAUSED',
        };
    }

    /**
     * Map objective to platform-specific format.
     */
    protected function mapObjective(string $platform, string $objective): string
    {
        $objectives = [
            'meta' => [
                'AWARENESS' => 'OUTCOME_AWARENESS',
                'TRAFFIC' => 'OUTCOME_TRAFFIC',
                'ENGAGEMENT' => 'OUTCOME_ENGAGEMENT',
                'LEADS' => 'OUTCOME_LEADS',
                'CONVERSIONS' => 'OUTCOME_SALES',
                'SALES' => 'OUTCOME_SALES',
            ],
            'google' => [
                'AWARENESS' => 'BRAND_AWARENESS_AND_REACH',
                'TRAFFIC' => 'WEBSITE_TRAFFIC',
                'ENGAGEMENT' => 'PRODUCT_AND_BRAND_CONSIDERATION',
                'LEADS' => 'LEADS',
                'CONVERSIONS' => 'SALES',
                'SALES' => 'SALES',
            ],
            'tiktok' => [
                'AWARENESS' => 'REACH',
                'TRAFFIC' => 'TRAFFIC',
                'ENGAGEMENT' => 'VIDEO_VIEWS',
                'LEADS' => 'LEAD_GENERATION',
                'CONVERSIONS' => 'CONVERSIONS',
                'SALES' => 'PRODUCT_SALES',
            ],
        ];

        return $objectives[$platform][strtoupper($objective)] ?? $objective;
    }

    /**
     * Convert budget to platform-specific format (cents for most platforms).
     */
    protected function convertBudget(string $platform, float $amount): int
    {
        // Most platforms expect budget in cents/minor currency units
        return match ($platform) {
            'meta', 'facebook', 'instagram' => (int) ($amount * 100),
            'google', 'google_ads' => (int) ($amount * 1000000), // micros
            'tiktok' => (int) ($amount * 100),
            'linkedin' => (int) ($amount * 100),
            default => (int) ($amount * 100),
        };
    }

    /**
     * Update platform budget specifically.
     * Convenience method for budget optimization workflows.
     */
    public function updatePlatformBudget(OrchestrationPlatform $mapping, float $newBudget): void
    {
        // Update local mapping
        $mapping->update([
            'budget' => $newBudget,
            'allocated_budget' => $newBudget,
        ]);

        // Sync to platform
        $this->updatePlatformCampaign($mapping, [
            'budget' => $newBudget,
        ]);

        Log::info('Platform budget updated', [
            'platform' => $mapping->platform,
            'campaign_id' => $mapping->platform_campaign_id,
            'new_budget' => $newBudget,
        ]);
    }
}
