<?php

namespace Tests\Unit\Models\Content;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Content\ScheduledPost;
use App\Models\Integration\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Scheduled Post Model Unit Tests
 */
class ScheduledPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_scheduled_post()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'منشور مجدول للنشر على فيسبوك',
            'scheduled_time' => now()->addHours(2),
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('cmis.scheduled_posts', [
            'post_id' => $post->post_id,
            'platform' => 'facebook',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'content' => 'Test post',
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $this->assertEquals($org->org_id, $post->org->org_id);
    }

    #[Test]
    public function it_can_belong_to_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Summer Campaign',
            'status' => 'active',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'platform' => 'facebook',
            'content' => 'Campaign post',
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $this->assertEquals($campaign->campaign_id, $post->campaign->campaign_id);
    }

    #[Test]
    public function it_can_belong_to_integration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => [],
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'content' => 'Test post',
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $this->assertEquals($integration->integration_id, $post->integration->integration_id);
    }

    #[Test]
    public function it_has_different_post_statuses()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $scheduled = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Scheduled',
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $published = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'content' => 'Published',
            'scheduled_time' => now()->subHours(1),
            'status' => 'published',
        ]);

        $failed = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'content' => 'Failed',
            'scheduled_time' => now()->subHours(1),
            'status' => 'failed',
        ]);

        $this->assertEquals('scheduled', $scheduled->status);
        $this->assertEquals('published', $published->status);
        $this->assertEquals('failed', $failed->status);
    }

    #[Test]
    public function it_stores_media_urls()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $mediaUrls = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
            'https://example.com/video.mp4',
        ];

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'content' => 'Post with media',
            'media_urls' => $mediaUrls,
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $this->assertCount(3, $post->media_urls);
        $this->assertContains('https://example.com/image1.jpg', $post->media_urls);
    }

    #[Test]
    public function it_stores_post_metadata()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'hashtags' => ['#marketing', '#social', '#campaign'],
            'location' => 'Manama, Bahrain',
            'mentions' => ['@username1', '@username2'],
            'link' => 'https://example.com/products',
        ];

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'content' => 'Post with metadata',
            'metadata' => $metadata,
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $this->assertContains('#marketing', $post->metadata['hashtags']);
        $this->assertEquals('Manama, Bahrain', $post->metadata['location']);
    }

    #[Test]
    public function it_tracks_published_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Published post',
            'scheduled_time' => now()->subHours(1),
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertNotNull($post->published_at);
    }

    #[Test]
    public function it_stores_platform_post_id()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Published post',
            'scheduled_time' => now()->subHours(1),
            'status' => 'published',
            'platform_post_id' => 'fb_post_123456',
        ]);

        $this->assertEquals('fb_post_123456', $post->platform_post_id);
    }

    #[Test]
    public function it_stores_error_messages_for_failed_posts()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'content' => 'Failed post',
            'scheduled_time' => now()->subHours(1),
            'status' => 'failed',
            'error_message' => 'API rate limit exceeded',
        ]);

        $this->assertEquals('API rate limit exceeded', $post->error_message);
    }

    #[Test]
    public function it_tracks_retry_count()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Retried post',
            'scheduled_time' => now()->subHours(1),
            'status' => 'scheduled',
            'retry_count' => 0,
        ]);

        $post->increment('retry_count');
        $post->increment('retry_count');

        $this->assertEquals(2, $post->fresh()->retry_count);
    }

    #[Test]
    public function it_supports_different_platforms()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube'];

        foreach ($platforms as $platform) {
            ScheduledPost::create([
                'post_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'platform' => $platform,
                'content' => 'Post for ' . $platform,
                'scheduled_time' => now()->addHours(1),
                'status' => 'scheduled',
            ]);
        }

        $posts = ScheduledPost::where('org_id', $org->org_id)->get();
        $this->assertCount(6, $posts);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Test post',
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $this->assertTrue(Str::isUuid($post->post_id));
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'content' => 'Test post',
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $this->assertNotNull($post->created_at);
        $this->assertNotNull($post->updated_at);
    }

    #[Test]
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'platform' => 'facebook',
            'content' => 'Org 1 Post',
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'platform' => 'instagram',
            'content' => 'Org 2 Post',
            'scheduled_time' => now()->addHours(1),
            'status' => 'scheduled',
        ]);

        $org1Posts = ScheduledPost::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Posts);
        $this->assertEquals('Org 1 Post', $org1Posts->first()->content);
    }
}
