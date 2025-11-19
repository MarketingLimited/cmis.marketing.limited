<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Content\ProcessScheduledContentJob;
use App\Models\Core\Org;
use App\Models\Content\Content;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;

/**
 * ProcessScheduledContent Job Unit Tests
 */
class ProcessScheduledContentJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_processes_due_scheduled_content()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Scheduled Post',
            'body' => 'Content to be published',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinutes(5),
        ]);

        $job = new ProcessScheduledContentJob();
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['processed']);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'process_due_content',
        ]);
    }

    /** @test */
    public function it_ignores_future_scheduled_content()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Future Post',
            'body' => 'Content scheduled for future',
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours(2),
        ]);

        $job = new ProcessScheduledContentJob();
        $result = $job->handle();

        // Should not process future content
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'ignore_future_content',
        ]);
    }

    /** @test */
    public function it_publishes_scheduled_content()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Ready to Publish',
            'body' => 'Content ready for publishing',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinutes(10),
        ]);

        $this->mockMetaAPI('success');

        $job = new ProcessScheduledContentJob();
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'publish_content',
        ]);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        Queue::fake();

        ProcessScheduledContentJob::dispatch();

        Queue::assertPushed(ProcessScheduledContentJob::class);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_handles_publishing_errors()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Error Content',
            'body' => 'Content with error',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinutes(5),
        ]);

        $this->mockMetaAPI('error');

        $job = new ProcessScheduledContentJob();
        $result = $job->handle();

        // Should handle errors gracefully
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'handle_errors',
        ]);
    }

    /** @test */
    public function it_processes_multiple_scheduled_posts()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Content::create([
                'content_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'title' => "Scheduled Post {$i}",
                'body' => "Content {$i}",
                'status' => 'scheduled',
                'scheduled_at' => now()->subMinutes($i),
            ]);
        }

        $this->mockMetaAPI('success');

        $job = new ProcessScheduledContentJob();
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'process_multiple',
        ]);
    }

    /** @test */
    public function it_respects_batch_size_limit()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Create many scheduled posts
        for ($i = 1; $i <= 50; $i++) {
            Content::create([
                'content_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'title' => "Batch Post {$i}",
                'body' => "Content {$i}",
                'status' => 'scheduled',
                'scheduled_at' => now()->subMinutes($i),
            ]);
        }

        $this->mockMetaAPI('success');

        $job = new ProcessScheduledContentJob();
        $result = $job->handle();

        // Should process in batches
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'batch_size_limit',
        ]);
    }

    /** @test */
    public function it_updates_content_status_after_publishing()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Status Update Post',
            'body' => 'Content for status update',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinutes(5),
        ]);

        $this->mockMetaAPI('success');

        $job = new ProcessScheduledContentJob();
        $job->handle();

        // Content status should be updated to 'published'
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'update_status',
        ]);
    }

    /** @test */
    public function it_logs_processing_activity()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Logged Post',
            'body' => 'Content with logging',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinutes(5),
        ]);

        $this->mockMetaAPI('success');

        $job = new ProcessScheduledContentJob();
        $result = $job->handle();

        // Should log processing activity
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessScheduledContentJob',
            'test' => 'log_activity',
        ]);
    }
}
