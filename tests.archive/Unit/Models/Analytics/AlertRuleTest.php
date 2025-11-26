<?php

namespace Tests\Unit\Models\Analytics;

use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertRule;
use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertRuleTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'org_id' => $this->org->org_id,
        ]);
    }

    /** @test */
    public function it_can_create_alert_rule()
    {
        $rule = AlertRule::create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'High CTR Alert',
            'entity_type' => 'campaign',
            'metric' => 'ctr',
            'condition' => 'gt',
            'threshold' => 5.0,
            'severity' => 'high',
            'notification_channels' => ['email'],
            'notification_config' => [
                'email' => [
                    'recipients' => ['test@example.com']
                ]
            ],
            'cooldown_minutes' => 60,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('cmis.alert_rules', [
            'rule_id' => $rule->rule_id,
            'name' => 'High CTR Alert',
            'metric' => 'ctr',
        ]);
    }

    /** @test */
    public function it_belongs_to_organization()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $this->assertInstanceOf(Org::class, $rule->org);
        $this->assertEquals($this->org->org_id, $rule->org->org_id);
    }

    /** @test */
    public function it_belongs_to_creator()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
        ]);

        $this->assertInstanceOf(User::class, $rule->creator);
        $this->assertEquals($this->user->user_id, $rule->creator->user_id);
    }

    /** @test */
    public function it_has_many_alerts()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        AlertHistory::factory()->count(3)->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
        ]);

        $this->assertCount(3, $rule->alerts);
    }

    /** @test */
    public function scope_active_filters_active_rules()
    {
        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $this->assertEquals(1, AlertRule::active()->count());
    }

    /** @test */
    public function scope_for_entity_filters_by_entity_type()
    {
        $campaignRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
        ]);

        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'ad',
        ]);

        $rules = AlertRule::forEntity('campaign')->get();

        $this->assertCount(1, $rules);
        $this->assertEquals($campaignRule->rule_id, $rules->first()->rule_id);
    }

    /** @test */
    public function scope_for_entity_includes_null_entity_id_rules()
    {
        $globalRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => null,
        ]);

        $specificRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => fake()->uuid(),
        ]);

        $rules = AlertRule::forEntity('campaign', $specificRule->entity_id)->get();

        // Should include both global (null entity_id) and specific entity rules
        $this->assertCount(2, $rules);
    }

    /** @test */
    public function scope_due_for_evaluation_includes_never_triggered()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
            'last_triggered_at' => null,
        ]);

        $this->assertTrue(AlertRule::dueForEvaluation()->pluck('rule_id')->contains($rule->rule_id));
    }

    /** @test */
    public function scope_due_for_evaluation_excludes_in_cooldown()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
            'last_triggered_at' => now()->subMinutes(30),
            'cooldown_minutes' => 60,
        ]);

        $this->assertFalse(AlertRule::dueForEvaluation()->pluck('rule_id')->contains($rule->rule_id));
    }

    /** @test */
    public function is_in_cooldown_returns_true_when_in_cooldown()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'last_triggered_at' => now()->subMinutes(30),
            'cooldown_minutes' => 60,
        ]);

        $this->assertTrue($rule->isInCooldown());
    }

    /** @test */
    public function is_in_cooldown_returns_false_when_cooldown_expired()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'last_triggered_at' => now()->subMinutes(90),
            'cooldown_minutes' => 60,
        ]);

        $this->assertFalse($rule->isInCooldown());
    }

    /** @test */
    public function evaluate_condition_gt_works_correctly()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'condition' => 'gt',
            'threshold' => 5.0,
        ]);

        $this->assertTrue($rule->evaluateCondition(6.0));
        $this->assertFalse($rule->evaluateCondition(5.0));
        $this->assertFalse($rule->evaluateCondition(4.0));
    }

    /** @test */
    public function evaluate_condition_lt_works_correctly()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'condition' => 'lt',
            'threshold' => 5.0,
        ]);

        $this->assertTrue($rule->evaluateCondition(4.0));
        $this->assertFalse($rule->evaluateCondition(5.0));
        $this->assertFalse($rule->evaluateCondition(6.0));
    }

    /** @test */
    public function evaluate_condition_eq_works_correctly()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'condition' => 'eq',
            'threshold' => 5.0,
        ]);

        $this->assertTrue($rule->evaluateCondition(5.0));
        $this->assertFalse($rule->evaluateCondition(5.1));
    }

    /** @test */
    public function mark_triggered_updates_timestamp_and_count()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'trigger_count' => 0,
        ]);

        $rule->markTriggered();

        $this->assertNotNull($rule->fresh()->last_triggered_at);
        $this->assertEquals(1, $rule->fresh()->trigger_count);

        $rule->fresh()->markTriggered();
        $this->assertEquals(2, $rule->fresh()->trigger_count);
    }

    /** @test */
    public function get_condition_text_returns_readable_text()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'condition' => 'gt',
        ]);

        $this->assertEquals('greater than', $rule->getConditionText());

        $rule->condition = 'lt';
        $this->assertEquals('less than', $rule->getConditionText());

        $rule->condition = 'change_pct';
        $this->assertEquals('changes by', $rule->getConditionText());
    }
}
