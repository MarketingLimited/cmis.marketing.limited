<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\Campaign;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use PHPUnit\Framework\Attributes\Test;

/**
 * Campaign Performance API Tests (Phase 2 - Option 3)
 *
 * Tests the new campaign performance dashboard endpoints:
 * - Performance metrics
 * - Campaign comparison
 * - Performance trends
 * - Top performing campaigns
 */
class CampaignPerformanceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_get_campaign_performance_metrics()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Test Performance Campaign',
            'status' => 'active',
        ]);

        // Create some test metrics data
        $this->createTestMetrics($campaign->campaign_id, [
            ['kpi' => 'impressions', 'observed' => 1000],
            ['kpi' => 'clicks', 'observed' => 50],
            ['kpi' => 'conversions', 'observed' => 5],
            ['kpi' => 'spend', 'observed' => 100.00],
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-metrics");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'summary',
                'metrics',
                'trends',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'GET /api/campaigns/{id}/performance-metrics',
        ]);
    }

    #[Test]
    public function it_can_get_performance_metrics_with_date_range()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-metrics?" . http_build_query([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'performance_metrics_with_date_range',
        ]);
    }

    #[Test]
    public function it_can_compare_multiple_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign1 = $this->createTestCampaign($org->org_id, ['name' => 'Campaign A']);
        $campaign2 = $this->createTestCampaign($org->org_id, ['name' => 'Campaign B']);
        $campaign3 = $this->createTestCampaign($org->org_id, ['name' => 'Campaign C']);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/campaigns/compare', [
            'campaign_ids' => [
                $campaign1->campaign_id,
                $campaign2->campaign_id,
                $campaign3->campaign_id,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'campaigns',
                'comparison',
                'summary',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'POST /api/campaigns/compare',
        ]);
    }

    #[Test]
    public function it_validates_campaign_comparison_input()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        // Test without campaign_ids
        $response = $this->postJson('/api/campaigns/compare', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('campaign_ids');

        // Test with too many campaigns (max 10)
        $response = $this->postJson('/api/campaigns/compare', [
            'campaign_ids' => array_fill(0, 11, '550e8400-e29b-41d4-a716-446655440000'),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('campaign_ids');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'campaign_comparison_validation',
        ]);
    }

    #[Test]
    public function it_can_get_performance_trends()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Trending Campaign',
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-trends");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'trends',
                'period',
                'interval',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'GET /api/campaigns/{id}/performance-trends',
        ]);
    }

    #[Test]
    public function it_can_get_performance_trends_with_custom_interval()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        // Test with week interval
        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-trends?interval=week&periods=12");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Test with month interval
        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-trends?interval=month&periods=6");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'performance_trends_custom_interval',
        ]);
    }

    #[Test]
    public function it_validates_performance_trends_parameters()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        // Test with invalid interval
        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-trends?interval=invalid");
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('interval');

        // Test with periods exceeding limit
        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-trends?periods=500");
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('periods');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'performance_trends_validation',
        ]);
    }

    #[Test]
    public function it_can_get_top_performing_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        // Create multiple campaigns
        $this->createTestCampaign($org->org_id, ['name' => 'Campaign 1', 'status' => 'active']);
        $this->createTestCampaign($org->org_id, ['name' => 'Campaign 2', 'status' => 'active']);
        $this->createTestCampaign($org->org_id, ['name' => 'Campaign 3', 'status' => 'active']);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/campaigns/top-performing');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'campaigns',
                'metric',
                'period',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'GET /api/campaigns/top-performing',
        ]);
    }

    #[Test]
    public function it_can_filter_top_performing_by_metric()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->createTestCampaign($org->org_id, ['status' => 'active']);
        $this->createTestCampaign($org->org_id, ['status' => 'active']);

        $this->actingAs($user, 'sanctum');

        // Test different metrics
        $metrics = ['impressions', 'clicks', 'conversions', 'spend', 'roi'];

        foreach ($metrics as $metric) {
            $response = $this->getJson("/api/campaigns/top-performing?metric={$metric}");
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
        }

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'top_performing_by_metric',
        ]);
    }

    #[Test]
    public function it_can_limit_top_performing_results()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        // Create 15 campaigns
        for ($i = 1; $i <= 15; $i++) {
            $this->createTestCampaign($org->org_id, [
                'name' => "Campaign {$i}",
                'status' => 'active',
            ]);
        }

        $this->actingAs($user, 'sanctum');

        // Request top 5
        $response = $this->getJson('/api/campaigns/top-performing?limit=5');
        $response->assertStatus(200);

        // Should return at most 5 campaigns
        $data = $response->json('data.campaigns');
        if ($data) {
            $this->assertLessThanOrEqual(5, count($data));
        }

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'top_performing_with_limit',
        ]);
    }

    #[Test]
    public function it_validates_top_performing_parameters()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        // Test with invalid metric
        $response = $this->getJson('/api/campaigns/top-performing?metric=invalid');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('metric');

        // Test with limit exceeding maximum
        $response = $this->getJson('/api/campaigns/top-performing?limit=100');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('limit');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'top_performing_validation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_performance_metrics()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $campaign1 = $this->createTestCampaign($setup1['org']->org_id);

        $this->actingAs($setup2['user'], 'sanctum');

        // Should not be able to access performance metrics from another org
        $response = $this->getJson("/api/campaigns/{$campaign1->campaign_id}/performance-metrics");
        $response->assertStatus(404);

        $response = $this->getJson("/api/campaigns/{$campaign1->campaign_id}/performance-trends");
        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'performance_org_isolation',
        ]);
    }

    #[Test]
    public function it_prevents_cross_org_campaign_comparison()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $campaign1 = $this->createTestCampaign($setup1['org']->org_id);
        $campaign2 = $this->createTestCampaign($setup2['org']->org_id);

        $this->actingAs($setup1['user'], 'sanctum');

        // Should not be able to compare campaigns from different orgs
        $response = $this->postJson('/api/campaigns/compare', [
            'campaign_ids' => [
                $campaign1->campaign_id,
                $campaign2->campaign_id,
            ],
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'comparison_cross_org_prevention',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_performance_endpoints()
    {
        $setup = $this->createUserWithOrg();
        $campaign = $this->createTestCampaign($setup['org']->org_id);

        // Test all performance endpoints without authentication
        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-metrics");
        $response->assertStatus(401);

        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/performance-trends");
        $response->assertStatus(401);

        $response = $this->postJson('/api/campaigns/compare', [
            'campaign_ids' => [$campaign->campaign_id],
        ]);
        $response->assertStatus(401);

        $response = $this->getJson('/api/campaigns/top-performing');
        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'performance_authentication_required',
        ]);
    }

    /**
     * Helper: Create test metrics for a campaign
     */
    private function createTestMetrics(string $campaignId, array $metrics): void
    {
        foreach ($metrics as $metric) {
            DB::table('cmis.performance_metrics')->insert([
                'metric_id' => \Illuminate\Support\Str::uuid()->toString(),
                'campaign_id' => $campaignId,
                'kpi' => $metric['kpi'],
                'observed' => $metric['observed'],
                'recorded_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
