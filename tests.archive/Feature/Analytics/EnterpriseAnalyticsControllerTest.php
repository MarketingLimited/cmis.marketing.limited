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
 * Feature tests for EnterpriseAnalyticsController (Phase 10)
 *
 * Tests all analytics dashboard routes with authentication,
 * multi-tenancy, and data isolation
 */
class EnterpriseAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Org $org;
    protected Campaign $campaign;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization
        $this->org = Org::factory()->create([
            'name' => 'Test Analytics Org',
            'slug' => 'test-analytics-org'
        ]);

        // Create test user and associate with org
        $this->user = User::factory()->create([
            'name' => 'Test Analytics User',
            'email' => 'analytics@test.com',
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
            'name' => 'Test Analytics Campaign',
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(30),
            'budget' => 10000.00
        ]);

        // Set RLS context
        DB::statement("SET app.current_org_id = '{$this->org->org_id}'");
    }

    /**
     * Test enterprise analytics hub requires authentication
     */
    public function test_enterprise_hub_requires_authentication(): void
    {
        $response = $this->get(route('analytics.enterprise'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test enterprise analytics hub loads successfully
     */
    public function test_enterprise_hub_loads_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.enterprise'));

        $response->assertStatus(200);
        $response->assertViewIs('analytics.enterprise');
        $response->assertViewHas('orgId', $this->org->org_id);
        $response->assertViewHas('activeCampaigns');
        $response->assertSee('Enterprise Analytics Hub');
    }

    /**
     * Test real-time dashboard requires authentication
     */
    public function test_realtime_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('analytics.realtime'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test real-time dashboard loads successfully
     */
    public function test_realtime_dashboard_loads_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.realtime'));

        $response->assertStatus(200);
        $response->assertViewIs('analytics.realtime');
        $response->assertViewHas('orgId', $this->org->org_id);
        $response->assertSee('Real-Time Analytics Dashboard');
    }

    /**
     * Test campaigns list requires authentication
     */
    public function test_campaigns_list_requires_authentication(): void
    {
        $response = $this->get(route('analytics.campaigns'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test campaigns list loads successfully
     */
    public function test_campaigns_list_loads_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.campaigns'));

        $response->assertStatus(200);
        $response->assertViewIs('analytics.campaigns');
        $response->assertViewHas('orgId', $this->org->org_id);
        $response->assertViewHas('campaigns');
        $response->assertSee('Campaign Analytics');
    }

    /**
     * Test campaigns list shows only org's campaigns (RLS)
     */
    public function test_campaigns_list_respects_multi_tenancy(): void
    {
        // Create another org with campaign
        $otherOrg = Org::factory()->create(['name' => 'Other Org']);
        $otherCampaign = Campaign::factory()->create([
            'org_id' => $otherOrg->org_id,
            'name' => 'Other Org Campaign'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.campaigns'));

        $response->assertStatus(200);
        $response->assertSee($this->campaign->name);
        $response->assertDontSee($otherCampaign->name);
    }

    /**
     * Test campaign analytics requires authentication
     */
    public function test_campaign_analytics_requires_authentication(): void
    {
        $response = $this->get(route('analytics.campaign', $this->campaign->campaign_id));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test campaign analytics loads successfully
     */
    public function test_campaign_analytics_loads_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.campaign', $this->campaign->campaign_id));

        $response->assertStatus(200);
        $response->assertViewIs('analytics.campaign');
        $response->assertViewHas('orgId', $this->org->org_id);
        $response->assertViewHas('campaignId', $this->campaign->campaign_id);
        $response->assertViewHas('campaign');
        $response->assertSee($this->campaign->name);
    }

    /**
     * Test campaign analytics blocks access to other org's campaigns
     */
    public function test_campaign_analytics_respects_multi_tenancy(): void
    {
        // Create another org with campaign
        $otherOrg = Org::factory()->create(['name' => 'Other Org']);
        $otherCampaign = Campaign::factory()->create([
            'org_id' => $otherOrg->org_id,
            'name' => 'Other Org Campaign'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.campaign', $otherCampaign->campaign_id));

        $response->assertStatus(404);
    }

    /**
     * Test campaign analytics returns 404 for non-existent campaign
     */
    public function test_campaign_analytics_returns_404_for_invalid_campaign(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.campaign', 'invalid-uuid'));

        $response->assertStatus(404);
    }

    /**
     * Test KPI dashboard requires authentication
     */
    public function test_kpi_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('analytics.kpis'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test KPI dashboard loads successfully (org-level)
     */
    public function test_kpi_dashboard_loads_org_level(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.kpis'));

        $response->assertStatus(200);
        $response->assertViewIs('analytics.kpis');
        $response->assertViewHas('orgId', $this->org->org_id);
        $response->assertViewHas('entityType', 'org');
        $response->assertViewHas('entityId', $this->org->org_id);
        $response->assertSee('KPI Performance Dashboard');
    }

    /**
     * Test KPI dashboard loads successfully (campaign-level)
     */
    public function test_kpi_dashboard_loads_campaign_level(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.kpis.entity', [
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('analytics.kpis');
        $response->assertViewHas('entityType', 'campaign');
        $response->assertViewHas('entityId', $this->campaign->campaign_id);
        $response->assertSee($this->campaign->name);
    }

    /**
     * Test analytics index redirects to enterprise hub
     */
    public function test_analytics_index_redirects_to_enterprise(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.index'));

        $response->assertStatus(302);
        $response->assertRedirect(route('analytics.enterprise'));
    }

    /**
     * Test user without active org gets 404
     */
    public function test_user_without_org_gets_error(): void
    {
        $userWithoutOrg = User::factory()->create([
            'name' => 'No Org User',
            'email' => 'noorg@test.com',
            'active_org_id' => null
        ]);

        Sanctum::actingAs($userWithoutOrg);

        $response = $this->get(route('analytics.enterprise'));

        $response->assertStatus(404);
    }

    /**
     * Test view passes correct data structure to enterprise hub
     */
    public function test_enterprise_hub_has_correct_data_structure(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.enterprise'));

        $response->assertStatus(200);
        $response->assertViewHas('activeCampaigns', function ($campaigns) {
            return $campaigns->count() > 0 && $campaigns->first()->campaign_id === $this->campaign->campaign_id;
        });
    }

    /**
     * Test campaign analytics view has all required data
     */
    public function test_campaign_analytics_has_complete_data(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get(route('analytics.campaign', $this->campaign->campaign_id));

        $response->assertStatus(200);
        $response->assertViewHas('campaign', function ($campaign) {
            return $campaign->campaign_id === $this->campaign->campaign_id
                && isset($campaign->name)
                && isset($campaign->status)
                && isset($campaign->start_date)
                && isset($campaign->budget);
        });
    }

    /**
     * Test all analytics routes are properly named
     */
    public function test_all_analytics_routes_exist(): void
    {
        $this->assertTrue(\Route::has('analytics.index'));
        $this->assertTrue(\Route::has('analytics.enterprise'));
        $this->assertTrue(\Route::has('analytics.realtime'));
        $this->assertTrue(\Route::has('analytics.campaigns'));
        $this->assertTrue(\Route::has('analytics.campaign'));
        $this->assertTrue(\Route::has('analytics.kpis'));
        $this->assertTrue(\Route::has('analytics.kpis.entity'));
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
