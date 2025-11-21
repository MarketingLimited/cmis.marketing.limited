<?php

namespace App\Services\Automation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutomationRulesEngine
{
    /**
     * Rule types
     */
    const RULE_TYPE_PAUSE_UNDERPERFORMING = 'pause_underperforming';
    const RULE_TYPE_INCREASE_BUDGET = 'increase_budget';
    const RULE_TYPE_DECREASE_BUDGET = 'decrease_budget';
    const RULE_TYPE_ADJUST_BID = 'adjust_bid';
    const RULE_TYPE_NOTIFY = 'notify';

    /**
     * Metric types
     */
    const METRIC_CPA = 'cpa'; // Cost per acquisition
    const METRIC_ROAS = 'roas'; // Return on ad spend
    const METRIC_CTR = 'ctr'; // Click-through rate
    const METRIC_CONVERSION_RATE = 'conversion_rate';
    const METRIC_SPEND = 'spend';

    /**
     * Operators
     */
    const OPERATOR_GREATER_THAN = '>';
    const OPERATOR_LESS_THAN = '<';
    const OPERATOR_EQUALS = '=';
    const OPERATOR_GREATER_THAN_OR_EQUAL = '>=';
    const OPERATOR_LESS_THAN_OR_EQUAL = '<=';

    /**
     * Evaluate a rule against campaign metrics
     */
    public function evaluateRule(array $rule, array $metrics): bool
    {
        $condition = $rule['condition'];
        $metric = $metrics[$condition['metric']] ?? null;

        if ($metric === null) {
            return false;
        }

        return $this->compareValues(
            $metric,
            $condition['value'],
            $condition['operator']
        );
    }

    /**
     * Apply a rule action to a campaign
     */
    public function applyRule(array $rule, string $campaignId, string $orgId): array
    {
        try {
            switch ($rule['action']['type']) {
                case self::RULE_TYPE_PAUSE_UNDERPERFORMING:
                    return $this->pauseCampaign($campaignId, $orgId, $rule);

                case self::RULE_TYPE_INCREASE_BUDGET:
                    return $this->adjustBudget($campaignId, $orgId, $rule['action']['value'], 'increase');

                case self::RULE_TYPE_DECREASE_BUDGET:
                    return $this->adjustBudget($campaignId, $orgId, $rule['action']['value'], 'decrease');

                case self::RULE_TYPE_ADJUST_BID:
                    return $this->adjustBid($campaignId, $orgId, $rule['action']['value']);

                case self::RULE_TYPE_NOTIFY:
                    return $this->sendNotification($campaignId, $orgId, $rule);

                default:
                    throw new \Exception("Unknown rule action type: {$rule['action']['type']}");
            }
        } catch (\Exception $e) {
            Log::error('Failed to apply automation rule', [
                'rule_id' => $rule['id'] ?? 'unknown',
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Pause a campaign
     */
    private function pauseCampaign(string $campaignId, string $orgId, array $rule): array
    {
        DB::table('cmis.campaigns')
            ->where('id', $campaignId)
            ->where('org_id', $orgId)
            ->update([
                'status' => 'paused',
                'updated_at' => Carbon::now(),
                'metadata->automation_paused' => true,
                'metadata->automation_rule_id' => $rule['id'] ?? null,
                'metadata->automation_reason' => $rule['name'] ?? 'Automated pause'
            ]);

        $this->logRuleExecution($rule, $campaignId, 'paused', 'Campaign paused due to rule');

        return [
            'success' => true,
            'action' => 'paused',
            'message' => 'Campaign paused successfully'
        ];
    }

    /**
     * Adjust campaign budget
     */
    private function adjustBudget(string $campaignId, string $orgId, float $adjustmentPercent, string $direction): array
    {
        $campaign = DB::table('cmis.campaigns')
            ->where('id', $campaignId)
            ->where('org_id', $orgId)
            ->first();

        if (!$campaign) {
            throw new \Exception('Campaign not found');
        }

        $currentBudget = $campaign->budget ?? 0;

        if ($direction === 'increase') {
            $newBudget = $currentBudget * (1 + $adjustmentPercent / 100);
        } else {
            $newBudget = $currentBudget * (1 - $adjustmentPercent / 100);
        }

        // Ensure minimum budget
        $newBudget = max($newBudget, 10);

        DB::table('cmis.campaigns')
            ->where('id', $campaignId)
            ->where('org_id', $orgId)
            ->update([
                'budget' => $newBudget,
                'updated_at' => Carbon::now()
            ]);

        $this->logRuleExecution([], $campaignId, 'budget_adjusted', "Budget adjusted from {$currentBudget} to {$newBudget}");

        return [
            'success' => true,
            'action' => 'budget_adjusted',
            'old_budget' => $currentBudget,
            'new_budget' => $newBudget,
            'message' => "Budget adjusted from {$currentBudget} to {$newBudget}"
        ];
    }

    /**
     * Adjust campaign bid
     */
    private function adjustBid(string $campaignId, string $orgId, float $adjustmentPercent): array
    {
        // This would integrate with platform-specific APIs
        // For now, just log the action

        $this->logRuleExecution([], $campaignId, 'bid_adjusted', "Bid adjustment of {$adjustmentPercent}% requested");

        return [
            'success' => true,
            'action' => 'bid_adjustment_queued',
            'adjustment_percent' => $adjustmentPercent,
            'message' => "Bid adjustment queued"
        ];
    }

    /**
     * Send notification
     */
    private function sendNotification(string $campaignId, string $orgId, array $rule): array
    {
        // Get campaign details
        $campaign = DB::table('cmis.campaigns')
            ->where('id', $campaignId)
            ->where('org_id', $orgId)
            ->first();

        if (!$campaign) {
            throw new \Exception('Campaign not found');
        }

        // Create notification
        DB::table('cmis.notifications')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'org_id' => $orgId,
            'type' => 'campaign_automation_alert',
            'title' => $rule['name'] ?? 'Campaign Alert',
            'message' => "Campaign '{$campaign->name}' triggered automation rule: {$rule['name']}",
            'data' => json_encode([
                'campaign_id' => $campaignId,
                'rule_id' => $rule['id'] ?? null,
                'condition' => $rule['condition'] ?? null
            ]),
            'read' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $this->logRuleExecution($rule, $campaignId, 'notification_sent', 'Notification sent');

        return [
            'success' => true,
            'action' => 'notification_sent',
            'message' => 'Notification sent successfully'
        ];
    }

    /**
     * Compare two values using an operator
     */
    private function compareValues($actual, $expected, string $operator): bool
    {
        return match ($operator) {
            self::OPERATOR_GREATER_THAN => $actual > $expected,
            self::OPERATOR_LESS_THAN => $actual < $expected,
            self::OPERATOR_EQUALS => $actual == $expected,
            self::OPERATOR_GREATER_THAN_OR_EQUAL => $actual >= $expected,
            self::OPERATOR_LESS_THAN_OR_EQUAL => $actual <= $expected,
            default => false
        };
    }

    /**
     * Log rule execution
     */
    private function logRuleExecution(array $rule, string $campaignId, string $action, string $details): void
    {
        DB::table('cmis_automation.rule_execution_log')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'rule_id' => $rule['id'] ?? null,
            'campaign_id' => $campaignId,
            'action' => $action,
            'details' => $details,
            'executed_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }

    /**
     * Get available rule templates
     */
    public function getRuleTemplates(): array
    {
        return [
            [
                'id' => 'pause_high_cpa',
                'name' => 'Pause campaigns with high CPA',
                'description' => 'Automatically pause campaigns when cost per acquisition exceeds threshold',
                'condition' => [
                    'metric' => self::METRIC_CPA,
                    'operator' => self::OPERATOR_GREATER_THAN,
                    'value' => 50 // Example value
                ],
                'action' => [
                    'type' => self::RULE_TYPE_PAUSE_UNDERPERFORMING
                ]
            ],
            [
                'id' => 'increase_budget_high_roas',
                'name' => 'Increase budget for high ROAS campaigns',
                'description' => 'Automatically increase budget by percentage when ROAS exceeds threshold',
                'condition' => [
                    'metric' => self::METRIC_ROAS,
                    'operator' => self::OPERATOR_GREATER_THAN,
                    'value' => 3.0
                ],
                'action' => [
                    'type' => self::RULE_TYPE_INCREASE_BUDGET,
                    'value' => 20 // Increase by 20%
                ]
            ],
            [
                'id' => 'decrease_budget_low_ctr',
                'name' => 'Decrease budget for low CTR campaigns',
                'description' => 'Automatically decrease budget when CTR is below threshold',
                'condition' => [
                    'metric' => self::METRIC_CTR,
                    'operator' => self::OPERATOR_LESS_THAN,
                    'value' => 0.01 // 1%
                ],
                'action' => [
                    'type' => self::RULE_TYPE_DECREASE_BUDGET,
                    'value' => 30 // Decrease by 30%
                ]
            ],
            [
                'id' => 'notify_high_spend',
                'name' => 'Alert on high spending',
                'description' => 'Send notification when daily spend exceeds threshold',
                'condition' => [
                    'metric' => self::METRIC_SPEND,
                    'operator' => self::OPERATOR_GREATER_THAN,
                    'value' => 1000
                ],
                'action' => [
                    'type' => self::RULE_TYPE_NOTIFY
                ]
            ]
        ];
    }

    /**
     * Validate rule structure
     */
    public function validateRule(array $rule): array
    {
        $errors = [];

        // Validate condition
        if (!isset($rule['condition'])) {
            $errors[] = 'Rule must have a condition';
        } else {
            if (!isset($rule['condition']['metric'])) {
                $errors[] = 'Condition must have a metric';
            }
            if (!isset($rule['condition']['operator'])) {
                $errors[] = 'Condition must have an operator';
            }
            if (!isset($rule['condition']['value'])) {
                $errors[] = 'Condition must have a value';
            }
        }

        // Validate action
        if (!isset($rule['action'])) {
            $errors[] = 'Rule must have an action';
        } else {
            if (!isset($rule['action']['type'])) {
                $errors[] = 'Action must have a type';
            }
        }

        return $errors;
    }
}
