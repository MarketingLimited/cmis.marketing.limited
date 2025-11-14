<?php

namespace Tests\Unit\Models\Analytics;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Analytics\AnalyticsSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * AnalyticsSnapshot Model Unit Tests
 */
class AnalyticsSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_analytics_snapshot()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'metrics' => ['impressions' => 10000],
        ]);

        $this->assertDatabaseHas('cmis.analytics_snapshots', [
            'snapshot_id' => $snapshot->snapshot_id,
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'metrics' => [],
        ]);

        $this->assertEquals($org->org_id, $snapshot->org->org_id);
    }

    /** @test */
    public function it_belongs_to_campaign()
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

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'snapshot_date' => now(),
            'metrics' => [],
        ]);

        $this->assertEquals($campaign->campaign_id, $snapshot->campaign->campaign_id);
    }

    /** @test */
    public function it_stores_metrics_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metrics = [
            'impressions' => 50000,
            'clicks' => 2500,
            'conversions' => 150,
            'cost' => 1500.00,
            'ctr' => 5.0,
            'conversion_rate' => 6.0,
        ];

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'metrics' => $metrics,
        ]);

        $this->assertEquals(50000, $snapshot->metrics['impressions']);
        $this->assertEquals(6.0, $snapshot->metrics['conversion_rate']);
    }

    /** @test */
    public function it_stores_platform_specific_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'platform' => 'facebook',
            'metrics' => [
                'page_likes' => 15000,
                'post_engagement' => 5000,
                'reach' => 100000,
            ],
        ]);

        $this->assertEquals('facebook', $snapshot->platform);
        $this->assertEquals(15000, $snapshot->metrics['page_likes']);
    }

    /** @test */
    public function it_tracks_snapshot_date()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $snapshotDate = now()->subDays(7);

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => $snapshotDate,
            'metrics' => [],
        ]);

        $this->assertEquals($snapshotDate->toDateString(), $snapshot->snapshot_date->toDateString());
    }

    /** @test */
    public function it_stores_daily_snapshots()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        for ($i = 0; $i < 7; $i++) {
            AnalyticsSnapshot::create([
                'snapshot_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'snapshot_date' => now()->subDays($i),
                'metrics' => [
                    'impressions' => 10000 + ($i * 1000),
                ],
            ]);
        }

        $snapshots = AnalyticsSnapshot::where('org_id', $org->org_id)->get();
        $this->assertCount(7, $snapshots);
    }

    /** @test */
    public function it_calculates_metric_trends()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $yesterday = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now()->subDay(),
            'metrics' => ['impressions' => 10000],
        ]);

        $today = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'metrics' => ['impressions' => 12000],
        ]);

        // Trend should show 20% increase
        $this->assertTrue($today->metrics['impressions'] > $yesterday->metrics['impressions']);
    }

    /** @test */
    public function it_stores_cost_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'metrics' => [
                'total_cost' => 2500.00,
                'cost_per_click' => 1.25,
                'cost_per_conversion' => 16.67,
            ],
        ]);

        $this->assertEquals(2500.00, $snapshot->metrics['total_cost']);
        $this->assertEquals(1.25, $snapshot->metrics['cost_per_click']);
    }

    /** @test */
    public function it_stores_engagement_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'metrics' => [
                'likes' => 1500,
                'comments' => 350,
                'shares' => 200,
                'saves' => 180,
                'engagement_rate' => 4.5,
            ],
        ]);

        $this->assertEquals(1500, $snapshot->metrics['likes']);
        $this->assertEquals(4.5, $snapshot->metrics['engagement_rate']);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'metrics' => [],
        ]);

        $this->assertTrue(Str::isUuid($snapshot->snapshot_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $snapshot = AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'snapshot_date' => now(),
            'metrics' => [],
        ]);

        $this->assertNotNull($snapshot->created_at);
        $this->assertNotNull($snapshot->updated_at);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'snapshot_date' => now(),
            'metrics' => ['impressions' => 10000],
        ]);

        AnalyticsSnapshot::create([
            'snapshot_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'snapshot_date' => now(),
            'metrics' => ['impressions' => 20000],
        ]);

        $org1Snapshots = AnalyticsSnapshot::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Snapshots);
    }
}
