<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ScheduledSocialPost;
use App\Jobs\PublishToFacebookJob;
use App\Jobs\PublishToTikTokJob;
use App\Jobs\PublishToYouTubeJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Facebook, TikTok & YouTube Publishing Integration Test
 *
 * اختبارات نشر المحتوى على Facebook و TikTok و YouTube
 */
class FacebookTikTokYouTubePublishingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_creates_and_publishes_facebook_post()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['facebook'],
            'post_type' => 'post',
            'content' => 'منشور جديد على Facebook',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/fb-image.jpg'],
            ],
            'scheduled_at' => now()->addMinutes(15),
            'status' => 'scheduled',
            'metadata' => [
                'facebook' => [
                    'link' => 'https://example.com',
                    'targeting' => [
                        'geo_locations' => ['countries' => ['BH', 'SA']],
                    ],
                ],
            ],
        ]);

        $this->mockMetaAPI('success', [
            'id' => 'fb_post_123',
            'post_id' => '123456_789012',
        ]);

        PublishToFacebookJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToFacebookJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'facebook',
            'post_type' => 'post',
        ]);
    }

    /** @test */
    public function it_creates_and_publishes_facebook_story()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['facebook'],
            'post_type' => 'story',
            'content' => 'قصة Facebook',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/fb-story.jpg'],
            ],
            'scheduled_at' => now()->addMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->mockMetaAPI('success', [
            'id' => 'story_fb_123',
        ]);

        PublishToFacebookJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToFacebookJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'facebook',
            'post_type' => 'story',
        ]);
    }

    /** @test */
    public function it_creates_and_publishes_tiktok_video()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['tiktok'],
            'post_type' => 'video',
            'content' => 'فيديو TikTok جديد',
            'media' => [
                ['type' => 'video', 'url' => 'https://example.com/tiktok-video.mp4'],
            ],
            'scheduled_at' => now()->addMinutes(20),
            'status' => 'scheduled',
            'metadata' => [
                'tiktok' => [
                    'privacy_level' => 'PUBLIC_TO_EVERYONE',
                    'disable_duet' => false,
                    'disable_stitch' => false,
                    'disable_comment' => false,
                    'video_cover_timestamp' => 1.0,
                ],
            ],
        ]);

        $this->mockTikTokAPI('success', [
            'data' => [
                'publish_id' => 'tiktok_123',
            ],
        ]);

        PublishToTikTokJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToTikTokJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'tiktok',
            'post_type' => 'video',
        ]);
    }

    /** @test */
    public function it_creates_and_publishes_youtube_video()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['youtube'],
            'post_type' => 'video',
            'content' => 'فيديو YouTube جديد',
            'media' => [
                ['type' => 'video', 'url' => 'https://example.com/youtube-video.mp4'],
            ],
            'scheduled_at' => now()->addHours(2),
            'status' => 'scheduled',
            'metadata' => [
                'youtube' => [
                    'title' => 'فيديو YouTube جديد',
                    'description' => 'وصف مفصل للفيديو',
                    'tags' => ['marketing', 'tutorial', 'arabic'],
                    'category_id' => '22', // People & Blogs
                    'privacy_status' => 'public',
                    'thumbnail' => 'https://example.com/thumbnail.jpg',
                ],
            ],
        ]);

        $this->mockGoogleAdsAPI('success', [
            'id' => 'yt_video_123',
        ]);

        PublishToYouTubeJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToYouTubeJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'youtube',
            'post_type' => 'video',
        ]);
    }

    /** @test */
    public function it_creates_and_publishes_youtube_short()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['youtube'],
            'post_type' => 'short',
            'content' => 'YouTube Short',
            'media' => [
                ['type' => 'video', 'url' => 'https://example.com/short-video.mp4'],
            ],
            'scheduled_at' => now()->addMinutes(30),
            'status' => 'scheduled',
            'metadata' => [
                'youtube' => [
                    'title' => 'YouTube Short #Shorts',
                    'description' => '#Shorts #Marketing',
                    'privacy_status' => 'public',
                ],
            ],
        ]);

        $this->mockGoogleAdsAPI('success', [
            'id' => 'yt_short_456',
        ]);

        PublishToYouTubeJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToYouTubeJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'youtube',
            'post_type' => 'short',
        ]);
    }
}
