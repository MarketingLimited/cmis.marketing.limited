<?php

namespace Tests\Feature\Influencer;

use App\Models\Influencer\Influencer;
use App\Models\Influencer\InfluencerCampaign;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerCampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected Influencer $influencer;

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

        $this->influencer = Influencer::factory()->create([
            'org_id' => $this->org->org_id,
        ]);
    }

    /** @test */
    public function it_can_list_campaigns()
    {
        InfluencerCampaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.campaigns.index'));

        $response->assertOk();
        $response->assertViewIs('influencer.campaigns.index');
        $response->assertViewHas('campaigns');
    }

    /** @test */
    public function it_can_create_a_campaign()
    {
        $data = [
            'influencer_id' => $this->influencer->influencer_id,
            'name' => 'Test Campaign',
            'campaign_type' => 'sponsored_post',
            'status' => 'draft',
            'budget' => 5000,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('influencer.campaigns.store'), $data);

        $this->assertDatabaseHas('cmis_influencer.influencer_campaigns', [
            'org_id' => $this->org->org_id,
            'name' => 'Test Campaign',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_show_a_campaign()
    {
        $campaign = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.campaigns.show', $campaign->campaign_id));

        $response->assertOk();
        $response->assertViewIs('influencer.campaigns.show');
        $response->assertViewHas('campaign');
    }

    /** @test */
    public function it_can_update_a_campaign()
    {
        $campaign = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
            'name' => 'Original Name',
        ]);

        $data = [
            'name' => 'Updated Name',
            'campaign_type' => 'sponsored_post',
            'status' => 'active',
            'budget' => 6000,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('influencer.campaigns.update', $campaign->campaign_id), $data);

        $this->assertDatabaseHas('cmis_influencer.influencer_campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_a_campaign()
    {
        $campaign = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('influencer.campaigns.destroy', $campaign->campaign_id));

        $this->assertSoftDeleted('cmis_influencer.influencer_campaigns', [
            'campaign_id' => $campaign->campaign_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_get_campaign_performance()
    {
        $campaign = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.campaigns.performance', $campaign->campaign_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'campaign_id',
                'total_reach',
                'total_engagement',
                'engagement_rate',
                'roi',
            ],
        ]);
    }

    /** @test */
    public function it_can_update_campaign_status()
    {
        $campaign = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.campaigns.updateStatus', $campaign->campaign_id), [
                'status' => 'active',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_influencer.influencer_campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_can_get_campaign_analytics()
    {
        InfluencerCampaign::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.campaigns.analytics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'summary',
                'by_type',
                'by_status',
            ],
        ]);
    }

    /** @test */
    public function it_can_bulk_update_campaigns()
    {
        $campaigns = InfluencerCampaign::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.campaigns.bulkUpdate'), [
                'campaign_ids' => $campaigns->pluck('campaign_id')->toArray(),
                'status' => 'active',
            ]);

        $response->assertOk();

        foreach ($campaigns as $campaign) {
            $this->assertDatabaseHas('cmis_influencer.influencer_campaigns', [
                'campaign_id' => $campaign->campaign_id,
                'status' => 'active',
            ]);
        }
    }

    /** @test */
    public function it_can_export_campaign_report()
    {
        $campaign = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.campaigns.export', $campaign->campaign_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'campaign',
                'influencer',
                'performance',
                'exported_at',
            ],
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();
        $otherInfluencer = Influencer::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $otherCampaign = InfluencerCampaign::factory()->create([
            'org_id' => $otherOrg->org_id,
            'influencer_id' => $otherInfluencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.campaigns.show', $otherCampaign->campaign_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $data = [];

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.campaigns.store'), $data);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_filters_campaigns_by_influencer()
    {
        $influencer2 = Influencer::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $influencer2->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.campaigns.index', ['influencer_id' => $this->influencer->influencer_id]));

        $response->assertOk();
    }

    /** @test */
    public function it_filters_campaigns_by_status()
    {
        InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'active',
        ]);

        InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.campaigns.index', ['status' => 'active']));

        $response->assertOk();
    }
}
