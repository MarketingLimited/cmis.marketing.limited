<?php

namespace Tests\Integration\AdPlatform;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AdPlatform\TwitterAdsService;
use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdSet;
use App\Models\AdPlatform\Ad;
use Illuminate\Support\Str;

/**
 * Twitter/X Ads Platform Integration Tests
 */
class TwitterAdsWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_creates_twitter_ads_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'twitter_campaign_123',
                'name' => 'Summer Awareness Campaign',
                'funding_instrument_id' => 'funding_123',
                'entity_status' => 'ACTIVE',
                'objective' => 'AWARENESS',
            ],
        ]);

        $campaignData = [
            'account_id' => 'twitter_account_123',
            'name' => 'Summer Awareness Campaign',
            'funding_instrument_id' => 'funding_123',
            'objective' => 'AWARENESS',
            'daily_budget_amount_local_micro' => 100000000, // $100
            'standard_delivery' => true,
        ];

        $service = app(TwitterAdsService::class);
        $result = $service->createCampaign($integration, $campaignData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_campaigns', [
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'external_campaign_id' => 'twitter_campaign_123',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'create_campaign',
        ]);
    }

    /** @test */
    public function it_creates_twitter_line_item()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'external_campaign_id' => 'twitter_campaign_123',
            'name' => 'Summer Awareness Campaign',
            'objective' => 'awareness',
            'status' => 'active',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'twitter_lineitem_456',
                'name' => 'Mobile Users 18-35',
                'campaign_id' => 'twitter_campaign_123',
                'entity_status' => 'ACTIVE',
            ],
        ]);

        $lineItemData = [
            'account_id' => 'twitter_account_123',
            'campaign_id' => 'twitter_campaign_123',
            'name' => 'Mobile Users 18-35',
            'placements' => ['ALL_ON_TWITTER'],
            'objective' => 'AWARENESS',
            'product_type' => 'PROMOTED_TWEETS',
            'bid_amount_local_micro' => 500000, // $0.50
            'total_budget_amount_local_micro' => 50000000, // $50
        ];

        $service = app(TwitterAdsService::class);
        $result = $service->createLineItem($integration, $lineItemData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_sets', [
            'campaign_id' => $campaign->campaign_id,
            'platform' => 'twitter',
            'external_ad_set_id' => 'twitter_lineitem_456',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'create_line_item',
        ]);
    }

    /** @test */
    public function it_creates_twitter_promoted_tweet()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'external_campaign_id' => 'twitter_campaign_123',
            'name' => 'Summer Awareness Campaign',
            'objective' => 'awareness',
            'status' => 'active',
        ]);

        $adSet = AdSet::create([
            'ad_set_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'external_ad_set_id' => 'twitter_lineitem_456',
            'name' => 'Mobile Users 18-35',
            'status' => 'active',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'twitter_tweet_789',
                'text' => 'خصومات الصيف حتى 50%! تسوق الآن 🌞',
            ],
        ]);

        $tweetData = [
            'text' => 'خصومات الصيف حتى 50%! تسوق الآن 🌞',
            'media' => [
                'media_ids' => ['media_123'],
            ],
        ];

        $service = app(TwitterAdsService::class);
        $result = $service->createPromotedTweet($integration, $tweetData, 'twitter_lineitem_456');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ads', [
            'ad_set_id' => $adSet->ad_set_id,
            'platform' => 'twitter',
            'external_ad_id' => 'twitter_tweet_789',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'create_promoted_tweet',
        ]);
    }

    /** @test */
    public function it_creates_twitter_tailored_audience()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'audience_123',
                'name' => 'Website Visitors',
                'audience_type' => 'WEB',
            ],
        ]);

        $audienceData = [
            'account_id' => 'twitter_account_123',
            'name' => 'Website Visitors',
            'audience_type' => 'WEB',
            'list_type' => 'WEB',
        ];

        $service = app(TwitterAdsService::class);
        $result = $service->createTailoredAudience($integration, $audienceData);

        $this->assertTrue($result['success']);
        $this->assertEquals('audience_123', $result['audience_id']);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'create_tailored_audience',
        ]);
    }

    /** @test */
    public function it_uploads_media_to_twitter()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $this->mockTwitterAPI('success', [
            'media_id' => 'media_123',
            'media_id_string' => 'media_123',
        ]);

        $service = app(TwitterAdsService::class);
        $result = $service->uploadMedia($integration, '/path/to/image.jpg');

        $this->assertTrue($result['success']);
        $this->assertEquals('media_123', $result['media_id']);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'upload_media',
        ]);
    }

    /** @test */
    public function it_fetches_twitter_campaign_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'external_campaign_id' => 'twitter_campaign_123',
            'name' => 'Summer Awareness Campaign',
            'objective' => 'awareness',
            'status' => 'active',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                [
                    'id' => 'twitter_campaign_123',
                    'id_data' => [
                        [
                            'metrics' => [
                                'impressions' => ['100000'],
                                'engagements' => ['5000'],
                                'billed_charge_local_micro' => ['125000000'],
                                'clicks' => ['2500'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $service = app(TwitterAdsService::class);
        $result = $service->getCampaignAnalytics($integration, 'twitter_campaign_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(100000, $result['data']['impressions']);
        $this->assertEquals(5000, $result['data']['engagements']);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'fetch_analytics',
        ]);
    }

    /** @test */
    public function it_creates_twitter_website_card()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'card_123',
                'card_type' => 'WEBSITE',
            ],
        ]);

        $cardData = [
            'account_id' => 'twitter_account_123',
            'name' => 'Summer Sale Card',
            'website_title' => 'خصومات الصيف 2024',
            'website_url' => 'https://example.com/summer-sale',
            'image_media_id' => 'media_123',
        ];

        $service = app(TwitterAdsService::class);
        $result = $service->createWebsiteCard($integration, $cardData);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'create_website_card',
        ]);
    }

    /** @test */
    public function it_pauses_twitter_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'external_campaign_id' => 'twitter_campaign_123',
            'name' => 'Summer Awareness Campaign',
            'objective' => 'awareness',
            'status' => 'active',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'twitter_campaign_123',
                'entity_status' => 'PAUSED',
            ],
        ]);

        $service = app(TwitterAdsService::class);
        $result = $service->pauseCampaign($integration, 'twitter_campaign_123');

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'pause_campaign',
        ]);
    }

    /** @test */
    public function it_updates_twitter_campaign_budget()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'external_campaign_id' => 'twitter_campaign_123',
            'name' => 'Summer Awareness Campaign',
            'objective' => 'awareness',
            'status' => 'active',
            'daily_budget' => 100.00,
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'twitter_campaign_123',
                'daily_budget_amount_local_micro' => '200000000',
            ],
        ]);

        $service = app(TwitterAdsService::class);
        $result = $service->updateCampaignBudget($integration, 'twitter_campaign_123', 200.00);

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals(200.00, $campaign->daily_budget);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'action' => 'update_budget',
        ]);
    }

    /** @test */
    public function it_handles_twitter_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter_ads');

        $this->mockTwitterAPI('error');

        $service = app(TwitterAdsService::class);
        $result = $service->createCampaign($integration, [
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'workflow' => 'twitter_ads',
            'test' => 'error_handling',
        ]);
    }
}
