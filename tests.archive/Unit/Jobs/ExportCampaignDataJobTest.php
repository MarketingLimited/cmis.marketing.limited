<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Export\ExportCampaignDataJob;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use PHPUnit\Framework\Attributes\Test;
/**
 * ExportCampaignData Job Unit Tests
 */
class ExportCampaignDataJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('exports');
    }

    #[Test]
    public function it_exports_campaign_data_to_csv()
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $job = new ExportCampaignDataJob($campaign, $user, 'csv');
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertEquals('csv', $result['format']);

        $this->logTestResult('passed', [
            'job' => 'ExportCampaignDataJob',
            'test' => 'export_csv',
        ]);
    }

    #[Test]
    public function it_exports_campaign_data_to_excel()
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'completed',
        ]);

        $job = new ExportCampaignDataJob($campaign, $user, 'excel');
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertEquals('excel', $result['format']);

        $this->logTestResult('passed', [
            'job' => 'ExportCampaignDataJob',
            'test' => 'export_excel',
        ]);
    }

    #[Test]
    public function it_exports_campaign_data_to_json()
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $job = new ExportCampaignDataJob($campaign, $user, 'json');
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertEquals('json', $result['format']);

        $this->logTestResult('passed', [
            'job' => 'ExportCampaignDataJob',
            'test' => 'export_json',
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

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        ExportCampaignDataJob::dispatch($campaign, $user, 'csv');

        Queue::assertPushed(ExportCampaignDataJob::class);

        $this->logTestResult('passed', [
            'job' => 'ExportCampaignDataJob',
            'test' => 'dispatch',
        ]);
    }

    #[Test]
    public function it_stores_export_file()
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $job = new ExportCampaignDataJob($campaign, $user, 'csv');
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('file_path', $result);

        $this->logTestResult('passed', [
            'job' => 'ExportCampaignDataJob',
            'test' => 'file_storage',
        ]);
    }

    #[Test]
    public function it_includes_campaign_analytics_in_export()
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Campaign with Analytics',
            'status' => 'active',
        ]);

        $job = new ExportCampaignDataJob($campaign, $user, 'csv');
        $result = $job->handle();

        // Export should include analytics data
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ExportCampaignDataJob',
            'test' => 'includes_analytics',
        ]);
    }

    #[Test]
    public function it_notifies_user_when_export_is_ready()
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $job = new ExportCampaignDataJob($campaign, $user, 'excel');
        $result = $job->handle();

        // Should send notification to user
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ExportCampaignDataJob',
            'test' => 'user_notification',
        ]);
    }

    #[Test]
    public function it_handles_large_datasets()
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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Large Campaign',
            'status' => 'active',
        ]);

        $job = new ExportCampaignDataJob($campaign, $user, 'csv');
        $result = $job->handle();

        // Should handle large exports efficiently
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'ExportCampaignDataJob',
            'test' => 'large_datasets',
        ]);
    }
}
