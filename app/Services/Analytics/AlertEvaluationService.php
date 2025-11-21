<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Alert Evaluation Service (Phase 13)
 *
 * Evaluates alert rules against current metrics and triggers alerts when conditions are met
 *
 * Features:
 * - Rule evaluation against real-time metrics
 * - Cooldown period enforcement
 * - Alert history creation
 * - Notification triggering
 */
class AlertEvaluationService
{
    /**
     * Evaluate all active rules for an entity
     *
     * @param string $entityType
     * @param string $entityId
     * @param array $metrics Current metrics
     * @return array Triggered alerts
     */
    public function evaluateEntityRules(string $entityType, string $entityId, array $metrics): array
    {
        $rules = AlertRule::active()
            ->forEntity($entityType, $entityId)
            ->dueForEvaluation()
            ->get();

        $triggeredAlerts = [];

        foreach ($rules as $rule) {
            if ($alert = $this->evaluateRule($rule, $metrics, $entityId)) {
                $triggeredAlerts[] = $alert;
            }
        }

        return $triggeredAlerts;
    }

    /**
     * Evaluate a single rule against metrics
     *
     * @param AlertRule $rule
     * @param array $metrics
     * @param string|null $entityId
     * @return AlertHistory|null
     */
    public function evaluateRule(AlertRule $rule, array $metrics, ?string $entityId = null): ?AlertHistory
    {
        // Check if rule is in cooldown
        if ($rule->isInCooldown()) {
            return null;
        }

        // Get the metric value
        $metricValue = $this->getMetricValue($metrics, $rule->metric);

        if ($metricValue === null) {
            return null;
        }

        // Handle percentage change condition
        if ($rule->condition === 'change_pct') {
            $metricValue = $this->calculatePercentageChange(
                $rule,
                $metricValue,
                $entityId ?? $rule->entity_id
            );

            if ($metricValue === null) {
                return null;
            }
        }

        // Evaluate condition
        if (!$rule->evaluateCondition($metricValue)) {
            return null;
        }

        // Condition met - create alert
        $alert = $this->createAlert($rule, $metricValue, $entityId);

        // Mark rule as triggered
        $rule->markTriggered();

        Log::info('Alert triggered', [
            'rule_id' => $rule->rule_id,
            'rule_name' => $rule->name,
            'metric' => $rule->metric,
            'actual_value' => $metricValue,
            'threshold' => $rule->threshold,
            'severity' => $rule->severity
        ]);

        return $alert;
    }

    /**
     * Create alert history record
     *
     * @param AlertRule $rule
     * @param float $actualValue
     * @param string|null $entityId
     * @return AlertHistory
     */
    protected function createAlert(AlertRule $rule, float $actualValue, ?string $entityId = null): AlertHistory
    {
        $message = $this->generateAlertMessage($rule, $actualValue);

        return AlertHistory::create([
            'rule_id' => $rule->rule_id,
            'org_id' => $rule->org_id,
            'triggered_at' => now(),
            'entity_type' => $rule->entity_type,
            'entity_id' => $entityId ?? $rule->entity_id,
            'metric' => $rule->metric,
            'actual_value' => $actualValue,
            'threshold_value' => $rule->threshold,
            'condition' => $rule->condition,
            'severity' => $rule->severity,
            'message' => $message,
            'metadata' => [
                'rule_name' => $rule->name,
                'time_window' => $rule->time_window_minutes,
                'evaluation_time' => now()->toIso8601String()
            ],
            'status' => 'new'
        ]);
    }

    /**
     * Generate human-readable alert message
     *
     * @param AlertRule $rule
     * @param float $actualValue
     * @return string
     */
    protected function generateAlertMessage(AlertRule $rule, float $actualValue): string
    {
        $metric = ucfirst(str_replace('_', ' ', $rule->metric));
        $condition = $rule->getConditionText();
        $threshold = $this->formatValue($rule->metric, $rule->threshold);
        $actual = $this->formatValue($rule->metric, $actualValue);

        $message = "{$metric} is {$condition} threshold: {$actual} (threshold: {$threshold})";

        if ($rule->entity_id) {
            $message = "[{$rule->entity_type}] " . $message;
        } else {
            $message = "[All {$rule->entity_type}s] " . $message;
        }

        return $message;
    }

    /**
     * Get metric value from metrics array
     *
     * @param array $metrics
     * @param string $metricName
     * @return float|null
     */
    protected function getMetricValue(array $metrics, string $metricName): ?float
    {
        // Support nested metrics with dot notation
        $keys = explode('.', $metricName);
        $value = $metrics;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Calculate percentage change for a metric
     *
     * @param AlertRule $rule
     * @param float $currentValue
     * @param string|null $entityId
     * @return float|null
     */
    protected function calculatePercentageChange(AlertRule $rule, float $currentValue, ?string $entityId): ?float
    {
        // Get historical value from time_window_minutes ago
        $historicalValue = $this->getHistoricalMetricValue(
            $rule->entity_type,
            $entityId,
            $rule->metric,
            $rule->time_window_minutes
        );

        if ($historicalValue === null || $historicalValue == 0) {
            return null;
        }

        $percentageChange = (($currentValue - $historicalValue) / $historicalValue) * 100;

        return abs($percentageChange);
    }

    /**
     * Get historical metric value
     *
     * @param string $entityType
     * @param string|null $entityId
     * @param string $metric
     * @param int $minutesAgo
     * @return float|null
     */
    protected function getHistoricalMetricValue(
        string $entityType,
        ?string $entityId,
        string $metric,
        int $minutesAgo
    ): ?float {
        // This would query from your metrics/analytics tables
        // Simplified implementation - would need actual table structure

        try {
            $timestamp = now()->subMinutes($minutesAgo);

            // Example query structure (adjust based on actual tables)
            $result = DB::table('cmis.analytics_metrics')
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->where('metric_name', $metric)
                ->where('recorded_at', '<=', $timestamp)
                ->orderBy('recorded_at', 'desc')
                ->first();

            return $result ? (float) $result->value : null;
        } catch (\Exception $e) {
            Log::warning('Failed to get historical metric value', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Format value based on metric type
     *
     * @param string $metric
     * @param float $value
     * @return string
     */
    protected function formatValue(string $metric, float $value): string
    {
        // Format based on metric type
        if (str_contains($metric, 'rate') || str_contains($metric, 'ctr') || str_contains($metric, 'percentage')) {
            return number_format($value, 2) . '%';
        }

        if (str_contains($metric, 'cost') || str_contains($metric, 'spend') || str_contains($metric, 'revenue')) {
            return '$' . number_format($value, 2);
        }

        if (str_contains($metric, 'time') || str_contains($metric, 'duration')) {
            return number_format($value, 0) . 's';
        }

        // Default numeric format
        return number_format($value, 2);
    }

    /**
     * Batch evaluate all due rules
     *
     * @return array Statistics
     */
    public function evaluateAllDueRules(): array
    {
        $rules = AlertRule::active()
            ->dueForEvaluation()
            ->get();

        $stats = [
            'total_rules' => $rules->count(),
            'alerts_triggered' => 0,
            'errors' => 0
        ];

        foreach ($rules as $rule) {
            try {
                // For rules without specific entity_id, evaluate against all entities
                if ($rule->entity_id) {
                    $metrics = $this->getCurrentMetrics($rule->entity_type, $rule->entity_id);
                    if ($this->evaluateRule($rule, $metrics, $rule->entity_id)) {
                        $stats['alerts_triggered']++;
                    }
                } else {
                    // Evaluate against all entities of this type
                    $entities = $this->getEntitiesOfType($rule->entity_type, $rule->org_id);
                    foreach ($entities as $entityId) {
                        $metrics = $this->getCurrentMetrics($rule->entity_type, $entityId);
                        if ($this->evaluateRule($rule, $metrics, $entityId)) {
                            $stats['alerts_triggered']++;
                        }
                    }
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Failed to evaluate rule', [
                    'rule_id' => $rule->rule_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $stats;
    }

    /**
     * Get current metrics for entity
     *
     * @param string $entityType
     * @param string $entityId
     * @return array
     */
    protected function getCurrentMetrics(string $entityType, string $entityId): array
    {
        // This would fetch real-time metrics from your analytics system
        // Simplified implementation - integrate with actual analytics endpoints

        try {
            // Example: fetch from analytics service or database
            $metrics = DB::table('cmis.analytics_metrics')
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->where('recorded_at', '>=', now()->subMinutes(5))
                ->get()
                ->pluck('value', 'metric_name')
                ->toArray();

            return $metrics;
        } catch (\Exception $e) {
            Log::warning('Failed to get current metrics', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get all entity IDs of a given type for organization
     *
     * @param string $entityType
     * @param string $orgId
     * @return array
     */
    protected function getEntitiesOfType(string $entityType, string $orgId): array
    {
        // Map entity types to tables
        $tableMap = [
            'campaign' => 'cmis.campaigns',
            'ad' => 'cmis_platform.ads',
            'post' => 'cmis_social.posts',
            // Add more mappings as needed
        ];

        $table = $tableMap[$entityType] ?? null;

        if (!$table) {
            return [];
        }

        try {
            $idColumn = $entityType . '_id';
            return DB::table($table)
                ->where('org_id', $orgId)
                ->pluck($idColumn)
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('Failed to get entities', [
                'entity_type' => $entityType,
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }
}
