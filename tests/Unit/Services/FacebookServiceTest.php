<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\FacebookService;

/**
 * Facebook Service Unit Tests
 */
class FacebookServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected FacebookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->service = app(FacebookService::class);
    }

    /** @test */
    public function it_can_publish_page_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'fb_post_123',
        ]);

        $result = $this->service->publishPagePost($integration, [
            'message' => 'منشور تجريبي على فيسبوك',
            'link' => 'https://example.com',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('fb_post_123', $result['post_id']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'publishPagePost',
        ]);
    }

    /** @test */
    public function it_can_publish_photo()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'fb_photo_456',
            'post_id' => 'fb_post_456',
        ]);

        $result = $this->service->publishPhoto($integration, [
            'url' => 'https://example.com/photo.jpg',
            'message' => 'صورة جديدة 📷',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'publishPhoto',
        ]);
    }

    /** @test */
    public function it_can_publish_video()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'fb_video_789',
        ]);

        $result = $this->service->publishVideo($integration, [
            'file_url' => 'https://example.com/video.mp4',
            'description' => 'فيديو جديد 🎥',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'publishVideo',
        ]);
    }

    /** @test */
    public function it_can_publish_story()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'fb_story_111',
        ]);

        $result = $this->service->publishStory($integration, [
            'photo_url' => 'https://example.com/story.jpg',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'publishStory',
        ]);
    }

    /** @test */
    public function it_can_get_page_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'name' => 'page_impressions',
                    'values' => [['value' => 25000]],
                ],
                [
                    'name' => 'page_engaged_users',
                    'values' => [['value' => 3500]],
                ],
                [
                    'name' => 'page_fans',
                    'values' => [['value' => 12000]],
                ],
            ],
        ]);

        $result = $this->service->getPageInsights($integration, now()->subDays(30), now());

        $this->assertTrue($result['success']);
        $this->assertEquals(25000, $result['data']['page_impressions']);
        $this->assertEquals(12000, $result['data']['page_fans']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'getPageInsights',
        ]);
    }

    /** @test */
    public function it_can_get_post_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'name' => 'post_impressions',
                    'values' => [['value' => 5000]],
                ],
                [
                    'name' => 'post_engaged_users',
                    'values' => [['value' => 750]],
                ],
            ],
        ]);

        $result = $this->service->getPostInsights($integration, 'fb_post_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(5000, $result['data']['post_impressions']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'getPostInsights',
        ]);
    }

    /** @test */
    public function it_can_get_comments()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'comment_1',
                    'message' => 'رائع! 👏',
                    'from' => ['name' => 'أحمد محمد', 'id' => 'user_1'],
                    'created_time' => now()->toIso8601String(),
                ],
            ],
        ]);

        $result = $this->service->getComments($integration, 'fb_post_123');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'getComments',
        ]);
    }

    /** @test */
    public function it_can_reply_to_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'comment_reply_123',
        ]);

        $result = $this->service->replyToComment($integration, 'comment_1', 'شكراً لك!');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'replyToComment',
        ]);
    }

    /** @test */
    public function it_can_get_page_conversations()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'conversation_1',
                    'updated_time' => now()->toIso8601String(),
                ],
            ],
        ]);

        $result = $this->service->getPageConversations($integration);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'getPageConversations',
        ]);
    }

    /** @test */
    public function it_can_send_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'message_id' => 'msg_123',
        ]);

        $result = $this->service->sendMessage($integration, 'user_123', 'مرحباً! كيف يمكنني مساعدتك؟');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'method' => 'sendMessage',
        ]);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('error');

        $result = $this->service->publishPagePost($integration, [
            'message' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'FacebookService',
            'test' => 'error_handling',
        ]);
    }
}
