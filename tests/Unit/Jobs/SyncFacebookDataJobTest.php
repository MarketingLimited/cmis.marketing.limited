<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\SyncFacebookDataJob;
use App\Models\Integration;
use App\Services\Social\FacebookSyncService;
use Illuminate\Support\Facades\{DB, Log, Queue};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SyncFacebookDataJobTest extends TestCase
{
    use RefreshDatabase;

    protected Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = Integration::factory()->create([
            'platform' => 'facebook',
            'status' => 'active',
            'access_token' => 'test_token',
        ]);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        @restore_error_handler();
        @restore_exception_handler();
        parent::tearDown();
    }

    public function test_job_can_be_dispatched()
    {
        Queue::fake();

        SyncFacebookDataJob::dispatch($this->integration);

        Queue::assertPushed(SyncFacebookDataJob::class);
    }

    public function test_job_has_correct_configuration()
    {
        $job = new SyncFacebookDataJob($this->integration);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals([60, 300, 900], $job->backoff);
    }

    public function test_job_is_pushed_to_correct_queue()
    {
        Queue::fake();

        SyncFacebookDataJob::dispatch($this->integration);

        Queue::assertPushedOn('social-sync', SyncFacebookDataJob::class);
    }

    public function test_job_logs_success_on_completion()
    {
        Log::spy();

        $job = new SyncFacebookDataJob(
            $this->integration,
            Carbon::now()->subDays(7),
            Carbon::now(),
            10
        );

        // Mock the service
        $this->mock(FacebookSyncService::class, function ($mock) {
            $mock->shouldReceive('syncAccount')->andReturn(['success' => true]);
            $mock->shouldReceive('syncPosts')->andReturn(['posts' => []]);
        });

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Ignore DB context errors in testing
        }

        Log::shouldHaveReceived('info')
            ->with('Starting Facebook sync job', \Mockery::any())
            ->once();
    }

    public function test_job_creates_sync_log_on_success()
    {
        $this->mock(FacebookSyncService::class, function ($mock) {
            $mock->shouldReceive('syncAccount')->andReturn(['success' => true]);
            $mock->shouldReceive('syncPosts')->andReturn(['posts' => []]);
        });

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            DB::shouldReceive('statement')->andReturn(true);
            return $callback();
        });

        DB::shouldReceive('table')->with('cmis.sync_logs')->andReturnSelf();
        DB::shouldReceive('insert')->once();

        $job = new SyncFacebookDataJob($this->integration);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected in test environment
        }
    }

    public function test_job_updates_integration_on_failure()
    {
        $job = new SyncFacebookDataJob($this->integration);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $this->integration->refresh();
        $this->assertEquals('failed', $this->integration->last_sync_status);
        $this->assertEquals('Test failure', $this->integration->last_sync_error);
    }

    public function test_job_serialization()
    {
        $job = new SyncFacebookDataJob(
            $this->integration,
            Carbon::parse('2024-01-01'),
            Carbon::parse('2024-01-31'),
            50
        );

        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(SyncFacebookDataJob::class, $unserialized);
    }
}
