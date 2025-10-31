<?php

namespace App\Services\Social;

use App\Models\Integration;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Response;
use Throwable;
use App\Services\Social\InstagramAccountSyncService;

class InstagramSyncService
{
    public function syncAllActive(): int
    {
        $integrations = Integration::query()
            ->where('platform', 'instagram')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            Log::warning('No active Instagram integrations found for sync.');
            return 0;
        }

        $processed = 0;

        foreach ($integrations as $integration) {
            Log::info('Loaded integration', [
                'integration_id' => $integration->integration_id,
                'org_id' => $integration->org_id,
            ]);

            try {
                if (!empty($integration->org_id)) {
                    DB::statement("SELECT set_config('app.current_org_id', '{$integration->org_id}', true);");
                }

                if (empty($integration->access_token) || empty($integration->account_id)) {
                    Log::warning('Instagram integration missing credentials.', [
                        'integration_id' => $integration->integration_id ?? null,
                        'org_id' => $integration->org_id ?? null,
                        'account_id' => $integration->account_id ?? null,
                    ]);
                    continue;
                }

                $this->syncIntegrationByAccountId($integration);
                $processed++;

            } catch (Throwable $exception) {
                Log::error('Failed to sync Instagram integration.', [
                    'integration_id' => $integration->integration_id ?? null,
                    'org_id' => $integration->org_id ?? null,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    public function syncIntegrationByAccountId(Integration $integration): void
    {
        // sync account data first
        (new InstagramAccountSyncService())->sync($integration);

        $endpoint = sprintf('%s/media', $integration->account_id);
        $fields = [
            'id','caption','media_type','media_url','thumbnail_url','permalink','timestamp','like_count','comments_count'
        ];
        $params = ['fields' => implode(',', $fields), 'limit' => 100];

        $response = $this->get($integration, $endpoint, $params);
        $this->throwIfFailed($response, 'Unable to fetch Instagram media.');

        $mediaItems = $response->json('data', []);

        foreach ($mediaItems as $media) {
            $insights = $this->fetchMediaInsights($integration, $media['id'], $media['media_type'] ?? null);
            $this->storePost($integration, $media, $insights);
        }
    }

    // ... باقي الدوال بدون تغيير ...
}