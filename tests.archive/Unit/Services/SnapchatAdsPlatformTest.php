<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AdPlatforms\Snapchat\SnapchatAdsPlatform;
use App\Models\Core\Integration;
use Illuminate\Support\Facades\Http;

use PHPUnit\Framework\Attributes\Test;

/**
 * Snapchat Ads Platform Service Unit Tests
 *
 * Tests the SnapchatAdsPlatform service which extends AbstractAdPlatform
 */
class SnapchatAdsPlatformTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_snapchat_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'campaigns' => [
                    [
                        'campaign' => [
                            'id' => 'snap_campaign_123',
                            'name' => 'Test Campaign',
                            'status' => 'ACTIVE',
                            'objective' => 'AWARENESS',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);

        $result = $service->createCampaign([
            'name' => 'Test Campaign',
            'objective' => 'AWARENESS',
            'daily_budget' => 100.00,
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('snap_campaign_123', $result['campaign_id']);
    }

    #[Test]
    public function it_can_fetch_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'campaigns' => [
                    [
                        'campaign' => [
                            'id' => 'camp_1',
                            'name' => 'Campaign 1',
                            'status' => 'ACTIVE',
                        ],
                    ],
                    [
                        'campaign' => [
                            'id' => 'camp_2',
                            'name' => 'Campaign 2',
                            'status' => 'PAUSED',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->fetchCampaigns();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['campaigns']);
    }

    #[Test]
    public function it_can_get_campaign_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'timeseries_stats' => [
                    [
                        'stats' => [
                            'impressions' => 50000,
                            'swipes' => 2500,
                            'spend' => 250500000, // micros
                            'view_completion' => 15000,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->getCampaignMetrics('camp_123', '2024-01-01', '2024-01-31');

        $this->assertTrue($result['success']);
        $this->assertEquals(50000, $result['metrics']['impressions']);
        $this->assertEquals(2500, $result['metrics']['swipes']);
    }

    #[Test]
    public function it_can_create_ad_set()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'adsquads' => [
                    [
                        'adsquad' => [
                            'id' => 'squad_123',
                            'name' => 'Test Ad Squad',
                            'status' => 'ACTIVE',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->createAdSet('camp_123', [
            'name' => 'Test Ad Squad',
            'type' => 'SNAP_ADS',
            'daily_budget' => 50.00,
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('squad_123', $result['adsquad_id']);
    }

    #[Test]
    public function it_can_create_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'ads' => [
                    [
                        'ad' => [
                            'id' => 'ad_123',
                            'name' => 'Test Ad',
                            'status' => 'ACTIVE',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->createAd('squad_123', [
            'name' => 'Test Ad',
            'creative_id' => 'creative_456',
            'type' => 'SNAP_AD',
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('ad_123', $result['ad_id']);
    }

    #[Test]
    public function it_can_create_creative()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'creatives' => [
                    [
                        'creative' => [
                            'id' => 'creative_123',
                            'name' => 'Test Creative',
                            'type' => 'WEB_VIEW',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->createCreative([
            'name' => 'Test Creative',
            'type' => 'WEB_VIEW',
            'headline' => 'Test Headline',
            'brand_name' => 'Test Brand',
            'top_snap_media_id' => 'media_123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('creative_123', $result['creative_id']);
    }

    #[Test]
    public function it_can_update_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'campaigns' => [
                    [
                        'campaign' => [
                            'id' => 'camp_123',
                            'status' => 'PAUSED',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->updateCampaign('camp_123', [
            'status' => 'PAUSED',
        ]);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_api_errors_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'request_status' => 'ERROR',
                'error' => 'Invalid credentials',
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
    public function it_can_refresh_access_token()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'old_token',
                'refresh_token' => 'refresh_token_123',
            ],
        ]);

        Http::fake([
            'accounts.snapchat.com/*' => Http::response([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $result = $service->refreshAccessToken();

        $this->assertTrue($result['success']);
        $this->assertEquals('new_access_token', $result['access_token']);
    }

    #[Test]
    public function it_maps_objectives_correctly()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'snapchat',
            'is_active' => true,
            'metadata' => [
                'ad_account_id' => 'acc_123',
                'access_token' => 'test_token',
            ],
        ]);

        $service = new SnapchatAdsPlatform($integration);
        $objectives = $service->getAvailableObjectives();

        $this->assertIsArray($objectives);
        $this->assertArrayHasKey('AWARENESS', $objectives);
        $this->assertArrayHasKey('APP_INSTALLS', $objectives);
        $this->assertArrayHasKey('DRIVE_TRAFFIC', $objectives);
    }
}
