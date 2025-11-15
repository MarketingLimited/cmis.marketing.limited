<?php

namespace App\Services\AdPlatforms\TikTok;

use App\Services\AdPlatforms\AbstractAdPlatform;

/**
 * TikTok Ads Platform Service
 *
 * TODO: Implement TikTok Marketing API integration
 */
class TikTokAdsPlatform extends AbstractAdPlatform
{
    protected function getConfig(): array
    {
        return [
            'api_version' => 'v1.3',
            'api_base_url' => 'https://business-api.tiktok.com',
        ];
    }

    protected function getPlatformName(): string
    {
        return 'tiktok';
    }

    // Placeholder implementations
    public function createCampaign(array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function updateCampaign(string $externalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getCampaign(string $externalId): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function deleteCampaign(string $externalId): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function fetchCampaigns(array $filters = []): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function updateCampaignStatus(string $externalId, string $status): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function createAdSet(string $campaignExternalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function createAd(string $adSetExternalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getAvailableObjectives(): array { return ['REACH', 'TRAFFIC', 'APP_INSTALL', 'VIDEO_VIEWS', 'CONVERSIONS']; }
    public function getAvailablePlacements(): array { return ['tiktok', 'pangle', 'global_app_bundle']; }
    public function syncAccount(): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function refreshAccessToken(): array { return ['success' => false, 'error' => 'Not implemented']; }
}
