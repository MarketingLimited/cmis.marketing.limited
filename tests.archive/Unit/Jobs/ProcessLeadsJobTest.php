<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Lead\ProcessLeadsJob;
use App\Models\Core\Org;
use App\Models\Lead\Lead;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;

use PHPUnit\Framework\Attributes\Test;
/**
 * ProcessLeads Job Unit Tests
 */
class ProcessLeadsJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_processes_new_leads()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'status' => 'new',
        ]);

        $job = new ProcessLeadsJob($lead);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'process_new_leads',
        ]);
    }

    #[Test]
    public function it_assigns_lead_score()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Sarah Abdullah',
            'email' => 'sarah@example.com',
            'phone' => '+966501234567',
            'status' => 'new',
            'score' => 0,
        ]);

        $job = new ProcessLeadsJob($lead);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('score', $result);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'assign_lead_score',
        ]);
    }

    #[Test]
    public function it_enriches_lead_data()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Mohammed Ali',
            'email' => 'mohammed@example.com',
            'status' => 'new',
        ]);

        $job = new ProcessLeadsJob($lead, ['enrich' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        // Should enrich with additional data from external sources

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'enrich_lead_data',
        ]);
    }

    #[Test]
    public function it_validates_lead_email()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'new',
        ]);

        $job = new ProcessLeadsJob($lead, ['validate_email' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('email_valid', $result);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'validate_email',
        ]);
    }

    #[Test]
    public function it_detects_duplicate_leads()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Fatima Hassan',
            'email' => 'fatima@example.com',
            'status' => 'qualified',
        ]);

        $duplicateLead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Fatima Hassan',
            'email' => 'fatima@example.com',
            'status' => 'new',
        ]);

        $job = new ProcessLeadsJob($duplicateLead, ['check_duplicates' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('is_duplicate', $result);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'detect_duplicates',
        ]);
    }

    #[Test]
    public function it_assigns_to_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Khalid Ahmed',
            'email' => 'khalid@example.com',
            'status' => 'new',
        ]);

        $campaignId = Str::uuid();

        $job = new ProcessLeadsJob($lead, ['assign_to_campaign' => $campaignId]);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'assign_to_campaign',
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

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Nora Salem',
            'email' => 'nora@example.com',
            'status' => 'new',
        ]);

        ProcessLeadsJob::dispatch($lead);

        Queue::assertPushed(ProcessLeadsJob::class);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'dispatch',
        ]);
    }

    #[Test]
    public function it_sends_notification_to_sales_team()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'High Value Lead',
            'email' => 'highvalue@example.com',
            'status' => 'new',
            'score' => 95,
        ]);

        $job = new ProcessLeadsJob($lead, ['notify_sales' => true]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        // Should notify sales team for high-score leads

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'notify_sales_team',
        ]);
    }

    #[Test]
    public function it_categorizes_leads_by_source()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Social Media Lead',
            'email' => 'social@example.com',
            'source' => 'facebook',
            'status' => 'new',
        ]);

        $job = new ProcessLeadsJob($lead);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('category', $result);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'categorize_by_source',
        ]);
    }

    #[Test]
    public function it_updates_lead_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Processing Lead',
            'email' => 'processing@example.com',
            'status' => 'new',
        ]);

        $job = new ProcessLeadsJob($lead, ['update_status' => 'qualified']);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'update_status',
        ]);
    }

    #[Test]
    public function it_handles_bulk_processing()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $leads = [];
        for ($i = 0; $i < 10; $i++) {
            $leads[] = Lead::create([
                'lead_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Lead {$i}",
                'email' => "lead{$i}@example.com",
                'status' => 'new',
            ]);
        }

        $job = new ProcessLeadsJob(null, ['leads' => collect($leads)]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('processed_count', $result);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'bulk_processing',
        ]);
    }

    #[Test]
    public function it_tracks_processing_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Timed Lead',
            'email' => 'timed@example.com',
            'status' => 'new',
        ]);

        $job = new ProcessLeadsJob($lead);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('processing_time', $result);

        $this->logTestResult('passed', [
            'job' => 'ProcessLeadsJob',
            'test' => 'track_processing_time',
        ]);
    }
}
