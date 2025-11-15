<?php

namespace App\Services\AdPlatforms\Snapchat;

use App\Services\AdPlatforms\AbstractAdPlatform;

class SnapchatAdsPlatform extends AbstractAdPlatform
{
    protected function getConfig(): array { return ['api_version' => 'v1', 'api_base_url' => 'https://adsapi.snapchat.com']; }
    protected function getPlatformName(): string { return 'snapchat'; }
    public function createCampaign(array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function updateCampaign(string $externalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getCampaign(string $externalId): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function deleteCampaign(string $externalId): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function fetchCampaigns(array $filters = []): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function updateCampaignStatus(string $externalId, string $status): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function createAdSet(string $campaignExternalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function createAd(string $adSetExternalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getAvailableObjectives(): array { return ['AWARENESS', 'APP_INSTALLS', 'DRIVE_TRAFFIC', 'VIDEO_VIEWS', 'LEAD_GENERATION']; }
    public function getAvailablePlacements(): array { return ['snap_ads', 'story_ads', 'collection_ads']; }
    public function syncAccount(): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function refreshAccessToken(): array { return ['success' => false, 'error' => 'Not implemented']; }
}
