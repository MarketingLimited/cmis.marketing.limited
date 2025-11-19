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
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'ig_post_123',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'Test Instagram post',
            'published_at' => now(),
        ]);

        $this->assertDatabaseHas('cmis.social_posts', [
            'post_id' => $socialPost->post_id,
            'platform' => 'instagram',
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
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'fb_post_456',
            'platform' => 'facebook',
            'post_type' => 'post',
            'content' => 'Facebook post',
            'published_at' => now(),
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
            'shares' => 10,
            'views' => 5000,
            'engagement_rate' => 3.7,
        ];

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'ig_post_metrics',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'Metrics test',
            'published_at' => now(),
            'metrics' => $metrics,
        ]);

        $this->assertEquals(150, $socialPost->metrics['likes']);
        $this->assertEquals(3.7, $socialPost->metrics['engagement_rate']);
    }

    #[Test]
    public function it_validates_post_type()
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
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'ig_post_type',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'Type test',
            'published_at' => now(),
        ]);

        $this->assertContains($socialPost->post_type, ['feed', 'story', 'reel', 'post', 'video', 'tweet', 'thread']);
    }

    #[Test]
    public function it_validates_platform()
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
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'platform_test',
            'platform' => 'facebook',
            'post_type' => 'post',
            'content' => 'Platform test',
            'published_at' => now(),
        ]);

        $this->assertContains($socialPost->platform, [
            'instagram',
            'facebook',
            'twitter',
            'linkedin',
            'tiktok',
            'youtube',
            'snapchat',
        ]);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
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
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'uuid_test',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'UUID test',
            'published_at' => now(),
        ]);

        $this->assertTrue(Str::isUuid($socialPost->post_id));
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
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'soft_delete',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'Soft delete test',
            'published_at' => now(),
        ]);

        $socialPost->delete();

        $this->assertSoftDeleted('cmis.social_posts', [
            'post_id' => $socialPost->post_id,
        ]);
    }

    #[Test]
    public function it_has_timestamps()
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
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'timestamp_test',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'Timestamp test',
            'published_at' => now(),
        ]);

        $this->assertNotNull($socialPost->created_at);
        $this->assertNotNull($socialPost->updated_at);
        $this->assertNotNull($socialPost->published_at);
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

        $integration1 = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $integration2 = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'integration_id' => $integration1->integration_id,
            'post_external_id' => 'org1_post',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'Org 1 post',
            'published_at' => now(),
        ]);

        SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'integration_id' => $integration2->integration_id,
            'post_external_id' => 'org2_post',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'Org 2 post',
            'published_at' => now(),
        ]);

        $org1Posts = SocialPost::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Posts);
        $this->assertEquals('Org 1 post', $org1Posts->first()->content);
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
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'engagement_test',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'Engagement test',
            'published_at' => now(),
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
