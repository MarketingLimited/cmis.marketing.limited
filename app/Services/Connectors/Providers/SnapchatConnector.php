<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Connector for Snapchat Marketing API.
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

    public function syncCampaigns(Integration $integration, array $options = []): Collection { return collect(); }
    public function syncPosts(Integration $integration, array $options = []): Collection { return collect(); }
    public function syncComments(Integration $integration, array $options = []): Collection { return collect(); }
    public function syncMessages(Integration $integration, array $options = []): Collection { return collect(); }
    public function getAccountMetrics(Integration $integration): Collection { return collect(); }
    public function publishPost(Integration $integration, ContentItem $item): string { throw new \Exception('Not implemented'); }
    public function schedulePost(Integration $integration, ContentItem $item, Carbon $scheduledTime): string { throw new \Exception('Not implemented'); }
    public function sendMessage(Integration $integration, string $conversationId, string $messageText, array $options = []): array { return ['success' => false]; }
    public function replyToComment(Integration $integration, string $commentId, string $replyText): array { return ['success' => false]; }
    public function hideComment(Integration $integration, string $commentId, bool $hide = true): bool { return false; }
    public function deleteComment(Integration $integration, string $commentId): bool { return false; }
    public function likeComment(Integration $integration, string $commentId): bool { return false; }
    public function createAdCampaign(Integration $integration, array $campaignData): array { return ['success' => false]; }
    public function updateAdCampaign(Integration $integration, string $campaignId, array $updates): array { return ['success' => false]; }
    public function getAdCampaignMetrics(Integration $integration, string $campaignId, array $options = []): Collection { return collect(); }
}
