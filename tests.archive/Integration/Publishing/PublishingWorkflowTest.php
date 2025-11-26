<?php

namespace Tests\Integration\Publishing;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\PublishingService;
use App\Services\PublishingQueueService;
use App\Jobs\PublishScheduledPostJob;
use Illuminate\Support\Facades\Queue;

use PHPUnit\Framework\Attributes\Test;
class PublishingWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected PublishingService $publishingService;
    protected PublishingQueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishingService = app(PublishingService::class);
        $this->queueService = app(PublishingQueueService::class);
    }

    #[Test]
    public function it_can_create_a_publishing_queue()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $socialAccount = $this->createTestSocialAccount($org->org_id);

        $queueConfig = [
            'social_account_id' => $socialAccount->id,
            'weekdays_enabled' => '1111100', // Mon-Fri
            'time_slots' => [
                ['time' => '09:00', 'enabled' => true],
                ['time' => '14:00', 'enabled' => true],
                ['time' => '18:00', 'enabled' => true],
            ],
            'timezone' => 'Asia/Bahrain',
        ];

        $queue = $this->queueService->createQueue($org->org_id, $queueConfig);

        $this->assertNotNull($queue);
        $this->assertEquals($socialAccount->id, $queue->social_account_id);
        $this->assertEquals('1111100', $queue->weekdays_enabled);

        $this->assertDatabaseHasWithRLS('cmis.publishing_queues', [
            'queue_id' => $queue->queue_id,
            'org_id' => $org->org_id,
        ]);

        $this->logTestResult('passed', [
            'queue_id' => $queue->queue_id,
            'time_slots_count' => count($queueConfig['time_slots']),
        ]);
    }

    #[Test]
    public function it_can_schedule_a_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $scheduledPost = $this->createTestScheduledPost($org->org_id, $user->user_id, [
            'content' => 'Test scheduled post',
            'platforms' => ['facebook', 'instagram'],
            'scheduled_at' => now()->addHours(2),
            'status' => 'scheduled',
        ]);

        $this->assertNotNull($scheduledPost);
        $this->assertEquals('scheduled', $scheduledPost->status);

        $this->assertDatabaseHasWithRLS('cmis.scheduled_social_posts', [
            'id' => $scheduledPost->id,
            'org_id' => $org->org_id,
            'status' => 'scheduled',
        ]);

        $this->logTestResult('passed', [
            'post_id' => $scheduledPost->id,
            'scheduled_at' => $scheduledPost->scheduled_at,
        ]);
    }

    #[Test]
    public function it_dispatches_publish_job_at_scheduled_time()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $scheduledPost = $this->createTestScheduledPost($org->org_id, $user->user_id, [
            'scheduled_at' => now()->addMinutes(5),
        ]);

        PublishScheduledPostJob::dispatch($scheduledPost);

        Queue::assertPushed(PublishScheduledPostJob::class, function ($job) use ($scheduledPost) {
            return $job->post->id === $scheduledPost->id;
        });

        $this->logTestResult('passed', [
            'job' => 'PublishScheduledPostJob',
            'post_id' => $scheduledPost->id,
        ]);
    }

    #[Test]
    public function it_publishes_post_to_multiple_platforms()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->mockAllAPIs();

        $scheduledPost = $this->createTestScheduledPost($org->org_id, $user->user_id, [
            'platforms' => ['facebook', 'instagram', 'twitter'],
            'content' => 'Multi-platform test post',
        ]);

        $result = $this->publishingService->publishPost($scheduledPost);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('published_ids', $result);
        $this->assertCount(3, $result['published_ids']);

        // Verify post status updated
        $scheduledPost = $scheduledPost->fresh();
        $this->assertEquals('published', $scheduledPost->status);

        $this->logTestResult('passed', [
            'post_id' => $scheduledPost->id,
            'platforms' => ['facebook', 'instagram', 'twitter'],
            'published_count' => count($result['published_ids']),
        ]);
    }

    #[Test]
    public function it_handles_publishing_failures_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->mockMetaAPI('error');

        $scheduledPost = $this->createTestScheduledPost($org->org_id, $user->user_id, [
            'platforms' => ['facebook'],
        ]);

        $result = $this->publishingService->publishPost($scheduledPost);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        // Verify post status updated to failed
        $scheduledPost = $scheduledPost->fresh();
        $this->assertEquals('failed', $scheduledPost->status);
        $this->assertNotNull($scheduledPost->error_message);

        $this->logTestResult('passed', [
            'post_id' => $scheduledPost->id,
            'error_handling' => 'verified',
            'status' => 'failed',
        ]);
    }

    #[Test]
    public function it_calculates_next_available_time_slot()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $publishingQueue = $this->createTestPublishingQueue($org->org_id);

        $nextSlot = $this->queueService->getNextAvailableSlot(
            $publishingQueue->queue_id,
            now()
        );

        $this->assertNotNull($nextSlot);
        $this->assertArrayHasKey('time', $nextSlot);
        $this->assertArrayHasKey('date', $nextSlot);

        $this->logTestResult('passed', [
            'queue_id' => $publishingQueue->queue_id,
            'next_slot' => $nextSlot,
        ]);
    }

    #[Test]
    public function it_validates_time_slots_configuration()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $socialAccount = $this->createTestSocialAccount($org->org_id);

        $invalidConfig = [
            'social_account_id' => $socialAccount->id,
            'weekdays_enabled' => '1111100',
            'time_slots' => [
                ['time' => '25:00', 'enabled' => true], // Invalid time
            ],
            'timezone' => 'Asia/Bahrain',
        ];

        $this->expectException(\InvalidArgumentException::class);

        $this->queueService->createQueue($org->org_id, $invalidConfig);
    }

    #[Test]
    public function it_enforces_one_queue_per_social_account()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $publishingQueue = $this->createTestPublishingQueue($org->org_id);

        // Try to create another queue for the same social account
        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->createTestPublishingQueue($org->org_id, $publishingQueue->social_account_id);
    }

    #[Test]
    public function it_can_update_post_status_after_publishing()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->mockMetaAPI('success');

        $scheduledPost = $this->createTestScheduledPost($org->org_id, $user->user_id);

        $this->publishingService->publishPost($scheduledPost);

        $scheduledPost = $scheduledPost->fresh();

        $this->assertEquals('published', $scheduledPost->status);
        $this->assertNotNull($scheduledPost->published_at);
        $this->assertIsArray($scheduledPost->published_ids);

        $this->logTestResult('passed', [
            'post_id' => $scheduledPost->id,
            'status_update' => 'verified',
        ]);
    }
}
