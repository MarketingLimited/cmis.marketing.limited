<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    /**
     * Available workflow statuses
     */
    const STATUSES = [
        'draft' => 'مسودة',
        'pending_review' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'archived' => 'مؤرشف',
        'rejected' => 'مرفوض',
    ];

    /**
     * Initialize workflow for campaign
     */
    public function initializeCampaignWorkflow(string $campaignId, array $steps = []): array
    {
        try {
            $campaign = Campaign::findOrFail($campaignId);

            // Default workflow steps if not provided
            if (empty($steps)) {
                $steps = $this->getDefaultCampaignSteps();
            }

            // Create workflow record
            $workflow = [
                'workflow_id' => \Illuminate\Support\Str::uuid(),
                'entity_type' => 'campaign',
                'entity_id' => $campaignId,
                'current_step' => 0,
                'steps' => $steps,
                'status' => 'draft',
                'created_at' => now(),
            ];

            // Store in database (you would have a Workflow model)
            // For now, store in campaign metadata
            $campaign->update([
                'workflow_data' => $workflow
            ]);

            Log::info('Workflow initialized', [
                'campaign_id' => $campaignId,
                'steps_count' => count($steps),
            ]);

            return $workflow;
        } catch (\Exception $e) {
            Log::error('Failed to initialize workflow', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get default campaign workflow steps
     */
    protected function getDefaultCampaignSteps(): array
    {
        return [
            [
                'step_number' => 1,
                'name' => 'إنشاء الحملة',
                'description' => 'إنشاء معلومات الحملة الأساسية',
                'status' => 'completed',
                'required' => true,
            ],
            [
                'step_number' => 2,
                'name' => 'تحديد الجمهور المستهدف',
                'description' => 'تحديد شرائح الجمهور والاستهداف',
                'status' => 'pending',
                'required' => true,
            ],
            [
                'step_number' => 3,
                'name' => 'إنشاء المحتوى',
                'description' => 'إنشاء المحتوى الإبداعي والرسائل',
                'status' => 'pending',
                'required' => true,
            ],
            [
                'step_number' => 4,
                'name' => 'المراجعة والموافقة',
                'description' => 'مراجعة الحملة والحصول على الموافقات',
                'status' => 'pending',
                'required' => true,
            ],
            [
                'step_number' => 5,
                'name' => 'الإطلاق',
                'description' => 'إطلاق الحملة ونشرها',
                'status' => 'pending',
                'required' => true,
            ],
            [
                'step_number' => 6,
                'name' => 'المتابعة والتحسين',
                'description' => 'متابعة الأداء وإجراء التحسينات',
                'status' => 'pending',
                'required' => false,
            ],
        ];
    }

    /**
     * Move workflow to next step
     */
    public function moveToNextStep(string $entityType, string $entityId): bool
    {
        try {
            // Get entity and workflow
            $entity = $this->getEntity($entityType, $entityId);
            $workflow = $entity->workflow_data ?? null;

            if (!$workflow) {
                throw new \Exception('Workflow not found for entity');
            }

            $currentStep = $workflow['current_step'];
            $steps = $workflow['steps'];

            // Validate current step is completed
            if (!isset($steps[$currentStep]) || $steps[$currentStep]['status'] !== 'completed') {
                throw new \Exception('Current step must be completed before moving to next');
            }

            // Move to next step
            $nextStep = $currentStep + 1;

            if ($nextStep < count($steps)) {
                $workflow['current_step'] = $nextStep;
                $workflow['steps'][$nextStep]['status'] = 'in_progress';
                $workflow['steps'][$nextStep]['started_at'] = now();

                $entity->update(['workflow_data' => $workflow]);

                Log::info('Workflow moved to next step', [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'step' => $nextStep,
                ]);

                return true;
            } else {
                // Workflow completed
                $workflow['status'] = 'completed';
                $workflow['completed_at'] = now();
                $entity->update(['workflow_data' => $workflow]);

                Log::info('Workflow completed', [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ]);

                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to move workflow to next step', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Complete current step
     */
    public function completeStep(string $entityType, string $entityId, int $stepNumber, array $data = []): bool
    {
        try {
            $entity = $this->getEntity($entityType, $entityId);
            $workflow = $entity->workflow_data ?? null;

            if (!$workflow) {
                throw new \Exception('Workflow not found');
            }

            // Find step
            $stepIndex = array_search($stepNumber, array_column($workflow['steps'], 'step_number'));

            if ($stepIndex === false) {
                throw new \Exception('Step not found');
            }

            // Update step status
            $workflow['steps'][$stepIndex]['status'] = 'completed';
            $workflow['steps'][$stepIndex]['completed_at'] = now();
            $workflow['steps'][$stepIndex]['data'] = $data;

            $entity->update(['workflow_data' => $workflow]);

            Log::info('Workflow step completed', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'step' => $stepNumber,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to complete workflow step', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'step' => $stepNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get workflow status
     */
    public function getWorkflowStatus(string $entityType, string $entityId): ?array
    {
        try {
            $entity = $this->getEntity($entityType, $entityId);
            $workflow = $entity->workflow_data ?? null;

            if (!$workflow) {
                return null;
            }

            // Calculate progress
            $totalSteps = count($workflow['steps']);
            $completedSteps = count(array_filter($workflow['steps'], fn($step) => $step['status'] === 'completed'));
            $progress = ($totalSteps > 0) ? round(($completedSteps / $totalSteps) * 100) : 0;

            return [
                'workflow' => $workflow,
                'progress' => $progress,
                'total_steps' => $totalSteps,
                'completed_steps' => $completedSteps,
                'current_step' => $workflow['current_step'],
                'status' => $workflow['status'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get workflow status', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Assign workflow step to user
     */
    public function assignStep(string $entityType, string $entityId, int $stepNumber, string $userId): bool
    {
        try {
            $entity = $this->getEntity($entityType, $entityId);
            $workflow = $entity->workflow_data ?? null;

            if (!$workflow) {
                throw new \Exception('Workflow not found');
            }

            $stepIndex = array_search($stepNumber, array_column($workflow['steps'], 'step_number'));

            if ($stepIndex === false) {
                throw new \Exception('Step not found');
            }

            $workflow['steps'][$stepIndex]['assigned_to'] = $userId;
            $workflow['steps'][$stepIndex]['assigned_at'] = now();

            $entity->update(['workflow_data' => $workflow]);

            Log::info('Workflow step assigned', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'step' => $stepNumber,
                'user_id' => $userId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to assign workflow step', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Add comment to workflow step
     */
    public function addComment(string $entityType, string $entityId, int $stepNumber, string $userId, string $comment): bool
    {
        try {
            $entity = $this->getEntity($entityType, $entityId);
            $workflow = $entity->workflow_data ?? null;

            if (!$workflow) {
                throw new \Exception('Workflow not found');
            }

            $stepIndex = array_search($stepNumber, array_column($workflow['steps'], 'step_number'));

            if ($stepIndex === false) {
                throw new \Exception('Step not found');
            }

            if (!isset($workflow['steps'][$stepIndex]['comments'])) {
                $workflow['steps'][$stepIndex]['comments'] = [];
            }

            $workflow['steps'][$stepIndex]['comments'][] = [
                'user_id' => $userId,
                'comment' => $comment,
                'created_at' => now(),
            ];

            $entity->update(['workflow_data' => $workflow]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add workflow comment', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get entity by type and ID
     */
    protected function getEntity(string $entityType, string $entityId): mixed
    {
        switch ($entityType) {
            case 'campaign':
                return Campaign::findOrFail($entityId);
            // Add more entity types as needed
            default:
                throw new \Exception('Unknown entity type: ' . $entityType);
        }
    }
}
