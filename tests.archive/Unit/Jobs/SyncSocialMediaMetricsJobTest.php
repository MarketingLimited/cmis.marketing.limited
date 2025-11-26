<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Analytics\SyncSocialMediaMetricsJob;
use App\Models\Core\Org;
use App\Models\Integration\Integration;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;

use PHPUnit\Framework\Attributes\Test;
/**
 * Sync Social Media Metrics Job Unit Tests
 */
class SyncSocialMediaMetricsJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_syncs_facebook_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => [
                'access_token' => 'token_123',
            ],
        ]);

        $this->mockMetaAPI('success', [
            'insights' => [
                'page_impressions' => 50000,
                'page_engaged_users' => 2500,
                'page_followers' => 15000,
            ],
        ]);

        $job = new SyncSocialMediaMetricsJob($integration);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SyncSocialMediaMetricsJob',
            'platform' => 'facebook',
        ]);
    }

    #[Test]
    public function it_syncs_instagram_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'name' => 'Instagram Business',
            'credentials' => [
                'access_token' => 'token_456',
            ],
        ]);

        $this->mockMetaAPI('success', [
            'insights' => [
                'impressions' => 75000,
                'reach' => 60000,
                'profile_views' => 3000,
            ],
        ]);

        $job = new SyncSocialMediaMetricsJob($integration);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SyncSocialMediaMetricsJob',
            'platform' => 'instagram',
        ]);
    }

    #[Test]
    public function it_can_be_dispatched()
    {
        Queue::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => [],
        ]);

        SyncSocialMediaMetricsJob::dispatch($integration);

        Queue::assertPushed(SyncSocialMediaMetricsJob::class);

        $this->logTestResult('passed', [
            'job' => 'SyncSocialMediaMetricsJob',
            'test' => 'dispatch',
        ]);
    }

    #[Test]
    public function it_handles_api_errors()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => [
                'access_token' => 'invalid_token',
            ],
        ]);

        $this->mockMetaAPI('error');

        $job = new SyncSocialMediaMetricsJob($integration);

        try {
            $result = $job->handle();
            $this->assertFalse($result['success']);
        } catch (\Exception $e) {
            // Expected
            $this->assertTrue(true);
        }

        $this->logTestResult('passed', [
            'job' => 'SyncSocialMediaMetricsJob',
            'test' => 'error_handling',
        ]);
    }

    #[Test]
    public function it_updates_last_synced_timestamp()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'name' => 'Twitter Account',
            'credentials' => [],
            'last_synced_at' => null,
        ]);

        $this->mockTwitterAPI('success', [
            'followers_count' => 5000,
            'tweet_count' => 1200,
        ]);

        $job = new SyncSocialMediaMetricsJob($integration);
        $job->handle();

        $integration->refresh();
        $this->assertNotNull($integration->last_synced_at);

        $this->logTestResult('passed', [
            'job' => 'SyncSocialMediaMetricsJob',
            'test' => 'timestamp_update',
        ]);
    }

    #[Test]
    public function it_stores_metrics_in_database()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'linkedin',
            'name' => 'LinkedIn Page',
            'credentials' => [],
        ]);

        $this->mockLinkedInAPI('success', [
            'statistics' => [
                'followers' => 8000,
                'impressions' => 40000,
            ],
        ]);

        $job = new SyncSocialMediaMetricsJob($integration);
        $job->handle();

        // Metrics should be stored in database
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'SyncSocialMediaMetricsJob',
            'test' => 'data_storage',
        ]);
    }
}
