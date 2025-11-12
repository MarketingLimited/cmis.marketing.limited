<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Connector for WordPress REST API.
 */
class WordPressConnector extends AbstractConnector
{
    protected string $platform = 'wordpress';
    protected string $baseUrl = '';
    protected string $apiVersion = 'wp/v2';

    public function getAuthUrl(array $options = []): string
    {
        // WordPress uses Application Passwords or OAuth
        return '';
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        $integration = Integration::create([
            'org_id' => $options['org_id'],
            'platform' => 'wordpress',
            'is_active' => true,
            'settings' => [
                'site_url' => $options['site_url'],
                'username' => $options['username'],
                'application_password' => $options['application_password'],
            ],
        ]);

        return $integration;
    }

    public function disconnect(Integration $integration): bool
    {
        $integration->update(['is_active' => false]);
        return true;
    }

    public function refreshToken(Integration $integration): Integration
    {
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
