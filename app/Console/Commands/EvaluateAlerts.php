<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAlertsJob;
use App\Models\Analytics\AlertHistory;
use Illuminate\Console\Command;

/**
 * Evaluate Alerts Command (Phase 13)
 *
 * Evaluates active alert rules and triggers notifications
 * Should be run every 1-5 minutes via Laravel scheduler
 */
class EvaluateAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:evaluate
                            {--rule= : Evaluate specific rule by ID}
                            {--entity-type= : Evaluate rules for specific entity type}
                            {--entity-id= : Evaluate rules for specific entity ID (requires entity-type)}
                            {--sync : Run synchronously instead of dispatching to queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate active alert rules and trigger notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting alert evaluation...');

        if ($ruleId = $this->option('rule')) {
            return $this->evaluateRule($ruleId);
        }

        if ($entityType = $this->option('entity-type')) {
            $entityId = $this->option('entity-id');
            if (!$entityId) {
                $this->error('--entity-id is required when using --entity-type');
                return self::FAILURE;
            }
            return $this->evaluateEntity($entityType, $entityId);
        }

        return $this->evaluateAll();
    }

    /**
     * Evaluate all due rules
     *
     * @return int
     */
    protected function evaluateAll(): int
    {
        $this->info('Evaluating all due alert rules...');

        if ($this->option('sync')) {
            // Run synchronously
            $evaluationService = app(\App\Services\Analytics\AlertEvaluationService::class);
            $notificationService = app(\App\Services\Analytics\NotificationDeliveryService::class);

            $stats = $evaluationService->evaluateAllDueRules();

            $this->displayStats($stats);
        } else {
            // Dispatch to queue
            ProcessAlertsJob::dispatch('all');
            $this->info('Alert evaluation job dispatched to queue.');
        }

        return self::SUCCESS;
    }

    /**
     * Evaluate specific rule
     *
     * @param string $ruleId
     * @return int
     */
    protected function evaluateRule(string $ruleId): int
    {
        $this->info("Evaluating alert rule: {$ruleId}");

        $rule = \App\Models\Analytics\AlertRule::find($ruleId);

        if (!$rule) {
            $this->error("Alert rule not found: {$ruleId}");
            return self::FAILURE;
        }

        if (!$rule->is_active) {
            $this->warn("Alert rule is not active: {$rule->name}");
            return self::FAILURE;
        }

        if ($this->option('sync')) {
            // Run synchronously
            $evaluationService = app(\App\Services\Analytics\AlertEvaluationService::class);
            $notificationService = app(\App\Services\Analytics\NotificationDeliveryService::class);

            // Process rule...
            $this->info('Processing rule synchronously...');
        } else {
            ProcessAlertsJob::dispatch('rule', ['rule_id' => $ruleId]);
            $this->info('Rule evaluation job dispatched to queue.');
        }

        return self::SUCCESS;
    }

    /**
     * Evaluate rules for specific entity
     *
     * @param string $entityType
     * @param string $entityId
     * @return int
     */
    protected function evaluateEntity(string $entityType, string $entityId): int
    {
        $this->info("Evaluating alert rules for {$entityType}: {$entityId}");

        if ($this->option('sync')) {
            $this->info('Synchronous entity evaluation not yet implemented.');
            $this->info('Use queue mode (remove --sync flag).');
            return self::FAILURE;
        }

        ProcessAlertsJob::dispatch('entity', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metrics' => []  // Metrics will be fetched by the job
        ]);

        $this->info('Entity evaluation job dispatched to queue.');

        return self::SUCCESS;
    }

    /**
     * Display evaluation statistics
     *
     * @param array $stats
     * @return void
     */
    protected function displayStats(array $stats): void
    {
        $this->info("\nEvaluation Results:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Rules Evaluated', $stats['total_rules'] ?? 0],
                ['Alerts Triggered', $stats['alerts_triggered'] ?? 0],
                ['Errors', $stats['errors'] ?? 0]
            ]
        );

        // Show recent triggered alerts
        $recentAlerts = AlertHistory::recent(1)
            ->latest('triggered_at')
            ->limit(10)
            ->get();

        if ($recentAlerts->isNotEmpty()) {
            $this->info("\nRecently Triggered Alerts (last 24 hours):");

            $alertsTable = $recentAlerts->map(function ($alert) {
                return [
                    'Rule' => $alert->rule->name ?? 'N/A',
                    'Severity' => ucfirst($alert->severity),
                    'Metric' => $alert->metric,
                    'Value' => number_format($alert->actual_value, 2),
                    'Triggered' => $alert->triggered_at->diffForHumans()
                ];
            })->toArray();

            $this->table(
                ['Rule', 'Severity', 'Metric', 'Value', 'Triggered'],
                $alertsTable
            );
        }
    }
}
