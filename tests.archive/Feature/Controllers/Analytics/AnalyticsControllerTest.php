<?php

namespace Tests\Feature\Controllers\Analytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\User;
use App\Models\Core\Organization;
use App\Services\Analytics\{RealTimeAnalyticsService, CustomMetricsService, ROICalculationEngine, AttributionModelingService};
use Mockery;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $org;
    protected string $orgId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->orgId = $this->org->org_id;
        $this->user = User::factory()->create(['org_id' => $this->orgId]);
    }

    // REAL-TIME ANALYTICS TESTS
    public function test_get_realtime_metrics_success()
    {
        $mockService = Mockery::mock(RealTimeAnalyticsService::class);
        $mockService->shouldReceive('getRealtimeMetrics')
            ->once()
            ->andReturn([
                'success' => true,
                'metrics' => ['impressions' => ['value' => 1000], 'clicks' => ['value' => 50]],
                'window' => '5m'
            ]);

        $this->app->instance(RealTimeAnalyticsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/analytics/realtime/campaign/camp-123/metrics");

        $response->assertStatus(200)->assertJsonStructure(['success', 'metrics']);
    }

    public function test_get_timeseries_success()
    {
        $mockService = Mockery::mock(RealTimeAnalyticsService::class);
        $mockService->shouldReceive('getTimeSeries')
            ->once()
            ->andReturn([
                'success' => true,
                'series' => [['timestamp' => '2025-01-01', 'value' => 100]],
                'points' => 12
            ]);

        $this->app->instance(RealTimeAnalyticsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/analytics/realtime/campaign/camp-123/timeseries?metric=impressions");

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_detect_anomalies_success()
    {
        $mockService = Mockery::mock(RealTimeAnalyticsService::class);
        $mockService->shouldReceive('detectAnomalies')
            ->once()
            ->andReturn([
                'success' => true,
                'anomalies_detected' => false,
                'statistics' => ['mean' => 100.5, 'std_dev' => 10.2]
            ]);

        $this->app->instance(RealTimeAnalyticsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/analytics/realtime/campaign/camp-123/anomalies/impressions");

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    // CUSTOM METRICS TESTS
    public function test_create_metric_success()
    {
        $mockService = Mockery::mock(CustomMetricsService::class);
        $mockService->shouldReceive('createMetric')
            ->once()
            ->andReturn(['success' => true, 'metric_id' => 'metric-123']);

        $this->app->instance(CustomMetricsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/metrics/custom", [
                'name' => 'Custom Engagement Rate',
                'calculation_type' => 'ratio',
                'source_metrics' => ['engagements', 'impressions']
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_create_kpi_success()
    {
        $mockService = Mockery::mock(CustomMetricsService::class);
        $mockService->shouldReceive('createKPI')
            ->once()
            ->andReturn(['success' => true, 'kpi_id' => 'kpi-123']);

        $this->app->instance(CustomMetricsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/kpis", [
                'name' => 'Monthly Revenue Target',
                'target_value' => 100000,
                'period' => 'monthly'
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_evaluate_kpi_success()
    {
        $mockService = Mockery::mock(CustomMetricsService::class);
        $mockService->shouldReceive('evaluateKPI')
            ->once()
            ->andReturn([
                'success' => true,
                'status' => 'on_track',
                'current_value' => 75000,
                'target_value' => 100000,
                'progress_percentage' => 75.0
            ]);

        $this->app->instance(CustomMetricsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/kpis/kpi-123/evaluate", [
                'entity_type' => 'campaign',
                'entity_id' => 'camp-123'
            ]);

        $response->assertStatus(200)->assertJson(['success' => true, 'status' => 'on_track']);
    }

    // ROI CALCULATION TESTS
    public function test_calculate_campaign_roi_success()
    {
        $mockService = Mockery::mock(ROICalculationEngine::class);
        $mockService->shouldReceive('calculateCampaignROI')
            ->once()
            ->andReturn([
                'success' => true,
                'financial_metrics' => [
                    'total_spend' => 10000,
                    'total_revenue' => 15000,
                    'profit' => 5000,
                    'roi_percentage' => 50.0,
                    'roas' => 1.5
                ]
            ]);

        $this->app->instance(ROICalculationEngine::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/roi/campaigns/camp-123");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'financial_metrics']);
    }

    public function test_calculate_organization_roi_success()
    {
        $mockService = Mockery::mock(ROICalculationEngine::class);
        $mockService->shouldReceive('calculateOrganizationROI')
            ->once()
            ->andReturn([
                'success' => true,
                'overall_metrics' => [
                    'total_spend' => 50000,
                    'total_revenue' => 80000,
                    'profit' => 30000,
                    'roi_percentage' => 60.0
                ]
            ]);

        $this->app->instance(ROICalculationEngine::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/roi/organization");

        $response->assertStatus(200)->assertJsonStructure(['success', 'overall_metrics']);
    }

    public function test_calculate_lifetime_value_success()
    {
        $mockService = Mockery::mock(ROICalculationEngine::class);
        $mockService->shouldReceive('calculateLifetimeValue')
            ->once()
            ->andReturn([
                'success' => true,
                'lifetime_metrics' => [
                    'total_customers' => 500,
                    'average_revenue_per_customer' => 200.0,
                    'estimated_ltv' => 200.0
                ]
            ]);

        $this->app->instance(ROICalculationEngine::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->orgId}/analytics/roi/campaigns/camp-123/ltv");

        $response->assertStatus(200)->assertJsonStructure(['success', 'lifetime_metrics']);
    }

    public function test_project_roi_success()
    {
        $mockService = Mockery::mock(ROICalculationEngine::class);
        $mockService->shouldReceive('projectROI')
            ->once()
            ->andReturn([
                'success' => true,
                'projected_metrics' => [
                    'projected_spend' => 15000,
                    'projected_revenue' => 25000,
                    'projected_roi_percentage' => 66.67
                ],
                'confidence_level' => ['level' => 'high', 'percentage' => 90]
            ]);

        $this->app->instance(ROICalculationEngine::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/roi/campaigns/camp-123/project", [
                'days_to_project' => 30
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'projected_metrics', 'confidence_level']);
    }

    // ATTRIBUTION MODELING TESTS
    public function test_attribute_conversions_success()
    {
        $mockService = Mockery::mock(AttributionModelingService::class);
        $mockService->shouldReceive('attributeConversions')
            ->once()
            ->andReturn([
                'success' => true,
                'attribution_model' => 'linear',
                'attributed_conversions' => [
                    ['channel' => 'social', 'conversions' => 25.5],
                    ['channel' => 'search', 'conversions' => 15.5]
                ],
                'total_conversions' => 41
            ]);

        $this->app->instance(AttributionModelingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/attribution/campaigns/camp-123", [
                'model' => 'linear'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'attribution_model', 'attributed_conversions']);
    }

    public function test_compare_attribution_models_success()
    {
        $mockService = Mockery::mock(AttributionModelingService::class);
        $mockService->shouldReceive('compareAttributionModels')
            ->once()
            ->andReturn([
                'success' => true,
                'comparison' => [
                    'last_click' => ['total_conversions' => 41],
                    'linear' => ['total_conversions' => 41],
                    'time_decay' => ['total_conversions' => 41]
                ]
            ]);

        $this->app->instance(AttributionModelingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/attribution/campaigns/camp-123/compare");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'comparison']);
    }

    public function test_get_attribution_insights_success()
    {
        $mockService = Mockery::mock(AttributionModelingService::class);
        $mockService->shouldReceive('getAttributionInsights')
            ->once()
            ->andReturn([
                'success' => true,
                'insights' => [
                    [
                        'channel' => 'social',
                        'conversions' => 25.5,
                        'conversion_value' => 5000.0,
                        'contribution_percentage' => 55.0
                    ]
                ],
                'top_channel' => ['channel' => 'social']
            ]);

        $this->app->instance(AttributionModelingService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/attribution/campaigns/camp-123/insights", [
                'model' => 'linear'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'insights', 'top_channel']);
    }

    // VALIDATION TESTS
    public function test_create_metric_validation_fails()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/metrics/custom", [
                'name' => '' // Missing required name
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'calculation_type']);
    }

    public function test_create_kpi_validation_fails()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->orgId}/analytics/kpis", [
                'name' => 'Test KPI'
                // Missing required target_value
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['target_value']);
    }

    // AUTHENTICATION TESTS
    public function test_unauthenticated_requests_fail()
    {
        $response = $this->getJson("/api/orgs/{$this->orgId}/analytics/realtime/campaign/camp-123/metrics");
        $response->assertStatus(401);

        $response = $this->postJson("/api/orgs/{$this->orgId}/analytics/metrics/custom", []);
        $response->assertStatus(401);

        $response = $this->postJson("/api/orgs/{$this->orgId}/analytics/roi/campaigns/camp-123", []);
        $response->assertStatus(401);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
