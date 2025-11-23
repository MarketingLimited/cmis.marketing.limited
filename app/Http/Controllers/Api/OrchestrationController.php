<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Orchestration\CampaignOrchestrationService;
use App\Services\Orchestration\WorkflowEngine;
use App\Models\Orchestration\CampaignOrchestration;
use App\Models\Orchestration\CampaignTemplate;
use App\Models\Orchestration\OrchestrationPlatform;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class OrchestrationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CampaignOrchestrationService $orchestrationService,
        protected WorkflowEngine $workflowEngine
    ) {}

    // ===== Templates =====

    /**
     * Get campaign templates.
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $templates = CampaignTemplate::where(function($q) use ($orgId) {
                $q->where('org_id', $orgId)
                  ->orWhere('is_global', true);
            })
            ->active()
            ->get();

        return $this->success(['templates' => $templates,], 'Operation completed successfully');
    }

    /**
     * Create campaign template.
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|in:awareness,consideration,conversion,retention',
            'objective' => 'required|string',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|in:meta,google,tiktok,linkedin,twitter,snapchat',
            'base_config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $template = CampaignTemplate::create(array_merge($request->all(), [
            'org_id' => $request->user()->org_id,
            'created_by' => $request->user()->user_id,
        ]));

        return response()->json([
            'success' => true,
            'template' => $template,
        ], 201);
    }

    // ===== Orchestrations =====

    /**
     * Create orchestration from template.
     */
    public function createFromTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|uuid|exists:cmis.campaign_templates,template_id',
            'name' => 'required|string|max:255',
            'total_budget' => 'nullable|numeric|min:0',
            'platforms' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $orchestration = $this->orchestrationService->createFromTemplate(
                $request->user()->org_id,
                $request->user()->user_id,
                $request->template_id,
                $request->only(['name', 'description', 'total_budget', 'platforms', 'config'])
            );

            return response()->json([
                'success' => true,
                'orchestration' => $orchestration->load(['platformMappings', 'template']),
            ], 201);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(),
            );
        }
    }

    /**
     * Get all orchestrations.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $query = CampaignOrchestration::where('org_id', $orgId)
            ->with(['platformMappings', 'template']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orchestrations = $query->orderByDesc('created_at')->get();

        return $this->success(['orchestrations' => $orchestrations,], 'Operation completed successfully');
    }

    /**
     * Get orchestration details.
     */
    public function show(Request $request, string $orchestrationId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $orchestration = CampaignOrchestration::where('org_id', $orgId)
            ->where('orchestration_id', $orchestrationId)
            ->with(['platformMappings.connection', 'workflows', 'syncLogs'])
            ->firstOrFail();

        $performance = $this->orchestrationService->getAggregatedPerformance($orchestration);

        return $this->success(['orchestration' => $orchestration,
            'performance' => $performance,], 'Operation completed successfully');
    }

    /**
     * Deploy orchestration to all platforms.
     */
    public function deploy(Request $request, string $orchestrationId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $orchestration = CampaignOrchestration::where('org_id', $orgId)
            ->where('orchestration_id', $orchestrationId)
            ->firstOrFail();

        if ($orchestration->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orchestrations can be deployed',
            ], 422);
        }

        try {
            $workflow = $this->orchestrationService->deploy($orchestration);

            return $this->success(['message' => 'Deployment started',
                'workflow' => $workflow,], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(),
            );
        }
    }

    /**
     * Sync orchestration with platforms.
     */
    public function sync(Request $request, string $orchestrationId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $orchestration = CampaignOrchestration::where('org_id', $orgId)
            ->where('orchestration_id', $orchestrationId)
            ->firstOrFail();

        try {
            $results = $this->orchestrationService->sync(
                $orchestration,
                $request->input('sync_type', 'full')
            );

            return $this->success(['message' => 'Sync completed',
                'results' => $results,], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(),
            );
        }
    }

    /**
     * Pause orchestration.
     */
    public function pause(Request $request, string $orchestrationId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $orchestration = CampaignOrchestration::where('org_id', $orgId)
            ->where('orchestration_id', $orchestrationId)
            ->firstOrFail();

        try {
            $this->orchestrationService->pause($orchestration);

            return $this->success(['message' => 'Orchestration paused on all platforms',], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(),
            );
        }
    }

    /**
     * Resume orchestration.
     */
    public function resume(Request $request, string $orchestrationId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $orchestration = CampaignOrchestration::where('org_id', $orgId)
            ->where('orchestration_id', $orchestrationId)
            ->firstOrFail();

        try {
            $this->orchestrationService->resume($orchestration);

            return $this->success(['message' => 'Orchestration resumed on all platforms',], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(),
            );
        }
    }

    /**
     * Update budget allocation.
     */
    public function updateBudget(Request $request, string $orchestrationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'total_budget' => 'required|numeric|min:0',
            'budget_allocation' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = $request->user()->org_id;

        $orchestration = CampaignOrchestration::where('org_id', $orgId)
            ->where('orchestration_id', $orchestrationId)
            ->firstOrFail();

        $orchestration->update([
            'total_budget' => $request->total_budget,
            'budget_allocation' => $request->budget_allocation,
        ]);

        // Update platform mappings with new budgets
        foreach ($request->budget_allocation as $platform => $budget) {
            $orchestration->platformMappings()
                ->where('platform', $platform)
                ->update(['allocated_budget' => $budget]);
        }

        return $this->success(['message' => 'Budget updated successfully',
            'orchestration' => $orchestration,], 'Operation completed successfully');
    }

    /**
     * Get performance across all platforms.
     */
    public function getPerformance(Request $request, string $orchestrationId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $orchestration = CampaignOrchestration::where('org_id', $orgId)
            ->where('orchestration_id', $orchestrationId)
            ->with('platformMappings')
            ->firstOrFail();

        $performance = $this->orchestrationService->getAggregatedPerformance($orchestration);

        return $this->success(['performance' => $performance,], 'Operation completed successfully');
    }
}
