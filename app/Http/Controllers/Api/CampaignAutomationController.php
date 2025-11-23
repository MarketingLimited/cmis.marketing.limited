<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Automation\CampaignOptimizationService;
use App\Services\Automation\AutomationRulesEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class CampaignAutomationController extends Controller
{
    use ApiResponse;

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

            return $this->success([
                'rules' => $rules,
                'count' => count($rules)
            ], 'Automation rules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch automation rules: ' . $e->getMessage());
        }
    }

    /**
     * Get rule templates
     */
    public function getRuleTemplates(Request $request): JsonResponse
    {
        try {
            $templates = $this->rulesEngine->getRuleTemplates();

            return $this->success([
                'templates' => $templates,
                'count' => count($templates)
            ], 'Rule templates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch rule templates: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;

            $result = $this->optimizationService->createRule($orgId, $request->all());

            if (!$result['success']) {
                return $this->error($result['message'] ?? 'Failed to create automation rule', 400);
            }

            return $this->created($result, 'Automation rule created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create automation rule: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;

            $result = $this->optimizationService->updateRule($ruleId, $orgId, $request->all());

            if (!$result['success']) {
                return $this->error($result['message'] ?? 'Failed to update automation rule', 400);
            }

            return $this->success($result, 'Automation rule updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update automation rule: ' . $e->getMessage());
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
                return $this->error($result['message'] ?? 'Failed to delete automation rule', 400);
            }

            return $this->deleted('Automation rule deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete automation rule: ' . $e->getMessage());
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

            return $this->success(['results' => $results], 'Organization optimization completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to run organization optimization: ' . $e->getMessage());
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

            return $this->success(['result' => $result], 'Campaign optimization completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to optimize campaign: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;

            $history = $this->optimizationService->getRuleExecutionHistory(
                $orgId,
                $request->input('campaign_id')
            );

            return $this->success([
                'history' => $history,
                'count' => count($history)
            ], 'Execution history retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch execution history: ' . $e->getMessage());
        }
    }
}
