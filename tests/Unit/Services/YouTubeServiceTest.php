<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\YouTubeService;
use Illuminate\Support\Facades\Http;

use PHPUnit\Framework\Attributes\Test;
/**
 * YouTube Service Unit Tests
 */
class YouTubeServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected YouTubeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(YouTubeService::class);
    }

    #[Test]
    public function it_can_upload_video()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'id' => 'youtube_video_123',
            'snippet' => [
                'title' => 'فيديو تجريبي',
            ],
        ]);

        $result = $this->service->uploadVideo($integration, [
            'title' => 'فيديو تجريبي على يوتيوب',
            'description' => 'وصف الفيديو',
            'video_file' => '/path/to/video.mp4',
            'tags' => ['تسويق', 'تعليمي'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('youtube_video_123', $result['video_id']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'uploadVideo',
        ]);
    }

    #[Test]
    public function it_can_upload_short()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'id' => 'youtube_short_456',
        ]);

        $result = $this->service->uploadShort($integration, [
            'title' => '#Shorts فيديو قصير',
            'description' => 'فيديو قصير للشورتس',
            'video_file' => '/path/to/short.mp4',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'uploadShort',
        ]);
    }

    #[Test]
    public function it_can_get_channel_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'rows' => [
                [
                    'views' => 50000,
                    'estimatedMinutesWatched' => 150000,
                    'averageViewDuration' => 180,
                    'subscribersGained' => 250,
                ],
            ],
        ]);

        $result = $this->service->getChannelAnalytics($integration, now()->subDays(30), now());

        $this->assertTrue($result['success']);
        $this->assertEquals(50000, $result['data']['views']);
        $this->assertEquals(250, $result['data']['subscribersGained']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'getChannelAnalytics',
        ]);
    }

    #[Test]
    public function it_can_get_video_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'rows' => [
                [
                    'views' => 5000,
                    'likes' => 350,
                    'dislikes' => 15,
                    'comments' => 125,
                    'shares' => 45,
                    'averageViewPercentage' => 65.5,
                ],
            ],
        ]);

        $result = $this->service->getVideoAnalytics($integration, 'video_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(5000, $result['data']['views']);
        $this->assertEquals(350, $result['data']['likes']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'getVideoAnalytics',
        ]);
    }

    #[Test]
    public function it_can_get_comments()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'items' => [
                [
                    'id' => 'comment_1',
                    'snippet' => [
                        'topLevelComment' => [
                            'snippet' => [
                                'textDisplay' => 'فيديو رائع!',
                                'authorDisplayName' => 'أحمد محمد',
                                'likeCount' => 25,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->service->getComments($integration, 'video_123');

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'getComments',
        ]);
    }

    #[Test]
    public function it_can_reply_to_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'id' => 'reply_comment_123',
        ]);

        $result = $this->service->replyToComment($integration, 'comment_1', 'شكراً لك على الدعم!');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'replyToComment',
        ]);
    }

    #[Test]
    public function it_can_update_video_metadata()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'id' => 'video_123',
        ]);

        $result = $this->service->updateVideoMetadata($integration, 'video_123', [
            'title' => 'عنوان محدث',
            'description' => 'وصف محدث',
            'tags' => ['تسويق', 'تحديث'],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'updateVideoMetadata',
        ]);
    }

    #[Test]
    public function it_can_delete_video()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', []);

        $result = $this->service->deleteVideo($integration, 'video_123');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'deleteVideo',
        ]);
    }

    #[Test]
    public function it_can_create_playlist()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'id' => 'playlist_123',
        ]);

        $result = $this->service->createPlaylist($integration, [
            'title' => 'قائمة تشغيل جديدة',
            'description' => 'قائمة تشغيل للفيديوهات التعليمية',
            'privacy_status' => 'public',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('playlist_123', $result['playlist_id']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'createPlaylist',
        ]);
    }

    #[Test]
    public function it_can_add_video_to_playlist()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('success', [
            'id' => 'playlistitem_123',
        ]);

        $result = $this->service->addVideoToPlaylist($integration, 'playlist_123', 'video_456');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'method' => 'addVideoToPlaylist',
        ]);
    }

    #[Test]
    public function it_validates_video_file()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $result = $this->service->uploadVideo($integration, [
            'title' => 'Test',
            // Missing video_file
        ]);

        $this->assertFalse($result['success']);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'test' => 'validation',
        ]);
    }

    #[Test]
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $this->mockGoogleAPI('error');

        $result = $this->service->uploadVideo($integration, [
            'title' => 'Test',
            'video_file' => '/path/to/video.mp4',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'YouTubeService',
            'test' => 'error_handling',
        ]);
    }
}
