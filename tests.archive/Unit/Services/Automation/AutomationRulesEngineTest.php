<?php

namespace Tests\Unit\Services\Automation;

use App\Services\Automation\AutomationRulesEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutomationRulesEngineTest extends TestCase
{
    use RefreshDatabase;

    private AutomationRulesEngine $engine;
    private string $orgId;
    private string $campaignId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AutomationRulesEngine();

        // Create test organization and campaign
        $this->orgId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->campaignId = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function test_evaluate_rule_with_greater_than_operator()
    {
        $rule = [
            'condition' => [
                'metric' => 'cpa',
                'operator' => '>',
                'value' => 50
            ]
        ];

        $metrics = ['cpa' => 75];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertTrue($result);
    }

    public function test_evaluate_rule_with_less_than_operator()
    {
        $rule = [
            'condition' => [
                'metric' => 'ctr',
                'operator' => '<',
                'value' => 0.02
            ]
        ];

        $metrics = ['ctr' => 0.01];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertTrue($result);
    }

    public function test_evaluate_rule_with_equals_operator()
    {
        $rule = [
            'condition' => [
                'metric' => 'roas',
                'operator' => '=',
                'value' => 3.0
            ]
        ];

        $metrics = ['roas' => 3.0];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertTrue($result);
    }

    public function test_evaluate_rule_with_greater_than_or_equal()
    {
        $rule = [
            'condition' => [
                'metric' => 'roas',
                'operator' => '>=',
                'value' => 2.5
            ]
        ];

        $metrics = ['roas' => 2.5];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertTrue($result);
    }

    public function test_evaluate_rule_with_less_than_or_equal()
    {
        $rule = [
            'condition' => [
                'metric' => 'conversion_rate',
                'operator' => '<=',
                'value' => 0.05
            ]
        ];

        $metrics = ['conversion_rate' => 0.03];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertTrue($result);
    }

    public function test_evaluate_rule_returns_false_when_condition_not_met()
    {
        $rule = [
            'condition' => [
                'metric' => 'cpa',
                'operator' => '>',
                'value' => 100
            ]
        ];

        $metrics = ['cpa' => 50];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertFalse($result);
    }

    public function test_evaluate_rule_returns_false_when_metric_missing()
    {
        $rule = [
            'condition' => [
                'metric' => 'cpa',
                'operator' => '>',
                'value' => 50
            ]
        ];

        $metrics = ['ctr' => 0.02]; // CPA is missing

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertFalse($result);
    }

    public function test_validate_rule_accepts_valid_rule()
    {
        $rule = [
            'condition' => [
                'metric' => 'cpa',
                'operator' => '>',
                'value' => 50
            ],
            'action' => [
                'type' => 'pause_underperforming'
            ]
        ];

        $errors = $this->engine->validateRule($rule);

        $this->assertEmpty($errors);
    }

    public function test_validate_rule_rejects_missing_condition()
    {
        $rule = [
            'action' => [
                'type' => 'pause_underperforming'
            ]
        ];

        $errors = $this->engine->validateRule($rule);

        $this->assertContains('Rule must have a condition', $errors);
    }

    public function test_validate_rule_rejects_missing_action()
    {
        $rule = [
            'condition' => [
                'metric' => 'cpa',
                'operator' => '>',
                'value' => 50
            ]
        ];

        $errors = $this->engine->validateRule($rule);

        $this->assertContains('Rule must have an action', $errors);
    }

    public function test_validate_rule_rejects_incomplete_condition()
    {
        $rule = [
            'condition' => [
                'metric' => 'cpa'
                // Missing operator and value
            ],
            'action' => [
                'type' => 'pause_underperforming'
            ]
        ];

        $errors = $this->engine->validateRule($rule);

        $this->assertContains('Condition must have an operator', $errors);
        $this->assertContains('Condition must have a value', $errors);
    }

    public function test_get_rule_templates_returns_pre_built_rules()
    {
        $templates = $this->engine->getRuleTemplates();

        $this->assertIsArray($templates);
        $this->assertGreaterThan(0, count($templates));

        // Check template structure
        $template = $templates[0];
        $this->assertArrayHasKey('id', $template);
        $this->assertArrayHasKey('name', $template);
        $this->assertArrayHasKey('description', $template);
        $this->assertArrayHasKey('condition', $template);
        $this->assertArrayHasKey('action', $template);
    }

    public function test_get_rule_templates_includes_pause_high_cpa()
    {
        $templates = $this->engine->getRuleTemplates();

        $pauseHighCPA = collect($templates)->first(function ($template) {
            return $template['id'] === 'pause_high_cpa';
        });

        $this->assertNotNull($pauseHighCPA);
        $this->assertEquals('cpa', $pauseHighCPA['condition']['metric']);
        $this->assertEquals('>', $pauseHighCPA['condition']['operator']);
        $this->assertEquals('pause_underperforming', $pauseHighCPA['action']['type']);
    }

    public function test_get_rule_templates_includes_increase_budget_high_roas()
    {
        $templates = $this->engine->getRuleTemplates();

        $increaseTemplate = collect($templates)->first(function ($template) {
            return $template['id'] === 'increase_budget_high_roas';
        });

        $this->assertNotNull($increaseTemplate);
        $this->assertEquals('roas', $increaseTemplate['condition']['metric']);
        $this->assertEquals('>', $increaseTemplate['condition']['operator']);
        $this->assertEquals('increase_budget', $increaseTemplate['action']['type']);
        $this->assertEquals(20, $increaseTemplate['action']['value']);
    }

    public function test_get_rule_templates_includes_decrease_budget_low_ctr()
    {
        $templates = $this->engine->getRuleTemplates();

        $decreaseTemplate = collect($templates)->first(function ($template) {
            return $template['id'] === 'decrease_budget_low_ctr';
        });

        $this->assertNotNull($decreaseTemplate);
        $this->assertEquals('ctr', $decreaseTemplate['condition']['metric']);
        $this->assertEquals('<', $decreaseTemplate['condition']['operator']);
        $this->assertEquals('decrease_budget', $decreaseTemplate['action']['type']);
    }

    public function test_get_rule_templates_includes_notify_high_spend()
    {
        $templates = $this->engine->getRuleTemplates();

        $notifyTemplate = collect($templates)->first(function ($template) {
            return $template['id'] === 'notify_high_spend';
        });

        $this->assertNotNull($notifyTemplate);
        $this->assertEquals('spend', $notifyTemplate['condition']['metric']);
        $this->assertEquals('notify', $notifyTemplate['action']['type']);
    }

    public function test_rule_evaluation_with_all_supported_metrics()
    {
        $metrics = [
            'cpa' => 75,
            'roas' => 2.5,
            'ctr' => 0.015,
            'conversion_rate' => 0.03,
            'spend' => 1200
        ];

        // Test each metric
        $cpaRule = [
            'condition' => ['metric' => 'cpa', 'operator' => '>', 'value' => 50]
        ];
        $this->assertTrue($this->engine->evaluateRule($cpaRule, $metrics));

        $roasRule = [
            'condition' => ['metric' => 'roas', 'operator' => '>', 'value' => 2.0]
        ];
        $this->assertTrue($this->engine->evaluateRule($roasRule, $metrics));

        $ctrRule = [
            'condition' => ['metric' => 'ctr', 'operator' => '<', 'value' => 0.02]
        ];
        $this->assertTrue($this->engine->evaluateRule($ctrRule, $metrics));

        $conversionRule = [
            'condition' => ['metric' => 'conversion_rate', 'operator' => '>=', 'value' => 0.03]
        ];
        $this->assertTrue($this->engine->evaluateRule($conversionRule, $metrics));

        $spendRule = [
            'condition' => ['metric' => 'spend', 'operator' => '>', 'value' => 1000]
        ];
        $this->assertTrue($this->engine->evaluateRule($spendRule, $metrics));
    }

    public function test_all_operators_work_correctly()
    {
        $metrics = ['test_metric' => 50];

        // Greater than
        $this->assertTrue($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '>', 'value' => 40]
        ], $metrics));

        $this->assertFalse($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '>', 'value' => 60]
        ], $metrics));

        // Less than
        $this->assertTrue($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '<', 'value' => 60]
        ], $metrics));

        $this->assertFalse($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '<', 'value' => 40]
        ], $metrics));

        // Equals
        $this->assertTrue($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '=', 'value' => 50]
        ], $metrics));

        $this->assertFalse($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '=', 'value' => 40]
        ], $metrics));

        // Greater than or equal
        $this->assertTrue($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '>=', 'value' => 50]
        ], $metrics));

        $this->assertTrue($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '>=', 'value' => 40]
        ], $metrics));

        // Less than or equal
        $this->assertTrue($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '<=', 'value' => 50]
        ], $metrics));

        $this->assertTrue($this->engine->evaluateRule([
            'condition' => ['metric' => 'test_metric', 'operator' => '<=', 'value' => 60]
        ], $metrics));
    }

    public function test_invalid_operator_returns_false()
    {
        $rule = [
            'condition' => [
                'metric' => 'cpa',
                'operator' => '!=', // Invalid operator
                'value' => 50
            ]
        ];

        $metrics = ['cpa' => 75];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertFalse($result);
    }

    public function test_handles_zero_values_correctly()
    {
        $metrics = ['cpa' => 0];

        $rule = [
            'condition' => [
                'metric' => 'cpa',
                'operator' => '=',
                'value' => 0
            ]
        ];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertTrue($result);
    }

    public function test_handles_negative_values_correctly()
    {
        $metrics = ['roas' => -1.5];

        $rule = [
            'condition' => [
                'metric' => 'roas',
                'operator' => '<',
                'value' => 0
            ]
        ];

        $result = $this->engine->evaluateRule($rule, $metrics);

        $this->assertTrue($result);
    }
}
