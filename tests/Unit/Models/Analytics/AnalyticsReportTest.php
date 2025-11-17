<?php

namespace Tests\Unit\Models\Analytics;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Analytics\AnalyticsReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Analytics Report Model Unit Tests
 */
class AnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_analytics_report()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Monthly Performance Report',
            'report_type' => 'campaign_summary',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('cmis.analytics_reports', [
            'report_id' => $report->report_id,
            'report_name' => 'Monthly Performance Report',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Test Report',
            'report_type' => 'summary',
            'status' => 'pending',
        ]);

        $this->assertEquals($org->org_id, $report->org->org_id);
    }

    /** @test */
    public function it_can_belong_to_campaign()
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

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'report_name' => 'Campaign Report',
            'report_type' => 'campaign_detail',
            'status' => 'completed',
        ]);

        $this->assertEquals($campaign->campaign_id, $report->campaign->campaign_id);
    }

    /** @test */
    public function it_stores_report_data_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $reportData = [
            'total_impressions' => 150000,
            'total_clicks' => 7500,
            'total_conversions' => 450,
            'total_spend' => 5000.00,
            'total_revenue' => 25000.00,
            'platforms' => [
                'facebook' => ['impressions' => 60000, 'clicks' => 3000],
                'instagram' => ['impressions' => 50000, 'clicks' => 2500],
                'google' => ['impressions' => 40000, 'clicks' => 2000],
            ],
        ];

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Detailed Report',
            'report_type' => 'detailed',
            'status' => 'completed',
            'report_data' => $reportData,
        ]);

        $this->assertEquals(150000, $report->report_data['total_impressions']);
        $this->assertEquals(3000, $report->report_data['platforms']['facebook']['clicks']);
    }

    /** @test */
    public function it_has_different_report_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $summaryReport = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Summary',
            'report_type' => 'summary',
            'status' => 'completed',
        ]);

        $detailedReport = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Detailed',
            'report_type' => 'detailed',
            'status' => 'completed',
        ]);

        $comparisonReport = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Comparison',
            'report_type' => 'comparison',
            'status' => 'completed',
        ]);

        $this->assertEquals('summary', $summaryReport->report_type);
        $this->assertEquals('detailed', $detailedReport->report_type);
        $this->assertEquals('comparison', $comparisonReport->report_type);
    }

    /** @test */
    public function it_tracks_report_generation_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $pendingReport = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Pending Report',
            'report_type' => 'summary',
            'status' => 'pending',
        ]);

        $processingReport = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Processing Report',
            'report_type' => 'summary',
            'status' => 'processing',
        ]);

        $completedReport = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Completed Report',
            'report_type' => 'summary',
            'status' => 'completed',
        ]);

        $this->assertEquals('pending', $pendingReport->status);
        $this->assertEquals('processing', $processingReport->status);
        $this->assertEquals('completed', $completedReport->status);
    }

    /** @test */
    public function it_stores_export_file_path()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Exported Report',
            'report_type' => 'summary',
            'status' => 'completed',
            'file_path' => '/exports/reports/2024-01-report.pdf',
        ]);

        $this->assertEquals('/exports/reports/2024-01-report.pdf', $report->file_path);
    }

    /** @test */
    public function it_tracks_generated_at_timestamp()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Test Report',
            'report_type' => 'summary',
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $this->assertNotNull($report->generated_at);
    }

    /** @test */
    public function it_supports_scheduled_reports()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Monthly Scheduled Report',
            'report_type' => 'summary',
            'status' => 'completed',
            'is_scheduled' => true,
            'schedule_frequency' => 'monthly',
        ]);

        $this->assertTrue($report->is_scheduled);
        $this->assertEquals('monthly', $report->schedule_frequency);
    }

    /** @test */
    public function it_stores_filters_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $filters = [
            'platforms' => ['facebook', 'instagram'],
            'campaign_status' => 'active',
            'min_spend' => 1000,
            'date_range' => 'last_30_days',
        ];

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Filtered Report',
            'report_type' => 'summary',
            'status' => 'completed',
            'filters' => $filters,
        ]);

        $this->assertContains('facebook', $report->filters['platforms']);
        $this->assertEquals(1000, $report->filters['min_spend']);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Test Report',
            'report_type' => 'summary',
            'status' => 'pending',
        ]);

        $this->assertTrue(Str::isUuid($report->report_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'report_name' => 'Test Report',
            'report_type' => 'summary',
            'status' => 'pending',
        ]);

        $this->assertNotNull($report->created_at);
        $this->assertNotNull($report->updated_at);
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

        AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'report_name' => 'Org 1 Report',
            'report_type' => 'summary',
            'status' => 'completed',
        ]);

        AnalyticsReport::create([
            'report_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'report_name' => 'Org 2 Report',
            'report_type' => 'summary',
            'status' => 'completed',
        ]);

        $org1Reports = AnalyticsReport::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Reports);
        $this->assertEquals('Org 1 Report', $org1Reports->first()->report_name);
    }
}
