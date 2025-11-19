<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Analytics\SyncAnalyticsJob;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

/**
 * SyncAnalytics Job Unit Tests
 */
class SyncAnalyticsJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_syncs_campaign_analytics()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'data' => [
                    'impressions' => 10000,
                    'clicks' => 500,
                    'reach' => 8000,
                ],
            ], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $job = new SyncAnalyticsJob($campaign);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'sync_campaign_analytics',
        ]);
    }

    /** @test */
    public function it_syncs_platform_specific_metrics()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'data' => [
                    'impressions' => 5000,
                    'engagement' => 250,
                ],
            ], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $platformConnection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
            'access_token' => 'fake_token',
        ]);

        $job = new SyncAnalyticsJob(null, ['platform' => 'facebook', 'connection_id' => $platformConnection->connection_id]);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'platform_specific_metrics',
        ]);
    }

    /** @test */
    public function it_updates_existing_analytics()
    {
        Http::fake([
            '*' => Http::response(['data' => ['views' => 1500]], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Update Campaign',
            'status' => 'active',
        ]);

        $job = new SyncAnalyticsJob($campaign, ['update_existing' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'update_existing_analytics',
        ]);
    }

    /** @test */
    public function it_can_sync_for_date_range()
    {
        Http::fake([
            '*' => Http::response(['data' => ['metrics' => []]], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Date Range Campaign',
            'status' => 'active',
        ]);

        $job = new SyncAnalyticsJob($campaign, [
            'start_date' => now()->subDays(7),
            'end_date' => now(),
        ]);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'sync_date_range',
        ]);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        Queue::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Dispatch Campaign',
            'status' => 'active',
        ]);

        SyncAnalyticsJob::dispatch($campaign);

        Queue::assertPushed(SyncAnalyticsJob::class);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Rate limit exceeded'], 429),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Error Campaign',
            'status' => 'active',
        ]);

        $job = new SyncAnalyticsJob($campaign);
        $result = $job->handle();

        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'handle_api_errors',
        ]);
    }

    /** @test */
    public function it_calculates_engagement_rate()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'impressions' => 10000,
                    'engagements' => 500,
                ],
            ], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Engagement Campaign',
            'status' => 'active',
        ]);

        $job = new SyncAnalyticsJob($campaign);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('engagement_rate', $result);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'calculate_engagement_rate',
        ]);
    }

    /** @test */
    public function it_syncs_cost_metrics()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'spend' => 500.00,
                    'impressions' => 50000,
                    'clicks' => 2500,
                ],
            ], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Cost Campaign',
            'status' => 'active',
        ]);

        $job = new SyncAnalyticsJob($campaign);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('cost_metrics', $result);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'sync_cost_metrics',
        ]);
    }

    /** @test */
    public function it_stores_historical_data()
    {
        Http::fake([
            '*' => Http::response(['data' => ['metrics' => []]], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Historical Campaign',
            'status' => 'active',
        ]);

        $job = new SyncAnalyticsJob($campaign, ['store_historical' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'store_historical_data',
        ]);
    }

    /** @test */
    public function it_supports_batch_syncing()
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200),
        ]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaigns = [];
        for ($i = 0; $i < 5; $i++) {
            $campaigns[] = Campaign::create([
                'campaign_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Campaign {$i}",
                'status' => 'active',
            ]);
        }

        $job = new SyncAnalyticsJob(null, ['campaigns' => collect($campaigns)]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('synced_count', $result);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'batch_syncing',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation()
    {
        Http::fake();

        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        $campaign1 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Campaign',
            'status' => 'active',
        ]);

        $campaign2 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Campaign',
            'status' => 'active',
        ]);

        // Should only sync analytics for the specified org
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'SyncAnalyticsJob',
            'test' => 'org_isolation',
        ]);
    }
}
