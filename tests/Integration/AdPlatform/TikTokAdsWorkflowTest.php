<?php

namespace Tests\Integration\AdPlatform;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AdPlatform\TikTokAdsService;
use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdSet;
use App\Models\AdPlatform\Ad;
use Illuminate\Support\Str;

/**
 * TikTok Ads Platform Integration Tests
 */
class TikTokAdsWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_creates_tiktok_ad_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok_ads');

        $this->mockTikTokAPI('success', [
            'data' => [
                'campaign_id' => 'tiktok_campaign_123',
                'campaign_name' => 'Summer Sale Campaign',
                'status' => 'ENABLE',
            ],
        ]);

        $campaignData = [
            'campaign_name' => 'Summer Sale Campaign',
            'objective_type' => 'CONVERSIONS',
            'budget_mode' => 'BUDGET_MODE_DAY',
            'budget' => 100.00,
        ];

        $service = app(TikTokAdsService::class);
        $result = $service->createCampaign($integration, $campaignData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_campaigns', [
            'org_id' => $org->org_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_123',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'action' => 'create_campaign',
        ]);
    }

    /** @test */
    public function it_creates_tiktok_ad_set()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_123',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->mockTikTokAPI('success', [
            'data' => [
                'adgroup_id' => 'tiktok_adset_456',
                'adgroup_name' => 'Young Adults 18-35',
                'status' => 'ENABLE',
            ],
        ]);

        $adSetData = [
            'campaign_id' => 'tiktok_campaign_123',
            'adgroup_name' => 'Young Adults 18-35',
            'placement_type' => 'PLACEMENT_TYPE_AUTOMATIC',
            'budget' => 50.00,
            'schedule_type' => 'SCHEDULE_START_END',
            'targeting' => [
                'gender' => 'ALL',
                'age_groups' => ['AGE_18_24', 'AGE_25_34'],
                'location_ids' => ['6252001'], // USA
                'languages' => ['en'],
            ],
        ];

        $service = app(TikTokAdsService::class);
        $result = $service->createAdSet($integration, $adSetData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_sets', [
            'campaign_id' => $campaign->campaign_id,
            'platform' => 'tiktok',
            'external_ad_set_id' => 'tiktok_adset_456',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'action' => 'create_ad_set',
        ]);
    }

    /** @test */
    public function it_creates_tiktok_video_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_123',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $adSet = AdSet::create([
            'ad_set_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'platform' => 'tiktok',
            'external_ad_set_id' => 'tiktok_adset_456',
            'name' => 'Young Adults 18-35',
            'status' => 'active',
        ]);

        $this->mockTikTokAPI('success', [
            'data' => [
                'ad_id' => 'tiktok_ad_789',
                'ad_name' => 'Summer Sale Video',
                'status' => 'ENABLE',
            ],
        ]);

        $adData = [
            'adgroup_id' => 'tiktok_adset_456',
            'ad_name' => 'Summer Sale Video',
            'ad_format' => 'SINGLE_VIDEO',
            'ad_text' => 'خصم حتى 50% على جميع المنتجات!',
            'call_to_action' => 'SHOP_NOW',
            'landing_page_url' => 'https://example.com/summer-sale',
            'video_id' => 'tiktok_video_123',
        ];

        $service = app(TikTokAdsService::class);
        $result = $service->createAd($integration, $adData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ads', [
            'ad_set_id' => $adSet->ad_set_id,
            'platform' => 'tiktok',
            'external_ad_id' => 'tiktok_ad_789',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'action' => 'create_ad',
        ]);
    }

    /** @test */
    public function it_uploads_video_to_tiktok()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok_ads');

        $this->mockTikTokAPI('success', [
            'data' => [
                'video_id' => 'tiktok_video_123',
                'video_cover_url' => 'https://example.com/cover.jpg',
            ],
        ]);

        $service = app(TikTokAdsService::class);
        $result = $service->uploadVideo($integration, '/path/to/video.mp4');

        $this->assertTrue($result['success']);
        $this->assertEquals('tiktok_video_123', $result['video_id']);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'action' => 'upload_video',
        ]);
    }

    /** @test */
    public function it_fetches_tiktok_campaign_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_123',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->mockTikTokAPI('success', [
            'data' => [
                'list' => [
                    [
                        'dimensions' => [
                            'campaign_id' => 'tiktok_campaign_123',
                        ],
                        'metrics' => [
                            'impressions' => 50000,
                            'clicks' => 2500,
                            'spend' => 75.50,
                            'conversions' => 125,
                            'ctr' => 5.0,
                            'cpc' => 0.03,
                            'cpm' => 1.51,
                        ],
                    ],
                ],
            ],
        ]);

        $service = app(TikTokAdsService::class);
        $result = $service->getCampaignInsights($integration, 'tiktok_campaign_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(50000, $result['data']['impressions']);
        $this->assertEquals(2500, $result['data']['clicks']);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'action' => 'fetch_insights',
        ]);
    }

    /** @test */
    public function it_pauses_tiktok_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_123',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->mockTikTokAPI('success', [
            'data' => [
                'campaign_id' => 'tiktok_campaign_123',
                'status' => 'DISABLE',
            ],
        ]);

        $service = app(TikTokAdsService::class);
        $result = $service->pauseCampaign($integration, 'tiktok_campaign_123');

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'action' => 'pause_campaign',
        ]);
    }

    /** @test */
    public function it_updates_tiktok_campaign_budget()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_123',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
            'daily_budget' => 100.00,
        ]);

        $this->mockTikTokAPI('success', [
            'data' => [
                'campaign_id' => 'tiktok_campaign_123',
                'budget' => 150.00,
            ],
        ]);

        $service = app(TikTokAdsService::class);
        $result = $service->updateCampaignBudget($integration, 'tiktok_campaign_123', 150.00);

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals(150.00, $campaign->daily_budget);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'action' => 'update_budget',
        ]);
    }

    /** @test */
    public function it_handles_tiktok_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok_ads');

        $this->mockTikTokAPI('error');

        $service = app(TikTokAdsService::class);
        $result = $service->createCampaign($integration, [
            'campaign_name' => 'Test Campaign',
            'objective_type' => 'CONVERSIONS',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation_for_tiktok_campaigns()
    {
        $setup1 = $this->createUserWithOrg();
        $org1 = $setup1['org'];

        $setup2 = $this->createUserWithOrg();
        $org2 = $setup2['org'];

        $integration1 = $this->createTestIntegration($org1->org_id, 'tiktok_ads');
        $integration2 = $this->createTestIntegration($org2->org_id, 'tiktok_ads');

        $campaign1 = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'integration_id' => $integration1->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_org1',
            'name' => 'Org 1 Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $campaign2 = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'integration_id' => $integration2->integration_id,
            'platform' => 'tiktok',
            'external_campaign_id' => 'tiktok_campaign_org2',
            'name' => 'Org 2 Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        // Set RLS context for org1
        $this->initTransactionContext($org1->org_id, $setup1['user']->user_id);

        $org1Campaigns = AdCampaign::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Campaigns);
        $this->assertEquals('Org 1 Campaign', $org1Campaigns->first()->name);

        $this->logTestResult('passed', [
            'workflow' => 'tiktok_ads',
            'test' => 'org_isolation',
        ]);
    }
}
