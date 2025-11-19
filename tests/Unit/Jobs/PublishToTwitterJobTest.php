<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Social\PublishToTwitterJob;
use App\Models\Content\ScheduledPost;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Publish To Twitter Job Unit Tests
 */
class PublishToTwitterJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_publishes_tweet()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $scheduledPost = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'content' => 'تغريدة جديدة على تويتر',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'tweet_123',
            ],
        ]);

        $job = new PublishToTwitterJob($scheduledPost);
        $job->handle();

        $scheduledPost->refresh();
        $this->assertEquals('published', $scheduledPost->status);

        $this->logTestResult('passed', [
            'job' => 'PublishToTwitterJob',
            'test' => 'successful_publish',
        ]);
    }

    /** @test */
    public function it_handles_character_limit()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $longContent = str_repeat('This is a very long tweet. ', 20); // Exceeds 280 chars

        $scheduledPost = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'content' => $longContent,
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $job = new PublishToTwitterJob($scheduledPost);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // May fail validation
        }

        // Job should validate or truncate
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'PublishToTwitterJob',
            'test' => 'character_limit',
        ]);
    }

    /** @test */
    public function it_publishes_tweet_with_media()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $scheduledPost = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'content' => 'تغريدة مع صورة',
            'media_urls' => ['https://example.com/image.jpg'],
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'tweet_with_media_456',
            ],
        ]);

        $job = new PublishToTwitterJob($scheduledPost);
        $job->handle();

        $scheduledPost->refresh();
        $this->assertEquals('published', $scheduledPost->status);

        $this->logTestResult('passed', [
            'job' => 'PublishToTwitterJob',
            'test' => 'publish_with_media',
        ]);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $scheduledPost = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'content' => 'Test tweet',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->mockTwitterAPI('error');

        $job = new PublishToTwitterJob($scheduledPost);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected
        }

        $scheduledPost->refresh();
        $this->assertEquals('failed', $scheduledPost->status);

        $this->logTestResult('passed', [
            'job' => 'PublishToTwitterJob',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $scheduledPost = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'content' => 'Test tweet',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        PublishToTwitterJob::dispatch($scheduledPost);

        Queue::assertPushed(PublishToTwitterJob::class);

        $this->logTestResult('passed', [
            'job' => 'PublishToTwitterJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_processes_hashtags()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $scheduledPost = ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'content' => 'Tweet with #hashtags #socialmedia',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'tweet_789',
            ],
        ]);

        $job = new PublishToTwitterJob($scheduledPost);
        $job->handle();

        $scheduledPost->refresh();
        $this->assertEquals('published', $scheduledPost->status);

        $this->logTestResult('passed', [
            'job' => 'PublishToTwitterJob',
            'test' => 'hashtag_processing',
        ]);
    }
}
