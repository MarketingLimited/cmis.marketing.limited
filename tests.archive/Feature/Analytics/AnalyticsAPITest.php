<?php

namespace Tests\Feature\Analytics;

use App\Models\Campaign\Campaign;
use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API tests for Phase 5-7 Analytics Endpoints (Phase 10)
 *
 * Tests backend APIs consumed by Alpine.js components:
 * - Real-time analytics
 * - ROI calculation
 * - Attribution modeling
 * - KPI tracking
 * - Enterprise alerts
 */
class AnalyticsAPITest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Org $org;
    protected Campaign $campaign;
    protected string $token;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization
        $this->org = Org::factory()->create([
            'name' => 'API Test Org',
            'slug' => 'api-test-org'
        ]);

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'API Test User',
            'email' => 'api@test.com',
            'active_org_id' => $this->org->org_id
        ]);

        // Attach user to org
        DB::table('cmis.user_orgs')->insert([
            'user_id' => $this->user->user_id,
            'org_id' => $this->org->org_id,
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Create test campaign
        $this->campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'API Test Campaign',
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(30),
            'budget' => 50000.00
        ]);

        // Create API token
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Set RLS context
        DB::statement("SET app.current_org_id = '{$this->org->org_id}'");
    }

    /**
     * Test real-time dashboard API requires authentication
     */
    public function test_realtime_dashboard_requires_authentication(): void
    {
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/analytics/realtime/dashboard");

        $response->assertStatus(401);
    }

    /**
     * Test real-time dashboard API returns structured data
     */
    public function test_realtime_dashboard_returns_structured_data(): void
    {
        $response = $this->withToken($this->token)
            ->getJson("/api/orgs/{$this->org->org_id}/analytics/realtime/dashboard?window=5m");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'campaigns' => [
                '*' => [
                    'campaign_id',
                    'campaign_name',
                    'impressions',
                    'clicks',
                    'conversions',
                    'spend',
                    'ctr'
                ]
            ],
            'totals' => [
                'impressions',
                'clicks',
                'conversions',
                'spend'
            ],
            'derived_metrics' => [
                'ctr',
                'cpc',
                'conversion_rate'
            ]
        ]);
    }

    /**
     * Test ROI calculation API requires authentication
     */
    public function test_roi_calculation_requires_authentication(): void
    {
        $response = $this->postJson(
            "/api/orgs/{$this->org->org_id}/analytics/roi/campaigns/{$this->campaign->campaign_id}",
            ['date_range' => ['start' => '2025-01-01', 'end' => '2025-01-31']]
        );

        $response->assertStatus(401);
    }

    /**
     * Test ROI calculation API returns financial metrics
     */
    public function test_roi_calculation_returns_financial_metrics(): void
    {
        $response = $this->withToken($this->token)
            ->postJson(
                "/api/orgs/{$this->org->org_id}/analytics/roi/campaigns/{$this->campaign->campaign_id}",
                [
                    'date_range' => [
                        'start' => now()->subDays(30)->toDateString(),
                        'end' => now()->toDateString()
                    ]
                ]
            );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'financial_metrics' => [
                'total_spend',
                'total_revenue',
                'profit',
                'roi_percentage',
                'roas'
            ],
            'profitability' => [
                'status',
                'message',
                'gross_margin',
                'net_margin',
                'break_even_point'
            ]
        ]);
    }

    /**
     * Test attribution API supports multiple models
     */
    public function test_attribution_supports_multiple_models(): void
    {
        $models = ['last-click', 'first-click', 'linear', 'time-decay', 'position-based', 'data-driven'];

        foreach ($models as $model) {
            $response = $this->withToken($this->token)
                ->postJson(
                    "/api/orgs/{$this->org->org_id}/analytics/attribution/campaigns/{$this->campaign->campaign_id}/insights",
                    [
                        'model' => $model,
                        'date_range' => [
                            'start' => now()->subDays(30)->toDateString(),
                            'end' => now()->toDateString()
                        ]
                    ]
                );

            $response->assertStatus(200);
            $response->assertJsonStructure([
                'success',
                'model',
                'insights' => [
                    '*' => [
                        'channel',
                        'touchpoints',
                        'contribution_percentage',
                        'attributed_conversions'
                    ]
                ]
            ]);
        }
    }

    /**
     * Test KPI dashboard API returns health scores
     */
    public function test_kpi_dashboard_returns_health_scores(): void
    {
        $response = $this->withToken($this->token)
            ->getJson("/api/orgs/{$this->org->org_id}/analytics/kpis/dashboard?entity_type=campaign&entity_id={$this->campaign->campaign_id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'kpis' => [
                '*' => [
                    'kpi_id',
                    'kpi_name',
                    'current_value',
                    'target_value',
                    'status',
                    'progress_percentage',
                    'gap'
                ]
            ],
            'summary' => [
                'health_score',
                'status_counts' => [
                    'exceeded',
                    'on_track',
                    'at_risk',
                    'off_track'
                ]
            ]
        ]);
    }

    /**
     * Test enterprise alerts API returns active alerts
     */
    public function test_enterprise_alerts_api_returns_active_alerts(): void
    {
        $response = $this->withToken($this->token)
            ->getJson("/api/orgs/{$this->org->org_id}/enterprise/alerts?status=active");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'alerts' => [
                '*' => [
                    'alert_id',
                    'type',
                    'severity',
                    'message',
                    'status',
                    'created_at'
                ]
            ]
        ]);
    }

    /**
     * Test alert acknowledgment API
     */
    public function test_alert_acknowledgment_works(): void
    {
        // First create an alert (this would normally be created by the system)
        $alertId = \Illuminate\Support\Str::uuid()->toString();

        DB::table('cmis_enterprise.alerts')->insert([
            'alert_id' => $alertId,
            'org_id' => $this->org->org_id,
            'type' => 'budget_exceeded',
            'severity' => 'high',
            'message' => 'Test alert',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->withToken($this->token)
            ->postJson(
                "/api/orgs/{$this->org->org_id}/enterprise/alerts/{$alertId}/acknowledge",
                [
                    'acknowledged_by' => $this->user->user_id,
                    'notes' => 'Acknowledged in test'
                ]
            );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify alert was updated
        $alert = DB::table('cmis_enterprise.alerts')->where('alert_id', $alertId)->first();
        $this->assertEquals('acknowledged', $alert->status);
    }

    /**
     * Test LTV calculation API
     */
    public function test_ltv_calculation_returns_customer_metrics(): void
    {
        $response = $this->withToken($this->token)
            ->getJson("/api/orgs/{$this->org->org_id}/analytics/roi/campaigns/{$this->campaign->campaign_id}/ltv");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'ltv' => [
                'average_ltv',
                'total_customer_value',
                'customer_acquisition_cost',
                'ltv_cac_ratio',
                'payback_period_days'
            ]
        ]);
    }

    /**
     * Test ROI projection API
     */
    public function test_roi_projection_returns_forecasts(): void
    {
        $response = $this->withToken($this->token)
            ->postJson(
                "/api/orgs/{$this->org->org_id}/analytics/roi/campaigns/{$this->campaign->campaign_id}/project",
                ['days_to_project' => 30]
            );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'projected_metrics' => [
                'projected_spend',
                'projected_revenue',
                'projected_profit',
                'projected_roi'
            ],
            'confidence_level' => [
                'level',
                'percentage'
            ]
        ]);
    }

    /**
     * Test API respects multi-tenancy (RLS)
     */
    public function test_apis_respect_multi_tenancy(): void
    {
        // Create another org with campaign
        $otherOrg = Org::factory()->create(['name' => 'Other API Org']);
        $otherCampaign = Campaign::factory()->create([
            'org_id' => $otherOrg->org_id,
            'name' => 'Other Org Campaign'
        ]);

        // Try to access other org's campaign analytics
        $response = $this->withToken($this->token)
            ->postJson(
                "/api/orgs/{$this->org->org_id}/analytics/roi/campaigns/{$otherCampaign->campaign_id}",
                ['date_range' => ['start' => '2025-01-01', 'end' => '2025-01-31']]
            );

        // Should fail or return empty/error (depending on implementation)
        // At minimum, should not return the other org's data
        $this->assertTrue(
            $response->status() === 404 ||
            $response->status() === 403 ||
            ($response->status() === 200 && $response->json('success') === false)
        );
    }

    /**
     * Test API validation for invalid date ranges
     */
    public function test_api_validates_date_ranges(): void
    {
        $response = $this->withToken($this->token)
            ->postJson(
                "/api/orgs/{$this->org->org_id}/analytics/roi/campaigns/{$this->campaign->campaign_id}",
                [
                    'date_range' => [
                        'start' => '2025-12-31',
                        'end' => '2025-01-01' // End before start
                    ]
                ]
            );

        $response->assertStatus(422); // Validation error
    }

    /**
     * Test API handles missing required parameters
     */
    public function test_api_handles_missing_parameters(): void
    {
        $response = $this->withToken($this->token)
            ->postJson(
                "/api/orgs/{$this->org->org_id}/analytics/attribution/campaigns/{$this->campaign->campaign_id}/insights",
                [] // Missing required parameters
            );

        $this->assertContains($response->status(), [400, 422]);
    }

    /**
     * Test API returns consistent error format
     */
    public function test_api_returns_consistent_error_format(): void
    {
        $response = $this->withToken($this->token)
            ->getJson("/api/orgs/{$this->org->org_id}/analytics/roi/campaigns/invalid-uuid");

        $response->assertJsonStructure([
            'success',
            'error' // or 'message'
        ]);

        $response->assertJson(['success' => false]);
    }

    /**
     * Test API performance - response time acceptable
     */
    public function test_api_response_time_is_acceptable(): void
    {
        $startTime = microtime(true);

        $response = $this->withToken($this->token)
            ->getJson("/api/orgs/{$this->org->org_id}/analytics/realtime/dashboard?window=5m");

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Response should be under 2 seconds (2000ms)
        $this->assertLessThan(2000, $responseTime, "API response took {$responseTime}ms, should be under 2000ms");
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        DB::statement("RESET app.current_org_id");
        parent::tearDown();
    }
}
