<?php

namespace App\Services\AdPlatforms\LinkedIn;

use App\Services\AdPlatforms\AbstractAdPlatform;

class LinkedInAdsPlatform extends AbstractAdPlatform
{
    protected function getConfig(): array { return ['api_version' => 'v2', 'api_base_url' => 'https://api.linkedin.com']; }
    protected function getPlatformName(): string { return 'linkedin'; }
    public function createCampaign(array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function updateCampaign(string $externalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getCampaign(string $externalId): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function deleteCampaign(string $externalId): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function fetchCampaigns(array $filters = []): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function updateCampaignStatus(string $externalId, string $status): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function createAdSet(string $campaignExternalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function createAd(string $adSetExternalId, array $data): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function getAvailableObjectives(): array { return ['BRAND_AWARENESS', 'WEBSITE_VISITS', 'LEAD_GENERATION', 'JOB_APPLICANTS']; }
    public function getAvailablePlacements(): array { return ['linkedin_feed', 'linkedin_right_rail']; }
    public function syncAccount(): array { return ['success' => false, 'error' => 'Not implemented']; }
    public function refreshAccessToken(): array { return ['success' => false, 'error' => 'Not implemented']; }
}
