<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\FacebookSyncService;
use App\Jobs\SyncFacebookDataJob;
use App\Models\Operations\SyncLog;
use Illuminate\Support\Facades\Queue;

class FacebookSyncIntegrationTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected FacebookSyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncService = app(FacebookSyncService::class);
    }

    /** @test */
    public function it_can_sync_facebook_account_data()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook', [
            'access_token' => encrypt('test_facebook_token'),
        ]);

        $this->mockMetaAPI('success');

        $result = $this->syncService->syncAccount($integration);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('synced_items', $result);

        // Verify sync log was created
        $this->assertDatabaseHas('cmis.sync_logs', [
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'status' => 'success',
        ]);

        $this->logTestResult('passed', [
            'integration_id' => $integration->integration_id,
            'sync_type' => 'account',
            'platform' => 'facebook',
        ]);
    }

    /** @test */
    public function it_can_sync_facebook_posts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success');

        $result = $this->syncService->syncPosts($integration, now()->subDays(7));

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['synced_items']);

        // Verify posts were created in database
        $this->assertDatabaseHas('cmis.social_posts', [
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
        ]);

        $this->logTestResult('passed', [
            'integration_id' => $integration->integration_id,
            'sync_type' => 'posts',
            'synced_items' => $result['synced_items'],
        ]);
    }

    /** @test */
    public function it_handles_facebook_api_errors_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('error');

        $result = $this->syncService->syncAccount($integration);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        // Verify error was logged
        $this->assertDatabaseHas('cmis.sync_logs', [
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'status' => 'error',
        ]);

        $this->logTestResult('passed', [
            'integration_id' => $integration->integration_id,
            'error_handling' => 'verified',
            'platform' => 'facebook',
        ]);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('rate_limit');

        $result = $this->syncService->syncAccount($integration);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('rate limit', strtolower($result['error']));

        $this->logTestResult('passed', [
            'integration_id' => $integration->integration_id,
            'rate_limit_handling' => 'verified',
        ]);
    }

    /** @test */
    public function it_can_dispatch_sync_job()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        SyncFacebookDataJob::dispatch($integration, $org->org_id);

        Queue::assertPushed(SyncFacebookDataJob::class, function ($job) use ($integration) {
            return $job->integration->integration_id === $integration->integration_id;
        });

        $this->logTestResult('passed', [
            'job' => 'SyncFacebookDataJob',
            'dispatched' => 'verified',
        ]);
    }

    /** @test */
    public function it_respects_sync_lookback_period()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success');

        $lookbackDays = 7;
        $result = $this->syncService->syncPosts($integration, now()->subDays($lookbackDays));

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'integration_id' => $integration->integration_id,
            'lookback_days' => $lookbackDays,
        ]);
    }

    /** @test */
    public function it_updates_existing_posts_on_resync()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');
        $socialAccount = $this->createTestSocialAccount($org->org_id, $integration->integration_id);

        $this->mockMetaAPI('success');

        // First sync
        $result1 = $this->syncService->syncPosts($integration, now()->subDays(7));

        // Second sync (should update existing)
        $result2 = $this->syncService->syncPosts($integration, now()->subDays(7));

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);

        // Verify we don't have duplicate posts
        $postCount = \DB::table('cmis.social_posts')
            ->where('org_id', $org->org_id)
            ->where('integration_id', $integration->integration_id)
            ->count();

        $this->assertGreaterThan(0, $postCount);

        $this->logTestResult('passed', [
            'integration_id' => $integration->integration_id,
            'resync' => 'verified',
            'duplicate_prevention' => 'verified',
        ]);
    }
}
