<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Connector for Google Merchant Center.
 */
class GoogleMerchantConnector extends AbstractConnector
{
    protected string $platform = 'google_merchant';
    protected string $baseUrl = 'https://www.googleapis.com/content';
    protected string $apiVersion = 'v2.1';

    public function getAuthUrl(array $options = []): string
    {
        return (new \App\Services\Connectors\Providers\GoogleConnector())->getAuthUrl($options);
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        $googleIntegration = (new \App\Services\Connectors\Providers\GoogleConnector())->connect($authCode, $options);
        $googleIntegration->update(['platform' => 'google_merchant']);
        return $googleIntegration;
    }

    public function disconnect(Integration $integration): bool
    {
        $integration->update(['is_active' => false]);
        return true;
    }

    public function refreshToken(Integration $integration): Integration
    {
        return (new \App\Services\Connectors\Providers\GoogleConnector())->refreshToken($integration);
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
