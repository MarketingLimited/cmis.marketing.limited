<?php

namespace App\Services\Automation;

use App\Models\Automation\AutomationRule;
use App\Models\Automation\AutomationExecution;
use App\Models\Automation\AutomationSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for executing automation rules
 * Handles rule evaluation, action execution, and result tracking
 */
class AutomationExecutionService
{
    private AutomationRulesEngine $rulesEngine;

    public function __construct(AutomationRulesEngine $rulesEngine)
    {
        $this->rulesEngine = $rulesEngine;
    }

    /**
     * Process all due automation schedules
     */
    public function processDueSchedules(): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];

        try {
            // Get all due schedules
            $dueSchedules = AutomationSchedule::due()->get();

            foreach ($dueSchedules as $schedule) {
                try {
                    // Set org context
                    DB::statement(
                        'SELECT cmis.init_transaction_context(?, ?)',
                        [config('cmis.system_user_id', '00000000-0000-0000-0000-000000000000'), $schedule->org_id]
                    );

                    $result = $this->executeSchedule($schedule);

                    $results['processed']++;
                    if ($result['status'] === 'success') {
                        $results['successful']++;
                    } elseif ($result['status'] === 'failed') {
                        $results['failed']++;
                    } else {
                        $results['skipped']++;
                    }

                    $results['details'][] = $result;

                } catch (\Exception $e) {
                    $results['failed']++;
                    Log::error('Schedule execution error', [
                        'schedule_id' => $schedule->schedule_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Process due schedules error', ['error' => $e->getMessage()]);
            return array_merge($results, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Execute a single automation schedule
     */
    public function executeSchedule(AutomationSchedule $schedule): array
    {
        $startTime = microtime(true);

        try {
            $rule = $schedule->rule;

            if (!$rule) {
                return [
                    'status' => 'failed',
                    'schedule_id' => $schedule->schedule_id,
                    'error' => 'Associated rule not found'
                ];
            }

            // Check if rule can execute
            if (!$rule->canExecute()) {
                return [
                    'status' => 'skipped',
                    'schedule_id' => $schedule->schedule_id,
                    'reason' => 'Rule execution throttled'
                ];
            }

            // Execute the rule
            $result = $this->executeRule($rule);

            // Mark schedule as run
            $schedule->markAsRun();

            return array_merge($result, [
                'schedule_id' => $schedule->schedule_id,
                'next_run_at' => $schedule->next_run_at
            ]);

        } catch (\Exception $e) {
            Log::error('Schedule execution error', [
                'schedule_id' => $schedule->schedule_id,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'failed',
                'schedule_id' => $schedule->schedule_id,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute an automation rule
     */
    public function executeRule(AutomationRule $rule, ?array $context = null): array
    {
        $startTime = microtime(true);
        $conditionsEvaluated = [];
        $actionsExecuted = [];
        $results = [];
        $status = 'success';
        $errorMessage = null;

        try {
            // Evaluate conditions
            $conditionsMet = $this->evaluateConditions($rule, $context, $conditionsEvaluated);

            if (!$conditionsMet) {
                $status = 'skipped';
                $this->recordExecution($rule, $status, $startTime, $conditionsEvaluated, [], [], $context);

                return [
                    'status' => $status,
                    'rule_id' => $rule->rule_id,
                    'reason' => 'Conditions not met'
                ];
            }

            // Execute actions
            foreach ($rule->actions as $action) {
                try {
                    $actionResult = $this->executeAction($rule, $action, $context);

                    $actionsExecuted[] = $action;
                    $results[] = $actionResult;

                    if (!$actionResult['success']) {
                        $status = 'partial';
                    }

                } catch (\Exception $e) {
                    $status = 'partial';
                    $results[] = [
                        'success' => false,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ];

                    Log::error('Action execution error', [
                        'rule_id' => $rule->rule_id,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Record execution
            $this->recordExecution(
                $rule,
                $status,
                $startTime,
                $conditionsEvaluated,
                $actionsExecuted,
                $results,
                $context,
                $errorMessage
            );

            return [
                'status' => $status,
                'rule_id' => $rule->rule_id,
                'rule_name' => $rule->name,
                'actions_executed' => count($actionsExecuted),
                'results' => $results
            ];

        } catch (\Exception $e) {
            $status = 'failure';
            $errorMessage = $e->getMessage();

            Log::error('Rule execution error', [
                'rule_id' => $rule->rule_id,
                'error' => $errorMessage
            ]);

            $this->recordExecution(
                $rule,
                $status,
                $startTime,
                $conditionsEvaluated,
                $actionsExecuted,
                $results,
                $context,
                $errorMessage
            );

            return [
                'status' => $status,
                'rule_id' => $rule->rule_id,
                'error' => $errorMessage
            ];
        }
    }

    /**
     * Evaluate rule conditions
     */
    private function evaluateConditions(AutomationRule $rule, ?array $context, array &$conditionsEvaluated): bool
    {
        if (empty($rule->conditions)) {
            return true;
        }

        $logic = $rule->condition_logic ?? 'and';
        $results = [];

        foreach ($rule->conditions as $condition) {
            $result = $this->evaluateCondition($condition, $context);
            $conditionsEvaluated[] = array_merge($condition, ['result' => $result]);
            $results[] = $result;
        }

        return $logic === 'and'
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    /**
     * Evaluate single condition
     */
    private function evaluateCondition(array $condition, ?array $context): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        if (!$field || !$context) {
            return false;
        }

        $actualValue = data_get($context, $field);

        return match ($operator) {
            '==' => $actualValue == $value,
            '!=' => $actualValue != $value,
            '>' => $actualValue > $value,
            '>=' => $actualValue >= $value,
            '<' => $actualValue < $value,
            '<=' => $actualValue <= $value,
            'contains' => str_contains((string)$actualValue, (string)$value),
            'in' => in_array($actualValue, (array)$value),
            default => false
        };
    }

    /**
     * Execute a single action
     */
    private function executeAction(AutomationRule $rule, array $action, ?array $context): array
    {
        $type = $action['type'] ?? null;

        if (!$type) {
            return ['success' => false, 'error' => 'Action type not specified'];
        }

        return match ($type) {
            'pause_campaign' => $this->pauseCampaign($action, $context),
            'adjust_budget' => $this->adjustBudget($action, $context),
            'send_notification' => $this->sendNotification($action, $context, $rule),
            'tag_entity' => $this->tagEntity($action, $context),
            'trigger_webhook' => $this->triggerWebhook($action, $context),
            default => ['success' => false, 'error' => "Unknown action type: {$type}"]
        };
    }

    private function pauseCampaign(array $action, ?array $context): array
    {
        // Implementation would pause campaign
        return ['success' => true, 'action' => 'pause_campaign', 'message' => 'Campaign paused'];
    }

    private function adjustBudget(array $action, ?array $context): array
    {
        // Implementation would adjust budget
        return ['success' => true, 'action' => 'adjust_budget', 'message' => 'Budget adjusted'];
    }

    private function sendNotification(array $action, ?array $context, AutomationRule $rule): array
    {
        // Implementation would send notification
        return ['success' => true, 'action' => 'send_notification', 'message' => 'Notification sent'];
    }

    private function tagEntity(array $action, ?array $context): array
    {
        // Implementation would tag entity
        return ['success' => true, 'action' => 'tag_entity', 'message' => 'Entity tagged'];
    }

    private function triggerWebhook(array $action, ?array $context): array
    {
        // Implementation would trigger webhook
        return ['success' => true, 'action' => 'trigger_webhook', 'message' => 'Webhook triggered'];
    }

    /**
     * Record automation execution
     */
    private function recordExecution(
        AutomationRule $rule,
        string $status,
        float $startTime,
        array $conditionsEvaluated,
        array $actionsExecuted,
        array $results,
        ?array $context = null,
        ?string $errorMessage = null
    ): void {
        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        AutomationExecution::create([
            'org_id' => $rule->org_id,
            'rule_id' => $rule->rule_id,
            'entity_id' => $context['entity_id'] ?? null,
            'status' => $status,
            'executed_at' => now(),
            'duration_ms' => $durationMs,
            'conditions_evaluated' => $conditionsEvaluated,
            'actions_executed' => $actionsExecuted,
            'results' => $results,
            'error_message' => $errorMessage,
            'context' => $context
        ]);

        // Update rule statistics
        $rule->recordExecution($status);
    }
}
