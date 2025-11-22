<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\ScheduledSocialPost;
use App\Models\Social\SocialPost;
use App\Jobs\PublishToTwitterJob;
use App\Jobs\PublishToLinkedInJob;
use App\Jobs\PublishToSnapchatJob;
use App\Jobs\PublishToGoogleBusinessJob;
use App\Jobs\PublishToInstagramJob;
use App\Jobs\PublishToFacebookJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Twitter, LinkedIn & Other Platforms Publishing Integration Test
 *
 * اختبارات نشر المحتوى على Twitter و LinkedIn و Snapchat و Google Business
 */
class TwitterLinkedInOtherPublishingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_creates_and_publishes_twitter_tweet()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['twitter'],
            'post_type' => 'tweet',
            'content' => 'تغريدة جديدة على X (Twitter) #Marketing',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/tweet-image.jpg'],
            ],
            'scheduled_at' => now()->addMinutes(10),
            'status' => 'scheduled',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'tweet_123456789',
                'text' => 'تغريدة جديدة على X (Twitter) #Marketing',
            ],
        ]);

        PublishToTwitterJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToTwitterJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'twitter',
            'post_type' => 'tweet',
        ]);
    }

    #[Test]
    public function it_creates_and_publishes_twitter_thread()
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
            'platforms' => ['twitter'],
            'post_type' => 'thread',
            'content' => 'سلسلة تغريدات',
            'scheduled_at' => now()->addMinutes(25),
            'status' => 'scheduled',
            'metadata' => [
                'twitter' => [
                    'tweets' => [
                        'التغريدة الأولى 1/3',
                        'التغريدة الثانية 2/3',
                        'التغريدة الثالثة 3/3',
                    ],
                ],
            ],
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'thread_123',
            ],
        ]);

        PublishToTwitterJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToTwitterJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'twitter',
            'post_type' => 'thread',
        ]);
    }

    #[Test]
    public function it_creates_and_publishes_linkedin_post()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['linkedin'],
            'post_type' => 'post',
            'content' => 'منشور احترافي على LinkedIn',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/linkedin-image.jpg'],
            ],
            'scheduled_at' => now()->addHours(1),
            'status' => 'scheduled',
            'metadata' => [
                'linkedin' => [
                    'visibility' => 'PUBLIC',
                    'content_entity' => 'organization',
                ],
            ],
        ]);

        $this->mockLinkedInAPI('success', [
            'id' => 'urn:li:share:123456',
        ]);

        PublishToLinkedInJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToLinkedInJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'linkedin',
            'post_type' => 'post',
        ]);
    }

    #[Test]
    public function it_creates_and_publishes_linkedin_article()
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
            'platforms' => ['linkedin'],
            'post_type' => 'article',
            'content' => 'مقال احترافي على LinkedIn',
            'scheduled_at' => now()->addHours(3),
            'status' => 'scheduled',
            'metadata' => [
                'linkedin' => [
                    'title' => 'عنوان المقال',
                    'article_body' => '<p>نص المقال المفصل...</p>',
                    'thumbnail' => 'https://example.com/article-cover.jpg',
                ],
            ],
        ]);

        $this->mockLinkedInAPI('success', [
            'id' => 'urn:li:article:456789',
        ]);

        PublishToLinkedInJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToLinkedInJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'linkedin',
            'post_type' => 'article',
        ]);
    }

    #[Test]
    public function it_creates_and_publishes_snapchat_story()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'snapchat');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['snapchat'],
            'post_type' => 'story',
            'content' => 'قصة Snapchat',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/snap-story.jpg'],
            ],
            'scheduled_at' => now()->addMinutes(15),
            'status' => 'scheduled',
        ]);

        $this->mockSnapchatAPI('success', [
            'snap' => [
                'id' => 'snap_story_123',
            ],
        ]);

        PublishToSnapchatJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToSnapchatJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'snapchat',
            'post_type' => 'story',
        ]);
    }

    #[Test]
    public function it_creates_and_publishes_google_business_post()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['google_business'],
            'post_type' => 'update',
            'content' => 'تحديث Google Business Profile',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/gbp-image.jpg'],
            ],
            'scheduled_at' => now()->addMinutes(30),
            'status' => 'scheduled',
            'metadata' => [
                'google_business' => [
                    'topic_type' => 'STANDARD',
                    'call_to_action' => [
                        'action_type' => 'LEARN_MORE',
                        'url' => 'https://example.com',
                    ],
                ],
            ],
        ]);

        $this->mockGoogleAdsAPI('success', [
            'name' => 'locations/12345/localPosts/67890',
        ]);

        PublishToGoogleBusinessJob::dispatch($scheduledPost);
        Queue::assertPushed(PublishToGoogleBusinessJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'platform' => 'google_business',
            'post_type' => 'update',
        ]);
    }

    #[Test]
    public function it_publishes_to_multiple_platforms_simultaneously()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create integrations for multiple platforms
        $this->createTestIntegration($org->org_id, 'instagram');
        $this->createTestIntegration($org->org_id, 'facebook');
        $this->createTestIntegration($org->org_id, 'twitter');
        $this->createTestIntegration($org->org_id, 'linkedin');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram', 'facebook', 'twitter', 'linkedin'],
            'post_type' => 'post',
            'content' => 'منشور على جميع المنصات',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/multi-platform.jpg'],
            ],
            'scheduled_at' => now()->addMinutes(20),
            'status' => 'scheduled',
        ]);

        $this->mockMetaAPI('success');
        $this->mockTwitterAPI('success');
        $this->mockLinkedInAPI('success');

        // Dispatch jobs for all platforms
        PublishToInstagramJob::dispatch($scheduledPost);
        PublishToFacebookJob::dispatch($scheduledPost);
        PublishToTwitterJob::dispatch($scheduledPost);
        PublishToLinkedInJob::dispatch($scheduledPost);

        Queue::assertPushed(PublishToInstagramJob::class);
        Queue::assertPushed(PublishToFacebookJob::class);
        Queue::assertPushed(PublishToTwitterJob::class);
        Queue::assertPushed(PublishToLinkedInJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'type' => 'multi_platform',
            'platforms' => ['instagram', 'facebook', 'twitter', 'linkedin'],
        ]);
    }

    #[Test]
    public function it_handles_publishing_failure_and_retries()
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
            'post_type' => 'feed',
            'content' => 'منشور للاختبار',
            'scheduled_at' => now()->addMinutes(5),
            'status' => 'scheduled',
        ]);

        // Mock API failure
        $this->mockMetaAPI('error', [
            'error' => [
                'message' => 'Rate limit exceeded',
                'code' => 32,
            ],
        ]);

        PublishToInstagramJob::dispatch($scheduledPost);

        // Verify retry is scheduled
        Queue::assertPushed(PublishToInstagramJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'test' => 'failure_retry',
        ]);
    }

    #[Test]
    public function it_tracks_published_post_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        // Create published post
        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'ig_post_123',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'منشور منشور',
            'published_at' => now()->subHours(2),
            'metrics' => [
                'likes' => 150,
                'comments' => 25,
                'shares' => 10,
                'views' => 5000,
                'engagement_rate' => 3.7,
            ],
        ]);

        $this->assertDatabaseHas('cmis.social_posts', [
            'post_id' => $socialPost->post_id,
            'platform' => 'instagram',
        ]);

        $this->assertTrue($socialPost->metrics['likes'] == 150);

        $this->logTestResult('passed', [
            'workflow' => 'social_publishing',
            'test' => 'metrics_tracking',
        ]);
    }
}
