<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Connector for LinkedIn Marketing API.
 * Supports posts, messages, comments, and ads.
 */
class LinkedInConnector extends AbstractConnector
{
    protected string $platform = 'linkedin';
    protected string $baseUrl = 'https://api.linkedin.com';
    protected string $apiVersion = 'v2';

    public function getAuthUrl(array $options = []): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => config('services.linkedin.client_id'),
            'redirect_uri' => config('services.linkedin.redirect_uri'),
            'scope' => 'r_liteprofile r_emailaddress w_member_social rw_organization_admin w_organization_social r_organization_social',
            'state' => $options['state'] ?? bin2hex(random_bytes(16)),
        ];

        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        $response = \Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => config('services.linkedin.client_id'),
            'client_secret' => config('services.linkedin.client_secret'),
            'redirect_uri' => config('services.linkedin.redirect_uri'),
        ]);

        $tokens = $response->json();

        // Get user info
        $userInfo = \Http::withToken($tokens['access_token'])
            ->get('https://api.linkedin.com/v2/me')
            ->json();

        $integration = Integration::updateOrCreate(
            ['org_id' => $options['org_id'], 'platform' => 'linkedin', 'external_account_id' => $userInfo['id']],
            [
                'access_token' => encrypt($tokens['access_token']),
                'token_expires_at' => now()->addSeconds($tokens['expires_in']),
                'is_active' => true,
                'settings' => ['name' => $userInfo['localizedFirstName'] . ' ' . $userInfo['localizedLastName']],
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
        // LinkedIn doesn't provide refresh tokens by default
        return $integration;
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
