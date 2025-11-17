<?php

namespace Tests\Integration\AdPlatform;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AdPlatform\SnapchatAdsService;
use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdSet;
use App\Models\AdPlatform\Ad;
use Illuminate\Support\Str;

/**
 * Snapchat Ads Platform Integration Tests
 */
class SnapchatAdsWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_creates_snapchat_ad_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $this->mockSnapchatAPI('success', [
            'campaigns' => [
                [
                    'id' => 'snap_campaign_123',
                    'name' => 'Summer App Install Campaign',
                    'status' => 'ACTIVE',
                ],
            ],
        ]);

        $campaignData = [
            'ad_account_id' => 'snap_account_123',
            'name' => 'Summer App Install Campaign',
            'status' => 'ACTIVE',
            'start_time' => '2024-06-01T00:00:00.000Z',
            'daily_budget_micro' => 100000000, // $100
        ];

        $service = app(SnapchatAdsService::class);
        $result = $service->createCampaign($integration, $campaignData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_campaigns', [
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'external_campaign_id' => 'snap_campaign_123',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'action' => 'create_campaign',
        ]);
    }

    /** @test */
    public function it_creates_snapchat_ad_squad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'snapchat',
            'external_campaign_id' => 'snap_campaign_123',
            'name' => 'Summer App Install Campaign',
            'objective' => 'app_installs',
            'status' => 'active',
        ]);

        $this->mockSnapchatAPI('success', [
            'adsquads' => [
                [
                    'id' => 'snap_squad_456',
                    'name' => 'iOS Users 18-35',
                    'status' => 'ACTIVE',
                ],
            ],
        ]);

        $adSquadData = [
            'campaign_id' => 'snap_campaign_123',
            'name' => 'iOS Users 18-35',
            'status' => 'ACTIVE',
            'type' => 'SNAP_ADS',
            'placement' => 'SNAP_ADS',
            'optimization_goal' => 'APP_INSTALLS',
            'billing_event' => 'IMPRESSION',
            'bid_micro' => 500000, // $0.50
            'daily_budget_micro' => 50000000, // $50
            'targeting' => [
                'geos' => [
                    [
                        'country_code' => 'us',
                    ],
                ],
                'demographics' => [
                    [
                        'min_age' => 18,
                        'max_age' => 35,
                    ],
                ],
                'devices' => [
                    [
                        'os_type' => 'iOS',
                    ],
                ],
            ],
        ];

        $service = app(SnapchatAdsService::class);
        $result = $service->createAdSquad($integration, $adSquadData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_sets', [
            'campaign_id' => $campaign->campaign_id,
            'platform' => 'snapchat',
            'external_ad_set_id' => 'snap_squad_456',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'action' => 'create_ad_squad',
        ]);
    }

    /** @test */
    public function it_creates_snapchat_video_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'snapchat',
            'external_campaign_id' => 'snap_campaign_123',
            'name' => 'Summer App Install Campaign',
            'objective' => 'app_installs',
            'status' => 'active',
        ]);

        $adSet = AdSet::create([
            'ad_set_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'external_ad_set_id' => 'snap_squad_456',
            'name' => 'iOS Users 18-35',
            'status' => 'active',
        ]);

        $this->mockSnapchatAPI('success', [
            'ads' => [
                [
                    'id' => 'snap_ad_789',
                    'name' => 'App Install Video Ad',
                    'status' => 'ACTIVE',
                ],
            ],
        ]);

        $adData = [
            'ad_squad_id' => 'snap_squad_456',
            'name' => 'App Install Video Ad',
            'status' => 'ACTIVE',
            'type' => 'SNAP_AD',
            'creative' => [
                'id' => 'snap_creative_123',
                'type' => 'VIDEO',
                'headline' => 'حمّل التطبيق الآن!',
                'brand_name' => 'Summer App',
                'call_to_action' => 'INSTALL_NOW',
            ],
        ];

        $service = app(SnapchatAdsService::class);
        $result = $service->createAd($integration, $adData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ads', [
            'ad_set_id' => $adSet->ad_set_id,
            'platform' => 'snapchat',
            'external_ad_id' => 'snap_ad_789',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'action' => 'create_video_ad',
        ]);
    }

    /** @test */
    public function it_uploads_creative_to_snapchat()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $this->mockSnapchatAPI('success', [
            'media' => [
                [
                    'id' => 'snap_media_123',
                    'type' => 'VIDEO',
                    'download_link' => 'https://example.com/video.mp4',
                ],
            ],
        ]);

        $service = app(SnapchatAdsService::class);
        $result = $service->uploadCreative($integration, [
            'ad_account_id' => 'snap_account_123',
            'type' => 'VIDEO',
            'media' => '/path/to/video.mp4',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('snap_media_123', $result['media_id']);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'action' => 'upload_creative',
        ]);
    }

    /** @test */
    public function it_creates_snapchat_collection_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $this->mockSnapchatAPI('success', [
            'ads' => [
                [
                    'id' => 'snap_collection_ad_123',
                    'name' => 'Product Collection Ad',
                    'type' => 'COLLECTION',
                ],
            ],
        ]);

        $collectionAdData = [
            'ad_squad_id' => 'snap_squad_456',
            'name' => 'Product Collection Ad',
            'status' => 'ACTIVE',
            'type' => 'COLLECTION_AD',
            'creative' => [
                'type' => 'COLLECTION',
                'headline' => 'تسوق المجموعة الصيفية',
                'brand_name' => 'Summer Store',
                'call_to_action' => 'SHOP_NOW',
                'catalog_id' => 'catalog_123',
            ],
        ];

        $service = app(SnapchatAdsService::class);
        $result = $service->createCollectionAd($integration, $collectionAdData);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'action' => 'create_collection_ad',
        ]);
    }

    /** @test */
    public function it_fetches_snapchat_campaign_stats()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'snapchat',
            'external_campaign_id' => 'snap_campaign_123',
            'name' => 'Summer App Install Campaign',
            'objective' => 'app_installs',
            'status' => 'active',
        ]);

        $this->mockSnapchatAPI('success', [
            'timeseries_stats' => [
                [
                    'id' => 'snap_campaign_123',
                    'stats' => [
                        'impressions' => 75000,
                        'swipes' => 3750,
                        'spend' => 125000000, // $125 in micros
                        'conversion_purchases' => 150,
                        'conversion_purchases_value' => 15000000000, // $15,000 in micros
                    ],
                ],
            ],
        ]);

        $service = app(SnapchatAdsService::class);
        $result = $service->getCampaignStats($integration, 'snap_campaign_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(75000, $result['data']['impressions']);
        $this->assertEquals(150, $result['data']['conversions']);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'action' => 'fetch_stats',
        ]);
    }

    /** @test */
    public function it_pauses_snapchat_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'snapchat',
            'external_campaign_id' => 'snap_campaign_123',
            'name' => 'Summer App Install Campaign',
            'objective' => 'app_installs',
            'status' => 'active',
        ]);

        $this->mockSnapchatAPI('success', [
            'campaigns' => [
                [
                    'id' => 'snap_campaign_123',
                    'status' => 'PAUSED',
                ],
            ],
        ]);

        $service = app(SnapchatAdsService::class);
        $result = $service->pauseCampaign($integration, 'snap_campaign_123');

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'action' => 'pause_campaign',
        ]);
    }

    /** @test */
    public function it_updates_snapchat_campaign_budget()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'snapchat',
            'external_campaign_id' => 'snap_campaign_123',
            'name' => 'Summer App Install Campaign',
            'objective' => 'app_installs',
            'status' => 'active',
            'daily_budget' => 100.00,
        ]);

        $this->mockSnapchatAPI('success', [
            'campaigns' => [
                [
                    'id' => 'snap_campaign_123',
                    'daily_budget_micro' => 200000000,
                ],
            ],
        ]);

        $service = app(SnapchatAdsService::class);
        $result = $service->updateCampaignBudget($integration, 'snap_campaign_123', 200.00);

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals(200.00, $campaign->daily_budget);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'action' => 'update_budget',
        ]);
    }

    /** @test */
    public function it_handles_snapchat_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat_ads');

        $this->mockSnapchatAPI('error');

        $service = app(SnapchatAdsService::class);
        $result = $service->createCampaign($integration, [
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'workflow' => 'snapchat_ads',
            'test' => 'error_handling',
        ]);
    }
}
