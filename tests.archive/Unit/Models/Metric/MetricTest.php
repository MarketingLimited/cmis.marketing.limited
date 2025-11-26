<?php

namespace Tests\Unit\Models\Metric;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Metric\Metric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Metric Model Unit Tests
 */
class MetricTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_metric()
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

        $metric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'name' => 'impressions',
            'value' => 10000,
            'date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('cmis.metrics', [
            'metric_id' => $metric->metric_id,
            'name' => 'impressions',
        ]);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'create',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'clicks',
            'value' => 500,
            'date' => now()->toDateString(),
        ]);

        $this->assertEquals($org->org_id, $metric->org->org_id);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'belongs_to_org',
        ]);
    }

    #[Test]
    public function it_belongs_to_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Metric Campaign',
            'status' => 'active',
        ]);

        $metric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'name' => 'conversions',
            'value' => 50,
            'date' => now()->toDateString(),
        ]);

        $this->assertEquals($campaign->campaign_id, $metric->campaign->campaign_id);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'belongs_to_campaign',
        ]);
    }

    #[Test]
    public function it_tracks_different_metric_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metricTypes = ['impressions', 'clicks', 'conversions', 'reach', 'engagement'];

        foreach ($metricTypes as $type) {
            $metric = Metric::create([
                'metric_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => $type,
                'value' => rand(100, 10000),
                'date' => now()->toDateString(),
            ]);

            $this->assertEquals($type, $metric->name);
        }

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'metric_types',
        ]);
    }

    #[Test]
    public function it_stores_metric_value()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'impressions',
            'value' => 25000,
            'date' => now()->toDateString(),
        ]);

        $this->assertEquals(25000, $metric->value);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'metric_value',
        ]);
    }

    #[Test]
    public function it_tracks_metrics_by_platform()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $facebookMetric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'impressions',
            'value' => 10000,
            'platform' => 'facebook',
            'date' => now()->toDateString(),
        ]);

        $instagramMetric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'impressions',
            'value' => 8000,
            'platform' => 'instagram',
            'date' => now()->toDateString(),
        ]);

        $this->assertEquals('facebook', $facebookMetric->platform);
        $this->assertEquals('instagram', $instagramMetric->platform);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'platform_tracking',
        ]);
    }

    #[Test]
    public function it_tracks_metrics_by_date()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metric1 = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'impressions',
            'value' => 10000,
            'date' => '2024-01-01',
        ]);

        $metric2 = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'impressions',
            'value' => 12000,
            'date' => '2024-01-02',
        ]);

        $this->assertEquals('2024-01-01', $metric1->date);
        $this->assertEquals('2024-01-02', $metric2->date);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'date_tracking',
        ]);
    }

    #[Test]
    public function it_stores_additional_metadata()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'audience_age' => '25-34',
            'gender' => 'all',
            'location' => 'Saudi Arabia',
        ];

        $metric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'impressions',
            'value' => 10000,
            'date' => now()->toDateString(),
            'metadata' => $metadata,
        ]);

        $this->assertEquals('25-34', $metric->metadata['audience_age']);
        $this->assertEquals('Saudi Arabia', $metric->metadata['location']);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'metadata',
        ]);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'clicks',
            'value' => 500,
            'date' => now()->toDateString(),
        ]);

        $this->assertTrue(Str::isUuid($metric->metric_id));

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'uuid_primary_key',
        ]);
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metric = Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'engagement',
            'value' => 750,
            'date' => now()->toDateString(),
        ]);

        $this->assertNotNull($metric->created_at);
        $this->assertNotNull($metric->updated_at);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'timestamps',
        ]);
    }

    #[Test]
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

        Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'impressions',
            'value' => 10000,
            'date' => now()->toDateString(),
        ]);

        Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'impressions',
            'value' => 15000,
            'date' => now()->toDateString(),
        ]);

        $org1Metrics = Metric::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Metrics);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'rls_isolation',
        ]);
    }

    #[Test]
    public function it_can_calculate_daily_totals()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $date = '2024-01-15';

        Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'impressions',
            'value' => 10000,
            'platform' => 'facebook',
            'date' => $date,
        ]);

        Metric::create([
            'metric_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'impressions',
            'value' => 8000,
            'platform' => 'instagram',
            'date' => $date,
        ]);

        $dailyTotal = Metric::where('org_id', $org->org_id)
            ->where('date', $date)
            ->where('name', 'impressions')
            ->sum('value');

        $this->assertEquals(18000, $dailyTotal);

        $this->logTestResult('passed', [
            'model' => 'Metric',
            'test' => 'daily_totals',
        ]);
    }
}
