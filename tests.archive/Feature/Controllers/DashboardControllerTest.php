<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

use PHPUnit\Framework\Attributes\Test;
/**
 * Dashboard Controller Feature Tests
 */
class DashboardControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_shows_dashboard_for_authenticated_user()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'campaigns',
                'analytics',
                'recent_activity',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'endpoint' => 'GET /api/dashboard',
        ]);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_returns_campaign_summary()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        // Create test campaigns
        $this->createTestCampaign($org->org_id, ['status' => 'active']);
        $this->createTestCampaign($org->org_id, ['status' => 'completed']);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard/campaigns-summary');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total',
                'active',
                'completed',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'endpoint' => 'GET /api/dashboard/campaigns-summary',
        ]);
    }

    #[Test]
    public function it_returns_analytics_overview()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard/analytics-overview');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'impressions',
                'clicks',
                'conversions',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'endpoint' => 'GET /api/dashboard/analytics-overview',
        ]);
    }

    #[Test]
    public function it_returns_recent_activity()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard/recent-activity');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'description',
                    'timestamp',
                ],
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'endpoint' => 'GET /api/dashboard/recent-activity',
        ]);
    }

    #[Test]
    public function it_returns_top_performing_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard/top-campaigns');

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'endpoint' => 'GET /api/dashboard/top-campaigns',
        ]);
    }

    #[Test]
    public function it_returns_upcoming_scheduled_posts()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard/upcoming-posts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
        ]);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'endpoint' => 'GET /api/dashboard/upcoming-posts',
        ]);
    }

    #[Test]
    public function it_returns_budget_summary()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard/budget-summary');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_budget',
                'spent',
                'remaining',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'endpoint' => 'GET /api/dashboard/budget-summary',
        ]);
    }

    #[Test]
    public function it_filters_data_by_date_range()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard?start_date=2024-01-01&end_date=2024-01-31');

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'test' => 'date_filtering',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $this->createTestCampaign($setup1['org']->org_id);
        $this->createTestCampaign($setup2['org']->org_id);

        $this->actingAs($setup1['user'], 'sanctum');

        $response = $this->getJson('/api/dashboard/campaigns-summary');

        $response->assertStatus(200);
        // Should only see data from org 1

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_handles_empty_data_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'campaigns' => [],
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'DashboardController',
            'test' => 'empty_data',
        ]);
    }
}
