<?php

namespace App\Jobs;

use App\Models\Core\Integration;
use App\Models\Platform\PlatformConnection;
use App\Models\Social\IntegrationQueueSettings;
use App\Services\Platform\MetaAssetsService;
use App\Services\Profile\ProfileSoftDeleteService;
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
            'threads_account' => ['platform' => 'threads', 'method' => 'getThreadsAccounts'],
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

                // For threads_account: UI sends Instagram ID, so we need to handle two cases:
                // 1. If Threads API works: key by instagram_id (Threads data includes this)
                // 2. If Threads API fails (returns empty): fall back to Instagram accounts data
                if ($assetType === 'threads_account') {
                    if (!empty($assets)) {
                        // Threads API worked - key by instagram_id
                        $assetsById = collect($assets)->keyBy('instagram_id')->toArray();
                    } else {
                        // Threads API failed - fall back to Instagram accounts (same IDs)
                        $instagramAssets = $service->getInstagramAccounts($this->connectionId, $accessToken, false);
                        $assetsById = collect($instagramAssets)->keyBy('id')->toArray();
                        Log::info('SyncMetaIntegrationRecords: Using Instagram data fallback for Threads');
                    }
                } else {
                    $assetsById = collect($assets)->keyBy('id')->toArray();
                }

                foreach ($this->selectedAssets[$assetType] as $assetId) {
                    $assetData = $assetsById[$assetId] ?? null;

                    $accountName = $assetData['name'] ?? $assetData['username'] ?? 'Unknown';
                    $accountUsername = $assetData['username'] ?? null;
                    $avatarUrl = $assetData['profile_picture'] ?? $assetData['picture'] ?? null;

                    // Check if soft-deleted integration exists and restore it
                    $existingIntegration = Integration::withTrashed()
                        ->where('org_id', $this->orgId)
                        ->where('platform', $platform)
                        ->where('account_id', $assetId)
                        ->first();

                    if ($existingIntegration && $existingIntegration->trashed()) {
                        // Restore the profile and its queue settings
                        $existingIntegration->restore();

                        // Restore queue settings if they exist
                        IntegrationQueueSettings::withTrashed()
                            ->where('integration_id', $existingIntegration->integration_id)
                            ->restore();

                        Log::info('Restored soft-deleted profile and related data', [
                            'integration_id' => $existingIntegration->integration_id,
                            'platform' => $platform,
                            'account_id' => $assetId,
                        ]);
                    }

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

        // Process deselected integrations - soft delete or mark inactive
        $profileService = app(ProfileSoftDeleteService::class);
        $deselectedProfiles = Integration::where('org_id', $this->orgId)
            ->whereIn('platform', ['facebook', 'instagram', 'threads'])
            ->where('metadata->connection_id', $connection->connection_id)
            ->whereNotIn('integration_id', $expectedIntegrationIds)
            ->get();

        $softDeletedCount = 0;
        $markedInactiveCount = 0;

        foreach ($deselectedProfiles as $profile) {
            // Check if this asset is used in another connection
            if (!$profileService->isAssetUsedInOtherConnections(
                $this->orgId,
                $profile->platform,
                $profile->account_id,
                $connection->connection_id
            )) {
                // Soft delete if not used elsewhere (observer handles cascade)
                $profile->delete();
                $softDeletedCount++;

                Log::info('Profile soft deleted (asset deselected)', [
                    'integration_id' => $profile->integration_id,
                    'platform' => $profile->platform,
                    'account_id' => $profile->account_id,
                ]);
            } else {
                // Just mark inactive if used in another connection
                $profile->update([
                    'is_active' => false,
                    'status' => 'inactive',
                ]);
                $markedInactiveCount++;

                Log::info('Profile marked inactive (asset in other connection)', [
                    'integration_id' => $profile->integration_id,
                    'platform' => $profile->platform,
                ]);
            }
        }

        Log::info('SyncMetaIntegrationRecords completed', [
            'org_id' => $this->orgId,
            'connection_id' => $this->connectionId,
            'integrations_synced' => count($expectedIntegrationIds),
            'profiles_soft_deleted' => $softDeletedCount,
            'profiles_marked_inactive' => $markedInactiveCount,
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
