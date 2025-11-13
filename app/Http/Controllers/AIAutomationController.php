<?php

namespace App\Http\Controllers;

use App\Services\AIAutomationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AIAutomationController
 *
 * Handles AI-powered automation features
 * Implements Sprint 6.2: AI-Powered Automation
 */
class AIAutomationController extends Controller
{
    protected AIAutomationService $aiService;

    public function __construct(AIAutomationService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get optimal posting times
     * GET /api/orgs/{org_id}/ai/optimal-times/{account_id}
     */
    public function getOptimalPostingTimes(string $orgId, string $accountId): JsonResponse
    {
        try {
            $result = $this->aiService->getOptimalPostingTimes($accountId);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to get optimal times', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Auto-schedule a post
     * POST /api/orgs/{org_id}/ai/auto-schedule/{account_id}
     */
    public function autoSchedulePost(string $orgId, string $accountId, Request $request): JsonResponse
    {
        try {
            $result = $this->aiService->autoSchedulePost($accountId, $request->all());
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to auto-schedule', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate hashtag recommendations
     * POST /api/orgs/{org_id}/ai/hashtags
     */
    public function generateHashtags(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'platform' => 'required|in:instagram,twitter,facebook,linkedin,tiktok',
            'account_id' => 'nullable|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->aiService->generateHashtagRecommendations(
                $request->input('content'),
                $request->input('platform'),
                $request->only('account_id')
            );

            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to generate hashtags', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate caption suggestions
     * POST /api/orgs/{org_id}/ai/captions
     */
    public function generateCaptions(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|max:255',
            'tone' => 'nullable|in:professional,casual,playful,inspirational',
            'platform' => 'nullable|in:instagram,twitter,facebook,linkedin,tiktok',
            'account_id' => 'nullable|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->aiService->generateCaptionSuggestions($request->all());
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to generate captions', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Optimize campaign budget
     * POST /api/orgs/{org_id}/ai/optimize-budget/{ad_account_id}
     */
    public function optimizeBudget(string $orgId, string $adAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'total_budget' => 'required|numeric|min:1',
            'goal' => 'nullable|in:roi,conversions,reach'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->aiService->optimizeCampaignBudget($adAccountId, $request->all());
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to optimize budget', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get automation rules
     * GET /api/orgs/{org_id}/ai/automation-rules
     */
    public function getAutomationRules(string $orgId): JsonResponse
    {
        try {
            $result = $this->aiService->getAutomationRules($orgId);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to get rules', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create automation rule
     * POST /api/orgs/{org_id}/ai/automation-rules
     */
    public function createAutomationRule(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|string|max:255',
            'rule_type' => 'required|in:post_scheduling,budget_adjustment,response_automation',
            'trigger_condition' => 'required|array',
            'action' => 'required|array',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->aiService->createAutomationRule($orgId, $data);
            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create rule', 'error' => $e->getMessage()], 500);
        }
    }
}
