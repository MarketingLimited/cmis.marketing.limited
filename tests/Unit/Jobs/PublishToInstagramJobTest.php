<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\PublishToInstagramJob;
use App\Models\ScheduledSocialPost;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Publish To Instagram Job Unit Tests
 */
class PublishToInstagramJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'feed',
            'content' => 'Test post',
            'scheduled_at' => now()->addMinutes(10),
            'status' => 'scheduled',
        ]);

        PublishToInstagramJob::dispatch($scheduledPost);

        Queue::assertPushed(PublishToInstagramJob::class);
    }

    /** @test */
    public function it_publishes_feed_post_to_instagram()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockMetaAPI('success', [
            'id' => 'ig_post_123',
            'permalink' => 'https://instagram.com/p/ABC123',
        ]);

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'feed',
            'content' => 'Instagram feed post',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/image.jpg'],
            ],
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $job = new PublishToInstagramJob($scheduledPost);
        $job->handle();

        $scheduledPost = $scheduledPost->fresh();
        $this->assertEquals('published', $scheduledPost->status);
    }

    /** @test */
    public function it_publishes_story_to_instagram()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockMetaAPI('success', [
            'id' => 'story_456',
        ]);

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'story',
            'content' => 'Instagram story',
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/story.jpg'],
            ],
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $job = new PublishToInstagramJob($scheduledPost);
        $job->handle();

        $this->assertEquals('published', $scheduledPost->fresh()->status);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockMetaAPI('error', [
            'error' => [
                'message' => 'Rate limit exceeded',
                'code' => 32,
            ],
        ]);

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'feed',
            'content' => 'Test post',
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $job = new PublishToInstagramJob($scheduledPost);
        $job->handle();

        $scheduledPost = $scheduledPost->fresh();
        $this->assertEquals('failed', $scheduledPost->status);
    }

    /** @test */
    public function it_retries_on_failure()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'feed',
            'content' => 'Retry test',
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $job = new PublishToInstagramJob($scheduledPost);

        // Check that job has retry configuration
        $this->assertTrue(method_exists($job, 'retryUntil') || property_exists($job, 'tries'));
    }

    /** @test */
    public function it_updates_post_metadata_after_publishing()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockMetaAPI('success', [
            'id' => 'ig_post_metadata_123',
            'permalink' => 'https://instagram.com/p/XYZ789',
        ]);

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'feed',
            'content' => 'Metadata test',
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $job = new PublishToInstagramJob($scheduledPost);
        $job->handle();

        $scheduledPost = $scheduledPost->fresh();
        $this->assertNotNull($scheduledPost->published_at);
        $this->assertNotNull($scheduledPost->metadata);
    }

    /** @test */
    public function it_validates_required_fields_before_publishing()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'platforms' => ['instagram'],
            'post_type' => 'feed',
            'content' => '', // Empty content
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $job = new PublishToInstagramJob($scheduledPost);
        $job->handle();

        $scheduledPost = $scheduledPost->fresh();
        $this->assertEquals('failed', $scheduledPost->status);
    }
}
