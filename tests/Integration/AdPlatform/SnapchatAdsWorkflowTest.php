<?php

namespace Tests\Integration\AdPlatform;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AdPlatforms\Snapchat\SnapchatAdsPlatform;
use App\Services\Platform\SnapchatAdsService;
use App\Models\Core\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

use PHPUnit\Framework\Attributes\Test;

/**
 * Snapchat Ads Platform Integration Tests
 *
 * Tests complete workflow for Snapchat advertising campaigns
 */
class SnapchatAdsWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_creates_snapchat_ad_campaign_complete_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        // Set RLS context
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->user_id, $org->org_id]);

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'snap_account_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*/campaigns' => Http::response([
                'campaigns' => [
                    [
                        'campaign' => [
                            'id' => 'snap_campaign_123',
                            'name' => 'Summer App Install Campaign',
                            'status' => 'ACTIVE',
                            'objective' => 'APP_INSTALLS',
                            'daily_budget_micro' => 100000000,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);

        $result = $service->createCampaign([
            'name' => 'Summer App Install Campaign',
            'objective' => 'APP_INSTALLS',
            'daily_budget' => 100.00,
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('snap_campaign_123', $result['campaign_id']);
    }

    #[Test]
    public function it_creates_snapchat_ad_set_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->user_id, $org->org_id]);

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'snap_account_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*/adsquads' => Http::response([
                'adsquads' => [
                    [
                        'adsquad' => [
                            'id' => 'snap_squad_456',
                            'name' => 'iOS Users 18-35',
                            'status' => 'ACTIVE',
                            'type' => 'SNAP_ADS',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);

        $result = $service->createAdSet('snap_campaign_123', [
            'name' => 'iOS Users 18-35',
            'type' => 'SNAP_ADS',
            'placement' => 'SNAP_ADS',
            'daily_budget' => 50.00,
            'status' => 'ACTIVE',
            'targeting' => [
                'geos' => [
                    ['country_code' => 'US'],
                ],
                'demographics' => [
                    [
                        'min_age' => 18,
                        'max_age' => 35,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('snap_squad_456', $result['adsquad_id']);
    }

    #[Test]
    public function it_creates_snapchat_video_ad_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->user_id, $org->org_id]);

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'snap_account_123',
                'access_token' => 'test_token',
            ],
        ]);

        // Mock creative creation
        Http::fake([
            'adsapi.snapchat.com/*/creatives' => Http::response([
                'creatives' => [
                    [
                        'creative' => [
                            'id' => 'snap_creative_123',
                            'name' => 'App Install Video',
                            'type' => 'WEB_VIEW',
                        ],
                    ],
                ],
            ], 200),
            'adsapi.snapchat.com/*/ads' => Http::response([
                'ads' => [
                    [
                        'ad' => [
                            'id' => 'snap_ad_789',
                            'name' => 'App Install Video Ad',
                            'status' => 'ACTIVE',
                            'type' => 'SNAP_AD',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);

        // First create creative
        $creativeResult = $service->createCreative([
            'name' => 'App Install Video',
            'type' => 'WEB_VIEW',
            'headline' => 'Download Now!',
            'brand_name' => 'Summer App',
            'top_snap_media_id' => 'media_123',
        ]);

        $this->assertTrue($creativeResult['success']);

        // Then create ad
        $adResult = $service->createAd('snap_squad_456', [
            'name' => 'App Install Video Ad',
            'creative_id' => $creativeResult['creative_id'],
            'type' => 'SNAP_AD',
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($adResult['success']);
        $this->assertEquals('snap_ad_789', $adResult['ad_id']);
    }

    #[Test]
    public function it_fetches_snapchat_campaign_stats_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->user_id, $org->org_id]);

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'snap_account_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*/stats' => Http::response([
                'timeseries_stats' => [
                    [
                        'stats' => [
                            'impressions' => 75000,
                            'swipes' => 3750,
                            'spend' => 125000000, // $125 in micros
                            'conversion_purchases' => 150,
                            'view_completion' => 25000,
                            'screen_time_millis' => 180000000,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->getCampaignMetrics('snap_campaign_123', '2024-01-01', '2024-01-31');

        $this->assertTrue($result['success']);
        $this->assertEquals(75000, $result['metrics']['impressions']);
        $this->assertEquals(3750, $result['metrics']['swipes']);
        $this->assertEquals(150, $result['metrics']['conversions']);
    }

    #[Test]
    public function it_pauses_and_resumes_snapchat_campaign_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->user_id, $org->org_id]);

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'snap_account_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*/campaigns' => Http::sequence()
                ->push([
                    'campaigns' => [
                        ['campaign' => ['id' => 'snap_campaign_123', 'status' => 'PAUSED']],
                    ],
                ], 200)
                ->push([
                    'campaigns' => [
                        ['campaign' => ['id' => 'snap_campaign_123', 'status' => 'ACTIVE']],
                    ],
                ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);

        // Pause campaign
        $pauseResult = $service->updateCampaignStatus('snap_campaign_123', 'PAUSED');
        $this->assertTrue($pauseResult['success']);

        // Resume campaign
        $resumeResult = $service->updateCampaignStatus('snap_campaign_123', 'ACTIVE');
        $this->assertTrue($resumeResult['success']);
    }

    #[Test]
    public function it_updates_snapchat_campaign_budget_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->user_id, $org->org_id]);

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'snap_account_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*/campaigns' => Http::response([
                'campaigns' => [
                    [
                        'campaign' => [
                            'id' => 'snap_campaign_123',
                            'daily_budget_micro' => 200000000, // $200
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->updateCampaign('snap_campaign_123', [
            'daily_budget' => 200.00,
        ]);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_snapchat_api_errors_in_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->user_id, $org->org_id]);

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'snap_account_123',
                'access_token' => 'invalid_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'request_status' => 'ERROR',
                'error' => 'Invalid access token',
            ], 401),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->createCampaign([
            'name' => 'Test Campaign',
            'objective' => 'AWARENESS',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_uses_snapchat_ads_service_for_caching()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'campaigns' => [
                    [
                        'campaign' => [
                            'id' => 'camp_1',
                            'name' => 'Test Campaign',
                            'status' => 'ACTIVE',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsService();

        // First call - should hit API
        $result1 = $service->fetchCampaigns('snap_account_123', 'test_token', 50);

        // Second call - should use cache
        $result2 = $service->fetchCampaigns('snap_account_123', 'test_token', 50);

        $this->assertCount(1, $result1['campaigns']);
        $this->assertEquals($result1, $result2);
    }

    #[Test]
    public function it_creates_audience_segment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$user->user_id, $org->org_id]);

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'snap_account_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*/segments' => Http::response([
                'segments' => [
                    [
                        'segment' => [
                            'id' => 'segment_123',
                            'name' => 'Custom Audience',
                            'source_type' => 'ENGAGEMENT',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->createAudienceSegment([
            'name' => 'Custom Audience',
            'description' => 'Users who engaged with previous campaigns',
            'source_type' => 'ENGAGEMENT',
            'retention_in_days' => 180,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('segment_123', $result['segment_id']);
    }
}
