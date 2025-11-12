<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdCampaignService
{
    protected $orgId;

    public function __construct($orgId)
    {
        $this->orgId = $orgId;
    }

    /**
     * Create campaign on Meta (Facebook/Instagram)
     */
    public function createMetaCampaign(array $data): array
    {
        $integration = $this->getIntegration('meta');
        if (!$integration) {
            return ['success' => false, 'error' => 'Meta integration not found'];
        }

        $adAccountId = $integration->settings['ad_account_id'] ?? null;
        if (!$adAccountId) {
            return ['success' => false, 'error' => 'Ad account not configured'];
        }

        try {
            // Create Campaign
            $campaignResponse = Http::post("https://graph.facebook.com/v19.0/act_{$adAccountId}/campaigns", [
                'name' => $data['campaign_name'],
                'objective' => $data['objective'], // OUTCOME_AWARENESS, OUTCOME_ENGAGEMENT, OUTCOME_TRAFFIC, OUTCOME_LEADS, OUTCOME_SALES
                'status' => $data['status'] ?? 'PAUSED',
                'special_ad_categories' => $data['special_ad_categories'] ?? [],
                'access_token' => $integration->access_token,
            ]);

            if ($campaignResponse->failed()) {
                return ['success' => false, 'error' => $campaignResponse->body()];
            }

            $campaign = $campaignResponse->json();
            $campaignId = $campaign['id'];

            // Create Ad Set
            $adSetResponse = Http::post("https://graph.facebook.com/v19.0/act_{$adAccountId}/adsets", [
                'name' => $data['adset_name'] ?? $data['campaign_name'] . ' - Ad Set',
                'campaign_id' => $campaignId,
                'billing_event' => $data['billing_event'] ?? 'IMPRESSIONS',
                'optimization_goal' => $data['optimization_goal'] ?? 'REACH',
                'bid_amount' => $data['bid_amount'] ?? null,
                'daily_budget' => $data['daily_budget'] ?? null,
                'lifetime_budget' => $data['lifetime_budget'] ?? null,
                'start_time' => $data['start_time'] ?? now()->toIso8601String(),
                'end_time' => $data['end_time'] ?? null,
                'targeting' => json_encode($this->buildMetaTargeting($data['targeting'] ?? [])),
                'status' => $data['status'] ?? 'PAUSED',
                'access_token' => $integration->access_token,
            ]);

            if ($adSetResponse->failed()) {
                return ['success' => false, 'error' => $adSetResponse->body()];
            }

            $adSet = $adSetResponse->json();
            $adSetId = $adSet['id'];

            // Create Ad Creative (if provided)
            $adCreativeId = null;
            if (isset($data['creative'])) {
                $creativeResponse = $this->createMetaAdCreative($adAccountId, $data['creative'], $integration);
                if ($creativeResponse['success']) {
                    $adCreativeId = $creativeResponse['creative_id'];
                }
            }

            // Create Ad
            if ($adCreativeId) {
                $adResponse = Http::post("https://graph.facebook.com/v19.0/act_{$adAccountId}/ads", [
                    'name' => $data['ad_name'] ?? $data['campaign_name'] . ' - Ad',
                    'adset_id' => $adSetId,
                    'creative' => ['creative_id' => $adCreativeId],
                    'status' => $data['status'] ?? 'PAUSED',
                    'access_token' => $integration->access_token,
                ]);

                if ($adResponse->failed()) {
                    Log::warning('Failed to create ad: ' . $adResponse->body());
                }
            }

            // Store in database
            $localCampaignId = DB::table('cmis_ads.ad_campaigns')->insertGetId([
                'org_id' => $this->orgId,
                'integration_id' => $integration->integration_id,
                'platform' => 'meta',
                'platform_campaign_id' => $campaignId,
                'campaign_name' => $data['campaign_name'],
                'objective' => $data['objective'],
                'status' => $data['status'] ?? 'PAUSED',
                'daily_budget' => $data['daily_budget'] ?? null,
                'lifetime_budget' => $data['lifetime_budget'] ?? null,
                'start_date' => $data['start_time'] ?? now(),
                'end_date' => $data['end_time'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'campaign_id');

            return [
                'success' => true,
                'campaign_id' => $localCampaignId,
                'platform_campaign_id' => $campaignId,
                'platform_adset_id' => $adSetId,
                'platform_ad_creative_id' => $adCreativeId,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Meta campaign: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create Meta Ad Creative
     */
    protected function createMetaAdCreative($adAccountId, array $creative, $integration): array
    {
        try {
            $creativeData = [
                'name' => $creative['name'] ?? 'Ad Creative',
                'access_token' => $integration->access_token,
            ];

            // Object Story Spec
            $objectStorySpec = [
                'page_id' => $integration->settings['facebook_page_id'] ?? null,
            ];

            if ($creative['type'] === 'image') {
                $objectStorySpec['link_data'] = [
                    'message' => $creative['message'] ?? '',
                    'link' => $creative['link'] ?? '',
                    'image_hash' => $creative['image_hash'] ?? '',
                    'call_to_action' => $creative['call_to_action'] ?? ['type' => 'LEARN_MORE'],
                ];
            } elseif ($creative['type'] === 'video') {
                $objectStorySpec['video_data'] = [
                    'message' => $creative['message'] ?? '',
                    'video_id' => $creative['video_id'] ?? '',
                    'call_to_action' => $creative['call_to_action'] ?? ['type' => 'LEARN_MORE'],
                ];
            } elseif ($creative['type'] === 'carousel') {
                $objectStorySpec['link_data'] = [
                    'message' => $creative['message'] ?? '',
                    'link' => $creative['link'] ?? '',
                    'child_attachments' => $creative['carousel_cards'] ?? [],
                ];
            }

            $creativeData['object_story_spec'] = json_encode($objectStorySpec);

            $response = Http::post("https://graph.facebook.com/v19.0/act_{$adAccountId}/adcreatives", $creativeData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'creative_id' => $response->json()['id'],
                ];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Failed to create Meta ad creative: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build Meta targeting
     */
    protected function buildMetaTargeting(array $targeting): array
    {
        $metaTargeting = [];

        if (isset($targeting['geo_locations'])) {
            $metaTargeting['geo_locations'] = $targeting['geo_locations'];
        } else {
            $metaTargeting['geo_locations'] = ['countries' => ['SA']]; // Default to Saudi Arabia
        }

        if (isset($targeting['age_min'])) {
            $metaTargeting['age_min'] = $targeting['age_min'];
        }

        if (isset($targeting['age_max'])) {
            $metaTargeting['age_max'] = $targeting['age_max'];
        }

        if (isset($targeting['genders'])) {
            $metaTargeting['genders'] = $targeting['genders']; // [1] = male, [2] = female
        }

        if (isset($targeting['interests'])) {
            $metaTargeting['flexible_spec'] = [
                ['interests' => $targeting['interests']]
            ];
        }

        if (isset($targeting['behaviors'])) {
            $metaTargeting['behaviors'] = $targeting['behaviors'];
        }

        if (isset($targeting['custom_audiences'])) {
            $metaTargeting['custom_audiences'] = $targeting['custom_audiences'];
        }

        return $metaTargeting;
    }

    /**
     * Create campaign on Google Ads
     */
    public function createGoogleAdsCampaign(array $data): array
    {
        $integration = $this->getIntegration('google_ads');
        if (!$integration) {
            return ['success' => false, 'error' => 'Google Ads integration not found'];
        }

        // Google Ads API implementation would go here
        // This requires Google Ads API client library

        return ['success' => false, 'error' => 'Google Ads implementation pending'];
    }

    /**
     * Create campaign on TikTok Ads
     */
    public function createTikTokAdsCampaign(array $data): array
    {
        $integration = $this->getIntegration('tiktok');
        if (!$integration) {
            return ['success' => false, 'error' => 'TikTok integration not found'];
        }

        // TikTok Ads API implementation
        return ['success' => false, 'error' => 'TikTok Ads implementation pending'];
    }

    /**
     * Create campaign on Snapchat Ads
     */
    public function createSnapchatAdsCampaign(array $data): array
    {
        $integration = $this->getIntegration('snapchat');
        if (!$integration) {
            return ['success' => false, 'error' => 'Snapchat integration not found'];
        }

        // Snapchat Ads API implementation
        return ['success' => false, 'error' => 'Snapchat Ads implementation pending'];
    }

    /**
     * Get campaigns for organization
     */
    public function getCampaigns(array $filters = []): array
    {
        $query = DB::table('cmis_ads.ad_campaigns')
            ->where('org_id', $this->orgId)
            ->orderBy('created_at', 'desc');

        if (isset($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->toArray();
    }

    /**
     * Update campaign status
     */
    public function updateCampaignStatus(int $campaignId, string $status): bool
    {
        $campaign = DB::table('cmis_ads.ad_campaigns')
            ->where('campaign_id', $campaignId)
            ->where('org_id', $this->orgId)
            ->first();

        if (!$campaign) {
            return false;
        }

        // Update on platform
        $this->updatePlatformCampaignStatus($campaign, $status);

        // Update in database
        return DB::table('cmis_ads.ad_campaigns')
            ->where('campaign_id', $campaignId)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }

    /**
     * Update campaign status on platform
     */
    protected function updatePlatformCampaignStatus($campaign, string $status): void
    {
        $integration = DB::table('cmis_integrations.integrations')
            ->where('integration_id', $campaign->integration_id)
            ->first();

        if (!$integration) {
            return;
        }

        switch ($campaign->platform) {
            case 'meta':
                $this->updateMetaCampaignStatus($campaign->platform_campaign_id, $status, $integration);
                break;
            // Add other platforms here
        }
    }

    /**
     * Update Meta campaign status
     */
    protected function updateMetaCampaignStatus(string $campaignId, string $status, $integration): void
    {
        try {
            Http::post("https://graph.facebook.com/v19.0/{$campaignId}", [
                'status' => $status,
                'access_token' => $integration->access_token,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Meta campaign status: ' . $e->getMessage());
        }
    }

    /**
     * Get integration for platform
     */
    protected function getIntegration(string $platform)
    {
        return DB::table('cmis_integrations.integrations')
            ->where('org_id', $this->orgId)
            ->where('platform', $platform)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get campaign performance metrics
     */
    public function getCampaignMetrics(int $campaignId): array
    {
        $campaign = DB::table('cmis_ads.ad_campaigns')
            ->where('campaign_id', $campaignId)
            ->where('org_id', $this->orgId)
            ->first();

        if (!$campaign) {
            return [];
        }

        return json_decode($campaign->metrics ?? '{}', true);
    }
}
