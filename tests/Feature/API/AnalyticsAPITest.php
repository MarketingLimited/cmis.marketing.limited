<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Campaign;
use App\Models\Analytics\CampaignMetric;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Analytics API Feature Tests
 */
class AnalyticsAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_get_campaign_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        CampaignMetric::create([
            'metric_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'date' => now()->format('Y-m-d'),
            'impressions' => 10000,
            'clicks' => 500,
            'conversions' => 50,
            'spend' => 250.00,
        ]);

        $response = $this->getJson("/api/analytics/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'impressions',
                         'clicks',
                         'conversions',
                         'spend',
                         'ctr',
                         'cpc',
                         'cpa',
                         'roi',
                     ],
                 ]);
    }

    #[Test]
    public function it_can_get_analytics_by_date_range()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        // Create metrics for multiple days
        for ($i = 0; $i < 7; $i++) {
            CampaignMetric::create([
                'metric_id' => Str::uuid(),
                'campaign_id' => $campaign->campaign_id,
                'org_id' => $org->org_id,
                'date' => now()->subDays($i)->format('Y-m-d'),
                'impressions' => 1000 * ($i + 1),
                'clicks' => 50 * ($i + 1),
                'conversions' => 5 * ($i + 1),
                'spend' => 50.00 * ($i + 1),
            ]);
        }

        $startDate = now()->subDays(6)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/analytics/campaigns/{$campaign->campaign_id}?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
                 ->assertJsonCount(7, 'data.daily_metrics');
    }

    #[Test]
    public function it_can_get_overall_organization_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $response = $this->getJson("/api/analytics/overview?org_id={$org->org_id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'total_campaigns',
                         'active_campaigns',
                         'total_impressions',
                         'total_clicks',
                         'total_conversions',
                         'total_spend',
                         'average_ctr',
                         'average_roi',
                     ],
                 ]);
    }

    #[Test]
    public function it_can_get_social_media_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $response = $this->getJson("/api/analytics/social?org_id={$org->org_id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'platforms' => [
                             '*' => [
                                 'platform',
                                 'followers',
                                 'posts',
                                 'engagement',
                                 'reach',
                             ],
                         ],
                     ],
                 ]);
    }

    #[Test]
    public function it_can_get_engagement_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $response = $this->getJson("/api/analytics/engagement?org_id={$org->org_id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'total_likes',
                         'total_comments',
                         'total_shares',
                         'engagement_rate',
                     ],
                 ]);
    }

    #[Test]
    public function it_can_compare_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign1 = $this->createTestCampaign($org->org_id, ['name' => 'Campaign 1']);
        $campaign2 = $this->createTestCampaign($org->org_id, ['name' => 'Campaign 2']);

        $response = $this->getJson("/api/analytics/compare?campaign_ids[]={$campaign1->campaign_id}&campaign_ids[]={$campaign2->campaign_id}");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.campaigns');
    }

    #[Test]
    public function it_can_get_funnel_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $response = $this->getJson("/api/analytics/funnel/{$campaign->campaign_id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'awareness',
                         'consideration',
                         'conversion',
                         'retention',
                         'drop_off_rates',
                     ],
                 ]);
    }

    #[Test]
    public function it_can_get_audience_demographics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $response = $this->getJson("/api/analytics/demographics?org_id={$org->org_id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'age_distribution',
                         'gender_distribution',
                         'location_distribution',
                         'interests',
                     ],
                 ]);
    }

    #[Test]
    public function it_can_get_content_performance()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $response = $this->getJson("/api/analytics/content-performance?org_id={$org->org_id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'top_posts',
                         'worst_posts',
                         'average_engagement',
                     ],
                 ]);
    }

    #[Test]
    public function it_can_calculate_roi()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $roiData = [
            'total_spend' => 5000.00,
            'total_revenue' => 15000.00,
        ];

        $response = $this->postJson("/api/analytics/roi/{$campaign->campaign_id}", $roiData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'roi_percentage',
                         'roi_ratio',
                         'profit',
                     ],
                 ])
                 ->assertJsonPath('data.roi_percentage', 200); // (15000-5000)/5000 * 100
    }

    #[Test]
    public function it_enforces_org_isolation_for_analytics()
    {
        $setup1 = $this->createUserWithOrg();
        $org1 = $setup1['org'];
        $user1 = $setup1['user'];

        $setup2 = $this->createUserWithOrg();
        $org2 = $setup2['org'];

        $campaign2 = $this->createTestCampaign($org2->org_id);

        $this->actingAsUserInOrg($user1, $org1);

        $response = $this->getJson("/api/analytics/campaigns/{$campaign2->campaign_id}");

        $response->assertStatus(403); // Forbidden - different org
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/analytics/overview');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_export_analytics_to_pdf()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $response = $this->getJson("/api/analytics/campaigns/{$campaign->campaign_id}/export?format=pdf");

        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function it_can_export_analytics_to_excel()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $response = $this->getJson("/api/analytics/campaigns/{$campaign->campaign_id}/export?format=excel");

        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
