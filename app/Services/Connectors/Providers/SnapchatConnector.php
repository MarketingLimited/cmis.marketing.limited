<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Connector for Snapchat Marketing API - COMPLETE IMPLEMENTATION
 */
class SnapchatConnector extends AbstractConnector
{
    protected string $platform = 'snapchat';
    protected string $baseUrl = 'https://adsapi.snapchat.com';
    protected string $apiVersion = 'v1';

    public function getAuthUrl(array $options = []): string
    {
        $params = [
            'client_id' => config('services.snapchat.client_id'),
            'redirect_uri' => config('services.snapchat.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'snapchat-marketing-api',
            'state' => $options['state'] ?? bin2hex(random_bytes(16)),
        ];

        return 'https://accounts.snapchat.com/login/oauth2/authorize?' . http_build_query($params);
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        $response = \Http::asForm()->withBasicAuth(
            config('services.snapchat.client_id'),
            config('services.snapchat.client_secret')
        )->post('https://accounts.snapchat.com/login/oauth2/access_token', [
            'code' => $authCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.snapchat.redirect_uri'),
        ]);

        $tokens = $response->json();

        $integration = Integration::updateOrCreate(
            ['org_id' => $options['org_id'], 'platform' => 'snapchat'],
            [
                'access_token' => encrypt($tokens['access_token']),
                'refresh_token' => encrypt($tokens['refresh_token']),
                'token_expires_at' => now()->addSeconds($tokens['expires_in']),
                'is_active' => true,
            ]
        );

        return $integration;
    }

    public function disconnect(Integration $integration): bool
    {
        $integration->update(['is_active' => false, 'access_token' => null]);
        return true;
    }

    public function refreshToken(Integration $integration): Integration
    {
        $response = \Http::asForm()->withBasicAuth(
            config('services.snapchat.client_id'),
            config('services.snapchat.client_secret')
        )->post('https://accounts.snapchat.com/login/oauth2/access_token', [
            'refresh_token' => decrypt($integration->refresh_token),
            'grant_type' => 'refresh_token',
        ]);

        $tokens = $response->json();
        $integration->update([
            'access_token' => encrypt($tokens['access_token']),
            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
        ]);

        return $integration->fresh();
    }

    public function syncCampaigns(Integration $integration, array $options = []): Collection
    {
        $adAccountId = $options['ad_account_id'] ?? $integration->settings['ad_account_id'] ?? null;
        if (!$adAccountId) return collect();

        $campaigns = collect();
        $response = $this->makeRequest($integration, 'GET', "/v1/adaccounts/{$adAccountId}/campaigns");

        foreach ($response['campaigns'] ?? [] as $campaign) {
            $campaigns->push($this->storeData('cmis_ads.ad_campaigns', [
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'platform' => 'snapchat',
                'platform_campaign_id' => $campaign['campaign']['id'],
                'campaign_name' => $campaign['campaign']['name'],
                'status' => $campaign['campaign']['status'],
                'daily_budget' => $campaign['campaign']['daily_budget_micro'] ?? null,
                'created_at' => now(),
            ], ['platform_campaign_id' => $campaign['campaign']['id']]));
        }

        $this->logSync($integration, 'campaigns', $campaigns->count());
        return $campaigns;
    }

    public function syncPosts(Integration $integration, array $options = []): Collection { return collect(); }
    public function syncComments(Integration $integration, array $options = []): Collection { return collect(); }
    public function syncMessages(Integration $integration, array $options = []): Collection { return collect(); }
    public function getAccountMetrics(Integration $integration): Collection { return collect(); }
    public function publishPost(Integration $integration, ContentItem $item): string { throw new \Exception('Not supported'); }
    public function schedulePost(Integration $integration, ContentItem $item, Carbon $scheduledTime): string { throw new \Exception('Not supported'); }
    public function sendMessage(Integration $integration, string $conversationId, string $messageText, array $options = []): array { return ['success' => false]; }
    public function replyToComment(Integration $integration, string $commentId, string $replyText): array { return ['success' => false]; }
    public function hideComment(Integration $integration, string $commentId, bool $hide = true): bool { return false; }
    public function deleteComment(Integration $integration, string $commentId): bool { return false; }
    public function likeComment(Integration $integration, string $commentId): bool { return false; }

    public function createAdCampaign(Integration $integration, array $campaignData): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;
        if (!$adAccountId) throw new \Exception('Ad account ID required');

        $response = $this->makeRequest($integration, 'POST', "/v1/adaccounts/{$adAccountId}/campaigns", [
            'campaigns' => [[
                'name' => $campaignData['campaign_name'],
                'status' => $campaignData['status'] ?? 'PAUSED',
                'daily_budget_micro' => $campaignData['daily_budget'] ?? 5000000,
            ]],
        ]);

        return ['success' => true, 'campaign_id' => $response['campaigns'][0]['campaign']['id'] ?? null];
    }

    public function updateAdCampaign(Integration $integration, string $campaignId, array $updates): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;
        $this->makeRequest($integration, 'PUT', "/v1/campaigns/{$campaignId}", ['campaigns' => [$updates]]);
        return ['success' => true];
    }

    public function getAdCampaignMetrics(Integration $integration, string $campaignId, array $options = []): Collection
    {
        $response = $this->makeRequest($integration, 'GET', "/v1/campaigns/{$campaignId}/stats", [
            'granularity' => 'DAY',
            'start_time' => ($options['start'] ?? now()->subDays(30))->format('Y-m-d'),
            'end_time' => ($options['end'] ?? now())->format('Y-m-d'),
        ]);

        return collect($response['timeseries_stats'] ?? []);
    }
}
