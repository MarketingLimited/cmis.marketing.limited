<?php

namespace Tests\Integration\AdPlatform;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AdPlatform\GoogleAdsService;
use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdSet;
use App\Models\AdPlatform\Ad;
use Illuminate\Support\Str;

/**
 * Google Ads Platform Integration Tests
 */
class GoogleAdsWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_creates_google_ads_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $this->mockGoogleAPI('success', [
            'results' => [
                [
                    'resourceName' => 'customers/123456789/campaigns/987654321',
                    'campaign' => [
                        'id' => '987654321',
                        'name' => 'Summer Sale Campaign',
                        'status' => 'ENABLED',
                    ],
                ],
            ],
        ]);

        $campaignData = [
            'name' => 'Summer Sale Campaign',
            'advertising_channel_type' => 'SEARCH',
            'status' => 'ENABLED',
            'budget' => [
                'amount_micros' => 100000000, // $100 in micros
                'delivery_method' => 'STANDARD',
            ],
            'network_settings' => [
                'target_google_search' => true,
                'target_search_network' => true,
            ],
        ];

        $service = app(GoogleAdsService::class);
        $result = $service->createCampaign($integration, $campaignData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_campaigns', [
            'org_id' => $org->org_id,
            'platform' => 'google',
            'external_campaign_id' => '987654321',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'action' => 'create_campaign',
        ]);
    }

    /** @test */
    public function it_creates_google_ads_ad_group()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'google',
            'external_campaign_id' => '987654321',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->mockGoogleAPI('success', [
            'results' => [
                [
                    'resourceName' => 'customers/123456789/adGroups/111222333',
                    'adGroup' => [
                        'id' => '111222333',
                        'name' => 'Summer Products',
                        'status' => 'ENABLED',
                    ],
                ],
            ],
        ]);

        $adGroupData = [
            'campaign' => 'customers/123456789/campaigns/987654321',
            'name' => 'Summer Products',
            'status' => 'ENABLED',
            'cpc_bid_micros' => 1000000, // $1 in micros
        ];

        $service = app(GoogleAdsService::class);
        $result = $service->createAdGroup($integration, $adGroupData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_sets', [
            'campaign_id' => $campaign->campaign_id,
            'platform' => 'google',
            'external_ad_set_id' => '111222333',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'action' => 'create_ad_group',
        ]);
    }

    /** @test */
    public function it_creates_google_responsive_search_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'google',
            'external_campaign_id' => '987654321',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $adSet = AdSet::create([
            'ad_set_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'platform' => 'google',
            'external_ad_set_id' => '111222333',
            'name' => 'Summer Products',
            'status' => 'active',
        ]);

        $this->mockGoogleAPI('success', [
            'results' => [
                [
                    'resourceName' => 'customers/123456789/adGroupAds/444555666',
                    'adGroupAd' => [
                        'ad' => [
                            'id' => '444555666',
                            'responsiveSearchAd' => [
                                'headlines' => ['خصم 50% على جميع المنتجات'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $adData = [
            'ad_group' => 'customers/123456789/adGroups/111222333',
            'status' => 'ENABLED',
            'ad' => [
                'responsiveSearchAd' => [
                    'headlines' => [
                        ['text' => 'خصم 50% على جميع المنتجات'],
                        ['text' => 'تسوق الآن - عروض الصيف'],
                        ['text' => 'جودة عالية - أسعار منخفضة'],
                    ],
                    'descriptions' => [
                        ['text' => 'لا تفوت فرصة التوفير الكبيرة'],
                        ['text' => 'توصيل مجاني للطلبات فوق 50 دينار'],
                    ],
                ],
                'finalUrls' => ['https://example.com/summer-sale'],
            ],
        ];

        $service = app(GoogleAdsService::class);
        $result = $service->createAd($integration, $adData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ads', [
            'ad_set_id' => $adSet->ad_set_id,
            'platform' => 'google',
            'external_ad_id' => '444555666',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'action' => 'create_responsive_search_ad',
        ]);
    }

    /** @test */
    public function it_creates_google_display_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $this->mockGoogleAPI('success', [
            'results' => [
                [
                    'resourceName' => 'customers/123456789/adGroupAds/777888999',
                    'adGroupAd' => [
                        'ad' => [
                            'id' => '777888999',
                        ],
                    ],
                ],
            ],
        ]);

        $adData = [
            'ad_group' => 'customers/123456789/adGroups/111222333',
            'status' => 'ENABLED',
            'ad' => [
                'responsiveDisplayAd' => [
                    'marketingImages' => [
                        ['asset' => 'customers/123456789/assets/image_123'],
                    ],
                    'headlines' => [
                        ['text' => 'عروض الصيف الحصرية'],
                    ],
                    'descriptions' => [
                        ['text' => 'خصم يصل إلى 50%'],
                    ],
                    'businessName' => 'Summer Store',
                ],
                'finalUrls' => ['https://example.com/summer-sale'],
            ],
        ];

        $service = app(GoogleAdsService::class);
        $result = $service->createAd($integration, $adData);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'action' => 'create_display_ad',
        ]);
    }

    /** @test */
    public function it_adds_keywords_to_ad_group()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $this->mockGoogleAPI('success', [
            'results' => [
                [
                    'resourceName' => 'customers/123456789/adGroupCriteria/keyword_123',
                ],
            ],
        ]);

        $keywordData = [
            'ad_group' => 'customers/123456789/adGroups/111222333',
            'keywords' => [
                [
                    'text' => 'ملابس صيفية',
                    'match_type' => 'BROAD',
                ],
                [
                    'text' => 'قمصان قطنية',
                    'match_type' => 'PHRASE',
                ],
                [
                    'text' => '[ملابس صيف 2024]',
                    'match_type' => 'EXACT',
                ],
            ],
        ];

        $service = app(GoogleAdsService::class);
        $result = $service->addKeywords($integration, $keywordData);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'action' => 'add_keywords',
        ]);
    }

    /** @test */
    public function it_fetches_google_ads_campaign_performance()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'google',
            'external_campaign_id' => '987654321',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->mockGoogleAPI('success', [
            [
                'campaign' => [
                    'resourceName' => 'customers/123456789/campaigns/987654321',
                    'id' => '987654321',
                ],
                'metrics' => [
                    'impressions' => '100000',
                    'clicks' => '5000',
                    'cost_micros' => '150000000',
                    'conversions' => 250.0,
                    'ctr' => 0.05,
                    'average_cpc' => 30000,
                ],
            ],
        ]);

        $service = app(GoogleAdsService::class);
        $result = $service->getCampaignPerformance($integration, '987654321');

        $this->assertTrue($result['success']);
        $this->assertEquals(100000, $result['data']['impressions']);
        $this->assertEquals(5000, $result['data']['clicks']);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'action' => 'fetch_performance',
        ]);
    }

    /** @test */
    public function it_pauses_google_ads_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'google',
            'external_campaign_id' => '987654321',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->mockGoogleAPI('success', [
            'result' => [
                'resourceName' => 'customers/123456789/campaigns/987654321',
            ],
        ]);

        $service = app(GoogleAdsService::class);
        $result = $service->pauseCampaign($integration, '987654321');

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'action' => 'pause_campaign',
        ]);
    }

    /** @test */
    public function it_updates_campaign_budget()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'google',
            'external_campaign_id' => '987654321',
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
            'status' => 'active',
            'daily_budget' => 100.00,
        ]);

        $this->mockGoogleAPI('success', [
            'result' => [
                'resourceName' => 'customers/123456789/campaignBudgets/budget_123',
            ],
        ]);

        $service = app(GoogleAdsService::class);
        $result = $service->updateCampaignBudget($integration, '987654321', 200.00);

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals(200.00, $campaign->daily_budget);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'action' => 'update_budget',
        ]);
    }

    /** @test */
    public function it_handles_google_ads_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_ads');

        $this->mockGoogleAPI('error');

        $service = app(GoogleAdsService::class);
        $result = $service->createCampaign($integration, [
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation_for_google_campaigns()
    {
        $setup1 = $this->createUserWithOrg();
        $org1 = $setup1['org'];

        $setup2 = $this->createUserWithOrg();
        $org2 = $setup2['org'];

        $integration1 = $this->createTestIntegration($org1->org_id, 'google_ads');
        $integration2 = $this->createTestIntegration($org2->org_id, 'google_ads');

        AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'integration_id' => $integration1->integration_id,
            'platform' => 'google',
            'external_campaign_id' => 'google_campaign_org1',
            'name' => 'Org 1 Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'integration_id' => $integration2->integration_id,
            'platform' => 'google',
            'external_campaign_id' => 'google_campaign_org2',
            'name' => 'Org 2 Campaign',
            'objective' => 'conversions',
            'status' => 'active',
        ]);

        $this->initTransactionContext($org1->org_id, $setup1['user']->user_id);

        $org1Campaigns = AdCampaign::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Campaigns);
        $this->assertEquals('Org 1 Campaign', $org1Campaigns->first()->name);

        $this->logTestResult('passed', [
            'workflow' => 'google_ads',
            'test' => 'org_isolation',
        ]);
    }
}
