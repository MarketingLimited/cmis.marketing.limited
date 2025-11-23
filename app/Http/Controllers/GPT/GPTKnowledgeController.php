<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\KnowledgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * GPT Knowledge Controller
 *
 * Handles knowledge base operations for GPT/ChatGPT integration
 */
class GPTKnowledgeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private KnowledgeService $knowledgeService
    ) {}

    /**
     * Search knowledge base
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:50',
            'content_type' => 'nullable|in:brand_guideline,market_research,competitor_analysis,campaign_brief,product_info',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $results = $this->knowledgeService->semanticSearch(
            $request->input('query'),
            $request->user()->current_org_id,
            $request->input('limit', 10),
            $request->input('content_type')
        );

        return $this->success($results);
    }

    /**
     * Add knowledge item
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:brand_guideline,market_research,competitor_analysis,campaign_brief,product_info',
            'content' => 'required|string',
            'summary' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $knowledge = $this->knowledgeService->create(
            $validator->validated(),
            $request->user()->current_org_id,
            $request->user()->user_id
        );

        return $this->created($this->formatKnowledge($knowledge), 'Knowledge item created successfully');
    }

    /**
     * Get AI insights
     */
    public function insights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'context_type' => 'required|in:campaign,content_plan,ad_account',
            'context_id' => 'required|uuid',
            'question' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $contextType = $request->input('context_type');
        $contextId = $request->input('context_id');

        // Get insights based on context type
        if ($contextType === 'campaign') {
            $insights = $this->analyticsService->getInsights($contextId, [
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->toDateString(),
            ]);

            return $this->success($insights);
        }

        // For other context types, return generic insights for now
        return $this->success([
            'insights' => [
                'Continue monitoring performance metrics',
                'Consider A/B testing different variations',
            ],
            'recommendations' => [
                'Review performance data regularly',
                'Test different targeting options',
            ],
            'confidence' => 0.65,
        ]);
    }

    /**
     * Format knowledge item for GPT response
     */
    private function formatKnowledge($knowledge): array
    {
        return [
            'id' => $knowledge->id,
            'title' => $knowledge->title,
            'content_type' => $knowledge->content_type,
            'content' => $knowledge->content,
            'summary' => $knowledge->summary,
            'relevance_score' => $knowledge->relevance_score ?? null,
            'created_at' => $knowledge->created_at?->toISOString(),
        ];
    }
}
