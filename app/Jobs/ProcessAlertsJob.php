<?php

namespace App\Jobs;

use App\Models\Analytics\AlertHistory;
use App\Services\Analytics\AlertEvaluationService;
use App\Services\Analytics\NotificationDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Process Alerts Job (Phase 13)
 *
 * Processes alert evaluation and notification delivery
 * Can be triggered for:
 * - All due rules (periodic evaluation)
 * - Specific rule evaluation
 * - Specific entity evaluation
 */
class ProcessAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $retryAfter = 120; // 2 minutes

    /**
     * Job mode
     */
    protected string $mode;

    /**
     * Optional parameters based on mode
     */
    protected array $params;

    /**
     * Create a new job instance.
     *
     * @param string $mode 'all', 'rule', 'entity'
     * @param array $params
     */
    public function __construct(string $mode = 'all', array $params = [])
    {
        $this->mode = $mode;
        $this->params = $params;
        $this->onQueue('alerts');
    }

    /**
     * Execute the job.
     */
    public function handle(
        AlertEvaluationService $evaluationService,
        NotificationDeliveryService $notificationService
    ): void {
        Log::info('Processing alerts', [
            'mode' => $this->mode,
            'params' => $this->params
        ]);

        try {
            $result = match ($this->mode) {
                'all' => $this->processAllDueRules($evaluationService),
                'rule' => $this->processSpecificRule($evaluationService, $this->params['rule_id'] ?? null),
                'entity' => $this->processEntityRules(
                    $evaluationService,
                    $this->params['entity_type'] ?? null,
                    $this->params['entity_id'] ?? null,
                    $this->params['metrics'] ?? []
                ),
                default => throw new \InvalidArgumentException("Invalid mode: {$this->mode}")
            };

            // Deliver notifications for any triggered alerts
            if (!empty($result['alerts'])) {
                $this->deliverNotifications($result['alerts'], $notificationService);
            }

            Log::info('Alert processing completed', [
                'mode' => $this->mode,
                'stats' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Alert processing failed', [
                'mode' => $this->mode,
                'params' => $this->params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Process all due alert rules
     *
     * @param AlertEvaluationService $evaluationService
     * @return array
     */
    protected function processAllDueRules(AlertEvaluationService $evaluationService): array
    {
        $stats = $evaluationService->evaluateAllDueRules();

        return [
            'mode' => 'all',
            'stats' => $stats,
            'alerts' => [] // Alerts are created and handled within evaluateAllDueRules
        ];
    }

    /**
     * Process specific alert rule
     *
     * @param AlertEvaluationService $evaluationService
     * @param string|null $ruleId
     * @return array
     */
    protected function processSpecificRule(AlertEvaluationService $evaluationService, ?string $ruleId): array
    {
        if (!$ruleId) {
            throw new \InvalidArgumentException('rule_id is required for rule mode');
        }

        $rule = \App\Models\Analytics\AlertRule::findOrFail($ruleId);

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $rule->created_by,
            $rule->org_id
        ]);

        $alerts = [];

        if ($rule->entity_id) {
            // Evaluate specific entity
            $metrics = $evaluationService->getCurrentMetrics($rule->entity_type, $rule->entity_id);
            if ($alert = $evaluationService->evaluateRule($rule, $metrics, $rule->entity_id)) {
                $alerts[] = $alert;
            }
        } else {
            // Evaluate all entities of type
            $entities = $evaluationService->getEntitiesOfType($rule->entity_type, $rule->org_id);
            foreach ($entities as $entityId) {
                $metrics = $evaluationService->getCurrentMetrics($rule->entity_type, $entityId);
                if ($alert = $evaluationService->evaluateRule($rule, $metrics, $entityId)) {
                    $alerts[] = $alert;
                }
            }
        }

        return [
            'mode' => 'rule',
            'rule_id' => $ruleId,
            'alerts' => $alerts
        ];
    }

    /**
     * Process alert rules for specific entity
     *
     * @param AlertEvaluationService $evaluationService
     * @param string|null $entityType
     * @param string|null $entityId
     * @param array $metrics
     * @return array
     */
    protected function processEntityRules(
        AlertEvaluationService $evaluationService,
        ?string $entityType,
        ?string $entityId,
        array $metrics
    ): array {
        if (!$entityType || !$entityId) {
            throw new \InvalidArgumentException('entity_type and entity_id are required for entity mode');
        }

        $alerts = $evaluationService->evaluateEntityRules($entityType, $entityId, $metrics);

        return [
            'mode' => 'entity',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'alerts' => $alerts
        ];
    }

    /**
     * Deliver notifications for triggered alerts
     *
     * @param array $alerts
     * @param NotificationDeliveryService $notificationService
     * @return void
     */
    protected function deliverNotifications(array $alerts, NotificationDeliveryService $notificationService): void
    {
        foreach ($alerts as $alert) {
            if (!$alert instanceof AlertHistory) {
                continue;
            }

            try {
                $rule = $alert->rule;

                if (!$rule) {
                    Log::warning('Alert rule not found', ['alert_id' => $alert->alert_id]);
                    continue;
                }

                $stats = $notificationService->deliverAlert($alert, $rule);

                Log::info('Notifications delivered for alert', [
                    'alert_id' => $alert->alert_id,
                    'rule_id' => $rule->rule_id,
                    'stats' => $stats
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to deliver notifications for alert', [
                    'alert_id' => $alert->alert_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Alert processing job failed after all retries', [
            'mode' => $this->mode,
            'params' => $this->params,
            'error' => $exception->getMessage()
        ]);
    }
}
