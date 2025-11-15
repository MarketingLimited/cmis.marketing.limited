<?php

namespace App\Services\AdPlatforms\Contracts;

use App\Models\Core\Integration;

/**
 * Interface for Ad Platform Services
 *
 * All platform-specific ad services (Meta, Google, TikTok, etc.) must implement this interface
 * to ensure consistency across different advertising platforms.
 *
 * @package App\Services\AdPlatforms\Contracts
 */
interface AdPlatformInterface
{
    /**
     * Initialize the platform service with integration credentials
     *
     * @param Integration $integration The platform integration instance
     * @return void
     */
    public function __construct(Integration $integration);

    /**
     * Create a new ad campaign on the platform
     *
     * @param array $data Campaign data
     * @return array Result with 'success' boolean and campaign data or error
     */
    public function createCampaign(array $data): array;

    /**
     * Update an existing campaign
     *
     * @param string $externalId The campaign ID on the external platform
     * @param array $data Updated campaign data
     * @return array Result with 'success' boolean and updated campaign data or error
     */
    public function updateCampaign(string $externalId, array $data): array;

    /**
     * Get campaign details from the platform
     *
     * @param string $externalId The campaign ID on the external platform
     * @return array Result with 'success' boolean and campaign data or error
     */
    public function getCampaign(string $externalId): array;

    /**
     * Delete/archive a campaign
     *
     * @param string $externalId The campaign ID on the external platform
     * @return array Result with 'success' boolean or error
     */
    public function deleteCampaign(string $externalId): array;

    /**
     * Fetch campaigns from the platform
     *
     * @param array $filters Optional filters (date range, status, etc.)
     * @return array Result with 'success' boolean and campaigns array or error
     */
    public function fetchCampaigns(array $filters = []): array;

    /**
     * Get campaign performance metrics
     *
     * @param string $externalId The campaign ID on the external platform
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Result with 'success' boolean and metrics data or error
     */
    public function getCampaignMetrics(string $externalId, string $startDate, string $endDate): array;

    /**
     * Update campaign status (active, paused, etc.)
     *
     * @param string $externalId The campaign ID on the external platform
     * @param string $status New status
     * @return array Result with 'success' boolean or error
     */
    public function updateCampaignStatus(string $externalId, string $status): array;

    /**
     * Create an ad set within a campaign
     *
     * @param string $campaignExternalId The campaign ID on the external platform
     * @param array $data Ad set data
     * @return array Result with 'success' boolean and ad set data or error
     */
    public function createAdSet(string $campaignExternalId, array $data): array;

    /**
     * Create an ad within an ad set
     *
     * @param string $adSetExternalId The ad set ID on the external platform
     * @param array $data Ad data
     * @return array Result with 'success' boolean and ad data or error
     */
    public function createAd(string $adSetExternalId, array $data): array;

    /**
     * Get available campaign objectives for this platform
     *
     * @return array List of valid objectives
     */
    public function getAvailableObjectives(): array;

    /**
     * Get available placements for this platform
     *
     * @return array List of valid placements
     */
    public function getAvailablePlacements(): array;

    /**
     * Validate campaign data before sending to platform
     *
     * @param array $data Campaign data to validate
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public function validateCampaignData(array $data): array;

    /**
     * Sync account data from the platform
     *
     * @return array Result with 'success' boolean and account data or error
     */
    public function syncAccount(): array;

    /**
     * Test connection to the platform
     *
     * @return array Result with 'success' boolean and connection status
     */
    public function testConnection(): array;

    /**
     * Refresh access token if needed
     *
     * @return array Result with 'success' boolean and new token data or error
     */
    public function refreshAccessToken(): array;
}
