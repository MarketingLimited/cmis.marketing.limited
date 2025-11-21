<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Automation\CampaignOptimizationService;
use App\Services\Automation\AutomationRulesEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignAutomationController extends Controller
{
    private CampaignOptimizationService $optimizationService;
    private AutomationRulesEngine $rulesEngine;

    public function __construct(
        CampaignOptimizationService $optimizationService,
        AutomationRulesEngine $rulesEngine
    ) {
        $this->optimizationService = $optimizationService;
        $this->rulesEngine = $rulesEngine;
    }

    /**
     * Get all automation rules
     */
    public function getRules(Request $request): JsonResponse
    {
        try {
            $orgId = auth()->user()->org_id;

            $rules = $this->optimizationService->getRules($orgId);

            return response()->json([
                'success' => true,
                'rules' => $rules,
                'count' => count($rules)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch automation rules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rule templates
     */
    public function getRuleTemplates(Request $request): JsonResponse
    {
        try {
            $templates = $this->rulesEngine->getRuleTemplates();

            return response()->json([
                'success' => true,
                'templates' => $templates,
                'count' => count($templates)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rule templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create automation rule
     */
    public function createRule(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:255',
                'description' => 'nullable|string|max:1000',
                'condition' => 'required|array',
                'condition.metric' => 'required|string|in:cpa,roas,ctr,conversion_rate,spend',
                'condition.operator' => 'required|string|in:>,<,=,>=,<=',
                'condition.value' => 'required|numeric',
                'action' => 'required|array',
                'action.type' => 'required|string|in:pause_underperforming,increase_budget,decrease_budget,adjust_bid,notify',
                'action.value' => 'nullable|numeric',
                'is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;

            $result = $this->optimizationService->createRule($orgId, $request->all());

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create automation rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update automation rule
     */
    public function updateRule(Request $request, string $ruleId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|min:3|max:255',
                'description' => 'nullable|string|max:1000',
                'condition' => 'nullable|array',
                'condition.metric' => 'required_with:condition|string|in:cpa,roas,ctr,conversion_rate,spend',
                'condition.operator' => 'required_with:condition|string|in:>,<,=,>=,<=',
                'condition.value' => 'required_with:condition|numeric',
                'action' => 'nullable|array',
                'action.type' => 'required_with:action|string|in:pause_underperforming,increase_budget,decrease_budget,adjust_bid,notify',
                'action.value' => 'nullable|numeric',
                'is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;

            $result = $this->optimizationService->updateRule($ruleId, $orgId, $request->all());

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update automation rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete automation rule
     */
    public function deleteRule(Request $request, string $ruleId): JsonResponse
    {
        try {
            $orgId = auth()->user()->org_id;

            $result = $this->optimizationService->deleteRule($ruleId, $orgId);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete automation rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run optimization for organization
     */
    public function optimizeOrganization(Request $request): JsonResponse
    {
        try {
            $orgId = auth()->user()->org_id;

            $results = $this->optimizationService->optimizeOrganizationCampaigns($orgId);

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run organization optimization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run optimization for specific campaign
     */
    public function optimizeCampaign(Request $request, string $campaignId): JsonResponse
    {
        try {
            $orgId = auth()->user()->org_id;

            $result = $this->optimizationService->optimizeCampaign($campaignId, $orgId);

            return response()->json([
                'success' => true,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rule execution history
     */
    public function getExecutionHistory(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'campaign_id' => 'nullable|uuid'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;

            $history = $this->optimizationService->getRuleExecutionHistory(
                $orgId,
                $request->input('campaign_id')
            );

            return response()->json([
                'success' => true,
                'history' => $history,
                'count' => count($history)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch execution history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
