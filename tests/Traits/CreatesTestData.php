<?php

namespace Tests\Traits;

use App\Models\Campaign;
use App\Models\Creative\CreativeBrief;
use App\Models\Creative\CreativeAsset;
use App\Models\SocialAccount;
use App\Models\Core\Integration;
use App\Models\AdPlatform\AdCampaign;
use App\Models\Publishing\PublishingQueue;
use App\Models\ScheduledSocialPost;
use Illuminate\Support\Str;

/**
 * Trait for creating test data across CMIS entities.
 */
trait CreatesTestData
{
    /**
     * Create a test campaign.
     *
     * @param string $orgId
     * @param array $attributes
     * @return Campaign
     */
    protected function createTestCampaign(string $orgId, array $attributes = []): Campaign
    {
        return Campaign::create(array_merge([
            'campaign_id' => Str::uuid(),
            'org_id' => $orgId,
            'name' => 'Test Campaign ' . Str::random(8),
            'objective' => 'awareness',
            'status' => 'draft',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'budget' => 1000.00,
            'currency' => 'BHD',
        ], $attributes));
    }

    /**
     * Create a test creative brief.
     *
     * @param string $orgId
     * @param array $attributes
     * @return CreativeBrief
     */
    protected function createTestCreativeBrief(string $orgId, array $attributes = []): CreativeBrief
    {
        return CreativeBrief::create(array_merge([
            'brief_id' => Str::uuid(),
            'org_id' => $orgId,
            'name' => 'Test Brief ' . Str::random(8),
            'brief_data' => [
                'objective' => 'brand_awareness',
                'target_audience' => 'Young professionals',
                'key_message' => 'Test message',
                'tone' => 'professional',
            ],
        ], $attributes));
    }

    /**
     * Create a test creative asset.
     *
     * @param string $orgId
     * @param string|null $campaignId
     * @param array $attributes
     * @return CreativeAsset
     */
    protected function createTestCreativeAsset(
        string $orgId,
        ?string $campaignId = null,
        array $attributes = []
    ): CreativeAsset {
        return CreativeAsset::create(array_merge([
            'asset_id' => Str::uuid(),
            'org_id' => $orgId,
            'campaign_id' => $campaignId,
            'channel_id' => 1,
            'format_id' => 1,
            'variation_tag' => 'v1',
            'status' => 'draft',
            'final_copy' => [
                'headline' => 'Test Headline',
                'body' => 'Test body content',
            ],
        ], $attributes));
    }

    /**
     * Create a test integration.
     *
     * @param string $orgId
     * @param string $platform
     * @param array $attributes
     * @return Integration
     */
    protected function createTestIntegration(
        string $orgId,
        string $platform = 'facebook',
        array $attributes = []
    ): Integration {
        return Integration::create(array_merge([
            'integration_id' => Str::uuid(),
            'org_id' => $orgId,
            'platform' => $platform,
            'account_id' => 'test_account_' . Str::random(8),
            'access_token' => encrypt('test_token_' . Str::random(32)),
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a test social account.
     *
     * @param string $orgId
     * @param string|null $integrationId
     * @param array $attributes
     * @return SocialAccount
     */
    protected function createTestSocialAccount(
        string $orgId,
        ?string $integrationId = null,
        array $attributes = []
    ): SocialAccount {
        if (!$integrationId) {
            $integration = $this->createTestIntegration($orgId, 'facebook');
            $integrationId = $integration->integration_id;
        }

        return SocialAccount::create(array_merge([
            'id' => Str::uuid(),
            'org_id' => $orgId,
            'integration_id' => $integrationId,
            'account_external_id' => 'ext_' . Str::random(12),
            'username' => 'test_user_' . Str::random(6),
            'display_name' => 'Test Account',
            'followers_count' => 1000,
        ], $attributes));
    }

    /**
     * Create a test ad campaign.
     *
     * @param string $orgId
     * @param string|null $integrationId
     * @param array $attributes
     * @return AdCampaign
     */
    protected function createTestAdCampaign(
        string $orgId,
        ?string $integrationId = null,
        array $attributes = []
    ): AdCampaign {
        if (!$integrationId) {
            $integration = $this->createTestIntegration($orgId, 'facebook');
            $integrationId = $integration->integration_id;
        }

        return AdCampaign::create(array_merge([
            'id' => Str::uuid(),
            'org_id' => $orgId,
            'integration_id' => $integrationId,
            'campaign_external_id' => 'ext_campaign_' . Str::random(12),
            'name' => 'Test Ad Campaign ' . Str::random(8),
            'objective' => 'CONVERSIONS',
            'status' => 'ACTIVE',
            'budget' => 500.00,
            'metrics' => [
                'impressions' => 10000,
                'clicks' => 500,
                'spend' => 250.00,
            ],
        ], $attributes));
    }

    /**
     * Create a test publishing queue.
     *
     * @param string $orgId
     * @param string|null $socialAccountId
     * @param array $attributes
     * @return PublishingQueue
     */
    protected function createTestPublishingQueue(
        string $orgId,
        ?string $socialAccountId = null,
        array $attributes = []
    ): PublishingQueue {
        if (!$socialAccountId) {
            $socialAccount = $this->createTestSocialAccount($orgId);
            $socialAccountId = $socialAccount->id;
        }

        return PublishingQueue::create(array_merge([
            'queue_id' => Str::uuid(),
            'org_id' => $orgId,
            'social_account_id' => $socialAccountId,
            'weekdays_enabled' => '1111100', // Mon-Fri
            'time_slots' => [
                ['time' => '09:00', 'enabled' => true],
                ['time' => '12:00', 'enabled' => true],
                ['time' => '17:00', 'enabled' => true],
            ],
            'timezone' => 'Asia/Bahrain',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a test scheduled post.
     *
     * @param string $orgId
     * @param string $userId
     * @param array $attributes
     * @return ScheduledSocialPost
     */
    protected function createTestScheduledPost(
        string $orgId,
        string $userId,
        array $attributes = []
    ): ScheduledSocialPost {
        return ScheduledSocialPost::create(array_merge([
            'id' => Str::uuid(),
            'org_id' => $orgId,
            'user_id' => $userId,
            'platforms' => ['facebook', 'instagram'],
            'content' => 'Test post content ' . Str::random(12),
            'media' => [],
            'scheduled_at' => now()->addHours(2),
            'status' => 'scheduled',
        ], $attributes));
    }

    /**
     * Create a complete test campaign ecosystem.
     *
     * @param string $orgId
     * @return array
     */
    protected function createCampaignEcosystem(string $orgId): array
    {
        $campaign = $this->createTestCampaign($orgId);
        $brief = $this->createTestCreativeBrief($orgId);
        $asset = $this->createTestCreativeAsset($orgId, $campaign->campaign_id);
        $integration = $this->createTestIntegration($orgId);
        $socialAccount = $this->createTestSocialAccount($orgId, $integration->integration_id);
        $publishingQueue = $this->createTestPublishingQueue($orgId, $socialAccount->id);

        return [
            'campaign' => $campaign,
            'brief' => $brief,
            'asset' => $asset,
            'integration' => $integration,
            'social_account' => $socialAccount,
            'publishing_queue' => $publishingQueue,
        ];
    }

    /**
     * Create test content/content item for publishing tests.
     *
     * @param string $campaignId
     * @param array $attributes
     * @return \App\Models\Creative\ContentItem
     */
    protected function createTestContent(string $campaignId, array $attributes = [])
    {
        $contentItem = \App\Models\Creative\ContentItem::create(array_merge([
            'content_id' => Str::uuid(),
            'campaign_id' => $campaignId,
            'org_id' => \App\Models\Campaign::find($campaignId)->org_id ?? Str::uuid(),
            'item_type' => $attributes['content_type'] ?? 'post',
            'platform' => $attributes['platform'] ?? 'facebook',
            'title' => 'Test Content ' . Str::random(8),
            'body' => 'Test content body for ' . ($attributes['platform'] ?? 'facebook'),
            'status' => 'approved',
            'metadata' => [
                'content_type' => $attributes['content_type'] ?? 'post',
                'platform' => $attributes['platform'] ?? 'facebook',
            ],
        ], $attributes));

        return $contentItem;
    }

    /**
     * Create test social post
     */
    protected function createTestSocialPost(string $orgId, array $attributes = [])
    {
        return \App\Models\SocialPost::create(array_merge([
            'post_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $orgId,
            'platform' => 'facebook',
            'content' => 'Test post content',
            'status' => 'published',
            'published_at' => now(),
        ], $attributes));
    }

    /**
     * Create test content plan
     */
    protected function createTestContentPlan(string $campaignId, array $attributes = [])
    {
        return \App\Models\ContentPlan::create(array_merge([
            'plan_id' => \Illuminate\Support\Str::uuid(),
            'campaign_id' => $campaignId,
            'org_id' => \App\Models\Campaign::find($campaignId)->org_id ?? \Illuminate\Support\Str::uuid(),
            'name' => 'Test Content Plan',
            'status' => 'active',
        ], $attributes));
    }

    /**
     * Create test ad account
     */
    protected function createTestAdAccount(string $orgId, array $attributes = [])
    {
        return \App\Models\AdAccount::create(array_merge([
            'account_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $orgId,
            'platform' => 'facebook',
            'external_account_id' => 'act_' . uniqid(),
            'account_name' => 'Test Ad Account',
            'status' => 'active',
        ], $attributes));
    }
}
