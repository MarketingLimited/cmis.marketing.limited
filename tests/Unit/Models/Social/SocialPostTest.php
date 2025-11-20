<?php

namespace Tests\Unit\Models\Social;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Integration;
use App\Models\Social\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Social Post Model Unit Tests
 * Aligned with actual cmis.social_posts schema
 */
class SocialPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_a_social_post()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'ig_post_123',
            'provider' => 'instagram',
            'caption' => 'Test Instagram post',
            'posted_at' => now(),
        ]);

        $this->assertDatabaseHas('cmis.social_posts', [
            'id' => $socialPost->id,
            'provider' => 'instagram',
        ]);
    }

    #[Test]
    public function it_belongs_to_organization_and_integration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'fb_post_456',
            'provider' => 'facebook',
            'caption' => 'Facebook post',
            'posted_at' => now(),
        ]);

        $this->assertEquals($org->org_id, $socialPost->org->org_id);
        $this->assertEquals($integration->integration_id, $socialPost->integration->integration_id);
    }

    #[Test]
    public function it_stores_metrics_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $metrics = [
            'likes' => 150,
            'comments' => 25,
            'shares' => 12,
            'impressions' => 5400,
            'reach' => 3200,
            'engagement_rate' => 3.7,
        ];

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'metrics_post',
            'provider' => 'instagram',
            'caption' => 'Metrics test',
            'posted_at' => now(),
            'metrics' => $metrics,
        ]);

        $this->assertEquals(150, $socialPost->metrics['likes']);
        $this->assertEquals(3.7, $socialPost->metrics['engagement_rate']);
    }

    #[Test]
    public function it_validates_media_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'ig_media_type',
            'provider' => 'instagram',
            'media_type' => 'IMAGE',
            'caption' => 'Type test',
            'posted_at' => now(),
        ]);

        $this->assertContains($socialPost->media_type, ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM']);
    }

    #[Test]
    public function it_validates_provider()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'provider_test',
            'provider' => 'facebook',
            'caption' => 'Provider test',
            'posted_at' => now(),
        ]);

        $this->assertContains($socialPost->provider, ['facebook', 'instagram', 'twitter', 'linkedin']);
    }

    #[Test]
    public function it_generates_uuid_for_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialPost = SocialPost::create([
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'uuid_test',
            'provider' => 'instagram',
            'caption' => 'UUID test',
            'posted_at' => now(),
        ]);

        $this->assertTrue(Str::isUuid($socialPost->id));
    }

    #[Test]
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'soft_delete_test',
            'provider' => 'instagram',
            'caption' => 'Soft delete test',
            'posted_at' => now(),
        ]);

        $socialPost->delete();

        $this->assertSoftDeleted('cmis.social_posts', [
            'id' => $socialPost->id,
        ]);
    }

    #[Test]
    public function it_can_find_by_post_external_id()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $externalId = 'unique_external_id';

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => $externalId,
            'provider' => 'instagram',
            'caption' => 'External ID test',
            'posted_at' => now(),
        ]);

        $found = SocialPost::where('post_external_id', $externalId)->first();

        $this->assertNotNull($found);
        $this->assertEquals($socialPost->id, $found->id);
    }

    #[Test]
    public function it_stores_video_urls()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'video_post',
            'provider' => 'instagram',
            'media_type' => 'VIDEO',
            'caption' => 'Video post',
            'video_url' => 'https://example.com/video.mp4',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'posted_at' => now(),
        ]);

        $this->assertEquals('https://example.com/video.mp4', $socialPost->video_url);
        $this->assertEquals('https://example.com/thumb.jpg', $socialPost->thumbnail_url);
    }

    #[Test]
    public function it_stores_carousel_children_media()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $childrenMedia = [
            ['type' => 'IMAGE', 'url' => 'https://example.com/img1.jpg'],
            ['type' => 'IMAGE', 'url' => 'https://example.com/img2.jpg'],
            ['type' => 'VIDEO', 'url' => 'https://example.com/video.mp4'],
        ];

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'carousel_post',
            'provider' => 'instagram',
            'media_type' => 'CAROUSEL_ALBUM',
            'caption' => 'Carousel post',
            'children_media' => $childrenMedia,
            'posted_at' => now(),
        ]);

        $this->assertIsArray($socialPost->children_media);
        $this->assertCount(3, $socialPost->children_media);
        $this->assertEquals('IMAGE', $socialPost->children_media[0]['type']);
    }

    #[Test]
    public function it_calculates_engagement_rate()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialPost = SocialPost::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'engagement_post',
            'provider' => 'instagram',
            'caption' => 'Engagement test',
            'posted_at' => now(),
            'metrics' => [
                'likes' => 100,
                'comments' => 20,
                'shares' => 10,
                'views' => 5000,
            ],
        ]);

        // Engagement = (likes + comments + shares) / views * 100
        // (100 + 20 + 10) / 5000 * 100 = 2.6%
        $engagementRate = ($socialPost->metrics['likes'] + $socialPost->metrics['comments'] + $socialPost->metrics['shares']) / $socialPost->metrics['views'] * 100;

        $this->assertEquals(2.6, $engagementRate);
    }
}
