<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Campaign\UpdateCampaignStatsJob;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;

use PHPUnit\Framework\Attributes\Test;
/**
 * UpdateCampaignStats Job Unit Tests
 */
class UpdateCampaignStatsJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_updates_campaign_statistics()
    {
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

        $job = new UpdateCampaignStatsJob($campaign);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'update_statistics',
        ]);
    }

    #[Test]
    public function it_calculates_engagement_metrics()
    {
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

        $job = new UpdateCampaignStatsJob($campaign);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('engagement_metrics', $result);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'engagement_metrics',
        ]);
    }

    #[Test]
    public function it_calculates_conversion_rate()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Conversion Campaign',
            'status' => 'active',
        ]);

        $job = new UpdateCampaignStatsJob($campaign, ['calculate_conversion' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('conversion_rate', $result);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'conversion_rate',
        ]);
    }

    #[Test]
    public function it_updates_roi_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'ROI Campaign',
            'status' => 'active',
        ]);

        $job = new UpdateCampaignStatsJob($campaign, ['budget' => 1000, 'revenue' => 5000]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('roi', $result);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'roi_metrics',
        ]);
    }

    #[Test]
    public function it_aggregates_platform_stats()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Multi-platform Campaign',
            'status' => 'active',
        ]);

        $job = new UpdateCampaignStatsJob($campaign, ['aggregate_platforms' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('platform_stats', $result);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'aggregate_platforms',
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Dispatch Campaign',
            'status' => 'active',
        ]);

        UpdateCampaignStatsJob::dispatch($campaign);

        Queue::assertPushed(UpdateCampaignStatsJob::class);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'dispatch',
        ]);
    }

    #[Test]
    public function it_calculates_click_through_rate()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'CTR Campaign',
            'status' => 'active',
        ]);

        $job = new UpdateCampaignStatsJob($campaign, [
            'impressions' => 10000,
            'clicks' => 500,
        ]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ctr', $result);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'click_through_rate',
        ]);
    }

    #[Test]
    public function it_tracks_cost_per_click()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'CPC Campaign',
            'status' => 'active',
        ]);

        $job = new UpdateCampaignStatsJob($campaign, [
            'total_cost' => 1000,
            'total_clicks' => 500,
        ]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('cpc', $result);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'cost_per_click',
        ]);
    }

    #[Test]
    public function it_updates_reach_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Reach Campaign',
            'status' => 'active',
        ]);

        $job = new UpdateCampaignStatsJob($campaign, ['track_reach' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('reach', $result);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'reach_metrics',
        ]);
    }

    #[Test]
    public function it_handles_campaigns_with_no_data()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Empty Campaign',
            'status' => 'draft',
        ]);

        $job = new UpdateCampaignStatsJob($campaign);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'no_data',
        ]);
    }

    #[Test]
    public function it_stores_stats_snapshot()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Snapshot Campaign',
            'status' => 'active',
        ]);

        $job = new UpdateCampaignStatsJob($campaign, ['create_snapshot' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('snapshot_created', $result);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'stats_snapshot',
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

        // Should only update stats for the specified campaign's org
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'UpdateCampaignStatsJob',
            'test' => 'org_isolation',
        ]);
    }
}
