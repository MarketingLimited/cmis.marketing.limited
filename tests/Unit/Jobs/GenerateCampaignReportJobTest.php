<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Reports\GenerateCampaignReportJob;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Generate Campaign Report Job Unit Tests
 */
class GenerateCampaignReportJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        Storage::fake('reports');
    }

    /** @test */
    public function it_generates_campaign_report()
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

        $job = new GenerateCampaignReportJob($campaign, 'pdf');
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'GenerateCampaignReportJob',
            'test' => 'generate_report',
        ]);
    }

    /** @test */
    public function it_generates_pdf_report()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'completed',
        ]);

        $job = new GenerateCampaignReportJob($campaign, 'pdf');
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertEquals('pdf', $result['format']);

        $this->logTestResult('passed', [
            'job' => 'GenerateCampaignReportJob',
            'format' => 'pdf',
        ]);
    }

    /** @test */
    public function it_generates_excel_report()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'completed',
        ]);

        $job = new GenerateCampaignReportJob($campaign, 'excel');
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertEquals('excel', $result['format']);

        $this->logTestResult('passed', [
            'job' => 'GenerateCampaignReportJob',
            'format' => 'excel',
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
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        GenerateCampaignReportJob::dispatch($campaign, 'pdf');

        Queue::assertPushed(GenerateCampaignReportJob::class);

        $this->logTestResult('passed', [
            'job' => 'GenerateCampaignReportJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_includes_campaign_analytics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'completed',
        ]);

        $job = new GenerateCampaignReportJob($campaign, 'pdf');
        $result = $job->handle();

        // Report should include analytics
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'GenerateCampaignReportJob',
            'test' => 'includes_analytics',
        ]);
    }

    /** @test */
    public function it_stores_report_file()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'completed',
        ]);

        $job = new GenerateCampaignReportJob($campaign, 'pdf');
        $result = $job->handle();

        // File should be stored
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('file_path', $result);

        $this->logTestResult('passed', [
            'job' => 'GenerateCampaignReportJob',
            'test' => 'file_storage',
        ]);
    }
}
