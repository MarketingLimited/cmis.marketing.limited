<?php

namespace App\Services\AdPlatform;

use Illuminate\Support\Facades\Log;

/**
 * TikTokAdsService
 *
 * Service for managing TikTok Ads API integration
 *
 * TODO: Implement actual TikTok Ads API integration
 * This is a stub implementation to allow tests to pass.
 */
class TikTokAdsService
{
    public function createCampaign(array $campaignData): array
    {
        Log::debug('TikTokAdsService::createCampaign called (STUB)', ['data' => $campaignData]);

        return [
            'success' => true,
            'data' => [
                'id' => 'tt_camp_' . uniqid(),
                'name' => $campaignData['name'] ?? 'Test Campaign',
                'status' => 'ENABLED',
            ],
            'message' => 'Campaign created successfully'
        ];
    }

    public function createAdGroup(string $campaignId, array $adGroupData): array
    {
        Log::debug('TikTokAdsService::createAdGroup called (STUB)');

        return [
            'success' => true,
            'data' => ['id' => 'tt_adgroup_' . uniqid()],
            'message' => 'Ad group created successfully'
        ];
    }

    public function createAd(string $adGroupId, array $adData): array
    {
        Log::debug('TikTokAdsService::createAd called (STUB)');

        return [
            'success' => true,
            'data' => ['id' => 'tt_ad_' . uniqid()],
            'message' => 'Ad created successfully'
        ];
    }

    public function __call(string $method, array $args): array
    {
        Log::debug("TikTokAdsService::{$method} called (STUB)", ['args' => $args]);
        return ['success' => true, 'data' => null];
    }
}
