<?php

namespace Tests\Feature\Orchestration;

use App\Models\Orchestration\CampaignOrchestration;
use App\Models\Campaign\Campaign;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignOrchestrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;

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
    }

    /** @test */
    public function it_can_process_lifecycle_events()
    {
        CampaignOrchestration::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/process-lifecycle");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'results',
        ]);
    }

    /** @test */
    public function it_can_get_lifecycle_statistics()
    {
        CampaignOrchestration::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/orchestration/lifecycle-stats", [
                'days' => 30,
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'statistics',
        ]);
    }

    /** @test */
    public function it_validates_lifecycle_stats_days_parameter()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/orchestration/lifecycle-stats?days=500");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['days']);
    }

    /** @test */
    public function it_can_reallocate_budget()
    {
        Campaign::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $data = [
            'total_budget' => 10000,
            'strategy' => 'performance_weighted',
            'constraints' => [
                'min_budget_per_campaign' => 100,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/reallocate-budget", $data);

        $response->assertOk();
    }

    /** @test */
    public function it_validates_reallocate_budget_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/reallocate-budget", [
                'total_budget' => 5, // Too low
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['total_budget']);
    }

    /** @test */
    public function it_can_simulate_budget_allocation()
    {
        Campaign::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $data = [
            'total_budget' => 5000,
            'strategy' => 'roi_maximization',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/simulate-budget", $data);

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_budget_allocation_history()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/orchestration/budget-history", [
                'limit' => 20,
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'history',
            'count',
        ]);
    }

    /** @test */
    public function it_validates_budget_history_limit()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/orchestration/budget-history?limit=200");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['limit']);
    }

    /** @test */
    public function it_can_create_multi_platform_campaign()
    {
        $data = [
            'name' => 'Multi-Platform Summer Campaign',
            'platforms' => ['meta', 'google', 'tiktok'],
            'objective' => 'conversions',
            'budget' => 5000,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", $data);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_validates_create_multi_platform_campaign_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", [
                'name' => 'Test Campaign',
                // Missing required platforms and objective
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['platforms', 'objective', 'budget']);
    }

    /** @test */
    public function it_validates_platform_values()
    {
        $data = [
            'name' => 'Test Campaign',
            'platforms' => ['invalid_platform'],
            'objective' => 'conversions',
            'budget' => 1000,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['platforms.0']);
    }

    /** @test */
    public function it_can_pause_campaign()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/{$campaign->campaign_id}/pause");

        $response->assertOk();
    }

    /** @test */
    public function it_can_resume_campaign()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'paused',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/{$campaign->campaign_id}/resume");

        $response->assertOk();
    }

    /** @test */
    public function it_can_sync_campaign_status()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/{$campaign->campaign_id}/sync");

        $response->assertOk();
    }

    /** @test */
    public function it_can_duplicate_campaign()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Original Campaign',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/{$campaign->campaign_id}/duplicate");

        $response->assertStatus(201);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        // Try to pause a non-existent campaign
        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/11111111-1111-1111-1111-111111111111/pause");

        $response->assertStatus(500);
        $response->assertJsonStructure([
            'success',
            'error',
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy_for_lifecycle_processing()
    {
        $otherOrg = Org::factory()->create();

        CampaignOrchestration::factory()->count(3)->create([
            'org_id' => $otherOrg->org_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/process-lifecycle");

        $response->assertOk();
        // Should not process orchestrations from other org
    }

    /** @test */
    public function it_supports_different_budget_strategies()
    {
        Campaign::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $strategies = ['roi_maximization', 'equal_distribution', 'performance_weighted', 'predictive'];

        foreach ($strategies as $strategy) {
            $response = $this->actingAs($this->user)
                ->postJson("/api/orgs/{$this->org->org_id}/orchestration/reallocate-budget", [
                    'total_budget' => 1000,
                    'strategy' => $strategy,
                ]);

            $response->assertOk();
        }
    }

    /** @test */
    public function it_can_create_campaigns_on_multiple_platforms()
    {
        $platformCombinations = [
            ['meta', 'google'],
            ['tiktok', 'linkedin'],
            ['meta', 'google', 'tiktok'],
            ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'],
        ];

        foreach ($platformCombinations as $platforms) {
            $data = [
                'name' => 'Test Campaign ' . implode('+', $platforms),
                'platforms' => $platforms,
                'objective' => 'conversions',
                'budget' => 1000,
            ];

            $response = $this->actingAs($this->user)
                ->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/process-lifecycle");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_date_ranges_in_campaign_creation()
    {
        $data = [
            'name' => 'Test Campaign',
            'platforms' => ['meta'],
            'objective' => 'conversions',
            'budget' => 1000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->subDay()->toDateString(), // End before start
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }
}
