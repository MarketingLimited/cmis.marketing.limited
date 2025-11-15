<?php

namespace App\Jobs\Sync;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Core\Integration;
use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdMetric;
use App\Models\Social\SocialPost;

class SyncPlatformData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Integration $integration,
        public string $dataType // 'campaigns', 'metrics', 'posts'
    ) {
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting sync", [
            'integration_id' => $this->integration->integration_id,
            'provider' => $this->integration->provider,
            'data_type' => $this->dataType,
        ]);

        // Update status to syncing
        $this->integration->updateSyncStatus('syncing');

        try {
            // Get platform service
            $platform = $this->getPlatformService();

            // Sync based on data type
            $result = match ($this->dataType) {
                'campaigns' => $this->syncCampaigns($platform),
                'metrics' => $this->syncMetrics($platform),
                'posts' => $this->syncPosts($platform),
                'all' => $this->syncAll($platform),
                default => throw new \Exception("Unknown data type: {$this->dataType}"),
            };

            // Update sync status to success
            $this->integration->updateSyncStatus('success');

            Log::info("Sync completed", [
                'integration_id' => $this->integration->integration_id,
                'data_type' => $this->dataType,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error("Sync failed", [
                'integration_id' => $this->integration->integration_id,
                'data_type' => $this->dataType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update sync status to failed
            $this->integration->updateSyncStatus('failed', [
                'error' => $e->getMessage(),
                'data_type' => $this->dataType,
                'timestamp' => now()->toIso8601String(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Get platform service instance
     */
    private function getPlatformService()
    {
        // In production, use a factory pattern
        return app("App\\Services\\AdPlatforms\\" . ucfirst($this->integration->provider) . "\\{$this->integration->provider}AdsPlatform");
    }

    /**
     * Sync campaigns from platform
     */
    private function syncCampaigns($platform): array
    {
        $campaigns = $platform->getCampaigns();
        $synced = 0;
        $created = 0;
        $updated = 0;

        foreach ($campaigns as $campaignData) {
            $existing = AdCampaign::where('campaign_external_id', $campaignData['id'])->first();

            if ($existing) {
                $existing->update([
                    'name' => $campaignData['name'] ?? $existing->name,
                    'status' => $campaignData['status'] ?? $existing->status,
                    'budget' => $campaignData['budget'] ?? $existing->budget,
                    'objective' => $campaignData['objective'] ?? $existing->objective,
                    'synced_at' => now(),
                ]);
                $updated++;
            } else {
                AdCampaign::create([
                    'integration_id' => $this->integration->integration_id,
                    'org_id' => $this->integration->org_id,
                    'campaign_external_id' => $campaignData['id'],
                    'name' => $campaignData['name'],
                    'status' => $campaignData['status'] ?? 'active',
                    'budget' => $campaignData['budget'] ?? 0,
                    'objective' => $campaignData['objective'] ?? null,
                    'synced_at' => now(),
                ]);
                $created++;
            }
            $synced++;
        }

        return [
            'total' => $synced,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Sync metrics from platform
     */
    private function syncMetrics($platform): array
    {
        $campaigns = AdCampaign::where('integration_id', $this->integration->integration_id)->get();
        $synced = 0;

        foreach ($campaigns as $campaign) {
            try {
                $metrics = $platform->getCampaignMetrics($campaign->campaign_external_id);

                AdMetric::updateOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'date' => $metrics['date'] ?? today(),
                    ],
                    [
                        'impressions' => $metrics['impressions'] ?? 0,
                        'clicks' => $metrics['clicks'] ?? 0,
                        'spend' => $metrics['spend'] ?? 0,
                        'conversions' => $metrics['conversions'] ?? 0,
                        'ctr' => $metrics['ctr'] ?? 0,
                        'cpc' => $metrics['cpc'] ?? 0,
                        'cpm' => $metrics['cpm'] ?? 0,
                        'synced_at' => now(),
                    ]
                );
                $synced++;
            } catch (\Exception $e) {
                Log::warning("Failed to sync metrics for campaign", [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['synced' => $synced];
    }

    /**
     * Sync social posts from platform
     */
    private function syncPosts($platform): array
    {
        // Implementation depends on platform
        return ['synced' => 0];
    }

    /**
     * Sync all data types
     */
    private function syncAll($platform): array
    {
        return [
            'campaigns' => $this->syncCampaigns($platform),
            'metrics' => $this->syncMetrics($platform),
            'posts' => $this->syncPosts($platform),
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Sync job failed permanently", [
            'integration_id' => $this->integration->integration_id,
            'data_type' => $this->dataType,
            'error' => $exception->getMessage(),
        ]);

        // Notify organization users
        // TODO: Implement notification
    }
}
