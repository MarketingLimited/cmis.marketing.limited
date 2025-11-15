<?php

namespace App\Services\AdPlatforms\Twitter;

use App\Services\AdPlatforms\AbstractAdPlatform;

class TwitterAdsPlatform extends AbstractAdPlatform
{
    protected function getConfig(): array { return ['api_version' => 'v11', 'api_base_url' => 'https://ads-api.x.com']; }
    protected function getPlatformName(): string { return 'twitter'; }
    public function createCampaign(array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function updateCampaign(string $externalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getCampaign(string $externalId): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function deleteCampaign(string $externalId): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function fetchCampaigns(array $filters = []): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function updateCampaignStatus(string $externalId, string $status): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function createAdSet(string $campaignExternalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function createAd(string $adSetExternalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getAvailableObjectives(): array { return ['AWARENESS', 'TWEET_ENGAGEMENTS', 'VIDEO_VIEWS', 'FOLLOWERS', 'APP_INSTALLS']; }
    public function getAvailablePlacements(): array { return ['timeline', 'search_results', 'profile']; }
    public function syncAccount(): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function refreshAccessToken(): array { return ['success' => false, 'error' => 'Not implemented']; }
}
