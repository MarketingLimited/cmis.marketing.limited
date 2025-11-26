<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\InstagramService;
use Illuminate\Support\Facades\Http;

use PHPUnit\Framework\Attributes\Test;
/**
 * Instagram Service Unit Tests
 */
class InstagramServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected InstagramService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InstagramService::class);
    }

    #[Test]
    public function it_can_publish_feed_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'id' => 'ig_media_123',
        ]);

        $result = $this->service->publishFeedPost($integration, [
            'image_url' => 'https://example.com/image.jpg',
            'caption' => 'اختبار النشر على إنستقرام 📸',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('ig_media_123', $result['media_id']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'publishFeedPost',
        ]);
    }

    #[Test]
    public function it_can_publish_story()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'id' => 'ig_story_456',
        ]);

        $result = $this->service->publishStory($integration, [
            'image_url' => 'https://example.com/story.jpg',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('ig_story_456', $result['media_id']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'publishStory',
        ]);
    }

    #[Test]
    public function it_can_publish_reel()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'id' => 'ig_reel_789',
        ]);

        $result = $this->service->publishReel($integration, [
            'video_url' => 'https://example.com/reel.mp4',
            'caption' => 'ريل جديد 🎬',
            'cover_url' => 'https://example.com/cover.jpg',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('ig_reel_789', $result['media_id']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'publishReel',
        ]);
    }

    #[Test]
    public function it_can_publish_carousel()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'id' => 'ig_carousel_111',
        ]);

        $result = $this->service->publishCarousel($integration, [
            'children' => [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
                'https://example.com/image3.jpg',
            ],
            'caption' => 'منشور متعدد الصور 🖼️',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'publishCarousel',
        ]);
    }

    #[Test]
    public function it_can_get_account_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'name' => 'impressions',
                    'values' => [['value' => 10000]],
                ],
                [
                    'name' => 'reach',
                    'values' => [['value' => 7500]],
                ],
                [
                    'name' => 'follower_count',
                    'values' => [['value' => 5000]],
                ],
            ],
        ]);

        $result = $this->service->getAccountInsights($integration, now()->subDays(30), now());

        $this->assertTrue($result['success']);
        $this->assertEquals(10000, $result['data']['impressions']);
        $this->assertEquals(5000, $result['data']['follower_count']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'getAccountInsights',
        ]);
    }

    #[Test]
    public function it_can_get_media_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'name' => 'impressions',
                    'values' => [['value' => 1500]],
                ],
                [
                    'name' => 'engagement',
                    'values' => [['value' => 250]],
                ],
                [
                    'name' => 'saved',
                    'values' => [['value' => 45]],
                ],
            ],
        ]);

        $result = $this->service->getMediaInsights($integration, 'ig_media_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(1500, $result['data']['impressions']);
        $this->assertEquals(250, $result['data']['engagement']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'getMediaInsights',
        ]);
    }

    #[Test]
    public function it_can_get_comments()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'comment_1',
                    'text' => 'منشور رائع! 👍',
                    'username' => 'user1',
                    'timestamp' => now()->toIso8601String(),
                ],
                [
                    'id' => 'comment_2',
                    'text' => 'أحب هذا المحتوى',
                    'username' => 'user2',
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ]);

        $result = $this->service->getComments($integration, 'ig_media_123');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'getComments',
        ]);
    }

    #[Test]
    public function it_can_reply_to_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'id' => 'reply_comment_123',
        ]);

        $result = $this->service->replyToComment($integration, 'comment_1', 'شكراً لك! ❤️');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'replyToComment',
        ]);
    }

    #[Test]
    public function it_can_delete_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'success' => true,
        ]);

        $result = $this->service->deleteComment($integration, 'comment_123');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'deleteComment',
        ]);
    }

    #[Test]
    public function it_can_get_hashtag_search()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'hashtag_123',
                    'name' => 'تسويق',
                ],
            ],
        ]);

        $result = $this->service->searchHashtag($integration, 'تسويق');

        $this->assertTrue($result['success']);
        $this->assertEquals('تسويق', $result['data']['name']);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'method' => 'searchHashtag',
        ]);
    }

    #[Test]
    public function it_handles_api_errors_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('error');

        $result = $this->service->publishFeedPost($integration, [
            'image_url' => 'https://example.com/image.jpg',
            'caption' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'test' => 'error_handling',
        ]);
    }

    #[Test]
    public function it_handles_rate_limiting()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'code' => 32,
                    'message' => 'Rate limit exceeded',
                ],
            ], 429),
        ]);

        $result = $this->service->publishFeedPost($integration, [
            'image_url' => 'https://example.com/image.jpg',
            'caption' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('rate limit', strtolower($result['error']));

        $this->logTestResult('passed', [
            'service' => 'InstagramService',
            'test' => 'rate_limiting',
        ]);
    }
}
