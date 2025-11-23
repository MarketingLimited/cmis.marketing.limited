<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listening\MonitoringKeyword;
use App\Models\Listening\SocialMention;
use App\Models\Listening\CompetitorProfile;
use App\Models\Listening\TrendingTopic;
use App\Models\Listening\MonitoringAlert;
use App\Models\Listening\SocialConversation;
use App\Models\Listening\ResponseTemplate;
use App\Services\Listening\SocialListeningService;
use App\Services\Listening\SentimentAnalysisService;
use App\Services\Listening\CompetitorMonitoringService;
use App\Services\Listening\TrendDetectionService;
use App\Services\Listening\AlertService;
use App\Services\Listening\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\ApiResponse;

class SocialListeningController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SocialListeningService $listeningService,
        protected SentimentAnalysisService $sentimentService,
        protected CompetitorMonitoringService $competitorService,
        protected TrendDetectionService $trendService,
        protected AlertService $alertService,
        protected ConversationService $conversationService
    ) {}

    // ========================================
    // MONITORING KEYWORDS
    // ========================================

    /**
     * Get all monitoring keywords
     */
    public function keywords(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $keywords = MonitoringKeyword::where('org_id', $orgId);

        if ($request->has('status')) {
            $keywords->where('status', $request->status);
        }

        if ($request->has('type')) {
            $keywords->where('keyword_type', $request->type);
        }

        $keywords = $keywords->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'keywords' => $keywords,
        ]);
    }

    /**
     * Create monitoring keyword
     */
    public function createKeyword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'keyword_type' => 'required|in:brand,hashtag,keyword,phrase,mention',
            'variations' => 'array',
            'case_sensitive' => 'boolean',
            'platforms' => 'array',
            'enable_alerts' => 'boolean',
            'alert_threshold' => 'in:low,medium,high',
            'alert_conditions' => 'array',
        ]);

        $keyword = $this->listeningService->createKeyword(
            $request->user()->org_id,
            $request->user()->id,
            $validated
        );

        return response()->json([
            'success' => true,
            'keyword' => $keyword,
        ], 201);
    }

    /**
     * Update monitoring keyword
     */
    public function updateKeyword(Request $request, string $keywordId): JsonResponse
    {
        $keyword = MonitoringKeyword::findOrFail($keywordId);

        $validated = $request->validate([
            'keyword' => 'string|max:255',
            'variations' => 'array',
            'platforms' => 'array',
            'enable_alerts' => 'boolean',
            'status' => 'in:active,paused,archived',
        ]);

        $keyword = $this->listeningService->updateKeyword($keyword, $validated);

        return response()->json([
            'success' => true,
            'keyword' => $keyword,
        ]);
    }

    /**
     * Delete monitoring keyword
     */
    public function deleteKeyword(string $keywordId): JsonResponse
    {
        $keyword = MonitoringKeyword::findOrFail($keywordId);
        $keyword->delete();

        return response()->json([
            'success' => true,
            'message' => 'Keyword deleted successfully',
        ]);
    }

    // ========================================
    // SOCIAL MENTIONS
    // ========================================

    /**
     * Get all mentions
     */
    public function mentions(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $mentions = SocialMention::where('org_id', $orgId);

        if ($request->has('keyword_id')) {
            $mentions->where('keyword_id', $request->keyword_id);
        }

        if ($request->has('platform')) {
            $mentions->where('platform', $request->platform);
        }

        if ($request->has('sentiment')) {
            $mentions->where('sentiment', $request->sentiment);
        }

        if ($request->has('status')) {
            $mentions->where('status', $request->status);
        }

        $mentions = $mentions->with(['keyword', 'sentimentAnalysis'])
            ->recentFirst()
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'mentions' => $mentions,
        ]);
    }

    /**
     * Get single mention details
     */
    public function mentionDetails(string $mentionId): JsonResponse
    {
        $mention = SocialMention::with(['keyword', 'sentimentAnalysis', 'conversations'])
            ->findOrFail($mentionId);

        return response()->json([
            'success' => true,
            'mention' => $mention,
        ]);
    }

    /**
     * Search mentions
     */
    public function searchMentions(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $criteria = $request->only([
            'keyword',
            'content',
            'author',
            'platform',
            'sentiment',
            'start_date',
            'end_date',
            'min_engagement',
            'influencers_only',
        ]);

        $mentions = $this->listeningService->searchMentions($orgId, $criteria);

        return response()->json([
            'success' => true,
            'mentions' => $mentions,
            'count' => $mentions->count(),
        ]);
    }

    /**
     * Update mention status
     */
    public function updateMention(Request $request, string $mentionId): JsonResponse
    {
        $mention = SocialMention::findOrFail($mentionId);

        $validated = $request->validate([
            'status' => 'in:new,reviewed,responded,archived,flagged',
            'requires_response' => 'boolean',
            'assigned_to' => 'uuid|nullable',
            'internal_notes' => 'string|nullable',
        ]);

        $mention->update($validated);

        return response()->json([
            'success' => true,
            'mention' => $mention->fresh(),
        ]);
    }

    // ========================================
    // STATISTICS & ANALYTICS
    // ========================================

    /**
     * Get listening statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $keywordId = $request->keyword_id ?? null;
        $days = $request->days ?? 30;

        $stats = $this->listeningService->getStatistics($orgId, $keywordId, $days);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Get sentiment timeline
     */
    public function sentimentTimeline(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $keywordId = $request->keyword_id ?? null;
        $days = $request->days ?? 30;

        $timeline = $this->listeningService->getSentimentTimeline($orgId, $keywordId, $days);

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get top authors
     */
    public function topAuthors(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $keywordId = $request->keyword_id ?? null;
        $limit = $request->limit ?? 10;

        $authors = $this->listeningService->getTopAuthors($orgId, $keywordId, $limit);

        return response()->json([
            'success' => true,
            'authors' => $authors,
        ]);
    }

    // ========================================
    // TRENDING TOPICS
    // ========================================

    /**
     * Get trending topics
     */
    public function trends(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $filters = $request->only([
            'status',
            'velocity',
            'min_relevance',
            'topic_type',
            'opportunities_only',
        ]);

        $trends = $this->trendService->getTrendingTopics($orgId, $filters);

        return response()->json([
            'success' => true,
            'trends' => $trends,
        ]);
    }

    /**
     * Get trend details
     */
    public function trendDetails(string $trendId): JsonResponse
    {
        $trend = TrendingTopic::findOrFail($trendId);

        $opportunity = $this->trendService->analyzeTrendOpportunity($trend);
        $timeline = $this->trendService->getTrendTimeline($trend, 7);

        return response()->json([
            'success' => true,
            'trend' => $trend,
            'opportunity_analysis' => $opportunity,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Detect emerging trends
     */
    public function detectTrends(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $hours = $request->hours ?? 24;

        $trends = $this->trendService->detectEmergingTrends($orgId, $hours);

        return response()->json([
            'success' => true,
            'trends' => $trends,
            'count' => $trends->count(),
        ]);
    }

    // ========================================
    // COMPETITOR MONITORING
    // ========================================

    /**
     * Get competitors
     */
    public function competitors(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $competitors = CompetitorProfile::where('org_id', $orgId);

        if ($request->has('status')) {
            $competitors->where('status', $request->status);
        }

        $competitors = $competitors->orderBy('competitor_name')->get();

        return response()->json([
            'success' => true,
            'competitors' => $competitors,
        ]);
    }

    /**
     * Create competitor
     */
    public function createCompetitor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'competitor_name' => 'required|string|max:255',
            'industry' => 'string|max:100',
            'description' => 'string',
            'website' => 'url|nullable',
            'social_accounts' => 'array',
        ]);

        $competitor = $this->competitorService->createCompetitor(
            $request->user()->org_id,
            $request->user()->id,
            $validated
        );

        return response()->json([
            'success' => true,
            'competitor' => $competitor,
        ], 201);
    }

    /**
     * Analyze competitor
     */
    public function analyzeCompetitor(string $competitorId): JsonResponse
    {
        $competitor = CompetitorProfile::findOrFail($competitorId);

        $results = $this->competitorService->analyzeCompetitor($competitor);
        $insights = $this->competitorService->getInsights($competitor);

        return response()->json([
            'success' => true,
            'analysis' => $results,
            'insights' => $insights,
        ]);
    }

    /**
     * Compare competitors
     */
    public function compareCompetitors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'competitor_ids' => 'required|array|min:2',
            'competitor_ids.*' => 'uuid',
        ]);

        $comparison = $this->competitorService->compareCompetitors(
            $request->user()->org_id,
            $validated['competitor_ids']
        );

        return response()->json([
            'success' => true,
            'comparison' => $comparison,
        ]);
    }

    // ========================================
    // ALERTS
    // ========================================

    /**
     * Get alerts
     */
    public function alerts(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $alerts = MonitoringAlert::where('org_id', $orgId);

        if ($request->has('status')) {
            $alerts->where('status', $request->status);
        }

        if ($request->has('type')) {
            $alerts->where('alert_type', $request->type);
        }

        $alerts = $alerts->orderBy('severity', 'desc')->get();

        return response()->json([
            'success' => true,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Create alert
     */
    public function createAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_name' => 'required|string|max:255',
            'alert_type' => 'required|in:mention,sentiment,volume,competitor,trend',
            'description' => 'string|nullable',
            'trigger_conditions' => 'required|array',
            'severity' => 'in:low,medium,high,critical',
            'threshold_value' => 'integer|nullable',
            'threshold_unit' => 'string|nullable',
            'notification_channels' => 'array',
            'recipients' => 'array',
        ]);

        $alert = $this->alertService->createAlert(
            $request->user()->org_id,
            $request->user()->id,
            $validated
        );

        return response()->json([
            'success' => true,
            'alert' => $alert,
        ], 201);
    }

    // ========================================
    // CONVERSATIONS
    // ========================================

    /**
     * Get conversation inbox
     */
    public function conversations(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $filters = $request->only([
            'status',
            'assigned_to',
            'unassigned',
            'priority',
            'platform',
            'escalated',
        ]);

        $conversations = $this->conversationService->getInbox($orgId, $filters);

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Get conversation details
     */
    public function conversationDetails(string $conversationId): JsonResponse
    {
        $conversation = SocialConversation::with(['rootMention'])
            ->findOrFail($conversationId);

        $suggestedTemplates = $this->conversationService->suggestTemplates($conversation);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
            'suggested_templates' => $suggestedTemplates,
        ]);
    }

    /**
     * Respond to conversation
     */
    public function respondToConversation(Request $request, string $conversationId): JsonResponse
    {
        $conversation = SocialConversation::findOrFail($conversationId);

        $validated = $request->validate([
            'response_content' => 'required|string',
            'template_id' => 'uuid|nullable',
        ]);

        $result = $this->conversationService->respond(
            $conversation,
            $validated['response_content'],
            $validated['template_id'] ?? null
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Response sent successfully' : 'Failed to send response',
            'error' => $result['error'] ?? null,
        ]);
    }

    /**
     * Assign conversation
     */
    public function assignConversation(Request $request, string $conversationId): JsonResponse
    {
        $conversation = SocialConversation::findOrFail($conversationId);

        $validated = $request->validate([
            'assigned_to' => 'required|uuid',
        ]);

        $this->conversationService->assignConversation($conversation, $validated['assigned_to']);

        return response()->json([
            'success' => true,
            'conversation' => $conversation->fresh(),
        ]);
    }

    /**
     * Get conversation statistics
     */
    public function conversationStats(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $days = $request->days ?? 30;

        $stats = $this->conversationService->getStatistics($orgId, $days);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    // ========================================
    // RESPONSE TEMPLATES
    // ========================================

    /**
     * Get response templates
     */
    public function templates(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $templates = ResponseTemplate::where('org_id', $orgId);

        if ($request->has('category')) {
            $templates->where('category', $request->category);
        }

        if ($request->has('platform')) {
            $templates->forPlatform($request->platform);
        }

        $templates = $templates->active()->orderBy('usage_count', 'desc')->get();

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Create response template
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'category' => 'string|max:100',
            'template_content' => 'required|string',
            'description' => 'string|nullable',
            'variables' => 'array',
            'suggested_triggers' => 'array',
            'platforms' => 'array',
        ]);

        $template = ResponseTemplate::create([
            'org_id' => $request->user()->org_id,
            'created_by' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'template' => $template,
        ], 201);
    }
}
