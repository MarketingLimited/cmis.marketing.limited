<?php

namespace App\Http\Controllers\Automation;

use App\Http\Controllers\Controller;
use App\Services\Automation\CampaignLifecycleManager;
use App\Services\Automation\AutomatedBudgetAllocator;
use App\Services\CampaignOrchestratorService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

/**
 * Campaign Orchestration Controller (Phase 4 - Advanced Automation)
 *
 * Unified API for campaign lifecycle management and automated optimization
 */
class CampaignOrchestrationController extends Controller
{
    use ApiResponse;

    protected CampaignLifecycleManager $lifecycleManager;
    protected AutomatedBudgetAllocator $budgetAllocator;
    protected CampaignOrchestratorService $orchestrator;

    public function __construct(
        CampaignLifecycleManager $lifecycleManager,
        AutomatedBudgetAllocator $budgetAllocator,
        CampaignOrchestratorService $orchestrator
    ) {
        $this->middleware('auth:sanctum');
        $this->lifecycleManager = $lifecycleManager;
        $this->budgetAllocator = $budgetAllocator;
        $this->orchestrator = $orchestrator;
    }

    /**
     * Process lifecycle events for organization
     *
     * POST /api/orgs/{org_id}/orchestration/process-lifecycle
     */
    public function processLifecycle(string $orgId): JsonResponse
    {
        try {
            $results = $this->lifecycleManager->processLifecycleEvents($orgId);

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lifecycle statistics
     *
     * GET /api/orgs/{org_id}/orchestration/lifecycle-stats
     */
    public function getLifecycleStats(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $days = $request->input('days', 30);

        try {
            $stats = $this->lifecycleManager->getLifecycleStatistics($orgId, $days);

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reallocate budget across campaigns
     *
     * POST /api/orgs/{org_id}/orchestration/reallocate-budget
     */
    public function reallocateBudget(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'total_budget' => 'required|numeric|min:10',
            'strategy' => 'nullable|string|in:roi_maximization,equal_distribution,performance_weighted,predictive',
            'constraints' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->budgetAllocator->reallocateBudget(
                $orgId,
                $request->input('total_budget'),
                $request->input('strategy', 'performance_weighted'),
                $request->input('constraints', [])
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simulate budget allocation
     *
     * POST /api/orgs/{org_id}/orchestration/simulate-budget
     */
    public function simulateBudget(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'total_budget' => 'required|numeric|min:10',
            'strategy' => 'nullable|string|in:roi_maximization,equal_distribution,performance_weighted,predictive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->budgetAllocator->simulateAllocation(
                $orgId,
                $request->input('total_budget'),
                $request->input('strategy', 'performance_weighted')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget allocation history
     *
     * GET /api/orgs/{org_id}/orchestration/budget-history
     */
    public function getBudgetHistory(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $history = $this->budgetAllocator->getAllocationHistory(
                $orgId,
                $request->input('limit', 50)
            );

            return response()->json([
                'success' => true,
                'history' => $history,
                'count' => count($history)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create multi-platform campaign
     *
     * POST /api/orgs/{org_id}/orchestration/create-campaign
     */
    public function createMultiPlatformCampaign(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:meta,google,tiktok,linkedin,twitter,snapchat',
            'objective' => 'required|string',
            'budget' => 'required|numeric|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->orchestrator->createMultiPlatformCampaign(
                $orgId,
                $request->input('platforms'),
                $request->all()
            );

            return response()->json($result, $result['success'] ?? false ? 201 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pause campaign across all platforms
     *
     * POST /api/orgs/{org_id}/orchestration/campaigns/{campaign_id}/pause
     */
    public function pauseCampaign(string $orgId, string $campaignId): JsonResponse
    {
        try {
            $result = $this->orchestrator->pauseCampaign($campaignId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resume campaign across all platforms
     *
     * POST /api/orgs/{org_id}/orchestration/campaigns/{campaign_id}/resume
     */
    public function resumeCampaign(string $orgId, string $campaignId): JsonResponse
    {
        try {
            $result = $this->orchestrator->resumeCampaign($campaignId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync campaign status from all platforms
     *
     * POST /api/orgs/{org_id}/orchestration/campaigns/{campaign_id}/sync
     */
    public function syncCampaign(string $orgId, string $campaignId): JsonResponse
    {
        try {
            $result = $this->orchestrator->syncCampaignStatus($campaignId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate campaign
     *
     * POST /api/orgs/{org_id}/orchestration/campaigns/{campaign_id}/duplicate
     */
    public function duplicateCampaign(string $orgId, string $campaignId): JsonResponse
    {
        try {
            $result = $this->orchestrator->duplicateCampaign($campaignId);

            return response()->json($result, $result['success'] ?? false ? 201 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
