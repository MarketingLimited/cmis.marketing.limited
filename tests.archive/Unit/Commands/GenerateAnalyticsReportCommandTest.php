<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Generate Analytics Report Command Unit Tests
 */
class GenerateAnalyticsReportCommandTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_generate_report_for_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $this->artisan('analytics:generate', [
            'org_id' => $org->org_id,
        ])->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'analytics:generate',
            'test' => 'basic_execution',
        ]);
    }

    #[Test]
    public function it_accepts_date_range_parameters()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $this->artisan('analytics:generate', [
            'org_id' => $org->org_id,
            '--start-date' => '2024-01-01',
            '--end-date' => '2024-01-31',
        ])->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'analytics:generate',
            'test' => 'date_range_parameters',
        ]);
    }

    #[Test]
    public function it_accepts_report_type_option()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $this->artisan('analytics:generate', [
            'org_id' => $org->org_id,
            '--type' => 'summary',
        ])->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'analytics:generate',
            'test' => 'report_type_option',
        ]);
    }

    #[Test]
    public function it_validates_org_id()
    {
        $this->artisan('analytics:generate', [
            'org_id' => 'invalid-org-id',
        ])->assertExitCode(1);

        $this->logTestResult('passed', [
            'command' => 'analytics:generate',
            'test' => 'org_validation',
        ]);
    }

    #[Test]
    public function it_can_export_to_pdf()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $this->artisan('analytics:generate', [
            'org_id' => $org->org_id,
            '--format' => 'pdf',
        ])->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'analytics:generate',
            'test' => 'pdf_export',
        ]);
    }

    #[Test]
    public function it_can_export_to_excel()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $this->artisan('analytics:generate', [
            'org_id' => $org->org_id,
            '--format' => 'excel',
        ])->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'analytics:generate',
            'test' => 'excel_export',
        ]);
    }

    #[Test]
    public function it_shows_progress_output()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $this->artisan('analytics:generate', [
            'org_id' => $org->org_id,
        ])->expectsOutput('Generating analytics report...')
          ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'analytics:generate',
            'test' => 'output_display',
        ]);
    }

    #[Test]
    public function it_handles_errors_gracefully()
    {
        // Try with non-existent org
        $this->artisan('analytics:generate', [
            'org_id' => Str::uuid(),
        ])->assertExitCode(1);

        $this->logTestResult('passed', [
            'command' => 'analytics:generate',
            'test' => 'error_handling',
        ]);
    }
}
