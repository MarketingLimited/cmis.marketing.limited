<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
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
    use ApiResponse;

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

            return $result['success']
                ? $this->success($result, 'Optimal posting times retrieved successfully')
                : $this->serverError('Failed to get optimal times');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get optimal times: ' . $e->getMessage());
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

            return $result['success']
                ? $this->success($result, 'Post auto-scheduled successfully')
                : $this->serverError('Failed to auto-schedule post');

        } catch (\Exception $e) {
            return $this->serverError('Failed to auto-schedule: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Invalid input data');
        }

        try {
            $result = $this->aiService->generateHashtagRecommendations(
                $request->input('content'),
                $request->input('platform'),
                $request->only('account_id')
            );

            return $result['success']
                ? $this->success($result, 'Hashtags generated successfully')
                : $this->serverError('Failed to generate hashtags');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate hashtags: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Invalid input data');
        }

        try {
            $result = $this->aiService->generateCaptionSuggestions($request->all());

            return $result['success']
                ? $this->success($result, 'Captions generated successfully')
                : $this->serverError('Failed to generate captions');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate captions: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Invalid budget data');
        }

        try {
            $result = $this->aiService->optimizeCampaignBudget($adAccountId, $request->all());

            return $result['success']
                ? $this->success($result, 'Budget optimized successfully')
                : $this->serverError('Failed to optimize budget');

        } catch (\Exception $e) {
            return $this->serverError('Failed to optimize budget: ' . $e->getMessage());
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

            return $result['success']
                ? $this->success($result, 'Automation rules retrieved successfully')
                : $this->serverError('Failed to get automation rules');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get rules: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Invalid rule data');
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return $this->unauthorized('Authentication required');
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->aiService->createAutomationRule($orgId, $data);

            return $result['success']
                ? $this->created($result, 'Automation rule created successfully')
                : $this->serverError('Failed to create automation rule');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create rule: ' . $e->getMessage());
        }
    }
}
