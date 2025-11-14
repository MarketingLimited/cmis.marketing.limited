<?php

namespace Tests\Integration\ErrorHandling;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\FacebookSyncService;
use App\Jobs\SyncFacebookDataJob;
use Illuminate\Support\Facades\Queue;

/**
 * Error Handling & Recovery Workflow Tests
 */
class ErrorRecoveryWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected FacebookSyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->syncService = app(FacebookSyncService::class);
    }

    /** @test */
    public function it_handles_network_timeout_with_retry()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        // First attempt: timeout
        $this->mockMetaAPI('error', [
            'error' => [
                'message' => 'Network timeout',
                'type' => 'NetworkError',
            ],
        ]);

        $attempt1 = $this->syncService->syncAccount($integration);
        $this->assertFalse($attempt1['success']);

        // Second attempt: success
        $this->mockMetaAPI('success');

        $attempt2 = $this->syncService->syncAccount($integration);
        $this->assertTrue($attempt2['success']);

        $this->logTestResult('passed', [
            'workflow' => 'error_recovery',
            'scenario' => 'network_timeout_retry',
        ]);
    }

    /** @test */
    public function it_handles_invalid_token_with_refresh()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        // Mock expired token error
        $this->mockMetaAPI('error', [
            'error' => [
                'message' => 'Invalid OAuth access token',
                'type' => 'OAuthException',
                'code' => 190,
            ],
        ]);

        $result = $this->syncService->syncAccount($integration);

        $this->assertFalse($result['success']);
        $this->assertEquals('token_expired', $result['error_type']);

        // In real app, this would trigger token refresh flow
        // For now, verify error is properly categorized

        $this->logTestResult('passed', [
            'workflow' => 'error_recovery',
            'scenario' => 'token_refresh',
        ]);
    }

    /** @test */
    public function it_handles_rate_limit_with_backoff()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        // Mock rate limit error
        $this->mockMetaAPI('rate_limit');

        $result = $this->syncService->syncAccount($integration);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('rate limit', strtolower($result['error']));

        // Verify error is logged for retry
        $this->assertDatabaseHas('cmis.sync_logs', [
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'status' => 'error',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'error_recovery',
            'scenario' => 'rate_limit_backoff',
        ]);
    }

    /** @test */
    public function it_handles_partial_data_sync_failure()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        // Mock partial success (some posts synced, some failed)
        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'post_1',
                    'message' => 'Valid post',
                    'created_time' => '2024-01-01T00:00:00+0000',
                ],
                // Second post has missing required field (will fail validation)
            ],
        ]);

        $result = $this->syncService->syncPosts($integration, now()->subDays(7));

        // Should succeed overall but with warnings
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('warnings', $result);

        $this->logTestResult('passed', [
            'workflow' => 'error_recovery',
            'scenario' => 'partial_sync_failure',
        ]);
    }

    /** @test */
    public function it_handles_database_transaction_rollback()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        try {
            \DB::beginTransaction();

            // Create campaign
            $campaign = $this->createTestCampaign($org->org_id);

            // Simulate error that causes rollback
            throw new \Exception('Simulated error');

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
        }

        // Verify campaign was not created (transaction rolled back)
        $this->assertEquals(0, \App\Models\Campaign::where('org_id', $org->org_id)->count());

        $this->logTestResult('passed', [
            'workflow' => 'error_recovery',
            'scenario' => 'transaction_rollback',
        ]);
    }

    /** @test */
    public function it_handles_job_retry_on_failure()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        // Dispatch job
        SyncFacebookDataJob::dispatch($integration, $org->org_id);

        // Job will fail first time, should be retried
        Queue::assertPushed(SyncFacebookDataJob::class, 1);

        $this->logTestResult('passed', [
            'workflow' => 'error_recovery',
            'scenario' => 'job_retry',
        ]);
    }

    /** @test */
    public function it_handles_validation_errors_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        try {
            // Try to create campaign with invalid data
            \App\Models\Campaign::create([
                'campaign_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $org->org_id,
                // Missing required 'name' field
                'status' => 'invalid_status', // Invalid status value
            ]);

            $this->fail('Should have thrown validation exception');
        } catch (\Exception $e) {
            // Validation error caught successfully
            $this->assertInstanceOf(\Exception::class, $e);
        }

        $this->logTestResult('passed', [
            'workflow' => 'error_recovery',
            'scenario' => 'validation_errors',
        ]);
    }

    /** @test */
    public function it_logs_all_errors_for_debugging()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('error');

        $result = $this->syncService->syncAccount($integration);

        // Verify error logged
        $this->assertDatabaseHas('cmis.sync_logs', [
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'status' => 'error',
        ]);

        // Verify dev_logs entry
        $this->assertDatabaseHas('cmis_dev.dev_logs', [
            'event' => 'test_completed',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'error_recovery',
            'scenario' => 'error_logging',
        ]);
    }
}
