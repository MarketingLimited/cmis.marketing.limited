<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\TikTokService;

/**
 * TikTok Service Unit Tests
 */
class TikTokServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected TikTokService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TikTokService::class);
    }

    /** @test */
    public function it_can_publish_video()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'share_id' => 'tiktok_video_123',
            ],
        ]);

        $result = $this->service->publishVideo($integration, [
            'video_url' => 'https://example.com/video.mp4',
            'caption' => 'فيديو جديد على تيك توك 🎵',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('tiktok_video_123', $result['video_id']);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'method' => 'publishVideo',
        ]);
    }

    /** @test */
    public function it_can_get_user_info()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'user' => [
                    'display_name' => 'Test User',
                    'follower_count' => 15000,
                    'following_count' => 250,
                    'video_count' => 45,
                ],
            ],
        ]);

        $result = $this->service->getUserInfo($integration);

        $this->assertTrue($result['success']);
        $this->assertEquals(15000, $result['data']['follower_count']);
        $this->assertEquals(45, $result['data']['video_count']);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'method' => 'getUserInfo',
        ]);
    }

    /** @test */
    public function it_can_get_videos_list()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'videos' => [
                    [
                        'id' => 'video_1',
                        'title' => 'Video 1',
                        'view_count' => 50000,
                        'like_count' => 2500,
                    ],
                    [
                        'id' => 'video_2',
                        'title' => 'Video 2',
                        'view_count' => 75000,
                        'like_count' => 4200,
                    ],
                ],
            ],
        ]);

        $result = $this->service->getVideos($integration);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'method' => 'getVideos',
        ]);
    }

    /** @test */
    public function it_can_get_video_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'view_count' => 100000,
                'like_count' => 5000,
                'comment_count' => 350,
                'share_count' => 450,
            ],
        ]);

        $result = $this->service->getVideoAnalytics($integration, 'video_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(100000, $result['data']['view_count']);
        $this->assertEquals(5000, $result['data']['like_count']);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'method' => 'getVideoAnalytics',
        ]);
    }

    /** @test */
    public function it_can_get_comments()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'comments' => [
                    [
                        'id' => 'comment_1',
                        'text' => 'فيديو رائع! 🔥',
                        'create_time' => now()->timestamp,
                        'like_count' => 25,
                    ],
                ],
            ],
        ]);

        $result = $this->service->getComments($integration, 'video_123');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'method' => 'getComments',
        ]);
    }

    /** @test */
    public function it_can_reply_to_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'comment_id' => 'reply_123',
            ],
        ]);

        $result = $this->service->replyToComment($integration, 'comment_1', 'شكراً لك! ❤️');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'method' => 'replyToComment',
        ]);
    }

    /** @test */
    public function it_can_get_audience_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'follower_demographics' => [
                    'gender_distribution' => [
                        'male' => 45,
                        'female' => 55,
                    ],
                    'age_distribution' => [
                        '18-24' => 35,
                        '25-34' => 40,
                        '35-44' => 15,
                    ],
                ],
            ],
        ]);

        $result = $this->service->getAudienceInsights($integration);

        $this->assertTrue($result['success']);
        $this->assertEquals(45, $result['data']['follower_demographics']['gender_distribution']['male']);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'method' => 'getAudienceInsights',
        ]);
    }

    /** @test */
    public function it_validates_video_requirements()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        // Test missing video URL
        $result = $this->service->publishVideo($integration, [
            'caption' => 'Test without video',
        ]);

        $this->assertFalse($result['success']);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'test' => 'validation',
        ]);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('error');

        $result = $this->service->publishVideo($integration, [
            'video_url' => 'https://example.com/video.mp4',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'TikTokService',
            'test' => 'error_handling',
        ]);
    }
}
