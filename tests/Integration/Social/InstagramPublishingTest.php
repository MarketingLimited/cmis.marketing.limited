<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ScheduledSocialPost;
use App\Jobs\PublishToInstagramJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Instagram Publishing Integration Test
 *
 * اختبارات نشر المحتوى على Instagram (Feed, Story, Reel)
 */
class InstagramPublishingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_creates_and_publishes_instagram_feed_post()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'instagram');
        $socialAccount = $this->createTestSocialAccount($org->org_id, $integration->integration_id);

        // Create scheduled post
        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'feed',
            'content' => 'منشور جديد على Instagram 🌟',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/image.jpg'],
            ],
            'scheduled_at' => now()->addMinutes(5),
            'status' => 'scheduled',
            'metadata' => [
                'instagram' => [
                    'caption' => 'منشور جديد على Instagram 🌟',
                    'location_id' => '12345',
                    'hashtags' => ['#marketing', '#digital'],
                ],
            ],
        ]);

        $this->assertDatabaseHas('cmis.scheduled_social_posts', [
            'post_id' => $scheduledPost->post_id,
            'status' => 'scheduled',
        ]);

        // Mock Instagram API
        $this->mockMetaAPI('success', [
            'id' => 'ig_post_123456',
            'permalink' => 'https://instagram.com/p/ABC123',
        ]);

        // Publish now
        PublishToInstagramJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToInstagramJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'instagram',
            'post_type' => 'feed',
        ]);
    }

    /** @test */
    public function it_creates_and_publishes_instagram_story()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'story',
            'content' => 'قصة Instagram',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/story.jpg'],
            ],
            'scheduled_at' => now()->addMinutes(5),
            'status' => 'scheduled',
            'metadata' => [
                'instagram' => [
                    'stickers' => [
                        ['type' => 'location', 'location_id' => '12345'],
                        ['type' => 'hashtag', 'hashtag' => '#marketing'],
                    ],
                ],
            ],
        ]);

        $this->mockMetaAPI('success', [
            'id' => 'story_789',
        ]);

        PublishToInstagramJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToInstagramJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'instagram',
            'post_type' => 'story',
        ]);
    }

    /** @test */
    public function it_creates_and_publishes_instagram_reel()
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
            'platforms' => ['instagram'],
            'post_type' => 'reel',
            'content' => 'فيديو Reel قصير',
            'media' => [
                ['type' => 'video', 'url' => 'https://example.com/reel.mp4'],
            ],
            'scheduled_at' => now()->addMinutes(10),
            'status' => 'scheduled',
            'metadata' => [
                'instagram' => [
                    'caption' => 'فيديو Reel قصير',
                    'cover_url' => 'https://example.com/cover.jpg',
                    'audio_name' => 'Trending Audio',
                ],
            ],
        ]);

        $this->mockMetaAPI('success', [
            'id' => 'reel_456',
        ]);

        PublishToInstagramJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToInstagramJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'instagram',
            'post_type' => 'reel',
        ]);
    }
}
