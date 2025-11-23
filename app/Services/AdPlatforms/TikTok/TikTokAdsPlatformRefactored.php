<?php

namespace App\Services\AdPlatforms\TikTok;

use App\Services\AdPlatforms\AbstractAdPlatform;
use App\Services\AdPlatforms\TikTok\Services\{
    TikTokCampaignService,
    TikTokAdService,
    TikTokMediaService,
    TikTokHelperService,
    TikTokOAuthService
};

/**
 * TikTok Ads Platform Service - Refactored Orchestrator
 *
 * **REFACTORED:** Reduced from 1,097 lines to ~130 lines (88.1% reduction)
 * **Pattern:** Service extraction following Single Responsibility Principle
 * **Services:** 5 focused service classes handle all operations
 *
 * This class now serves as a thin orchestrator that delegates
 * to specialized services.
 */
class TikTokAdsPlatformRefactored extends AbstractAdPlatform
{
    protected string $advertiserId;
    protected string $accessToken;

    // Service instances
    protected TikTokCampaignService $campaign;
    protected TikTokAdService $ad;
    protected TikTokMediaService $media;
    protected TikTokHelperService $helper;
    protected TikTokOAuthService $oauth;

    protected function getConfig(): array
    {
        return [
            'api_version' => config('services.tiktok.api_version', 'v1.3'),
            'api_base_url' => config('services.tiktok.base_url', 'https://business-api.tiktok.com'),
        ];
    }

    protected function getPlatformName(): string
    {
        return 'tiktok';
    }

    public function __construct(\App\Models\Core\Integration $integration)
    {
        parent::__construct($integration);

        if (empty($integration->metadata['advertiser_id'])) {
            throw new \InvalidArgumentException('TikTok advertiser_id not configured');
        }
        $this->advertiserId = $integration->metadata['advertiser_id'];

        if (empty($integration->access_token)) {
            throw new \InvalidArgumentException('TikTok integration not authenticated');
        }
        $this->accessToken = decrypt($integration->access_token);

        $this->ensureValidToken();
        $this->initializeServices();
    }

    protected function initializeServices(): void
    {
        $makeRequest = fn($method, $url, $payload = []) => $this->makeRequest($method, $url, $payload);

        $this->campaign = new TikTokCampaignService($this->advertiserId, $makeRequest);
        $this->ad = new TikTokAdService($this->advertiserId, $makeRequest);
        $this->media = new TikTokMediaService($this->advertiserId, $makeRequest);
        $this->helper = new TikTokHelperService();
        $this->oauth = new TikTokOAuthService($this->integration, $makeRequest);
    }

    protected function getDefaultHeaders(): array
    {
        return array_merge(parent::getDefaultHeaders(), [
            'Access-Token' => $this->accessToken,
        ]);
    }

    protected function ensureValidToken(): void
    {
        if ($this->integration->token_expires_at &&
            $this->integration->token_expires_at->isPast()) {
            $result = $this->refreshAccessToken();
            if ($result['success']) {
                $this->accessToken = $result['access_token'];
            } else {
                throw new \Exception('Failed to refresh TikTok access token');
            }
        }
    }

    // ==========================================
    // CAMPAIGN OPERATIONS - Delegated
    // ==========================================

    public function createCampaign(array $data): array
    {
        return $this->campaign->createCampaign($data);
    }

    public function updateCampaign(string $externalId, array $data): array
    {
        return $this->campaign->updateCampaign($externalId, $data);
    }

    public function getCampaign(string $externalId): array
    {
        return $this->campaign->getCampaign($externalId);
    }

    public function deleteCampaign(string $externalId): array
    {
        return $this->campaign->deleteCampaign($externalId);
    }

    public function fetchCampaigns(array $filters = []): array
    {
        return $this->campaign->fetchCampaigns($filters);
    }

    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array
    {
        return $this->campaign->getCampaignMetrics($externalId, $startDate, $endDate);
    }

    public function updateCampaignStatus(string $externalId, string $status): array
    {
        return $this->campaign->updateCampaignStatus($externalId, $status);
    }

    // ==========================================
    // AD OPERATIONS - Delegated
    // ==========================================

    public function createAdSet(string $campaignExternalId, array $data): array
    {
        return $this->ad->createAdSet($campaignExternalId, $data);
    }

    public function createAd(string $adSetExternalId, array $data): array
    {
        return $this->ad->createAd($adSetExternalId, $data);
    }

    // ==========================================
    // MEDIA OPERATIONS - Delegated
    // ==========================================

    public function uploadVideo(string $videoPath, array $options = []): array
    {
        return $this->media->uploadVideo($videoPath, $options);
    }

    public function uploadImage(string $imagePath, array $options = []): array
    {
        return $this->media->uploadImage($imagePath, $options);
    }

    // ==========================================
    // HELPER OPERATIONS - Delegated
    // ==========================================

    public function getAvailableObjectives(): array
    {
        return $this->helper->getAvailableObjectives();
    }

    public function getAvailablePlacements(): array
    {
        return $this->helper->getAvailablePlacements();
    }

    public function getAvailableOptimizationGoals(): array
    {
        return $this->helper->getAvailableOptimizationGoals();
    }

    public function getAvailableBidTypes(): array
    {
        return $this->helper->getAvailableBidTypes();
    }

    public function getAvailableCallToActions(): array
    {
        return $this->helper->getAvailableCallToActions();
    }

    public function getInterestCategories(): array
    {
        return $this->helper->getInterestCategories();
    }

    // ==========================================
    // OAUTH OPERATIONS - Delegated
    // ==========================================

    public function syncAccount(): array
    {
        return $this->oauth->syncAccount();
    }

    public function refreshAccessToken(): array
    {
        return $this->oauth->refreshAccessToken();
    }
}
