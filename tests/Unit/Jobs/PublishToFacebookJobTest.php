<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\PublishToFacebookJob;
use App\Models\Social\ScheduledSocialPost;
use Illuminate\Support\Str;

/**
 * Publish To Facebook Job Unit Tests
 */
class PublishToFacebookJobTest extends TestCase
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

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => ['message' => 'Test post'],
            'scheduled_at' => now()->addHours(1),
            'status' => 'pending',
        ]);

        PublishToFacebookJob::dispatch($scheduledPost);

        Queue::assertPushed(PublishToFacebookJob::class);

        $this->logTestResult('passed', [
            'job' => 'PublishToFacebookJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_publishes_post_to_facebook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'content' => ['message' => 'اختبار النشر على فيسبوك'],
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);

        $this->mockMetaAPI('success', [
            'id' => 'fb_post_123',
        ]);

        $job = new PublishToFacebookJob($scheduledPost);
        $job->handle();

        $scheduledPost->refresh();
        $this->assertEquals('published', $scheduledPost->status);

        $this->logTestResult('passed', [
            'job' => 'PublishToFacebookJob',
            'test' => 'publish',
        ]);
    }

    /** @test */
    public function it_handles_publishing_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'content' => ['message' => 'Test'],
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);

        $this->mockMetaAPI('error');

        $job = new PublishToFacebookJob($scheduledPost);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected
        }

        $scheduledPost->refresh();
        $this->assertEquals('failed', $scheduledPost->status);

        $this->logTestResult('passed', [
            'job' => 'PublishToFacebookJob',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_retries_on_failure()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => ['message' => 'Test'],
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);

        $job = new PublishToFacebookJob($scheduledPost);

        $this->assertEquals(3, $job->tries);

        $this->logTestResult('passed', [
            'job' => 'PublishToFacebookJob',
            'test' => 'retry_logic',
        ]);
    }

    /** @test */
    public function it_updates_post_metadata_on_success()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'content' => ['message' => 'Test'],
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);

        $this->mockMetaAPI('success', [
            'id' => 'fb_post_456',
        ]);

        $job = new PublishToFacebookJob($scheduledPost);
        $job->handle();

        $scheduledPost->refresh();
        $this->assertNotNull($scheduledPost->published_at);
        $this->assertEquals('fb_post_456', $scheduledPost->external_post_id);

        $this->logTestResult('passed', [
            'job' => 'PublishToFacebookJob',
            'test' => 'metadata_update',
        ]);
    }

    /** @test */
    public function it_validates_post_content_before_publishing()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $scheduledPost = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => [], // Empty content
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);

        $job = new PublishToFacebookJob($scheduledPost);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected
        }

        $scheduledPost->refresh();
        $this->assertEquals('failed', $scheduledPost->status);

        $this->logTestResult('passed', [
            'job' => 'PublishToFacebookJob',
            'test' => 'validation',
        ]);
    }
}
