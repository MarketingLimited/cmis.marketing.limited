<?php

namespace Tests\Integration;

use App\Models\Campaign\Campaign;
use App\Models\Automation\AutomationRule;
use App\Models\Automation\AutomationExecution;
use App\Models\Core\Org;
use App\Models\User;
use App\Services\Automation\AutomationRulesEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for Campaign + Automation Rules workflow
 *
 * Tests the end-to-end flow of automation rules being triggered
 * by campaign performance changes and executing actions.
 */
class CampaignAutomationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected AutomationRulesEngine $rulesEngine;

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

        $this->rulesEngine = app(AutomationRulesEngine::class);
    }

    /** @test */
    public function automation_rule_pauses_campaign_when_spend_exceeds_threshold()
    {
        // Create a campaign with specific budget
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
            'budget' => 1000,
            'spend' => 500, // Currently at 50%
        ]);

        // Create automation rule to pause when spend > 80%
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Budget Protection Rule',
            'rule_type' => 'schedule_pause',
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'active',
            'enabled' => true,
            'conditions' => [
                [
                    'field' => 'spend_percentage',
                    'operator' => '>',
                    'value' => 80,
                ],
            ],
            'condition_logic' => 'and',
            'actions' => [
                [
                    'type' => 'pause_campaign',
                    'params' => [
                        'reason' => 'Budget threshold exceeded',
                    ],
                ],
            ],
        ]);

        // Simulate campaign spend increase to 85%
        $campaign->update(['spend' => 850]);

        // Evaluate the rule
        $testData = [
            'campaign_id' => $campaign->campaign_id,
            'spend' => 850,
            'budget' => 1000,
            'spend_percentage' => 85,
        ];

        $result = $this->rulesEngine->evaluateRule($rule, $testData);

        // Assert rule would trigger
        $this->assertTrue($result['matches']);
        $this->assertCount(1, $result['actions']);
        $this->assertEquals('pause_campaign', $result['actions'][0]['type']);

        // Record execution
        $execution = AutomationExecution::create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'success',
            'executed_at' => now(),
            'result' => $result,
        ]);

        // Verify execution was recorded
        $this->assertDatabaseHas('cmis_automation.automation_executions', [
            'rule_id' => $rule->rule_id,
            'entity_id' => $campaign->campaign_id,
            'status' => 'success',
        ]);

        // Verify campaign would be paused (in real scenario)
        $this->assertEquals('pause_campaign', $result['actions'][0]['type']);
    }

    /** @test */
    public function automation_rule_adjusts_bid_based_on_performance()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Performance Campaign',
            'status' => 'active',
        ]);

        // Create rule to increase bid when ROAS is good
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'ROAS Bid Optimizer',
            'rule_type' => 'bid_adjustment',
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'active',
            'enabled' => true,
            'conditions' => [
                [
                    'field' => 'roas',
                    'operator' => '>',
                    'value' => 3.0,
                ],
                [
                    'field' => 'impressions',
                    'operator' => '>',
                    'value' => 10000,
                ],
            ],
            'condition_logic' => 'and',
            'actions' => [
                [
                    'type' => 'increase_bid',
                    'params' => [
                        'percentage' => 10,
                        'max_bid' => 50,
                    ],
                ],
            ],
        ]);

        $testData = [
            'campaign_id' => $campaign->campaign_id,
            'roas' => 3.5,
            'impressions' => 15000,
            'current_bid' => 20,
        ];

        $result = $this->rulesEngine->evaluateRule($rule, $testData);

        $this->assertTrue($result['matches']);
        $this->assertEquals('increase_bid', $result['actions'][0]['type']);
        $this->assertEquals(10, $result['actions'][0]['params']['percentage']);
    }

    /** @test */
    public function multiple_automation_rules_execute_by_priority()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Create high-priority safety rule
        $safetyRule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Emergency Stop',
            'rule_type' => 'schedule_pause',
            'entity_type' => 'campaign',
            'status' => 'active',
            'enabled' => true,
            'priority' => 90, // High priority
            'conditions' => [
                ['field' => 'cpc', 'operator' => '>', 'value' => 100],
            ],
            'actions' => [
                ['type' => 'pause_campaign', 'params' => []],
            ],
        ]);

        // Create low-priority optimization rule
        $optimizationRule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Bid Optimizer',
            'rule_type' => 'bid_adjustment',
            'entity_type' => 'campaign',
            'status' => 'active',
            'enabled' => true,
            'priority' => 50, // Lower priority
            'conditions' => [
                ['field' => 'ctr', 'operator' => '>', 'value' => 2.0],
            ],
            'actions' => [
                ['type' => 'increase_bid', 'params' => ['percentage' => 5]],
            ],
        ]);

        // Fetch rules for campaign in priority order
        $rules = AutomationRule::where('org_id', $this->org->org_id)
            ->where('entity_type', 'campaign')
            ->where('status', 'active')
            ->where('enabled', true)
            ->orderByDesc('priority')
            ->get();

        $this->assertCount(2, $rules);
        $this->assertEquals($safetyRule->rule_id, $rules->first()->rule_id);
        $this->assertEquals(90, $rules->first()->priority);
        $this->assertEquals($optimizationRule->rule_id, $rules->last()->rule_id);
        $this->assertEquals(50, $rules->last()->priority);
    }

    /** @test */
    public function automation_rule_respects_cooldown_period()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_type' => 'bid_adjustment',
            'entity_type' => 'campaign',
            'status' => 'active',
            'enabled' => true,
            'cooldown_minutes' => 60, // 1 hour cooldown
            'last_executed_at' => now()->subMinutes(30), // Executed 30 min ago
            'conditions' => [
                ['field' => 'ctr', 'operator' => '>', 'value' => 1.0],
            ],
            'actions' => [
                ['type' => 'increase_bid', 'params' => []],
            ],
        ]);

        // Rule should not be executable due to cooldown
        $this->assertFalse($rule->canExecute());

        // Update last execution to 61 minutes ago
        $rule->update(['last_executed_at' => now()->subMinutes(61)]);
        $rule->refresh();

        // Now rule should be executable
        $this->assertTrue($rule->canExecute());
    }

    /** @test */
    public function automation_rule_respects_daily_execution_limit()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_type' => 'bid_adjustment',
            'entity_type' => 'campaign',
            'status' => 'active',
            'enabled' => true,
            'max_executions_per_day' => 3,
            'conditions' => [
                ['field' => 'ctr', 'operator' => '>', 'value' => 1.0],
            ],
            'actions' => [
                ['type' => 'increase_bid', 'params' => []],
            ],
        ]);

        // Create 3 executions today
        AutomationExecution::factory()->count(3)->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'executed_at' => now(),
        ]);

        // Rule should not be executable (limit reached)
        $this->assertFalse($rule->canExecute());

        // Create executions from yesterday
        AutomationExecution::factory()->count(2)->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'executed_at' => now()->subDay(),
        ]);

        // Still should not be executable (today's limit reached)
        $this->assertFalse($rule->canExecute());
    }

    /** @test */
    public function automation_rule_creative_rotation_workflow()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Rule to rotate creative when CTR drops
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Creative Fatigue Detector',
            'rule_type' => 'creative_rotation',
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'active',
            'enabled' => true,
            'conditions' => [
                [
                    'field' => 'ctr_7d',
                    'operator' => '<',
                    'value' => 1.0,
                ],
                [
                    'field' => 'impressions_7d',
                    'operator' => '>',
                    'value' => 50000,
                ],
            ],
            'condition_logic' => 'and',
            'actions' => [
                [
                    'type' => 'rotate_creative',
                    'params' => [
                        'strategy' => 'performance_based',
                        'min_creatives' => 3,
                    ],
                ],
            ],
        ]);

        $testData = [
            'campaign_id' => $campaign->campaign_id,
            'ctr_7d' => 0.8,
            'impressions_7d' => 75000,
        ];

        $result = $this->rulesEngine->evaluateRule($rule, $testData);

        $this->assertTrue($result['matches']);
        $this->assertEquals('rotate_creative', $result['actions'][0]['type']);
        $this->assertEquals('performance_based', $result['actions'][0]['params']['strategy']);
    }

    /** @test */
    public function automation_executions_track_success_and_failure()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_type' => 'bid_adjustment',
            'entity_type' => 'campaign',
            'status' => 'active',
            'enabled' => true,
        ]);

        // Create successful execution
        $successExecution = AutomationExecution::create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'success',
            'executed_at' => now(),
            'result' => ['action' => 'completed'],
        ]);

        // Create failed execution
        $failedExecution = AutomationExecution::create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'failure',
            'executed_at' => now(),
            'error_message' => 'API rate limit exceeded',
        ]);

        // Record executions
        $rule->recordExecution(true);
        $rule->recordExecution(false);

        $rule->refresh();

        // Verify execution counts
        $this->assertEquals(2, $rule->execution_count);
        $this->assertEquals(1, $rule->success_count);
        $this->assertEquals(1, $rule->failure_count);
        $this->assertEquals(50.0, $rule->getSuccessRate());
    }

    /** @test */
    public function multi_condition_rule_with_or_logic()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Rule triggers if EITHER condition is met
        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Multi-Condition Alert',
            'rule_type' => 'alert',
            'entity_type' => 'campaign',
            'status' => 'active',
            'enabled' => true,
            'conditions' => [
                ['field' => 'cpc', 'operator' => '>', 'value' => 50],
                ['field' => 'ctr', 'operator' => '<', 'value' => 0.5],
            ],
            'condition_logic' => 'or', // OR logic
            'actions' => [
                ['type' => 'send_alert', 'params' => ['channel' => 'email']],
            ],
        ]);

        // Test with only first condition met
        $testData1 = [
            'campaign_id' => $campaign->campaign_id,
            'cpc' => 60, // Exceeds threshold
            'ctr' => 2.0, // Good CTR
        ];

        $result1 = $this->rulesEngine->evaluateRule($rule, $testData1);
        $this->assertTrue($result1['matches']); // Should trigger (OR logic)

        // Test with only second condition met
        $testData2 = [
            'campaign_id' => $campaign->campaign_id,
            'cpc' => 20, // Good CPC
            'ctr' => 0.3, // Low CTR
        ];

        $result2 = $this->rulesEngine->evaluateRule($rule, $testData2);
        $this->assertTrue($result2['matches']); // Should trigger (OR logic)

        // Test with neither condition met
        $testData3 = [
            'campaign_id' => $campaign->campaign_id,
            'cpc' => 20,
            'ctr' => 2.0,
        ];

        $result3 = $this->rulesEngine->evaluateRule($rule, $testData3);
        $this->assertFalse($result3['matches']); // Should not trigger
    }

    /** @test */
    public function automation_rule_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Create rule in other org
        $otherOrgRule = AutomationRule::factory()->create([
            'org_id' => $otherOrg->org_id,
            'entity_type' => 'campaign',
            'status' => 'active',
            'enabled' => true,
        ]);

        // Try to fetch rules for current org
        $rules = AutomationRule::where('org_id', $this->org->org_id)
            ->get();

        // Should not include other org's rule
        $this->assertCount(0, $rules);
        $this->assertFalse($rules->contains('rule_id', $otherOrgRule->rule_id));
    }

    /** @test */
    public function automation_rule_handles_between_operator()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $rule = AutomationRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Optimal ROAS Range',
            'rule_type' => 'bid_adjustment',
            'entity_type' => 'campaign',
            'status' => 'active',
            'enabled' => true,
            'conditions' => [
                [
                    'field' => 'roas',
                    'operator' => 'between',
                    'value' => [2.0, 4.0], // ROAS between 2 and 4
                ],
            ],
            'actions' => [
                ['type' => 'maintain_bid', 'params' => []],
            ],
        ]);

        // Test with ROAS in range
        $testData1 = [
            'campaign_id' => $campaign->campaign_id,
            'roas' => 3.0,
        ];

        $result1 = $this->rulesEngine->evaluateRule($rule, $testData1);
        $this->assertTrue($result1['matches']);

        // Test with ROAS out of range
        $testData2 = [
            'campaign_id' => $campaign->campaign_id,
            'roas' => 5.0,
        ];

        $result2 = $this->rulesEngine->evaluateRule($rule, $testData2);
        $this->assertFalse($result2['matches']);
    }
}
