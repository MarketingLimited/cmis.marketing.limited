<?php

namespace Tests\Feature\Controllers\Automation;

use Tests\TestCase;
use App\Models\Core\{User, Org};
use App\Models\AdPlatform\AdCampaign;
use App\Services\Automation\{CampaignLifecycleManager, AutomatedBudgetAllocator};
use App\Services\CampaignOrchestratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

/**
 * Campaign Orchestration Tests (Phase 4 - Advanced Automation)
 *
 * Tests campaign lifecycle management and automated optimization features
 */
class CampaignOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Org $org;
    private AdCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create(['org_id' => $this->org->org_id]);
        $this->actingAs($this->user, 'sanctum');

        $this->campaign = AdCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
            'budget' => 1000
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_orchestration_endpoints()
    {
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/process-lifecycle");
        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_process_lifecycle_events()
    {
        $mockLifecycle = Mockery::mock(CampaignLifecycleManager::class);
        $mockLifecycle->shouldReceive('processLifecycleEvents')
            ->once()
            ->with($this->org->org_id)
            ->andReturn([
                'activated' => 2,
                'paused' => 1,
                'completed' => 3,
                'budget_adjusted' => 1,
                'analyzed' => 3,
                'errors' => []
            ]);

        $this->app->instance(CampaignLifecycleManager::class, $mockLifecycle);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/process-lifecycle");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'results' => [
                'activated' => 2,
                'paused' => 1,
                'completed' => 3
            ]
        ]);
    }

    #[Test]
    public function it_can_get_lifecycle_statistics()
    {
        $mockLifecycle = Mockery::mock(CampaignLifecycleManager::class);
        $mockLifecycle->shouldReceive('getLifecycleStatistics')
            ->once()
            ->with($this->org->org_id, 30)
            ->andReturn([
                'period_days' => 30,
                'events' => [
                    'activated' => 5,
                    'paused' => 2,
                    'completed' => 3
                ],
                'total_events' => 10
            ]);

        $this->app->instance(CampaignLifecycleManager::class, $mockLifecycle);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/orchestration/lifecycle-stats?days=30");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'statistics' => [
                'period_days',
                'events',
                'total_events'
            ]
        ]);
    }

    #[Test]
    public function it_validates_lifecycle_stats_days_parameter()
    {
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/orchestration/lifecycle-stats?days=-1");
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['days']);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/orchestration/lifecycle-stats?days=500");
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['days']);
    }

    #[Test]
    public function it_can_reallocate_budget_across_campaigns()
    {
        $mockAllocator = Mockery::mock(AutomatedBudgetAllocator::class);
        $mockAllocator->shouldReceive('reallocateBudget')
            ->once()
            ->with($this->org->org_id, 5000.0, 'performance_weighted', [])
            ->andReturn([
                'success' => true,
                'strategy' => 'performance_weighted',
                'total_budget' => 5000,
                'campaigns_updated' => 3,
                'allocation' => [],
                'changes' => []
            ]);

        $this->app->instance(AutomatedBudgetAllocator::class, $mockAllocator);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/reallocate-budget", [
            'total_budget' => 5000,
            'strategy' => 'performance_weighted'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'strategy' => 'performance_weighted',
            'total_budget' => 5000
        ]);
    }

    #[Test]
    public function it_validates_budget_reallocation_request()
    {
        // Missing total_budget
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/reallocate-budget", [
            'strategy' => 'roi_maximization'
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['total_budget']);

        // Invalid budget (too low)
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/reallocate-budget", [
            'total_budget' => 5
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['total_budget']);

        // Invalid strategy
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/reallocate-budget", [
            'total_budget' => 1000,
            'strategy' => 'invalid_strategy'
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['strategy']);
    }

    #[Test]
    public function it_can_simulate_budget_allocation()
    {
        $mockAllocator = Mockery::mock(AutomatedBudgetAllocator::class);
        $mockAllocator->shouldReceive('simulateAllocation')
            ->once()
            ->with($this->org->org_id, 3000.0, 'roi_maximization')
            ->andReturn([
                'success' => true,
                'simulation' => true,
                'strategy' => 'roi_maximization',
                'total_budget' => 3000,
                'allocation' => []
            ]);

        $this->app->instance(AutomatedBudgetAllocator::class, $mockAllocator);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/simulate-budget", [
            'total_budget' => 3000,
            'strategy' => 'roi_maximization'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'simulation' => true
        ]);
    }

    #[Test]
    public function it_can_get_budget_allocation_history()
    {
        $mockAllocator = Mockery::mock(AutomatedBudgetAllocator::class);
        $mockAllocator->shouldReceive('getAllocationHistory')
            ->once()
            ->with($this->org->org_id, 50)
            ->andReturn([
                [
                    'id' => 'test-id-1',
                    'campaign_id' => $this->campaign->campaign_id,
                    'old_budget' => 1000,
                    'new_budget' => 1200,
                    'change_amount' => 200,
                    'reason' => 'Performance-weighted allocation'
                ]
            ]);

        $this->app->instance(AutomatedBudgetAllocator::class, $mockAllocator);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/orchestration/budget-history");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'history' => [
                '*' => ['id', 'campaign_id', 'old_budget', 'new_budget']
            ],
            'count'
        ]);
    }

    #[Test]
    public function it_can_create_multi_platform_campaign()
    {
        $mockOrchestrator = Mockery::mock(CampaignOrchestratorService::class);
        $mockOrchestrator->shouldReceive('createMultiPlatformCampaign')
            ->once()
            ->andReturn([
                'success' => true,
                'cmis_campaign' => [
                    'success' => true,
                    'campaign_id' => 'test-campaign-id'
                ],
                'meta' => ['success' => true],
                'google' => ['success' => true]
            ]);

        $this->app->instance(CampaignOrchestratorService::class, $mockOrchestrator);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", [
            'name' => 'Multi-Platform Campaign',
            'platforms' => ['meta', 'google'],
            'objective' => 'conversions',
            'budget' => 5000
        ]);

        $response->assertStatus(201);
    }

    #[Test]
    public function it_validates_multi_platform_campaign_creation()
    {
        // Missing name
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", [
            'platforms' => ['meta'],
            'objective' => 'awareness',
            'budget' => 1000
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // Missing platforms
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", [
            'name' => 'Test Campaign',
            'objective' => 'awareness',
            'budget' => 1000
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['platforms']);

        // Invalid platform
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/create-campaign", [
            'name' => 'Test Campaign',
            'platforms' => ['invalid_platform'],
            'objective' => 'awareness',
            'budget' => 1000
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['platforms.0']);
    }

    #[Test]
    public function it_can_pause_campaign()
    {
        $mockOrchestrator = Mockery::mock(CampaignOrchestratorService::class);
        $mockOrchestrator->shouldReceive('pauseCampaign')
            ->once()
            ->with($this->campaign->campaign_id)
            ->andReturn([
                'success' => true,
                'campaign_id' => $this->campaign->campaign_id,
                'status' => 'paused'
            ]);

        $this->app->instance(CampaignOrchestratorService::class, $mockOrchestrator);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/{$this->campaign->campaign_id}/pause");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'paused'
        ]);
    }

    #[Test]
    public function it_can_resume_campaign()
    {
        $mockOrchestrator = Mockery::mock(CampaignOrchestratorService::class);
        $mockOrchestrator->shouldReceive('resumeCampaign')
            ->once()
            ->with($this->campaign->campaign_id)
            ->andReturn([
                'success' => true,
                'campaign_id' => $this->campaign->campaign_id,
                'status' => 'active'
            ]);

        $this->app->instance(CampaignOrchestratorService::class, $mockOrchestrator);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/{$this->campaign->campaign_id}/resume");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'active'
        ]);
    }

    #[Test]
    public function it_can_sync_campaign_status()
    {
        $mockOrchestrator = Mockery::mock(CampaignOrchestratorService::class);
        $mockOrchestrator->shouldReceive('syncCampaignStatus')
            ->once()
            ->with($this->campaign->campaign_id)
            ->andReturn([
                'success' => true,
                'campaign_id' => $this->campaign->campaign_id,
                'overall_status' => 'active',
                'platforms' => []
            ]);

        $this->app->instance(CampaignOrchestratorService::class, $mockOrchestrator);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/{$this->campaign->campaign_id}/sync");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
    }

    #[Test]
    public function it_can_duplicate_campaign()
    {
        $mockOrchestrator = Mockery::mock(CampaignOrchestratorService::class);
        $mockOrchestrator->shouldReceive('duplicateCampaign')
            ->once()
            ->with($this->campaign->campaign_id)
            ->andReturn([
                'success' => true,
                'new_campaign_id' => 'new-campaign-id',
                'campaign' => []
            ]);

        $this->app->instance(CampaignOrchestratorService::class, $mockOrchestrator);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/orchestration/campaigns/{$this->campaign->campaign_id}/duplicate");

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
