<?php

namespace Tests\Feature\Campaigns;

use Tests\TestCase;
use App\Models\Core\{User, Org, UserOrg, Role};
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->org = Org::factory()->create();

        $role = Role::factory()->create(['role_code' => 'owner']);
        UserOrg::factory()->create([
            'user_id' => $this->user->user_id,
            'org_id' => $this->org->org_id,
            'role_id' => $role->role_id,
            'is_active' => true,
        ]);
    }

    public function test_user_can_list_campaigns()
    {
        Campaign::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->org->org_id}/campaigns");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'campaigns' => [
                    'data' => [
                        '*' => ['campaign_id', 'campaign_name', 'status']
                    ]
                ]
            ]);
    }

    public function test_user_can_create_campaign()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orgs/{$this->org->org_id}/campaigns", [
                'campaign_name' => 'Summer Sale 2024',
                'description' => 'Summer promotional campaign',
                'status' => 'draft',
                'start_date' => '2024-06-01',
                'end_date' => '2024-08-31',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'campaign' => ['campaign_id', 'campaign_name'],
                'message',
            ]);

        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_name' => 'Summer Sale 2024',
            'org_id' => $this->org->org_id,
        ]);
    }

    public function test_user_can_view_campaign_details()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->org->org_id}/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200)
            ->assertJson([
                'campaign_id' => $campaign->campaign_id,
                'campaign_name' => $campaign->campaign_name,
            ]);
    }

    public function test_user_can_update_campaign()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/orgs/{$this->org->org_id}/campaigns/{$campaign->campaign_id}", [
                'campaign_name' => 'Updated Campaign Name',
                'status' => 'active',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'campaign_name' => 'Updated Campaign Name',
            'status' => 'active',
        ]);
    }

    public function test_user_can_delete_campaign()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/orgs/{$this->org->org_id}/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
        ]);
    }

    public function test_user_can_search_campaigns()
    {
        Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_name' => 'Summer Campaign',
        ]);

        Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_name' => 'Winter Campaign',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->org->org_id}/campaigns?search=Summer");

        $response->assertStatus(200);

        $data = $response->json('campaigns.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Summer Campaign', $data[0]['campaign_name']);
    }

    public function test_user_can_filter_campaigns_by_status()
    {
        Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$this->org->org_id}/campaigns?status=active");

        $response->assertStatus(200);

        $data = $response->json('campaigns.data');
        $this->assertCount(1, $data);
        $this->assertEquals('active', $data[0]['status']);
    }

    public function test_user_cannot_access_campaigns_from_another_org()
    {
        $anotherOrg = Org::factory()->create();
        $campaign = Campaign::factory()->create([
            'org_id' => $anotherOrg->org_id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orgs/{$anotherOrg->org_id}/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(403);
    }
}
