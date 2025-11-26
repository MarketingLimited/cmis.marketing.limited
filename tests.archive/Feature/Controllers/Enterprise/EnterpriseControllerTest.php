<?php

namespace Tests\Feature\Controllers\Enterprise;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\User;
use App\Models\Core\Organization;
use App\Services\Enterprise\PerformanceMonitoringService;
use App\Services\Enterprise\AdvancedReportingService;
use App\Services\Enterprise\WebhookManagementService;
use Mockery;

class EnterpriseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $org;
    protected string $orgId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization and user
        $this->org = Organization::factory()->create();
        $this->orgId = $this->org->org_id;

        $this->user = User::factory()->create([
            'org_id' => $this->orgId
        ]);
    }

    // =========================================================================
    // PERFORMANCE MONITORING TESTS
    // =========================================================================

    public function test_monitor_campaign_success()
    {
        $campaignId = 'campaign-123';

        $mockService = Mockery::mock(PerformanceMonitoringService::class);
        $mockService->shouldReceive('monitorCampaignPerformance')
            ->once()
            ->with($campaignId)
            ->andReturn([
                'success' => true,
                'campaign_id' => $campaignId,
                'status' => 'healthy',
                'alerts_triggered' => 0,
                'anomalies' => []
            ]);

        $this->app->instance(PerformanceMonitoringService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/monitor/campaign/{$campaignId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'campaign_id' => $campaignId,
                'status' => 'healthy'
            ]);
    }

    public function test_monitor_organization_success()
    {
        $mockService = Mockery::mock(PerformanceMonitoringService::class);
        $mockService->shouldReceive('monitorOrganizationPerformance')
            ->once()
            ->with($this->orgId)
            ->andReturn([
                'success' => true,
                'campaigns_monitored' => 5,
                'alerts_triggered' => 2,
                'anomalies' => []
            ]);

        $this->app->instance(PerformanceMonitoringService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/monitor/organization");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'campaigns_monitored' => 5
            ]);
    }

    public function test_get_alerts_success()
    {
        $mockService = Mockery::mock(PerformanceMonitoringService::class);
        $mockService->shouldReceive('getActiveAlerts')
            ->once()
            ->with($this->orgId, [], 50)
            ->andReturn([
                [
                    'alert_id' => 'alert-1',
                    'type' => 'budget_exceeded',
                    'severity' => 'high',
                    'status' => 'active'
                ]
            ]);

        $this->app->instance(PerformanceMonitoringService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/alerts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'alerts',
                'count'
            ]);
    }

    public function test_get_alerts_with_filters()
    {
        $mockService = Mockery::mock(PerformanceMonitoringService::class);
        $mockService->shouldReceive('getActiveAlerts')
            ->once()
            ->with($this->orgId, ['severity' => 'high', 'status' => 'active'], 20)
            ->andReturn([]);

        $this->app->instance(PerformanceMonitoringService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/alerts?severity=high&status=active&limit=20");

        $response->assertStatus(200);
    }

    public function test_acknowledge_alert_success()
    {
        $alertId = 'alert-123';

        $mockService = Mockery::mock(PerformanceMonitoringService::class);
        $mockService->shouldReceive('acknowledgeAlert')
            ->once()
            ->with($alertId, $this->user->user_id, 'Looking into this')
            ->andReturn(['success' => true, 'alert_id' => $alertId]);

        $this->app->instance(PerformanceMonitoringService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/alerts/{$alertId}/acknowledge", [
                'acknowledged_by' => $this->user->user_id,
                'notes' => 'Looking into this'
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_resolve_alert_success()
    {
        $alertId = 'alert-123';

        $mockService = Mockery::mock(PerformanceMonitoringService::class);
        $mockService->shouldReceive('resolveAlert')
            ->once()
            ->with($alertId, $this->user->user_id, 'Budget adjusted')
            ->andReturn(['success' => true, 'alert_id' => $alertId]);

        $this->app->instance(PerformanceMonitoringService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/alerts/{$alertId}/resolve", [
                'resolved_by' => $this->user->user_id,
                'resolution_notes' => 'Budget adjusted'
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_get_alert_statistics_success()
    {
        $mockService = Mockery::mock(PerformanceMonitoringService::class);
        $mockService->shouldReceive('getAlertStatistics')
            ->once()
            ->with($this->orgId, 30)
            ->andReturn([
                'total_alerts' => 50,
                'by_severity' => ['high' => 10, 'medium' => 25, 'low' => 15],
                'by_type' => ['budget_exceeded' => 20, 'performance_drop' => 30]
            ]);

        $this->app->instance(PerformanceMonitoringService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/alerts/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'total_alerts',
                    'by_severity',
                    'by_type'
                ]
            ]);
    }

    // =========================================================================
    // ADVANCED REPORTING TESTS
    // =========================================================================

    public function test_generate_campaign_report_success()
    {
        $campaignId = 'campaign-123';

        $mockService = Mockery::mock(AdvancedReportingService::class);
        $mockService->shouldReceive('generateCampaignReport')
            ->once()
            ->with($campaignId, Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'report_id' => 'report-123',
                'format' => 'pdf',
                'file_path' => '/storage/reports/report-123.pdf'
            ]);

        $this->app->instance(AdvancedReportingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/reports/campaign/{$campaignId}", [
                'format' => 'pdf',
                'include_charts' => true
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'report_id' => 'report-123'
            ]);
    }

    public function test_generate_organization_report_success()
    {
        $mockService = Mockery::mock(AdvancedReportingService::class);
        $mockService->shouldReceive('generateOrganizationReport')
            ->once()
            ->with($this->orgId, Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'report_id' => 'org-report-123',
                'format' => 'excel'
            ]);

        $this->app->instance(AdvancedReportingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/reports/organization", [
                'format' => 'excel',
                'include_campaigns' => true
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_schedule_report_success()
    {
        $mockService = Mockery::mock(AdvancedReportingService::class);
        $mockService->shouldReceive('scheduleReport')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'schedule_id' => 'schedule-123'
            ]);

        $this->app->instance(AdvancedReportingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/reports/schedule", [
                'report_type' => 'organization',
                'frequency' => 'weekly',
                'format' => 'pdf',
                'recipients' => ['user@example.com']
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_schedule_report_validation_fails()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/reports/schedule", [
                'report_type' => 'invalid_type',
                'frequency' => 'hourly'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['report_type', 'frequency', 'recipients']);
    }

    public function test_get_scheduled_reports_success()
    {
        $mockService = Mockery::mock(AdvancedReportingService::class);
        $mockService->shouldReceive('getScheduledReports')
            ->once()
            ->with($this->orgId)
            ->andReturn([
                ['schedule_id' => 'schedule-1', 'frequency' => 'weekly'],
                ['schedule_id' => 'schedule-2', 'frequency' => 'monthly']
            ]);

        $this->app->instance(AdvancedReportingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/reports/schedules");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'schedules',
                'count'
            ]);
    }

    public function test_delete_scheduled_report_success()
    {
        $scheduleId = 'schedule-123';

        $mockService = Mockery::mock(AdvancedReportingService::class);
        $mockService->shouldReceive('deleteSchedule')
            ->once()
            ->with($scheduleId)
            ->andReturn(['success' => true]);

        $this->app->instance(AdvancedReportingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/orgs/{$this->orgId}/enterprise/reports/schedules/{$scheduleId}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_get_report_history_success()
    {
        $mockService = Mockery::mock(AdvancedReportingService::class);
        $mockService->shouldReceive('getReportHistory')
            ->once()
            ->with($this->orgId, 50, null)
            ->andReturn([
                ['report_id' => 'report-1', 'type' => 'campaign'],
                ['report_id' => 'report-2', 'type' => 'organization']
            ]);

        $this->app->instance(AdvancedReportingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/reports/history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'reports',
                'count'
            ]);
    }

    public function test_download_report_success()
    {
        $reportId = 'report-123';

        $mockService = Mockery::mock(AdvancedReportingService::class);
        $mockService->shouldReceive('downloadReport')
            ->once()
            ->with($reportId)
            ->andReturn([
                'success' => true,
                'file_path' => '/storage/reports/report-123.pdf',
                'file_name' => 'campaign-report.pdf'
            ]);

        $this->app->instance(AdvancedReportingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/reports/{$reportId}/download");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =========================================================================
    // WEBHOOK MANAGEMENT TESTS
    // =========================================================================

    public function test_register_webhook_success()
    {
        $mockService = Mockery::mock(WebhookManagementService::class);
        $mockService->shouldReceive('registerWebhook')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'webhook_id' => 'webhook-123'
            ]);

        $this->app->instance(WebhookManagementService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/webhooks", [
                'url' => 'https://example.com/webhook',
                'events' => ['campaign.created', 'alert.triggered'],
                'secret' => str_repeat('a', 32),
                'description' => 'Test webhook'
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_register_webhook_validation_fails()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/webhooks", [
                'url' => 'invalid-url',
                'events' => [],
                'secret' => 'short'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url', 'events', 'secret']);
    }

    public function test_get_webhooks_success()
    {
        $mockService = Mockery::mock(WebhookManagementService::class);
        $mockService->shouldReceive('getWebhooks')
            ->once()
            ->with($this->orgId)
            ->andReturn([
                ['webhook_id' => 'webhook-1', 'url' => 'https://example.com/hook1'],
                ['webhook_id' => 'webhook-2', 'url' => 'https://example.com/hook2']
            ]);

        $this->app->instance(WebhookManagementService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/webhooks");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'webhooks',
                'count'
            ]);
    }

    public function test_update_webhook_success()
    {
        $webhookId = 'webhook-123';

        $mockService = Mockery::mock(WebhookManagementService::class);
        $mockService->shouldReceive('updateWebhook')
            ->once()
            ->with($webhookId, Mockery::type('array'))
            ->andReturn(['success' => true, 'webhook_id' => $webhookId]);

        $this->app->instance(WebhookManagementService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/orgs/{$this->orgId}/enterprise/webhooks/{$webhookId}", [
                'active' => false,
                'description' => 'Updated description'
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_delete_webhook_success()
    {
        $webhookId = 'webhook-123';

        $mockService = Mockery::mock(WebhookManagementService::class);
        $mockService->shouldReceive('deleteWebhook')
            ->once()
            ->with($webhookId)
            ->andReturn(['success' => true]);

        $this->app->instance(WebhookManagementService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/orgs/{$this->orgId}/enterprise/webhooks/{$webhookId}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_trigger_webhook_success()
    {
        $mockService = Mockery::mock(WebhookManagementService::class);
        $mockService->shouldReceive('triggerEvent')
            ->once()
            ->with($this->orgId, 'campaign.created', Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'webhooks_triggered' => 2,
                'deliveries' => ['delivery-1', 'delivery-2']
            ]);

        $this->app->instance(WebhookManagementService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/webhooks/trigger", [
                'event' => 'campaign.created',
                'data' => ['campaign_id' => 'campaign-123']
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_get_webhook_deliveries_success()
    {
        $webhookId = 'webhook-123';

        $mockService = Mockery::mock(WebhookManagementService::class);
        $mockService->shouldReceive('getDeliveries')
            ->once()
            ->with($webhookId, 50, null)
            ->andReturn([
                ['delivery_id' => 'delivery-1', 'status' => 'delivered'],
                ['delivery_id' => 'delivery-2', 'status' => 'failed']
            ]);

        $this->app->instance(WebhookManagementService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/webhooks/{$webhookId}/deliveries");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'deliveries',
                'count'
            ]);
    }

    public function test_get_webhook_statistics_success()
    {
        $webhookId = 'webhook-123';

        $mockService = Mockery::mock(WebhookManagementService::class);
        $mockService->shouldReceive('getStatistics')
            ->once()
            ->with($webhookId, 30)
            ->andReturn([
                'total_deliveries' => 100,
                'successful' => 95,
                'failed' => 5,
                'success_rate' => 95.0
            ]);

        $this->app->instance(WebhookManagementService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/enterprise/webhooks/{$webhookId}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'total_deliveries',
                    'successful',
                    'failed',
                    'success_rate'
                ]
            ]);
    }

    public function test_retry_webhook_delivery_success()
    {
        $deliveryId = 'delivery-123';

        $mockService = Mockery::mock(WebhookManagementService::class);
        $mockService->shouldReceive('retryDelivery')
            ->once()
            ->with($deliveryId)
            ->andReturn(['success' => true, 'delivery_id' => $deliveryId]);

        $this->app->instance(WebhookManagementService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/enterprise/webhooks/deliveries/{$deliveryId}/retry");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =========================================================================
    // AUTHENTICATION & AUTHORIZATION TESTS
    // =========================================================================

    public function test_unauthenticated_requests_fail()
    {
        $response = $this->getJson("/api/orgs/{$this->orgId}/enterprise/alerts");
        $response->assertStatus(401);

        $response = $this->postJson("/api/orgs/{$this->orgId}/enterprise/webhooks", []);
        $response->assertStatus(401);

        $response = $this->postJson("/api/orgs/{$this->orgId}/enterprise/reports/campaign/campaign-123", []);
        $response->assertStatus(401);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
