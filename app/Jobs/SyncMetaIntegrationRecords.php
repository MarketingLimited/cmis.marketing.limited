<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Platform\PlatformConnection;
use App\Services\Platform\MetaAssetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMetaIntegrationRecords implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $orgId,
        public string $connectionId,
        public array $selectedAssets
    ) {}

    /**
     * Execute the job.
     *
     * Uses cached data from MetaAssetsService instead of making
     * synchronous API calls to Meta, significantly improving performance.
     */
    public function handle(MetaAssetsService $service): void
    {
        $connection = PlatformConnection::find($this->connectionId);
        if (!$connection) {
            Log::warning('SyncMetaIntegrationRecords: Connection not found', [
                'connection_id' => $this->connectionId,
            ]);
            return;
        }

        $accessToken = $connection->access_token;
        $expectedIntegrationIds = [];

        // Asset type mapping - only sync types that create Integration records
        $assetTypeMapping = [
            'page' => ['platform' => 'facebook', 'method' => 'getPages'],
            'instagram_account' => ['platform' => 'instagram', 'method' => 'getInstagramAccounts'],
        ];

        foreach ($assetTypeMapping as $assetType => $config) {
            if (empty($this->selectedAssets[$assetType])) {
                continue;
            }

            $platform = $config['platform'];
            $method = $config['method'];

            try {
                // Use CACHED data from MetaAssetsService (not re-fetch from API)
                $assets = $service->$method($this->connectionId, $accessToken, false);
                $assetsById = collect($assets)->keyBy('id')->toArray();

                foreach ($this->selectedAssets[$assetType] as $assetId) {
                    $assetData = $assetsById[$assetId] ?? null;

                    $accountName = $assetData['name'] ?? $assetData['username'] ?? 'Unknown';
                    $accountUsername = $assetData['username'] ?? null;
                    $avatarUrl = $assetData['profile_picture'] ?? $assetData['picture'] ?? null;

                    $integration = Integration::updateOrCreate(
                        [
                            'org_id' => $this->orgId,
                            'platform' => $platform,
                            'account_id' => $assetId,
                        ],
                        [
                            'account_name' => $accountName,
                            'username' => $accountUsername,
                            'avatar_url' => $avatarUrl,
                            'status' => 'active',
                            'is_active' => true,
                            'access_token' => $accessToken,
                            'metadata' => array_merge($assetData ?? [], [
                                'connection_id' => $connection->connection_id,
                                'synced_at' => now()->toIso8601String(),
                            ]),
                        ]
                    );

                    $expectedIntegrationIds[] = $integration->integration_id;
                }
            } catch (\Exception $e) {
                Log::error("SyncMetaIntegrationRecords: Failed to sync {$platform} assets", [
                    'error' => $e->getMessage(),
                    'org_id' => $this->orgId,
                    'connection_id' => $this->connectionId,
                ]);
            }
        }

        // Deactivate old integrations that are no longer selected
        if (!empty($expectedIntegrationIds)) {
            Integration::where('org_id', $this->orgId)
                ->whereIn('platform', ['facebook', 'instagram'])
                ->where('metadata->connection_id', $connection->connection_id)
                ->whereNotIn('integration_id', $expectedIntegrationIds)
                ->update([
                    'is_active' => false,
                    'status' => 'inactive',
                ]);
        }

        Log::info('SyncMetaIntegrationRecords completed', [
            'org_id' => $this->orgId,
            'connection_id' => $this->connectionId,
            'integrations_synced' => count($expectedIntegrationIds),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncMetaIntegrationRecords job failed', [
            'org_id' => $this->orgId,
            'connection_id' => $this->connectionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
