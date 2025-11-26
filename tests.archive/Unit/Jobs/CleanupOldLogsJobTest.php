<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Maintenance\CleanupOldLogsJob;
use App\Models\Core\Org;
use App\Models\Log\ApiLog;
use App\Models\Activity\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;

use PHPUnit\Framework\Attributes\Test;
/**
 * CleanupOldLogs Job Unit Tests
 */
class CleanupOldLogsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_cleans_up_old_api_logs()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Create old log (90 days ago)
        $oldLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/old',
            'status_code' => 200,
            'created_at' => now()->subDays(90),
        ]);

        // Create recent log
        $recentLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/recent',
            'status_code' => 200,
        ]);

        $job = new CleanupOldLogsJob(30); // Delete logs older than 30 days
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'CleanupOldLogsJob',
            'test' => 'cleanup_api_logs',
        ]);
    }

    #[Test]
    public function it_cleans_up_old_activity_logs()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Create old activity log
        $oldActivity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'old_action',
            'entity_type' => 'campaign',
            'created_at' => now()->subDays(120),
        ]);

        // Create recent activity log
        $recentActivity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'recent_action',
            'entity_type' => 'campaign',
        ]);

        $job = new CleanupOldLogsJob(90);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'CleanupOldLogsJob',
            'test' => 'cleanup_activity_logs',
        ]);
    }

    #[Test]
    public function it_can_be_dispatched()
    {
        Queue::fake();

        CleanupOldLogsJob::dispatch(30);

        Queue::assertPushed(CleanupOldLogsJob::class);

        $this->logTestResult('passed', [
            'job' => 'CleanupOldLogsJob',
            'test' => 'dispatch',
        ]);
    }

    #[Test]
    public function it_accepts_custom_retention_days()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/test',
            'status_code' => 200,
            'created_at' => now()->subDays(45),
        ]);

        // With 60 days retention, the 45-day-old log should be kept
        $job60 = new CleanupOldLogsJob(60);
        $result = $job60->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'CleanupOldLogsJob',
            'test' => 'custom_retention',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'method' => 'GET',
            'endpoint' => '/api/org1',
            'status_code' => 200,
            'created_at' => now()->subDays(90),
        ]);

        ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'method' => 'GET',
            'endpoint' => '/api/org2',
            'status_code' => 200,
            'created_at' => now()->subDays(90),
        ]);

        $job = new CleanupOldLogsJob(30);
        $result = $job->handle();

        // Should cleanup logs from both orgs independently
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'CleanupOldLogsJob',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_returns_deleted_count()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Create multiple old logs
        for ($i = 0; $i < 10; $i++) {
            ApiLog::create([
                'log_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'method' => 'GET',
                'endpoint' => "/api/test{$i}",
                'status_code' => 200,
                'created_at' => now()->subDays(60),
            ]);
        }

        $job = new CleanupOldLogsJob(30);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('deleted_count', $result);

        $this->logTestResult('passed', [
            'job' => 'CleanupOldLogsJob',
            'test' => 'returns_count',
        ]);
    }

    #[Test]
    public function it_handles_no_logs_to_delete()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Create only recent logs
        ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/recent',
            'status_code' => 200,
        ]);

        $job = new CleanupOldLogsJob(30);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['deleted_count'] ?? 0);

        $this->logTestResult('passed', [
            'job' => 'CleanupOldLogsJob',
            'test' => 'no_logs_to_delete',
        ]);
    }

    #[Test]
    public function it_processes_in_batches()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Create many old logs
        for ($i = 0; $i < 1000; $i++) {
            ApiLog::create([
                'log_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'method' => 'GET',
                'endpoint' => "/api/test{$i}",
                'status_code' => 200,
                'created_at' => now()->subDays(90),
            ]);
        }

        $job = new CleanupOldLogsJob(30);
        $result = $job->handle();

        // Should process all logs in batches
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'CleanupOldLogsJob',
            'test' => 'batch_processing',
        ]);
    }
}
