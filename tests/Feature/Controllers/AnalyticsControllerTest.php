<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Analytics Controller Feature Tests
 */
class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_get_campaign_analytics()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/analytics/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'impressions',
                'clicks',
                'conversions',
                'spend',
                'revenue',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'endpoint' => 'GET /api/analytics/campaigns/{id}',
        ]);
    }

    /** @test */
    public function it_can_get_overview_analytics()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/analytics/overview');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_campaigns',
                'total_posts',
                'total_impressions',
                'total_engagement',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'endpoint' => 'GET /api/analytics/overview',
        ]);
    }

    /** @test */
    public function it_can_get_platform_analytics()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/analytics/platforms');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'platform',
                    'impressions',
                    'clicks',
                    'engagement_rate',
                ],
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'endpoint' => 'GET /api/analytics/platforms',
        ]);
    }

    /** @test */
    public function it_can_get_time_series_data()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/analytics/time-series?start_date=2024-01-01&end_date=2024-01-31');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'date',
                    'impressions',
                    'clicks',
                ],
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'endpoint' => 'GET /api/analytics/time-series',
        ]);
    }

    /** @test */
    public function it_can_export_analytics_report()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/analytics/export', [
            'format' => 'pdf',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'endpoint' => 'POST /api/analytics/export',
        ]);
    }

    /** @test */
    public function it_can_get_top_performing_content()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/analytics/top-content');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'content_id',
                    'title',
                    'impressions',
                    'engagement_rate',
                ],
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'endpoint' => 'GET /api/analytics/top-content',
        ]);
    }

    /** @test */
    public function it_can_compare_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign1 = $this->createTestCampaign($org->org_id);
        $campaign2 = $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/analytics/compare', [
            'campaign_ids' => [$campaign1->campaign_id, $campaign2->campaign_id],
        ]);

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'endpoint' => 'POST /api/analytics/compare',
        ]);
    }

    /** @test */
    public function it_validates_date_range()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/analytics/time-series?start_date=invalid');

        $response->assertStatus(422);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'test' => 'date_validation',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $campaign1 = $this->createTestCampaign($setup1['org']->org_id);

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->getJson("/api/analytics/campaigns/{$campaign1->campaign_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'test' => 'org_isolation',
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/analytics/overview');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'AnalyticsController',
            'test' => 'authentication_required',
        ]);
    }
}
