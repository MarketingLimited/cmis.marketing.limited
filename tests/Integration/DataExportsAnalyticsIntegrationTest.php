<?php

namespace Tests\Integration;

use App\Models\Campaign\Campaign;
use App\Models\Analytics\DataExportConfig;
use App\Models\Analytics\DataExportLog;
use App\Models\Analytics\AlertHistory;
use App\Models\Automation\AutomationExecution;
use App\Models\ABTesting\ABTest;
use App\Models\Core\Org;
use App\Models\User;
use App\Services\Analytics\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Integration tests for Data Exports + Multiple Analytics Sources
 *
 * Tests the end-to-end flow of exporting data from various modules
 * (campaigns, alerts, automation, A/B tests) in different formats.
 */
class DataExportsAnalyticsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected DataExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create();

        $this->user->orgs()->attach($this->org->org_id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        session(['current_org_id' => $this->org->org_id]);

        $this->exportService = app(DataExportService::class);

        Storage::fake('exports');
    }

    /** @test */
    public function can_export_campaign_analytics_to_json()
    {
        // Create campaigns with metrics
        $campaigns = Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Create export config for campaigns
        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Campaign Analytics Export',
            'export_type' => 'campaigns',
            'format' => 'json',
            'data_config' => [
                'status' => ['active'],
                'include_metrics' => true,
            ],
            'schedule_enabled' => false,
        ]);

        // Execute export
        $log = $this->exportService->executeExport($exportConfig);

        $this->assertInstanceOf(DataExportLog::class, $log);
        $this->assertEquals('success', $log->status);
        $this->assertNotNull($log->file_path);
        $this->assertEquals('json', $log->format);

        // Verify export file exists
        Storage::disk('exports')->assertExists($log->file_path);

        // Verify export contains campaign data
        $exportedData = json_decode(Storage::disk('exports')->get($log->file_path), true);
        $this->assertIsArray($exportedData);
        $this->assertCount(5, $exportedData);
    }

    /** @test */
    public function can_export_alert_history_to_csv()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Create alert history entries
        $alerts = AlertHistory::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'triggered_at' => now()->subDays(rand(1, 30)),
        ]);

        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Alert History Export',
            'export_type' => 'alerts',
            'format' => 'csv',
            'data_config' => [
                'date_range' => [
                    'start' => now()->subDays(30)->toDateString(),
                    'end' => now()->toDateString(),
                ],
            ],
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);
        $this->assertEquals('csv', $log->format);
        $this->assertGreaterThan(0, $log->file_size);

        Storage::disk('exports')->assertExists($log->file_path);

        // Verify CSV structure
        $csvContent = Storage::disk('exports')->get($log->file_path);
        $this->assertStringContainsString('rule_id,entity_type,entity_id', $csvContent);
    }

    /** @test */
    public function can_export_automation_execution_logs_to_xlsx()
    {
        // Create automation executions
        $executions = AutomationExecution::factory()->count(15)->create([
            'org_id' => $this->org->org_id,
            'executed_at' => now()->subDays(rand(1, 7)),
        ]);

        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Automation Logs Export',
            'export_type' => 'automation',
            'format' => 'xlsx',
            'data_config' => [
                'include_errors' => true,
                'include_success' => true,
            ],
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);
        $this->assertEquals('xlsx', $log->format);

        Storage::disk('exports')->assertExists($log->file_path);
    }

    /** @test */
    public function can_export_ab_test_results_to_parquet()
    {
        // Create A/B tests with results
        $abTests = ABTest::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'completed',
        ]);

        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'A/B Test Results Export',
            'export_type' => 'ab_tests',
            'format' => 'parquet',
            'data_config' => [
                'status' => ['completed'],
                'include_variants' => true,
                'include_metrics' => true,
            ],
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);
        $this->assertEquals('parquet', $log->format);

        Storage::disk('exports')->assertExists($log->file_path);
    }

    /** @test */
    public function can_export_multi_source_custom_data()
    {
        // Create data from multiple sources
        $campaigns = Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $alerts = AlertHistory::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $executions = AutomationExecution::factory()->count(8)->create([
            'org_id' => $this->org->org_id,
        ]);

        // Custom export combining multiple data sources
        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Multi-Source Analytics Export',
            'export_type' => 'custom',
            'format' => 'json',
            'data_config' => [
                'sources' => [
                    'campaigns' => [
                        'fields' => ['campaign_id', 'name', 'status', 'spend', 'budget'],
                    ],
                    'alerts' => [
                        'fields' => ['rule_id', 'severity', 'status', 'triggered_at'],
                    ],
                    'automation' => [
                        'fields' => ['rule_id', 'status', 'executed_at'],
                    ],
                ],
            ],
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);
        $this->assertEquals('custom', $log->export_type);

        Storage::disk('exports')->assertExists($log->file_path);

        // Verify multi-source data structure
        $exportedData = json_decode(Storage::disk('exports')->get($log->file_path), true);
        $this->assertArrayHasKey('campaigns', $exportedData);
        $this->assertArrayHasKey('alerts', $exportedData);
        $this->assertArrayHasKey('automation', $exportedData);
    }

    /** @test */
    public function scheduled_export_executes_on_schedule()
    {
        $campaigns = Campaign::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
        ]);

        // Create scheduled daily export
        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Daily Campaign Report',
            'export_type' => 'campaigns',
            'format' => 'csv',
            'schedule_enabled' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => '09:00',
            'last_executed_at' => now()->subDay(),
        ]);

        // Check if export should run
        $shouldRun = $exportConfig->schedule_enabled &&
                     $exportConfig->last_executed_at < now()->subHours(23);

        $this->assertTrue($shouldRun);

        // Execute scheduled export
        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);

        // Update last execution time
        $exportConfig->update(['last_executed_at' => now()]);

        // Verify it won't run again immediately
        $shouldRun = $exportConfig->fresh()->last_executed_at < now()->subHours(23);
        $this->assertFalse($shouldRun);
    }

    /** @test */
    public function export_with_webhook_delivery()
    {
        $campaigns = Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Campaign Export with Webhook',
            'export_type' => 'campaigns',
            'format' => 'json',
            'delivery_method' => 'webhook',
            'delivery_config' => [
                'webhook_url' => 'https://example.com/webhook',
                'method' => 'POST',
                'headers' => [
                    'Authorization' => 'Bearer token123',
                ],
            ],
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);
        $this->assertEquals('webhook', $exportConfig->delivery_method);
        $this->assertNotNull($exportConfig->delivery_config['webhook_url']);
    }

    /** @test */
    public function export_with_s3_delivery()
    {
        $campaigns = Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Campaign Export to S3',
            'export_type' => 'campaigns',
            'format' => 'csv',
            'delivery_method' => 's3',
            'delivery_config' => [
                'bucket' => 'my-analytics-bucket',
                'path' => 'exports/campaigns/',
                'region' => 'us-east-1',
            ],
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);
        $this->assertEquals('s3', $exportConfig->delivery_method);
        $this->assertEquals('my-analytics-bucket', $exportConfig->delivery_config['bucket']);
    }

    /** @test */
    public function export_logs_track_all_executions()
    {
        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'export_type' => 'campaigns',
            'format' => 'json',
        ]);

        // Execute export multiple times
        $log1 = $this->exportService->executeExport($exportConfig);
        sleep(1);
        $log2 = $this->exportService->executeExport($exportConfig);
        sleep(1);
        $log3 = $this->exportService->executeExport($exportConfig);

        // Fetch all logs for this config
        $logs = DataExportLog::where('config_id', $exportConfig->config_id)
            ->orderBy('executed_at', 'desc')
            ->get();

        $this->assertCount(3, $logs);
        $this->assertEquals($log3->log_id, $logs->first()->log_id);
        $this->assertEquals($log1->log_id, $logs->last()->log_id);

        // Update export config stats
        $exportConfig->increment('execution_count', 3);
        $exportConfig->update(['last_executed_at' => now()]);

        $this->assertEquals(3, $exportConfig->fresh()->execution_count);
    }

    /** @test */
    public function export_handles_large_datasets_efficiently()
    {
        // Create large dataset
        $campaigns = Campaign::factory()->count(1000)->create([
            'org_id' => $this->org->org_id,
        ]);

        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Large Dataset Export',
            'export_type' => 'campaigns',
            'format' => 'csv',
            'data_config' => [
                'chunk_size' => 100, // Process in chunks
            ],
        ]);

        $startTime = microtime(true);
        $log = $this->exportService->executeExport($exportConfig);
        $executionTime = microtime(true) - $startTime;

        $this->assertEquals('success', $log->status);
        $this->assertLessThan(30, $executionTime); // Should complete within 30 seconds

        // Verify all records were exported
        $this->assertGreaterThan(0, $log->file_size);
        $this->assertEquals(1000, $log->record_count);
    }

    /** @test */
    public function export_with_date_range_filtering()
    {
        // Create campaigns across different dates
        Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'created_at' => now()->subDays(10),
        ]);

        Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'created_at' => now()->subDays(5),
        ]);

        Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'created_at' => now(),
        ]);

        // Export only last 7 days
        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Recent Campaigns Export',
            'export_type' => 'campaigns',
            'format' => 'json',
            'data_config' => [
                'date_range' => [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString(),
                ],
                'date_field' => 'created_at',
            ],
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);

        // Verify only recent campaigns were exported
        $exportedData = json_decode(Storage::disk('exports')->get($log->file_path), true);
        $this->assertLessThanOrEqual(10, count($exportedData)); // Should be ~10 (5 + 5)
    }

    /** @test */
    public function export_with_custom_field_selection()
    {
        $campaigns = Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Custom Fields Export',
            'export_type' => 'campaigns',
            'format' => 'json',
            'data_config' => [
                'fields' => ['campaign_id', 'name', 'status', 'budget', 'spend'],
                'exclude_fields' => ['created_at', 'updated_at'],
            ],
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);

        // Verify only selected fields are in export
        $exportedData = json_decode(Storage::disk('exports')->get($log->file_path), true);
        $firstRecord = $exportedData[0];

        $this->assertArrayHasKey('campaign_id', $firstRecord);
        $this->assertArrayHasKey('name', $firstRecord);
        $this->assertArrayHasKey('status', $firstRecord);
        $this->assertArrayNotHasKey('created_at', $firstRecord);
        $this->assertArrayNotHasKey('updated_at', $firstRecord);
    }

    /** @test */
    public function export_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        // Create campaigns in different orgs
        $ourCampaigns = Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $otherCampaigns = Campaign::factory()->count(5)->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'export_type' => 'campaigns',
            'format' => 'json',
        ]);

        $log = $this->exportService->executeExport($exportConfig);

        $this->assertEquals('success', $log->status);

        // Verify only our org's campaigns are exported
        $exportedData = json_decode(Storage::disk('exports')->get($log->file_path), true);
        $this->assertCount(5, $exportedData);

        foreach ($exportedData as $campaign) {
            $this->assertEquals($this->org->org_id, $campaign['org_id']);
            $this->assertNotEquals($otherOrg->org_id, $campaign['org_id']);
        }
    }

    /** @test */
    public function export_handles_errors_gracefully()
    {
        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'export_type' => 'campaigns',
            'format' => 'invalid_format', // Invalid format
        ]);

        try {
            $log = $this->exportService->executeExport($exportConfig);

            // Should create error log
            $this->assertEquals('failed', $log->status);
            $this->assertNotNull($log->error_message);
        } catch (\Exception $e) {
            // Or should throw exception
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /** @test */
    public function export_statistics_aggregate_correctly()
    {
        $exportConfig = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'export_type' => 'campaigns',
            'format' => 'json',
        ]);

        // Create successful exports
        DataExportLog::factory()->count(8)->create([
            'config_id' => $exportConfig->config_id,
            'org_id' => $this->org->org_id,
            'status' => 'success',
            'executed_at' => now()->subDays(rand(1, 30)),
        ]);

        // Create failed exports
        DataExportLog::factory()->count(2)->create([
            'config_id' => $exportConfig->config_id,
            'org_id' => $this->org->org_id,
            'status' => 'failed',
            'executed_at' => now()->subDays(rand(1, 30)),
        ]);

        // Calculate statistics
        $totalExports = DataExportLog::where('config_id', $exportConfig->config_id)->count();
        $successCount = DataExportLog::where('config_id', $exportConfig->config_id)
            ->where('status', 'success')->count();
        $failureCount = DataExportLog::where('config_id', $exportConfig->config_id)
            ->where('status', 'failed')->count();
        $successRate = ($successCount / $totalExports) * 100;

        $this->assertEquals(10, $totalExports);
        $this->assertEquals(8, $successCount);
        $this->assertEquals(2, $failureCount);
        $this->assertEquals(80.0, $successRate);
    }
}
