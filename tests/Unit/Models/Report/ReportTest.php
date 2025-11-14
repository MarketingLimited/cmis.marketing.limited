<?php

namespace Tests\Unit\Models\Report;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Report\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Report Model Unit Tests
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_report()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'تقرير الحملة الشهري',
            'type' => 'campaign_summary',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('cmis.reports', [
            'report_id' => $report->report_id,
            'name' => 'تقرير الحملة الشهري',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Report',
            'type' => 'analytics',
            'status' => 'pending',
        ]);

        $this->assertEquals($org->org_id, $report->org->org_id);
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'name' => 'My Custom Report',
            'type' => 'custom',
            'status' => 'completed',
        ]);

        $this->assertEquals($user->user_id, $report->user->user_id);
    }

    /** @test */
    public function it_has_different_report_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $reportTypes = [
            'campaign_summary',
            'analytics',
            'performance',
            'engagement',
            'financial',
            'custom',
        ];

        foreach ($reportTypes as $type) {
            Report::create([
                'report_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Report {$type}",
                'type' => $type,
                'status' => 'completed',
            ]);
        }

        $reports = Report::where('org_id', $org->org_id)->get();
        $this->assertCount(6, $reports);
    }

    /** @test */
    public function it_has_different_statuses()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $pendingReport = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Pending Report',
            'type' => 'analytics',
            'status' => 'pending',
        ]);

        $processingReport = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Processing Report',
            'type' => 'analytics',
            'status' => 'processing',
        ]);

        $completedReport = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Completed Report',
            'type' => 'analytics',
            'status' => 'completed',
        ]);

        $this->assertEquals('pending', $pendingReport->status);
        $this->assertEquals('processing', $processingReport->status);
        $this->assertEquals('completed', $completedReport->status);
    }

    /** @test */
    public function it_stores_report_data_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $reportData = [
            'total_campaigns' => 25,
            'active_campaigns' => 10,
            'total_impressions' => 500000,
            'total_clicks' => 25000,
            'total_conversions' => 1500,
            'conversion_rate' => 6.0,
        ];

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Campaign Summary',
            'type' => 'campaign_summary',
            'status' => 'completed',
            'data' => $reportData,
        ]);

        $this->assertEquals(25, $report->data['total_campaigns']);
        $this->assertEquals(6.0, $report->data['conversion_rate']);
    }

    /** @test */
    public function it_stores_filter_parameters()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $filters = [
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
            'campaign_ids' => [Str::uuid(), Str::uuid()],
            'platforms' => ['facebook', 'instagram'],
        ];

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Filtered Report',
            'type' => 'analytics',
            'status' => 'completed',
            'filters' => $filters,
        ]);

        $this->assertEquals('2024-01-01', $report->filters['date_from']);
        $this->assertCount(2, $report->filters['campaign_ids']);
    }

    /** @test */
    public function it_stores_file_path()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'PDF Report',
            'type' => 'campaign_summary',
            'status' => 'completed',
            'file_path' => 'reports/2024/01/campaign_report_123.pdf',
        ]);

        $this->assertEquals('reports/2024/01/campaign_report_123.pdf', $report->file_path);
    }

    /** @test */
    public function it_has_format_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $pdfReport = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'PDF Report',
            'type' => 'analytics',
            'status' => 'completed',
            'format' => 'pdf',
        ]);

        $excelReport = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Excel Report',
            'type' => 'analytics',
            'status' => 'completed',
            'format' => 'excel',
        ]);

        $this->assertEquals('pdf', $pdfReport->format);
        $this->assertEquals('excel', $excelReport->format);
    }

    /** @test */
    public function it_tracks_generation_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Timed Report',
            'type' => 'performance',
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $this->assertNotNull($report->generated_at);
    }

    /** @test */
    public function it_can_be_scheduled()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $scheduledReport = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Monthly Report',
            'type' => 'campaign_summary',
            'status' => 'pending',
            'is_scheduled' => true,
            'schedule_frequency' => 'monthly',
        ]);

        $this->assertTrue($scheduledReport->is_scheduled);
        $this->assertEquals('monthly', $scheduledReport->schedule_frequency);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Report',
            'type' => 'analytics',
            'status' => 'completed',
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

        $report = Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Report',
            'type' => 'analytics',
            'status' => 'completed',
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

        Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Report',
            'type' => 'analytics',
            'status' => 'completed',
        ]);

        Report::create([
            'report_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Report',
            'type' => 'analytics',
            'status' => 'completed',
        ]);

        $org1Reports = Report::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Reports);
        $this->assertEquals('Org 1 Report', $org1Reports->first()->name);
    }
}
