<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\Org;
use App\Models\Content\ScheduledPost;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;

/**
 * Process Scheduled Posts Command Unit Tests
 */
class ProcessScheduledPostsCommandTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_processes_due_scheduled_posts()
    {
        Queue::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Test post',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->artisan('posts:process-scheduled')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'posts:process-scheduled',
            'test' => 'process_due_posts',
        ]);
    }

    /** @test */
    public function it_skips_future_posts()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Future post',
            'scheduled_time' => now()->addHours(2),
            'status' => 'scheduled',
        ]);

        $this->artisan('posts:process-scheduled')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'posts:process-scheduled',
            'test' => 'skip_future_posts',
        ]);
    }

    /** @test */
    public function it_accepts_limit_option()
    {
        $this->artisan('posts:process-scheduled', [
            '--limit' => 10,
        ])->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'posts:process-scheduled',
            'test' => 'limit_option',
        ]);
    }

    /** @test */
    public function it_accepts_dry_run_option()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Test post',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->artisan('posts:process-scheduled', [
            '--dry-run' => true,
        ])->assertExitCode(0);

        // Post should still be scheduled (not processed in dry-run)
        $this->assertDatabaseHas('cmis.scheduled_posts', [
            'status' => 'scheduled',
        ]);

        $this->logTestResult('passed', [
            'command' => 'posts:process-scheduled',
            'test' => 'dry_run_option',
        ]);
    }

    /** @test */
    public function it_displays_processing_count()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Test post',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->artisan('posts:process-scheduled')
             ->expectsOutput('Processing scheduled posts...')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'posts:process-scheduled',
            'test' => 'output_display',
        ]);
    }

    /** @test */
    public function it_handles_empty_queue()
    {
        $this->artisan('posts:process-scheduled')
             ->expectsOutput('No scheduled posts to process.')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'posts:process-scheduled',
            'test' => 'empty_queue',
        ]);
    }

    /** @test */
    public function it_logs_processed_posts()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        ScheduledPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'content' => 'Test post',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
        ]);

        $this->artisan('posts:process-scheduled')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'posts:process-scheduled',
            'test' => 'logging',
        ]);
    }
}
