<?php

namespace Tests\Feature\Core;

use Tests\TestCase;
use App\Models\User;
use App\Models\Core\Org;
use App\Models\Market\Market;
use App\Models\Market\OrgMarket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class OrgMarketControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Org $org;
    protected Market $market;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization
        $this->org = Org::factory()->create();

        // Create test user
        $this->user = User::factory()->create([
            'current_org_id' => $this->org->org_id,
        ]);

        // Create test market
        $this->market = Market::factory()->create();

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_organization_markets()
    {
        OrgMarket::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/markets");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['org_id', 'market_id', 'priority_level', 'status'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJson(['success' => true]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_can_filter_markets_by_status()
    {
        OrgMarket::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        OrgMarket::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'planning',
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/markets?status=active");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function it_can_add_market_to_organization()
    {
        $data = [
            'market_id' => $this->market->market_id,
            'status' => 'planning',
            'priority_level' => 8,
            'investment_budget' => 50000.00,
            'is_primary_market' => true,
            'target_audience' => ['segment1', 'segment2'],
        ];

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/markets", $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['org_id', 'market_id', 'priority_level'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'market_id' => $this->market->market_id,
                    'priority_level' => 8,
                ],
            ]);

        $this->assertDatabaseHas('cmis.org_markets', [
            'org_id' => $this->org->org_id,
            'market_id' => $this->market->market_id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_adding_market()
    {
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/markets", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['market_id', 'status', 'priority_level']);
    }

    /** @test */
    public function it_prevents_duplicate_markets()
    {
        OrgMarket::factory()->create([
            'org_id' => $this->org->org_id,
            'market_id' => $this->market->market_id,
        ]);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/markets", [
            'market_id' => $this->market->market_id,
            'status' => 'active',
            'priority_level' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This market is already added to your organization',
            ]);
    }

    /** @test */
    public function it_can_show_organization_market()
    {
        $orgMarket = OrgMarket::factory()->create([
            'org_id' => $this->org->org_id,
            'market_id' => $this->market->market_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/markets/{$this->market->market_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'org_id' => $this->org->org_id,
                    'market_id' => $this->market->market_id,
                ],
            ]);
    }

    /** @test */
    public function it_can_update_organization_market()
    {
        $orgMarket = OrgMarket::factory()->create([
            'org_id' => $this->org->org_id,
            'market_id' => $this->market->market_id,
            'priority_level' => 5,
            'status' => 'planning',
        ]);

        $response = $this->putJson("/api/orgs/{$this->org->org_id}/markets/{$this->market->market_id}", [
            'priority_level' => 9,
            'status' => 'active',
            'market_share' => 15.5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'priority_level' => 9,
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('cmis.org_markets', [
            'org_id' => $this->org->org_id,
            'market_id' => $this->market->market_id,
            'priority_level' => 9,
        ]);
    }

    /** @test */
    public function it_can_remove_market_from_organization()
    {
        $orgMarket = OrgMarket::factory()->create([
            'org_id' => $this->org->org_id,
            'market_id' => $this->market->market_id,
        ]);

        $response = $this->deleteJson("/api/orgs/{$this->org->org_id}/markets/{$this->market->market_id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('cmis.org_markets', [
            'org_id' => $this->org->org_id,
            'market_id' => $this->market->market_id,
        ]);
    }

    /** @test */
    public function it_can_list_available_markets()
    {
        // Create some markets
        $market1 = Market::factory()->create();
        $market2 = Market::factory()->create();
        $market3 = Market::factory()->create();

        // Add one to organization
        OrgMarket::factory()->create([
            'org_id' => $this->org->org_id,
            'market_id' => $market1->market_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/markets/available");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Should not include the already added market
        $marketIds = collect($response->json('data'))->pluck('market_id')->toArray();
        $this->assertNotContains($market1->market_id, $marketIds);
        $this->assertContains($market2->market_id, $marketIds);
    }

    /** @test */
    public function it_can_get_organization_market_stats()
    {
        OrgMarket::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
            'investment_budget' => 10000,
        ]);

        OrgMarket::factory()->count(2)->create([
            'org_id' => $this->org->org_id,
            'status' => 'planning',
            'investment_budget' => 5000,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/markets/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_markets',
                    'active_markets',
                    'total_investment',
                    'by_status',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(5, $data['total_markets']);
        $this->assertEquals(3, $data['active_markets']);
        $this->assertEquals(40000, $data['total_investment']);
    }

    /** @test */
    public function it_can_calculate_roi()
    {
        $orgMarket = OrgMarket::factory()->create([
            'org_id' => $this->org->org_id,
            'market_id' => $this->market->market_id,
            'investment_budget' => 10000,
        ]);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/markets/{$this->market->market_id}/roi", [
            'revenue' => 15000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'market_id' => $this->market->market_id,
                    'investment' => 10000,
                    'revenue' => 15000,
                    'profit' => 5000,
                ],
            ]);

        // ROI should be 50% ((15000-10000)/10000 * 100)
        $this->assertEquals(50, $response->json('data.roi_percentage'));
    }

    /** @test */
    public function it_validates_priority_level_range()
    {
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/markets", [
            'market_id' => $this->market->market_id,
            'status' => 'active',
            'priority_level' => 15, // Out of range (1-10)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority_level']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        Sanctum::actingAs(null);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/markets");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_cannot_access_markets_from_different_org()
    {
        $otherOrg = Org::factory()->create();
        $orgMarket = OrgMarket::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/markets/{$orgMarket->market_id}");

        $response->assertStatus(404);
    }
}
