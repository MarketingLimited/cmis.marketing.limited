<?php

namespace Tests\Feature\Optimization;

use App\Models\Optimization\OptimizationRule;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptimizationRuleControllerTest extends TestCase
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
    public function it_can_list_optimization_rules()
    {
        OptimizationRule::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('optimization.rules.index'));

        $response->assertOk();
        $response->assertViewIs('optimization.rules.index');
        $response->assertViewHas('rules');
    }

    /** @test */
    public function it_can_create_an_optimization_rule()
    {
        $data = [
            'rule_name' => 'Auto Budget Increase',
            'rule_type' => 'budget_optimization',
            'entity_type' => 'campaign',
            'entity_id' => '11111111-1111-1111-1111-111111111111',
            'conditions' => [
                ['field' => 'ctr', 'operator' => '>', 'value' => 5],
            ],
            'actions' => [
                ['type' => 'adjust_budget', 'params' => ['adjustment_type' => 'percentage', 'value' => 10]],
            ],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('optimization.rules.store'), $data);

        $this->assertDatabaseHas('cmis_optimization.optimization_rules', [
            'org_id' => $this->org->org_id,
            'rule_name' => 'Auto Budget Increase',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_show_an_optimization_rule()
    {
        $rule = OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('optimization.rules.show', $rule->rule_id));

        $response->assertOk();
        $response->assertViewIs('optimization.rules.show');
        $response->assertViewHas('rule');
    }

    /** @test */
    public function it_can_update_an_optimization_rule()
    {
        $rule = OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_name' => 'Original Name',
        ]);

        $data = [
            'rule_name' => 'Updated Name',
            'priority' => 'high',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('optimization.rules.update', $rule->rule_id), $data);

        $this->assertDatabaseHas('cmis_optimization.optimization_rules', [
            'rule_id' => $rule->rule_id,
            'rule_name' => 'Updated Name',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_an_optimization_rule()
    {
        $rule = OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('optimization.rules.destroy', $rule->rule_id));

        $this->assertSoftDeleted('cmis_optimization.optimization_rules', [
            'rule_id' => $rule->rule_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_activate_a_rule()
    {
        $rule = OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('optimization.rules.activate', $rule->rule_id));

        $response->assertOk();

        $this->assertDatabaseHas('cmis_optimization.optimization_rules', [
            'rule_id' => $rule->rule_id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_deactivate_a_rule()
    {
        $rule = OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('optimization.rules.deactivate', $rule->rule_id));

        $response->assertOk();

        $this->assertDatabaseHas('cmis_optimization.optimization_rules', [
            'rule_id' => $rule->rule_id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_can_test_a_rule()
    {
        $rule = OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'conditions' => [
                ['field' => 'ctr', 'operator' => '>', 'value' => 5],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('optimization.rules.test', $rule->rule_id), [
                'test_data' => [
                    'ctr' => 6.5,
                ],
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'rule_id',
                'would_trigger',
            ],
        ]);
    }

    /** @test */
    public function it_can_get_execution_history()
    {
        $rule = OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'execution_count' => 25,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('optimization.rules.executionHistory', $rule->rule_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_executions',
                'last_triggered_at',
            ],
        ]);
    }

    /** @test */
    public function it_can_get_optimization_suggestions()
    {
        OptimizationRule::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('optimization.rules.suggestions', [
                'entity_type' => 'campaign',
                'entity_id' => '11111111-1111-1111-1111-111111111111',
            ]));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_rule_analytics()
    {
        OptimizationRule::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('optimization.rules.analytics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'summary',
                'by_type',
                'by_entity_type',
                'most_triggered',
            ],
        ]);
    }

    /** @test */
    public function it_can_bulk_update_rules()
    {
        $rules = OptimizationRule::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('optimization.rules.bulkUpdate'), [
                'rule_ids' => $rules->pluck('rule_id')->toArray(),
                'is_active' => true,
            ]);

        $response->assertOk();

        foreach ($rules as $rule) {
            $this->assertDatabaseHas('cmis_optimization.optimization_rules', [
                'rule_id' => $rule->rule_id,
                'is_active' => true,
            ]);
        }
    }

    /** @test */
    public function it_can_duplicate_a_rule()
    {
        $rule = OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_name' => 'Original Rule',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('optimization.rules.duplicate', $rule->rule_id));

        $response->assertStatus(201);

        $this->assertDatabaseHas('cmis_optimization.optimization_rules', [
            'org_id' => $this->org->org_id,
            'rule_name' => 'Original Rule (Copy)',
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        $otherRule = OptimizationRule::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('optimization.rules.show', $otherRule->rule_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_filters_rules_by_type()
    {
        OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_type' => 'budget_optimization',
        ]);

        OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_type' => 'bid_optimization',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('optimization.rules.index', ['rule_type' => 'budget_optimization']));

        $response->assertOk();
    }

    /** @test */
    public function it_filters_rules_by_active_status()
    {
        OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        OptimizationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('optimization.rules.index', ['is_active' => 'true']));

        $response->assertOk();
    }
}
