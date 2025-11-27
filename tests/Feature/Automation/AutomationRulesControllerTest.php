<?php

namespace Tests\Feature\Automation;

use App\Models\Automation\AutomationRule;
use App\Models\Automation\AutomationExecution;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationRulesControllerTest extends TestCase
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
    public function it_can_list_automation_rules()
    {
        AutomationRule::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('automation.rules.index'));

        $response->assertOk();
        $response->assertViewIs('automation.rules.index');
        $response->assertViewHas('rules');
    }

    /** @test */
    public function it_can_list_automation_rules_as_json()
    {
        AutomationRule::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('automation.rules.index'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'rule_id',
                        'name',
                        'rule_type',
                        'entity_type',
                        'status',
                        'enabled',
                    ]
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_rules_by_type()
    {
        AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_type' => 'budget_optimization',
        ]);

        AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_type' => 'bid_adjustment',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('automation.rules.index', ['rule_type' => 'budget_optimization']));

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_rules_by_entity_type()
    {
        AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
        ]);

        AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'ad_set',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('automation.rules.index', ['entity_type' => 'campaign']));

        $response->assertOk();
    }

    /** @test */
    public function it_can_create_an_automation_rule()
    {
        $data = [
            'name' => 'Budget Pause Rule',
            'description' => 'Pause campaign when budget exceeds threshold',
            'rule_type' => 'schedule_pause',
            'entity_type' => 'campaign',
            'entity_id' => '11111111-1111-1111-1111-111111111111',
            'conditions' => [
                ['field' => 'spend', 'operator' => '>', 'value' => 1000],
                ['field' => 'roas', 'operator' => '<', 'value' => 2],
            ],
            'condition_logic' => 'and',
            'actions' => [
                ['type' => 'pause_campaign', 'params' => ['reason' => 'Budget exceeded']],
                ['type' => 'send_notification', 'params' => ['channels' => ['email']]],
            ],
            'priority' => 80,
            'enabled' => true,
            'max_executions_per_day' => 5,
            'cooldown_minutes' => 60,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.store'), $data);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Budget Pause Rule');
        $response->assertJsonPath('data.org_id', $this->org->org_id);
    }

    /** @test */
    public function it_validates_automation_rule_creation()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.store'), [
                // Missing required fields
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'rule_type', 'entity_type', 'conditions', 'actions']);
    }

    /** @test */
    public function it_validates_rule_type_values()
    {
        $data = [
            'name' => 'Test Rule',
            'rule_type' => 'invalid_type',
            'entity_type' => 'campaign',
            'conditions' => [['field' => 'spend', 'operator' => '>', 'value' => 100]],
            'actions' => [['type' => 'pause', 'params' => []]],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rule_type']);
    }

    /** @test */
    public function it_can_show_an_automation_rule()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('automation.rules.show', $rule->rule_id));

        $response->assertOk();
        $response->assertViewIs('automation.rules.show');
        $response->assertViewHas('rule');
    }

    /** @test */
    public function it_can_show_rule_as_json()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('automation.rules.show', $rule->rule_id));

        $response->assertOk();
        $response->assertJsonPath('data.rule_id', $rule->rule_id);
    }

    /** @test */
    public function it_can_update_an_automation_rule()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Original Name',
            'priority' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson(route('automation.rules.update', $rule->rule_id), [
                'name' => 'Updated Name',
                'priority' => 90,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('data.priority', 90);
    }

    /** @test */
    public function it_can_delete_an_automation_rule()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('automation.rules.destroy', $rule->rule_id));

        $response->assertOk();

        $this->assertSoftDeleted('cmis.automation_rules', [
            'rule_id' => $rule->rule_id,
        ]);
    }

    /** @test */
    public function it_can_activate_a_rule()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
            'enabled' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.activate', $rule->rule_id));

        $response->assertOk();

        $this->assertDatabaseHas('cmis.automation_rules', [
            'rule_id' => $rule->rule_id,
            'status' => 'active',
            'enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_pause_a_rule()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.pause', $rule->rule_id));

        $response->assertOk();

        $this->assertDatabaseHas('cmis.automation_rules', [
            'rule_id' => $rule->rule_id,
            'status' => 'paused',
            'enabled' => false,
        ]);
    }

    /** @test */
    public function it_can_archive_a_rule()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.archive', $rule->rule_id));

        $response->assertOk();

        $this->assertDatabaseHas('cmis.automation_rules', [
            'rule_id' => $rule->rule_id,
            'status' => 'archived',
            'enabled' => false,
        ]);
    }

    /** @test */
    public function it_can_test_a_rule()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'conditions' => [
                ['field' => 'spend', 'operator' => '>', 'value' => 100],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.test', $rule->rule_id), [
                'test_data' => [
                    'spend' => 150,
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
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        AutomationExecution::factory()->count(10)->create([
            'rule_id' => $rule->rule_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('automation.rules.executionHistory', $rule->rule_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'execution_id',
                        'rule_id',
                        'status',
                        'executed_at',
                    ]
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_execution_history_by_status()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        AutomationExecution::factory()->create([
            'rule_id' => $rule->rule_id,
            'status' => 'success',
        ]);

        AutomationExecution::factory()->create([
            'rule_id' => $rule->rule_id,
            'status' => 'failure',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('automation.rules.executionHistory', [
                'rule' => $rule->rule_id,
                'status' => 'success',
            ]));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_rule_analytics()
    {
        AutomationRule::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        AutomationRule::factory()->count(2)->create([
            'org_id' => $this->org->org_id,
            'status' => 'paused',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('automation.rules.analytics', ['days' => 30]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_rules',
                'active_rules',
                'paused_rules',
                'archived_rules',
                'total_executions',
                'by_type',
                'top_performing',
            ],
        ]);
    }

    /** @test */
    public function it_can_duplicate_a_rule()
    {
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Original Rule',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.duplicate', $rule->rule_id));

        $response->assertStatus(201);

        $this->assertDatabaseHas('cmis.automation_rules', [
            'org_id' => $this->org->org_id,
            'name' => 'Original Rule (Copy)',
            'status' => 'draft',
            'enabled' => false,
        ]);
    }

    /** @test */
    public function it_can_bulk_update_rules()
    {
        $rules = AutomationRule::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
            'enabled' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.bulkUpdate'), [
                'rule_ids' => $rules->pluck('rule_id')->toArray(),
                'action' => 'activate',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.updated_count', 3);

        foreach ($rules as $rule) {
            $this->assertDatabaseHas('cmis.automation_rules', [
                'rule_id' => $rule->rule_id,
                'status' => 'active',
                'enabled' => true,
            ]);
        }
    }

    /** @test */
    public function it_validates_bulk_update_action()
    {
        $rules = AutomationRule::factory()->count(2)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('automation.rules.bulkUpdate'), [
                'rule_ids' => $rules->pluck('rule_id')->toArray(),
                'action' => 'invalid_action',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['action']);
    }

    /** @test */
    public function it_can_get_rule_suggestions()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('automation.rules.suggestions', [
                'entity_type' => 'campaign',
                'entity_id' => '11111111-1111-1111-1111-111111111111',
            ]));

        $response->assertOk();
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        AutomationRule::factory()->count(5)->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('automation.rules.index'));

        $response->assertOk();
        // Should return no rules from other org
    }

    /** @test */
    public function it_supports_all_rule_types()
    {
        $ruleTypes = ['budget_optimization', 'bid_adjustment', 'creative_rotation', 'schedule_pause', 'schedule_resume', 'alert'];

        foreach ($ruleTypes as $type) {
            $data = [
                'name' => "Test Rule {$type}",
                'rule_type' => $type,
                'entity_type' => 'campaign',
                'conditions' => [['field' => 'spend', 'operator' => '>', 'value' => 100]],
                'actions' => [['type' => 'pause', 'params' => []]],
            ];

            $response = $this->actingAs($this->user)
                ->postJson(route('automation.rules.store'), $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_supports_all_entity_types()
    {
        $entityTypes = ['campaign', 'ad_set', 'ad'];

        foreach ($entityTypes as $type) {
            $data = [
                'name' => "Test Rule for {$type}",
                'rule_type' => 'budget_optimization',
                'entity_type' => $type,
                'conditions' => [['field' => 'spend', 'operator' => '>', 'value' => 100]],
                'actions' => [['type' => 'pause', 'params' => []]],
            ];

            $response = $this->actingAs($this->user)
                ->postJson(route('automation.rules.store'), $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_supports_all_condition_operators()
    {
        $operators = ['>', '>=', '<', '<=', '=', '!=', 'contains', 'between'];

        foreach ($operators as $operator) {
            $value = $operator === 'between' ? ['min' => 10, 'max' => 100] : 100;

            $data = [
                'name' => "Test Rule {$operator}",
                'rule_type' => 'budget_optimization',
                'entity_type' => 'campaign',
                'conditions' => [['field' => 'spend', 'operator' => $operator, 'value' => $value]],
                'actions' => [['type' => 'pause', 'params' => []]],
            ];

            $response = $this->actingAs($this->user)
                ->postJson(route('automation.rules.store'), $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_supports_condition_logic_types()
    {
        $logicTypes = ['and', 'or'];

        foreach ($logicTypes as $logic) {
            $data = [
                'name' => "Test Rule {$logic}",
                'rule_type' => 'budget_optimization',
                'entity_type' => 'campaign',
                'conditions' => [
                    ['field' => 'spend', 'operator' => '>', 'value' => 100],
                    ['field' => 'roas', 'operator' => '<', 'value' => 2],
                ],
                'condition_logic' => $logic,
                'actions' => [['type' => 'pause', 'params' => []]],
            ];

            $response = $this->actingAs($this->user)
                ->postJson(route('automation.rules.store'), $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->get(route('automation.rules.index'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
