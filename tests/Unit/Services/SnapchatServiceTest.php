<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\SnapchatService;

/**
 * Snapchat Service Unit Tests
 */
class SnapchatServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected SnapchatService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SnapchatService::class);
    }

    /** @test */
    public function it_can_create_snap_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('success', [
            'ad' => [
                'id' => 'snap_ad_123',
                'status' => 'ACTIVE',
            ],
        ]);

        $result = $this->service->createAd($integration, [
            'ad_account_id' => 'acc_123',
            'name' => 'إعلان سناب شات',
            'creative_id' => 'creative_456',
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('snap_ad_123', $result['ad_id']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'method' => 'createAd',
        ]);
    }

    /** @test */
    public function it_can_create_story_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('success', [
            'creative' => [
                'id' => 'story_creative_789',
            ],
        ]);

        $result = $this->service->createStoryAd($integration, [
            'ad_account_id' => 'acc_123',
            'name' => 'قصة إعلانية',
            'media_id' => 'media_123',
            'headline' => 'اكتشف منتجاتنا الجديدة',
            'brand_name' => 'علامتنا التجارية',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'method' => 'createStoryAd',
        ]);
    }

    /** @test */
    public function it_can_upload_media()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('success', [
            'media' => [
                'id' => 'media_upload_123',
                'download_link' => 'https://storage.snapchat.com/media.jpg',
            ],
        ]);

        $result = $this->service->uploadMedia($integration, [
            'ad_account_id' => 'acc_123',
            'file_url' => 'https://example.com/image.jpg',
            'media_type' => 'IMAGE',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('media_upload_123', $result['media_id']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'method' => 'uploadMedia',
        ]);
    }

    /** @test */
    public function it_can_get_ad_statistics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('success', [
            'stats' => [
                'impressions' => 50000,
                'swipes' => 2500,
                'spend' => 250.50,
                'video_views' => 15000,
            ],
        ]);

        $result = $this->service->getAdStatistics($integration, 'snap_ad_123', [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(50000, $result['stats']['impressions']);
        $this->assertEquals(2500, $result['stats']['swipes']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'method' => 'getAdStatistics',
        ]);
    }

    /** @test */
    public function it_can_get_audience_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('success', [
            'insights' => [
                'age_groups' => [
                    '13-17' => 25,
                    '18-24' => 45,
                    '25-34' => 30,
                ],
                'gender' => [
                    'male' => 48,
                    'female' => 52,
                ],
            ],
        ]);

        $result = $this->service->getAudienceInsights($integration, 'acc_123');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('age_groups', $result['insights']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'method' => 'getAudienceInsights',
        ]);
    }

    /** @test */
    public function it_can_create_pixel()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('success', [
            'pixel' => [
                'id' => 'pixel_123',
                'code' => 'snap_pixel_code_here',
            ],
        ]);

        $result = $this->service->createPixel($integration, [
            'ad_account_id' => 'acc_123',
            'name' => 'بكسل موقعنا',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('pixel_123', $result['pixel_id']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'method' => 'createPixel',
        ]);
    }

    /** @test */
    public function it_can_get_pixel_events()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('success', [
            'events' => [
                ['type' => 'PAGE_VIEW', 'count' => 1500],
                ['type' => 'ADD_TO_CART', 'count' => 250],
                ['type' => 'PURCHASE', 'count' => 75],
            ],
        ]);

        $result = $this->service->getPixelEvents($integration, 'pixel_123');

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['events']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'method' => 'getPixelEvents',
        ]);
    }

    /** @test */
    public function it_can_update_ad_status()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('success', [
            'ad' => [
                'id' => 'snap_ad_123',
                'status' => 'PAUSED',
            ],
        ]);

        $result = $this->service->updateAdStatus($integration, 'snap_ad_123', 'PAUSED');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'method' => 'updateAdStatus',
        ]);
    }

    /** @test */
    public function it_validates_ad_account_id()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $result = $this->service->createAd($integration, [
            'name' => 'Test Ad',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ad_account_id', strtolower($result['error']));

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'test' => 'validation',
        ]);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $this->mockSnapchatAPI('error');

        $result = $this->service->createAd($integration, [
            'ad_account_id' => 'acc_123',
            'name' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        Http::fake([
            'adsapi.snapchat.com/*' => Http::response([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Rate limit exceeded',
                ],
            ], 429),
        ]);

        $result = $this->service->createAd($integration, [
            'ad_account_id' => 'acc_123',
            'name' => 'Test',
        ]);

        $this->assertFalse($result['success']);

        $this->logTestResult('passed', [
            'service' => 'SnapchatService',
            'test' => 'rate_limiting',
        ]);
    }
}
