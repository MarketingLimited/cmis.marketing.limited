<?php

namespace App\Services\Optimization;

use App\Models\Optimization\OptimizationRule;
use App\Models\Optimization\OptimizationMetric;
use App\Models\Campaign\Campaign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizationService
{
    /**
     * Execute optimization rules for an entity
     */
    public function executeRules(string $entityType, string $entityId): array
    {
        $orgId = session('current_org_id');

        $rules = OptimizationRule::where('org_id', $orgId)
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        $executed = [];

        foreach ($rules as $rule) {
            if ($this->shouldExecuteRule($rule, $entityId)) {
                $result = $this->executeRule($rule, $entityId);
                $executed[] = $result;

                // Update rule execution count and timestamp
                $rule->increment('execution_count');
                $rule->update(['last_triggered_at' => now()]);
            }
        }

        return $executed;
    }

    /**
     * Determine if rule should be executed
     */
    protected function shouldExecuteRule(OptimizationRule $rule, string $entityId): bool
    {
        // Get entity data
        $entityData = $this->getEntityData($rule->entity_type, $entityId);

        if (!$entityData) {
            return false;
        }

        // Evaluate conditions
        $conditions = $rule->conditions ?? [];

        return $this->evaluateConditions($conditions, $entityData);
    }

    /**
     * Execute a single optimization rule
     */
    protected function executeRule(OptimizationRule $rule, string $entityId): array
    {
        $actions = $rule->actions ?? [];
        $results = [];

        foreach ($actions as $action) {
            $actionType = $action['type'] ?? '';
            $actionParams = $action['params'] ?? [];

            try {
                $result = $this->executeAction($rule->entity_type, $entityId, $actionType, $actionParams);
                $results[] = [
                    'action' => $actionType,
                    'success' => true,
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                Log::error("Optimization action failed: {$e->getMessage()}", [
                    'rule_id' => $rule->rule_id,
                    'action' => $actionType,
                ]);

                $results[] = [
                    'action' => $actionType,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'rule_id' => $rule->rule_id,
            'rule_name' => $rule->rule_name,
            'executed_at' => now()->toIso8601String(),
            'actions' => $results,
        ];
    }

    /**
     * Execute a specific optimization action
     */
    protected function executeAction(string $entityType, string $entityId, string $actionType, array $params): mixed
    {
        return match ($actionType) {
            'adjust_budget' => $this->adjustBudget($entityType, $entityId, $params),
            'adjust_bid' => $this->adjustBid($entityType, $entityId, $params),
            'pause_entity' => $this->pauseEntity($entityType, $entityId),
            'resume_entity' => $this->resumeEntity($entityType, $entityId),
            'send_notification' => $this->sendNotification($params),
            'update_targeting' => $this->updateTargeting($entityType, $entityId, $params),
            'create_alert' => $this->createAlert($entityType, $entityId, $params),
            default => throw new \Exception("Unknown action type: {$actionType}"),
        };
    }

    /**
     * Adjust budget for entity
     */
    protected function adjustBudget(string $entityType, string $entityId, array $params): array
    {
        $adjustmentType = $params['adjustment_type'] ?? 'percentage';
        $value = $params['value'] ?? 0;

        if ($entityType === 'campaign') {
            $campaign = Campaign::findOrFail($entityId);
            $currentBudget = $campaign->budget ?? 0;

            $newBudget = $adjustmentType === 'percentage'
                ? $currentBudget * (1 + $value / 100)
                : $currentBudget + $value;

            $campaign->update(['budget' => max(0, $newBudget)]);

            return [
                'previous_budget' => $currentBudget,
                'new_budget' => $newBudget,
            ];
        }

        return [];
    }

    /**
     * Adjust bid for entity
     */
    protected function adjustBid(string $entityType, string $entityId, array $params): array
    {
        $adjustmentType = $params['adjustment_type'] ?? 'percentage';
        $value = $params['value'] ?? 0;

        // Implementation would depend on entity type
        return [
            'adjustment_type' => $adjustmentType,
            'value' => $value,
        ];
    }

    /**
     * Pause entity
     */
    protected function pauseEntity(string $entityType, string $entityId): array
    {
        if ($entityType === 'campaign') {
            $campaign = Campaign::findOrFail($entityId);
            $campaign->update(['status' => 'paused']);

            return ['status' => 'paused'];
        }

        return [];
    }

    /**
     * Resume entity
     */
    protected function resumeEntity(string $entityType, string $entityId): array
    {
        if ($entityType === 'campaign') {
            $campaign = Campaign::findOrFail($entityId);
            $campaign->update(['status' => 'active']);

            return ['status' => 'active'];
        }

        return [];
    }

    /**
     * Send notification
     */
    protected function sendNotification(array $params): array
    {
        $message = $params['message'] ?? 'Optimization rule triggered';

        // Implementation would send actual notification
        Log::info("Optimization notification: {$message}");

        return ['message_sent' => $message];
    }

    /**
     * Update targeting settings
     */
    protected function updateTargeting(string $entityType, string $entityId, array $params): array
    {
        // Implementation would update targeting based on entity type
        return ['targeting_updated' => true];
    }

    /**
     * Create alert
     */
    protected function createAlert(string $entityType, string $entityId, array $params): array
    {
        // Implementation would create an alert in the alerts system
        return ['alert_created' => true];
    }

    /**
     * Get entity data for condition evaluation
     */
    protected function getEntityData(string $entityType, string $entityId): ?array
    {
        if ($entityType === 'campaign') {
            $campaign = Campaign::find($entityId);

            if (!$campaign) {
                return null;
            }

            return [
                'budget' => $campaign->budget,
                'spend' => $campaign->total_spend ?? 0,
                'status' => $campaign->status,
                'ctr' => $campaign->ctr ?? 0,
                'conversion_rate' => $campaign->conversion_rate ?? 0,
                'cost_per_conversion' => $campaign->cost_per_conversion ?? 0,
            ];
        }

        return null;
    }

    /**
     * Evaluate rule conditions
     */
    protected function evaluateConditions(array $conditions, array $data): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            $actualValue = $data[$field] ?? null;

            $result = match ($operator) {
                '=' => $actualValue == $value,
                '!=' => $actualValue != $value,
                '>' => $actualValue > $value,
                '>=' => $actualValue >= $value,
                '<' => $actualValue < $value,
                '<=' => $actualValue <= $value,
                'between' => $actualValue >= ($value['min'] ?? 0) && $actualValue <= ($value['max'] ?? PHP_FLOAT_MAX),
                'not_between' => !($actualValue >= ($value['min'] ?? 0) && $actualValue <= ($value['max'] ?? PHP_FLOAT_MAX)),
                default => false,
            };

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Analyze entity performance and generate optimization recommendations
     */
    public function analyzePerformance(string $entityType, string $entityId): array
    {
        $data = $this->getEntityData($entityType, $entityId);

        if (!$data) {
            return [];
        }

        $recommendations = [];

        // Budget recommendations
        if (isset($data['spend'], $data['budget'])) {
            $spendRate = $data['budget'] > 0 ? ($data['spend'] / $data['budget']) * 100 : 0;

            if ($spendRate < 50) {
                $recommendations[] = [
                    'type' => 'budget',
                    'priority' => 'low',
                    'message' => 'Underspending detected. Consider increasing bids or expanding targeting.',
                ];
            } elseif ($spendRate > 90) {
                $recommendations[] = [
                    'type' => 'budget',
                    'priority' => 'high',
                    'message' => 'Approaching budget limit. Consider increasing budget if performance is good.',
                ];
            }
        }

        // CTR recommendations
        if (isset($data['ctr']) && $data['ctr'] < 1) {
            $recommendations[] = [
                'type' => 'creative',
                'priority' => 'medium',
                'message' => 'Low CTR detected. Consider updating ad creatives or targeting.',
            ];
        }

        // Conversion rate recommendations
        if (isset($data['conversion_rate']) && $data['conversion_rate'] < 2) {
            $recommendations[] = [
                'type' => 'conversion',
                'priority' => 'medium',
                'message' => 'Low conversion rate. Review landing page and targeting settings.',
            ];
        }

        // Cost per conversion
        if (isset($data['cost_per_conversion']) && $data['cost_per_conversion'] > 50) {
            $recommendations[] = [
                'type' => 'efficiency',
                'priority' => 'high',
                'message' => 'High cost per conversion. Consider optimizing bids and targeting.',
            ];
        }

        return $recommendations;
    }

    /**
     * Get optimization metrics for entity
     */
    public function getMetrics(string $entityType, string $entityId, int $days = 30): Collection
    {
        return OptimizationMetric::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get();
    }

    /**
     * Record optimization metric
     */
    public function recordMetric(
        string $entityType,
        string $entityId,
        string $metricName,
        float $metricValue,
        ?array $metadata = null
    ): OptimizationMetric {
        return OptimizationMetric::create([
            'org_id' => session('current_org_id'),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metric_name' => $metricName,
            'metric_value' => $metricValue,
            'metadata' => $metadata,
            'recorded_at' => now(),
        ]);
    }
}
