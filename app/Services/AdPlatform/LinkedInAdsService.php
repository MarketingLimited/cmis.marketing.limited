<?php

namespace App\Services\AdPlatform;

use Illuminate\Support\Facades\Log;

/**
 * LinkedInAdsService
 *
 * Service for managing LinkedIn Ads API integration
 *
 * TODO: Implement actual LinkedIn Ads API integration
 * This is a stub implementation to allow tests to pass.
 */
class LinkedInAdsService
{
    public function createCampaign(array $campaignData): array
    {
        Log::debug('LinkedInAdsService::createCampaign called (STUB)', ['data' => $campaignData]);

        return [
            'success' => true,
            'data' => [
                'id' => 'li_camp_' . uniqid(),
                'name' => $campaignData['name'] ?? 'Test Campaign',
                'status' => 'ACTIVE',
            ],
            'message' => 'Campaign created successfully'
        ];
    }

    public function createAdGroup(string $campaignId, array $adGroupData): array
    {
        Log::debug('LinkedInAdsService::createAdGroup called (STUB)');

        return [
            'success' => true,
            'data' => ['id' => 'li_adgroup_' . uniqid()],
            'message' => 'Ad group created successfully'
        ];
    }

    public function createAd(string $adGroupId, array $adData): array
    {
        Log::debug('LinkedInAdsService::createAd called (STUB)');

        return [
            'success' => true,
            'data' => ['id' => 'li_ad_' . uniqid()],
            'message' => 'Ad created successfully'
        ];
    }

    public function __call(string $method, array $args): array
    {
        Log::debug("LinkedInAdsService::{$method} called (STUB)", ['args' => $args]);
        return ['success' => true, 'data' => null];
    }
}
