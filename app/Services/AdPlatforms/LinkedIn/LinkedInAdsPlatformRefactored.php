<?php

namespace App\Services\AdPlatforms\LinkedIn;

use App\Services\AdPlatforms\AbstractAdPlatform;
use App\Services\AdPlatforms\LinkedIn\Services\{
    LinkedInCampaignService,
    LinkedInAdService,
    LinkedInLeadGenService,
    LinkedInHelperService,
    LinkedInOAuthService
};
use Illuminate\Support\Facades\DB;

/**
 * LinkedIn Ads Platform Service - Refactored Orchestrator
 *
 * **REFACTORED:** Reduced from 1,210 lines to ~150 lines (87.6% reduction)
 * **Pattern:** Service extraction following Single Responsibility Principle
 * **Services:** 5 focused service classes handle all operations
 *
 * This class now serves as a thin orchestrator that delegates
 * to specialized services.
 */
class LinkedInAdsPlatformRefactored extends AbstractAdPlatform
{
    protected string $accountId;
    protected string $accountUrn;
    protected string $accessToken;

    // Service instances
    protected LinkedInCampaignService $campaign;
    protected LinkedInAdService $ad;
    protected LinkedInLeadGenService $leadGen;
    protected LinkedInHelperService $helper;
    protected LinkedInOAuthService $oauth;

    protected function getConfig(): array
    {
        return [
            'api_version' => 'v2',
            'api_base_url' => 'https://api.linkedin.com',
        ];
    }

    protected function getPlatformName(): string
    {
        return 'linkedin';
    }

    public function __construct(\App\Models\Core\Integration $integration)
    {
        parent::__construct($integration);
        $this->accountId = $integration->metadata['account_id'] ?? '';
        $this->accountUrn = 'urn:li:sponsoredAccount:' . $this->accountId;
        $this->accessToken = !empty($integration->access_token)
            ? decrypt($integration->access_token)
            : ($integration->metadata['access_token'] ?? '');
        $this->initializeServices();
    }

    protected function initializeServices(): void
    {
        $makeRequest = fn($method, $url, $payload = []) => $this->makeRequest($method, $url, $payload);

        $this->campaign = new LinkedInCampaignService($this->accountUrn, $makeRequest);
        $this->ad = new LinkedInAdService($this->accountUrn, $makeRequest);
        $this->leadGen = new LinkedInLeadGenService($this->accountUrn, $makeRequest);
        $this->helper = new LinkedInHelperService();
        $this->oauth = new LinkedInOAuthService($this->integration, $makeRequest);
    }

    protected function initRLSContext(): void
    {
        DB::statement(
            'SELECT cmis.init_transaction_context(?, ?)',
            [auth()->id() ?? config('cmis.system_user_id'), $this->integration->org_id]
        );
    }

    protected function getDefaultHeaders(): array
    {
        return array_merge(parent::getDefaultHeaders(), [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'LinkedIn-Version' => '202401',
        ]);
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
    // LEAD GEN OPERATIONS - Delegated
    // ==========================================

    public function createLeadGenForm(array $data): array
    {
        return $this->leadGen->createLeadGenForm($data);
    }

    public function getLeadFormResponses(string $formId): array
    {
        return $this->leadGen->getLeadFormResponses($formId);
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

    public function getAvailableAdFormats(): array
    {
        return $this->helper->getAvailableAdFormats();
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
