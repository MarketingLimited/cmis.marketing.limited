<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Models\Strategic\Campaign;
use App\Models\Creative\ContentPlan;
use App\Services\CampaignService;
use App\Services\ContentPlanService;
use App\Services\KnowledgeService;
use App\Services\AnalyticsService;
use App\Services\AIService;
use App\Services\GPTConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * GPT Controller
 *
 * Provides GPT-optimized endpoints for ChatGPT integration.
 * All responses follow a consistent structure for easy parsing.
 */
class GPTController extends Controller
{
    public function __construct(
        private CampaignService $campaignService,
        private ContentPlanService $contentPlanService,
        private KnowledgeService $knowledgeService,
        private AnalyticsService $analyticsService,
        private AIService $aiService,
        private GPTConversationService $conversationService
    ) {}

    /**
     * Get user and organization context
     */
    public function getContext(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return $this->success([
                'user' => [
                    'id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'organization' => [
                    'id' => $user->current_org_id,
                    'name' => $user->currentOrg?->name,
                    'currency' => $user->currentOrg?->currency ?? 'USD',
                    'locale' => $user->currentOrg?->default_locale ?? 'en',
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('GPT context error: ' . $e->getMessage());
            return $this->error('Failed to retrieve context', null, 500);
        }
    }

    /**
     * List campaigns
     */
    public function listCampaigns(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $limit = min($request->query('limit', 20), 100);

        $query = Campaign::where('org_id', $request->user()->current_org_id)
            ->with(['contentPlans'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $campaigns = $query->limit($limit)->get();

        return $this->success($campaigns->map(fn($c) => $this->formatCampaign($c)));
    }

    /**
     * Get single campaign
     */
    public function getCampaign(Request $request, string $campaignId): JsonResponse
    {
        $campaign = Campaign::with(['contentPlans', 'adAccounts'])
            ->findOrFail($campaignId);

        $this->authorize('view', $campaign);

        return $this->success($this->formatCampaign($campaign, true));
    }

    /**
     * Create campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|array',
            'objectives' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $data['org_id'] = $request->user()->current_org_id;
        $data['status'] = 'draft';
        $data['created_by'] = $request->user()->user_id;

        $campaign = $this->campaignService->create($data);

        return $this->success($this->formatCampaign($campaign), 'Campaign created successfully', 201);
    }

    /**
     * Update campaign
     */
    public function updateCampaign(Request $request, string $campaignId): JsonResponse
    {
        $campaign = Campaign::findOrFail($campaignId);
        $this->authorize('update', $campaign);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:draft,active,paused,completed,archived',
            'budget' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $campaign = $this->campaignService->update($campaign, $validator->validated());

        return $this->success($this->formatCampaign($campaign), 'Campaign updated successfully');
    }

    /**
     * Get campaign analytics
     */
    public function getCampaignAnalytics(Request $request, string $campaignId): JsonResponse
    {
        $campaign = Campaign::findOrFail($campaignId);
        $this->authorize('view', $campaign);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $metrics = $this->analyticsService->getMetrics($campaignId, $startDate, $endDate);

        if (empty($metrics)) {
            return $this->error('Analytics not available');
        }

        return $this->success([
            'impressions' => $metrics['impressions'] ?? 0,
            'clicks' => $metrics['clicks'] ?? 0,
            'conversions' => $metrics['conversions'] ?? 0,
            'spend' => $metrics['spend'] ?? 0,
            'ctr' => $metrics['ctr'] ?? 0,
            'cpc' => $metrics['cpc'] ?? 0,
            'cpa' => $metrics['cpa'] ?? 0,
            'conversion_rate' => $metrics['conversion_rate'] ?? 0,
            'roas' => $metrics['roas'] ?? 0,
        ]);
    }

    /**
     * List content plans
     */
    public function listContentPlans(Request $request): JsonResponse
    {
        $campaignId = $request->query('campaign_id');
        $status = $request->query('status');

        $query = ContentPlan::where('org_id', $request->user()->current_org_id)
            ->with(['campaign'])
            ->latest();

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $plans = $query->limit(50)->get();

        return $this->success($plans->map(fn($p) => $this->formatContentPlan($p)));
    }

    /**
     * Create content plan
     */
    public function createContentPlan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|uuid|exists:cmis.campaigns,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:social_post,blog_article,ad_copy,email,video_script',
            'target_platforms' => 'required|array',
            'tone' => 'nullable|string',
            'key_messages' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $plan = $this->contentPlanService->create($validator->validated());

        return $this->success($this->formatContentPlan($plan), 'Content plan created successfully', 201);
    }

    /**
     * Generate content for a content plan
     */
    public function generateContent(Request $request, string $contentPlanId): JsonResponse
    {
        $contentPlan = ContentPlan::findOrFail($contentPlanId);
        $this->authorize('update', $contentPlan);

        $validator = Validator::make($request->all(), [
            'prompt' => 'nullable|string',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $result = $this->contentPlanService->generateContent(
            $contentPlan,
            $request->input('prompt'),
            $request->input('options', [])
        );

        return $this->success($result, 'Content generation started');
    }

    /**
     * Search knowledge base
     */
    public function searchKnowledge(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:50',
            'content_type' => 'nullable|in:brand_guideline,market_research,competitor_analysis,campaign_brief,product_info',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
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
    public function addKnowledge(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:brand_guideline,market_research,competitor_analysis,campaign_brief,product_info',
            'content' => 'required|string',
            'summary' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $knowledge = $this->knowledgeService->create(
            $validator->validated(),
            $request->user()->current_org_id,
            $request->user()->user_id
        );

        return $this->success($this->formatKnowledge($knowledge), 'Knowledge item created successfully', 201);
    }

    /**
     * Get AI insights
     */
    public function getAIInsights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'context_type' => 'required|in:campaign,content_plan,ad_account',
            'context_id' => 'required|uuid',
            'question' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
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
     * Create or get conversation session
     */
    public function conversationSession(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->query('session_id');
            $session = $this->conversationService->getOrCreateSession(
                $sessionId,
                $request->user()->user_id,
                $request->user()->current_org_id
            );

            return $this->success($session, 'Conversation session ready');
        } catch (\Exception $e) {
            \Log::error('GPT conversation session error: ' . $e->getMessage());
            return $this->error('Failed to create/retrieve session', null, 500);
        }
    }

    /**
     * Send message in conversation
     */
    public function conversationMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|uuid',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $userMessage = $request->input('message');

            // Add user message
            $this->conversationService->addMessage(
                $sessionId,
                'user',
                $userMessage
            );

            // Get conversation context for AI
            $context = $this->conversationService->buildGPTContext($sessionId);

            // Build enhanced prompt with conversation history and context
            $prompt = $this->buildConversationalPrompt($userMessage, $context);

            // Generate AI response
            $aiResult = $this->aiService->generate($prompt, 'chat_response', [
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            $aiResponse = $aiResult['content'] ?? "I'm here to help with your marketing campaigns. What would you like to know?";

            // Add assistant message
            $this->conversationService->addMessage(
                $sessionId,
                'assistant',
                $aiResponse,
                [
                    'tokens_used' => $aiResult['tokens']['total'] ?? 0,
                    'model' => $aiResult['model'] ?? 'gpt-4',
                ]
            );

            return $this->success([
                'response' => $aiResponse,
                'session_id' => $sessionId,
                'tokens_used' => $aiResult['tokens']['total'] ?? 0,
            ], 'Message processed successfully');

        } catch (\Exception $e) {
            \Log::error('GPT conversation message error: ' . $e->getMessage(), [
                'session_id' => $request->input('session_id'),
                'user_id' => $request->user()->user_id,
            ]);

            // Return fallback response
            $fallbackResponse = "I apologize, but I'm having trouble processing your request right now. Please try again in a moment.";

            try {
                $this->conversationService->addMessage(
                    $request->input('session_id'),
                    'assistant',
                    $fallbackResponse,
                    ['error' => true]
                );
            } catch (\Exception $innerException) {
                \Log::error('Failed to save fallback message: ' . $innerException->getMessage());
            }

            return $this->error('Failed to process message', ['detail' => $e->getMessage()], 500);
        }
    }

    /**
     * Build conversational prompt with context
     */
    private function buildConversationalPrompt(string $userMessage, array $context): string
    {
        $prompt = "You are an AI assistant for CMIS (Cognitive Marketing Intelligence System), helping users manage their marketing campaigns.\n\n";

        // Add conversation history
        if (!empty($context['conversation_history'])) {
            $prompt .= "Previous conversation:\n";
            $recentMessages = array_slice($context['conversation_history'], -5); // Last 5 messages
            foreach ($recentMessages as $msg) {
                $prompt .= "{$msg['role']}: {$msg['content']}\n";
            }
            $prompt .= "\n";
        }

        // Add user context
        if (!empty($context['context'])) {
            $orgId = $context['context']['org_id'] ?? null;
            if ($orgId) {
                $prompt .= "User's Organization ID: {$orgId}\n";
            }
        }

        $prompt .= "\nCurrent user message: {$userMessage}\n\n";
        $prompt .= "Please provide a helpful, concise response focused on marketing campaign management. ";
        $prompt .= "If the user asks about campaigns, content plans, analytics, or knowledge base, provide specific actionable guidance.";

        return $prompt;
    }

    /**
     * Get conversation history
     */
    public function conversationHistory(Request $request, string $sessionId): JsonResponse
    {
        try {
            $limit = $request->query('limit', 20);
            $history = $this->conversationService->getHistory($sessionId, $limit);

            return $this->success([
                'session_id' => $sessionId,
                'messages' => $history,
                'count' => count($history),
            ]);
        } catch (\Exception $e) {
            \Log::error('GPT conversation history error: ' . $e->getMessage());
            return $this->error('Failed to retrieve conversation history', null, 500);
        }
    }

    /**
     * Clear conversation history
     */
    public function conversationClear(Request $request, string $sessionId): JsonResponse
    {
        try {
            $this->conversationService->clearHistory($sessionId);

            return $this->success([
                'session_id' => $sessionId,
                'cleared' => true,
            ], 'Conversation history cleared');
        } catch (\Exception $e) {
            \Log::error('GPT conversation clear error: ' . $e->getMessage());
            return $this->error('Failed to clear conversation history', null, 500);
        }
    }

    /**
     * Get conversation statistics
     */
    public function conversationStats(Request $request, string $sessionId): JsonResponse
    {
        try {
            $stats = $this->conversationService->getSessionStats($sessionId);

            if (!$stats) {
                return $this->error('Session not found', null, 404);
            }

            return $this->success($stats);
        } catch (\Exception $e) {
            \Log::error('GPT conversation stats error: ' . $e->getMessage());
            return $this->error('Failed to retrieve conversation statistics', null, 500);
        }
    }

    /**
     * Format campaign for GPT response
     */
    private function formatCampaign($campaign, bool $detailed = false): array
    {
        $data = [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'description' => $campaign->description,
            'status' => $campaign->status,
            'start_date' => $campaign->start_date?->toDateString(),
            'end_date' => $campaign->end_date?->toDateString(),
            'budget' => $campaign->budget,
            'spent' => $campaign->spent,
            'created_at' => $campaign->created_at?->toISOString(),
        ];

        if ($detailed) {
            $data['content_plans_count'] = $campaign->contentPlans?->count() ?? 0;
            $data['ad_accounts_count'] = $campaign->adAccounts?->count() ?? 0;
        }

        return $data;
    }

    /**
     * Format content plan for GPT response
     */
    private function formatContentPlan($plan): array
    {
        return [
            'id' => $plan->id,
            'campaign_id' => $plan->campaign_id,
            'name' => $plan->name,
            'description' => $plan->description,
            'content_type' => $plan->content_type,
            'target_platforms' => $plan->target_platforms,
            'status' => $plan->status,
            'generated_content' => $plan->generated_content,
            'created_at' => $plan->created_at?->toISOString(),
        ];
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

    /**
     * Success response
     */
    private function success($data, string $message = null, int $status = 200): JsonResponse
    {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        $response['data'] = $data;

        return response()->json($response, $status);
    }

    /**
     * Error response
     */
    private function error(string $message, $errors = null, int $status = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
