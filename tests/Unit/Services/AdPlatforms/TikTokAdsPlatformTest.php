<?php

namespace Tests\Unit\Services\AdPlatforms;

use Tests\TestCase;
use App\Services\AdPlatforms\TikTok\TikTokAdsPlatform;
use App\Models\Core\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;

/**
 * Characterization Tests for TikTokAdsPlatform
 *
 * These tests document the current behavior of TikTokAdsPlatform
 * to enable safe refactoring. Total: 28 tests
 */
class TikTokAdsPlatformTest extends TestCase
{
    use RefreshDatabase;

    private TikTokAdsPlatform $service;
    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test integration
        $this->integration = Integration::factory()->create([
            'platform' => 'tiktok',
            'account_id' => '1234567890',
            'access_token' => encrypt('test_access_token'),
            'refresh_token' => encrypt('test_refresh_token'),
            'token_expires_at' => now()->addDays(30),
            'metadata' => [
                'advertiser_id' => '1234567890',
            ],
        ]);

        $this->service = new TikTokAdsPlatform($this->integration);
    }

    // ==========================================
    // CAMPAIGN OPERATIONS (7 tests)
    // ==========================================

    /** @test */
    public function it_creates_campaign_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => ['campaign_id' => '123456789'],
            ], 200)
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign',
            'objective' => 'TRAFFIC',
            'daily_budget' => 100,
            'status' => 'DISABLE',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('123456789', $result['campaign_id']);
    }

    /** @test */
    public function it_handles_campaign_creation_failure()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 40001,
                'message' => 'Invalid parameters',
            ], 400)
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_updates_campaign_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => [],
            ], 200)
        ]);

        $result = $this->service->updateCampaign('123456789', [
            'name' => 'Updated Campaign',
            'budget' => 200,
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_gets_campaign_details()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        ['campaign_id' => '123456789', 'campaign_name' => 'Test Campaign'],
                    ],
                ],
            ], 200)
        ]);

        $result = $this->service->getCampaign('123456789');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    /** @test */
    public function it_deletes_campaign_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => [],
            ], 200)
        ]);

        $result = $this->service->deleteCampaign('123456789');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_fetches_campaigns_with_filters()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        ['campaign_id' => '1'],
                        ['campaign_id' => '2'],
                    ],
                    'page_info' => [
                        'page' => 1,
                        'page_size' => 100,
                        'total_number' => 2,
                    ],
                ],
            ], 200)
        ]);

        $result = $this->service->fetchCampaigns(['status' => 'ENABLE']);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['campaigns']);
        $this->assertArrayHasKey('pagination', $result);
    }

    /** @test */
    public function it_gets_campaign_metrics()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        [
                            'metrics' => [
                                'spend' => 100,
                                'impressions' => 10000,
                                'clicks' => 500,
                                'ctr' => 5.0,
                            ],
                        ],
                    ],
                ],
            ], 200)
        ]);

        $result = $this->service->getCampaignMetrics('123456789', '2024-01-01', '2024-01-31');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertEquals(100, $result['metrics']['spend']);
        $this->assertEquals(10000, $result['metrics']['impressions']);
    }

    // ==========================================
    // AD SET OPERATIONS (2 tests)
    // ==========================================

    /** @test */
    public function it_creates_ad_set_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => ['adgroup_id' => '987654321'],
            ], 200)
        ]);

        $result = $this->service->createAdSet('123456789', [
            'name' => 'Test Ad Group',
            'budget' => 50,
            'placement_type' => 'PLACEMENT_TYPE_AUTOMATIC',
            'targeting' => [
                'locations' => ['6252001'],
                'age_groups' => ['AGE_25_34'],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('987654321', $result['adgroup_id']);
    }

    /** @test */
    public function it_handles_ad_set_creation_failure()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 40001,
                'message' => 'Invalid targeting',
            ], 400)
        ]);

        $result = $this->service->createAdSet('123456789', [
            'name' => 'Test Ad Group',
        ]);

        $this->assertFalse($result['success']);
    }

    // ==========================================
    // AD OPERATIONS (2 tests)
    // ==========================================

    /** @test */
    public function it_creates_ad_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => ['ad_ids' => ['111222333']],
            ], 200)
        ]);

        $result = $this->service->createAd('987654321', [
            'name' => 'Test Ad',
            'video_id' => 'v123456',
            'ad_text' => 'Check out our product!',
            'call_to_action' => 'SHOP_NOW',
            'landing_page_url' => 'https://example.com',
        ]);

        $this->assertTrue($result['success']);
        $this->assertContains('111222333', $result['ad_ids']);
    }

    /** @test */
    public function it_handles_ad_creation_failure()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 40001,
                'message' => 'Invalid creative',
            ], 400)
        ]);

        $result = $this->service->createAd('987654321', [
            'name' => 'Test Ad',
        ]);

        $this->assertFalse($result['success']);
    }

    // ==========================================
    // MEDIA OPERATIONS (4 tests)
    // ==========================================

    /** @test */
    public function it_uploads_video_successfully()
    {
        $tempVideo = tempnam(sys_get_temp_dir(), 'test_video');
        file_put_contents($tempVideo, 'fake video data');

        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => ['video_id' => 'v123456'],
            ], 200)
        ]);

        $result = $this->service->uploadVideo($tempVideo);

        unlink($tempVideo);

        $this->assertTrue($result['success']);
        $this->assertEquals('v123456', $result['video_id']);
    }

    /** @test */
    public function it_handles_video_upload_missing_file()
    {
        $result = $this->service->uploadVideo('/nonexistent/file.mp4');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /** @test */
    public function it_uploads_image_successfully()
    {
        $tempImage = tempnam(sys_get_temp_dir(), 'test_image');
        file_put_contents($tempImage, 'fake image data');

        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => ['image_id' => 'i123456'],
            ], 200)
        ]);

        $result = $this->service->uploadImage($tempImage);

        unlink($tempImage);

        $this->assertTrue($result['success']);
        $this->assertEquals('i123456', $result['image_id']);
    }

    /** @test */
    public function it_handles_image_upload_missing_file()
    {
        $result = $this->service->uploadImage('/nonexistent/file.jpg');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    // ==========================================
    // TARGETING OPERATIONS (1 test)
    // ==========================================

    /** @test */
    public function it_gets_interest_categories()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        ['id' => '1', 'name' => 'Technology'],
                        ['id' => '2', 'name' => 'Fashion'],
                    ],
                ],
            ], 200)
        ]);

        $result = $this->service->getInterestCategories();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['categories']);
    }

    // ==========================================
    // HELPER METHODS (9 tests)
    // ==========================================

    /** @test */
    public function it_gets_available_objectives()
    {
        $objectives = $this->service->getAvailableObjectives();

        $this->assertIsArray($objectives);
        $this->assertArrayHasKey('REACH', $objectives);
        $this->assertArrayHasKey('TRAFFIC', $objectives);
        $this->assertArrayHasKey('CONVERSIONS', $objectives);
    }

    /** @test */
    public function it_gets_available_placements()
    {
        $placements = $this->service->getAvailablePlacements();

        $this->assertIsArray($placements);
        $this->assertArrayHasKey('PLACEMENT_TIKTOK', $placements);
        $this->assertArrayHasKey('PLACEMENT_PANGLE', $placements);
    }

    /** @test */
    public function it_gets_available_optimization_goals()
    {
        $goals = $this->service->getAvailableOptimizationGoals();

        $this->assertIsArray($goals);
        $this->assertArrayHasKey('CLICK', $goals);
        $this->assertArrayHasKey('CONVERSION', $goals);
        $this->assertArrayHasKey('VIDEO_VIEW', $goals);
    }

    /** @test */
    public function it_gets_available_bid_types()
    {
        $bidTypes = $this->service->getAvailableBidTypes();

        $this->assertIsArray($bidTypes);
        $this->assertArrayHasKey('BID_TYPE_NO_BID', $bidTypes);
        $this->assertArrayHasKey('BID_TYPE_CUSTOM', $bidTypes);
    }

    /** @test */
    public function it_gets_available_call_to_actions()
    {
        $ctas = $this->service->getAvailableCallToActions();

        $this->assertIsArray($ctas);
        $this->assertArrayHasKey('LEARN_MORE', $ctas);
        $this->assertArrayHasKey('SHOP_NOW', $ctas);
        $this->assertArrayHasKey('SIGN_UP', $ctas);
    }

    /** @test */
    public function it_syncs_account_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => [
                    'list' => [
                        [
                            'advertiser_id' => '1234567890',
                            'advertiser_name' => 'Test Advertiser',
                            'currency' => 'USD',
                            'timezone' => 'UTC',
                            'status' => 'STATUS_ENABLE',
                            'balance' => 10000,
                        ],
                    ],
                ],
            ], 200)
        ]);

        $result = $this->service->syncAccount();

        $this->assertTrue($result['success']);
        $this->assertEquals('1234567890', $result['account']['id']);
        $this->assertEquals('Test Advertiser', $result['account']['name']);
        $this->assertEquals(100, $result['account']['balance']);
    }

    /** @test */
    public function it_refreshes_access_token_successfully()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'data' => [
                    'access_token' => 'new_access_token',
                    'refresh_token' => 'new_refresh_token',
                    'expires_in' => 86400,
                ],
            ], 200)
        ]);

        $result = $this->service->refreshAccessToken();

        $this->assertTrue($result['success']);
        $this->assertEquals('new_access_token', $result['access_token']);
        $this->assertEquals(86400, $result['expires_in']);
    }

    /** @test */
    public function it_handles_token_refresh_failure()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 40104,
                'message' => 'Invalid refresh token',
            ], 401)
        ]);

        $result = $this->service->refreshAccessToken();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_maps_status_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapStatus');
        $method->setAccessible(true);

        $this->assertEquals('ENABLE', $method->invoke($this->service, 'ACTIVE'));
        $this->assertEquals('DISABLE', $method->invoke($this->service, 'PAUSED'));
        $this->assertEquals('ENABLE', $method->invoke($this->service, 'ENABLED'));
    }

    // ==========================================
    // ERROR HANDLING (3 tests)
    // ==========================================

    /** @test */
    public function it_handles_constructor_missing_advertiser_id()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('advertiser_id not configured');

        $integration = Integration::factory()->create([
            'platform' => 'tiktok',
            'access_token' => encrypt('test_token'),
            'metadata' => [],
        ]);

        new TikTokAdsPlatform($integration);
    }

    /** @test */
    public function it_handles_constructor_missing_access_token()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('integration not authenticated');

        $integration = Integration::factory()->create([
            'platform' => 'tiktok',
            'access_token' => null,
            'metadata' => ['advertiser_id' => '123'],
        ]);

        new TikTokAdsPlatform($integration);
    }

    /** @test */
    public function it_handles_api_rate_limit()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 40103,
                'message' => 'Rate limit exceeded',
            ], 429)
        ]);

        $result = $this->service->createCampaign([
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
