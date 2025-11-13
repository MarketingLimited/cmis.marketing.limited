<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AiGeneratedCampaign;
use App\Models\AiRecommendation;
use App\Models\CMIS\KnowledgeItem;
use App\Repositories\Knowledge\KnowledgeRepository;
use App\Repositories\Knowledge\EmbeddingRepository;
use App\Services\CMIS\SemanticSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AIGenerationController extends Controller
{
    protected KnowledgeRepository $knowledgeRepo;
    protected EmbeddingRepository $embeddingRepo;

    public function __construct(
        KnowledgeRepository $knowledgeRepo,
        EmbeddingRepository $embeddingRepo
    ) {
        $this->knowledgeRepo = $knowledgeRepo;
        $this->embeddingRepo = $embeddingRepo;
    }
    /**
     * AI model configurations
     */
    const AI_MODELS = [
        'gemini-pro' => [
            'name' => 'Google Gemini Pro',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent',
            'max_tokens' => 8192,
        ],
        'gemini-pro-vision' => [
            'name' => 'Google Gemini Pro Vision',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1/models/gemini-pro-vision:generateContent',
            'max_tokens' => 4096,
        ],
        'gpt-4' => [
            'name' => 'OpenAI GPT-4',
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'max_tokens' => 8192,
        ],
        'gpt-4-turbo' => [
            'name' => 'OpenAI GPT-4 Turbo',
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'max_tokens' => 128000,
        ],
        'gpt-3.5-turbo' => [
            'name' => 'OpenAI GPT-3.5 Turbo',
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'max_tokens' => 4096,
        ],
    ];

    /**
     * Get AI dashboard statistics
     */
    public function dashboard(Request $request, string $orgId)
    {
        Gate::authorize('ai.view_insights');

        try {
            $stats = Cache::remember("ai.dashboard.{$orgId}", now()->addMinutes(5), function () use ($orgId) {
                return [
                    'generated_campaigns' => AiGeneratedCampaign::where('org_id', $orgId)->count(),
                    'recommendations' => AiRecommendation::where('org_id', $orgId)
                        ->where('is_active', true)
                        ->count(),
                    'knowledge_items' => KnowledgeItem::where('is_deprecated', false)->count(),
                    'models_available' => count(self::AI_MODELS),
                ];
            });

            $recentGenerations = AiGeneratedCampaign::where('org_id', $orgId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($campaign) {
                    return [
                        'campaign_id' => $campaign->campaign_id,
                        'objective' => $campaign->objective_code,
                        'summary' => $campaign->ai_summary,
                        'engine' => $campaign->engine ?? 'gemini-pro',
                        'created_at' => $campaign->created_at,
                    ];
                });

            $models = array_map(function ($key, $config) {
                return [
                    'model' => $key,
                    'name' => $config['name'],
                    'max_tokens' => $config['max_tokens'],
                    'available' => $this->isModelAvailable($key),
                ];
            }, array_keys(self::AI_MODELS), self::AI_MODELS);

            return response()->json([
                'stats' => $stats,
                'recent_generations' => $recentGenerations,
                'models' => $models,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate AI content
     */
    public function generate(Request $request, string $orgId)
    {
        Gate::authorize('ai.generate_content');

        $validator = Validator::make($request->all(), [
            'content_type' => 'required|in:campaign,ad_copy,social_post,strategy,headline',
            'topic' => 'required|string|max:500',
            'objective' => 'nullable|string|max:500',
            'language' => 'nullable|string|in:ar,en',
            'tone' => 'nullable|string|in:professional,casual,friendly,formal,creative',
            'model' => 'nullable|string|in:' . implode(',', array_keys(self::AI_MODELS)),
            'max_tokens' => 'nullable|integer|min:100|max:4000',
            'context' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contentType = $request->input('content_type');
            $topic = $request->input('topic');
            $objective = $request->input('objective', '');
            $language = $request->input('language', 'ar');
            $tone = $request->input('tone', 'professional');
            $model = $request->input('model', 'gemini-pro');
            $maxTokens = $request->input('max_tokens', 1000);
            $context = $request->input('context', '');

            // Build prompt based on content type
            $prompt = $this->buildPrompt($contentType, $topic, $objective, $language, $tone, $context);

            // Generate content using selected AI model
            $generatedContent = $this->callAIModel($model, $prompt, $maxTokens);

            if (!$generatedContent) {
                return response()->json([
                    'error' => 'AI generation failed',
                    'message' => 'Unable to generate content. Please try again.'
                ], 500);
            }

            // Store generation history if it's a campaign
            if ($contentType === 'campaign') {
                $this->storeGeneratedCampaign($orgId, $generatedContent, $model, $objective);
            }

            return response()->json([
                'content' => $generatedContent,
                'model' => $model,
                'tokens_used' => strlen($generatedContent) / 4, // Rough estimate
                'generated_at' => now()->toIso8601String(),
            ], 201);

        } catch (\Exception $e) {
            Log::error('AI generation failed', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to generate content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform semantic search using pgvector
     */
    public function semanticSearch(Request $request, string $orgId, SemanticSearchService $searchService)
    {
        Gate::authorize('ai.semantic_search');

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'sources' => 'nullable|array',
            'sources.*' => 'nullable|string|in:knowledge,campaigns,assets,posts',
            'limit' => 'nullable|integer|min:1|max:50',
            'threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = $request->input('query');
            $sources = $request->input('sources', ['knowledge']);
            $limit = $request->input('limit', 10);
            $threshold = $request->input('threshold', 0.7);

            // Use the existing SemanticSearchService
            $results = $searchService->search($query, $limit);

            // Filter results by threshold if needed
            $filteredResults = collect($results)->filter(function ($result) use ($threshold) {
                return ($result['similarity'] ?? 0) >= $threshold;
            })->values();

            return response()->json([
                'query' => $query,
                'results' => $filteredResults,
                'count' => $filteredResults->count(),
                'sources' => $sources,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'org_id' => $orgId,
                'query' => $request->input('query'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Semantic search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI-powered recommendations
     */
    public function recommendations(Request $request, string $orgId)
    {
        Gate::authorize('ai.view_recommendations');

        try {
            $type = $request->input('type', 'all'); // all, campaign, content, optimization

            $query = AiRecommendation::where('org_id', $orgId)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc');

            if ($type !== 'all') {
                $query->where('recommendation_type', $type);
            }

            $recommendations = $query->limit(20)->get()->map(function ($rec) {
                return [
                    'id' => $rec->recommendation_id,
                    'type' => $rec->recommendation_type,
                    'title' => $rec->title,
                    'description' => $rec->description,
                    'priority' => $rec->priority,
                    'confidence' => $rec->confidence_score,
                    'created_at' => $rec->created_at,
                ];
            });

            return response()->json([
                'recommendations' => $recommendations,
                'count' => $recommendations->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch recommendations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get knowledge base items
     */
    public function knowledge(Request $request, string $orgId)
    {
        Gate::authorize('ai.manage_knowledge');

        try {
            $category = $request->input('category'); // dev, marketing, org, research
            $domain = $request->input('domain');
            $limit = $request->input('limit', 50);

            $query = KnowledgeItem::where('is_deprecated', false);

            if ($category) {
                $query->where('category', $category);
            }

            if ($domain) {
                $query->where('domain', $domain);
            }

            $items = $query->orderBy('tier', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'knowledge_id' => $item->knowledge_id,
                        'domain' => $item->domain,
                        'topic' => $item->topic,
                        'category' => $item->category,
                        'tier' => $item->tier,
                        'keywords' => $item->keywords,
                        'has_embedding' => !empty($item->topic_embedding),
                    ];
                });

            return response()->json([
                'items' => $items,
                'count' => $items->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch knowledge items',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process and vectorize knowledge documents
     */
    public function processKnowledge(Request $request, string $orgId)
    {
        Gate::authorize('ai.manage_knowledge');

        $validator = Validator::make($request->all(), [
            'knowledge_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'خطأ في التحقق',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $knowledgeId = $request->input('knowledge_id');

            $item = KnowledgeItem::where('knowledge_id', $knowledgeId)->firstOrFail();

            // Generate embedding using the EmbeddingRepository
            $result = $this->embeddingRepo->updateSingleEmbedding($knowledgeId);

            if ($result && isset($result->success) && $result->success) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم معالجة عنصر المعرفة بنجاح',
                    'knowledge_id' => $knowledgeId,
                    'status' => 'completed',
                    'result' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشلت معالجة عنصر المعرفة',
                'knowledge_id' => $knowledgeId,
                'status' => 'failed',
            ], 500);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'لم يتم العثور على عنصر المعرفة'], 404);
        } catch (\Exception $e) {
            Log::error('فشلت معالجة المعرفة: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشلت معالجة المعرفة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content generation history
     */
    public function history(Request $request, string $orgId)
    {
        Gate::authorize('ai.view_insights');

        try {
            $perPage = $request->input('per_page', 20);

            $history = AiGeneratedCampaign::where('org_id', $orgId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->through(function ($item) {
                    return [
                        'campaign_id' => $item->campaign_id,
                        'objective' => $item->objective_code,
                        'principle' => $item->recommended_principle,
                        'kpi' => $item->linked_kpi,
                        'summary' => $item->ai_summary,
                        'design_guideline' => $item->ai_design_guideline,
                        'engine' => $item->engine ?? 'gemini-pro',
                        'created_at' => $item->created_at,
                    ];
                });

            return response()->json($history);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build prompt based on content type
     */
    protected function buildPrompt(string $contentType, string $topic, string $objective, string $language, string $tone, string $context): string
    {
        $langName = $language === 'ar' ? 'Arabic' : 'English';

        $prompts = [
            'campaign' => "You are an expert marketing strategist. Create a comprehensive marketing campaign strategy in {$langName}.

Topic: {$topic}
Objective: {$objective}
Tone: {$tone}
Context: {$context}

Please provide:
1. Campaign overview and objectives
2. Target audience analysis
3. Key messages and positioning
4. Recommended channels and tactics
5. Success metrics and KPIs
6. Timeline and milestones",

            'ad_copy' => "You are a creative copywriter. Write compelling ad copy in {$langName}.

Topic: {$topic}
Objective: {$objective}
Tone: {$tone}
Context: {$context}

Create 3 variations of ad copy (headline + description) that are engaging, action-oriented, and aligned with the objective.",

            'social_post' => "You are a social media expert. Create engaging social media content in {$langName}.

Topic: {$topic}
Objective: {$objective}
Tone: {$tone}
Context: {$context}

Write 3 social media posts optimized for engagement. Include relevant hashtags and call-to-action.",

            'strategy' => "You are a marketing strategy consultant. Develop a strategic marketing plan in {$langName}.

Topic: {$topic}
Objective: {$objective}
Tone: {$tone}
Context: {$context}

Provide a detailed strategic analysis including market insights, competitive positioning, and actionable recommendations.",

            'headline' => "You are a creative headline writer. Generate attention-grabbing headlines in {$langName}.

Topic: {$topic}
Objective: {$objective}
Tone: {$tone}
Context: {$context}

Create 10 compelling headline variations that capture attention and drive action.",
        ];

        return $prompts[$contentType] ?? $prompts['campaign'];
    }

    /**
     * Call AI model for content generation
     */
    protected function callAIModel(string $model, string $prompt, int $maxTokens): ?string
    {
        if (!isset(self::AI_MODELS[$model])) {
            return null;
        }

        $config = self::AI_MODELS[$model];

        try {
            if (str_starts_with($model, 'gemini')) {
                return $this->callGemini($config['endpoint'], $prompt, $maxTokens);
            } elseif (str_starts_with($model, 'gpt')) {
                return $this->callOpenAI($config['endpoint'], $model, $prompt, $maxTokens);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('AI model call failed', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Call Google Gemini API
     */
    protected function callGemini(string $endpoint, string $prompt, int $maxTokens): ?string
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return 'SIMULATED: ' . substr($prompt, 0, 200) . '... [Gemini API key not configured]';
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($endpoint . '?key=' . $apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature' => 0.7,
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        return null;
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(string $endpoint, string $model, string $prompt, int $maxTokens): ?string
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            return 'SIMULATED: ' . substr($prompt, 0, 200) . '... [OpenAI API key not configured]';
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post($endpoint, [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;
        }

        return null;
    }

    /**
     * Check if AI model is available
     */
    protected function isModelAvailable(string $model): bool
    {
        if (str_starts_with($model, 'gemini')) {
            return !empty(env('GEMINI_API_KEY'));
        } elseif (str_starts_with($model, 'gpt')) {
            return !empty(env('OPENAI_API_KEY'));
        }

        return false;
    }

    /**
     * Store generated campaign for history
     */
    protected function storeGeneratedCampaign(string $orgId, string $content, string $engine, string $objective): void
    {
        AiGeneratedCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $orgId,
            'objective_code' => $objective,
            'ai_summary' => $content,
            'engine' => $engine,
            'created_at' => now(),
        ]);
    }

    /**
     * Batch process knowledge embeddings
     */
    public function batchProcessEmbeddings(Request $request, string $orgId)
    {
        Gate::authorize('ai.manage_knowledge');

        $validator = Validator::make($request->all(), [
            'batch_size' => 'nullable|integer|min:1|max:500',
            'category' => 'nullable|string|in:dev,marketing,org,research',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'خطأ في التحقق',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $batchSize = $request->input('batch_size', 100);
            $category = $request->input('category');

            $result = $this->embeddingRepo->batchUpdateEmbeddings($batchSize, $category);

            return response()->json([
                'success' => true,
                'message' => 'تم معالجة الدفعة بنجاح',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('فشلت معالجة الدفعة: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشلت معالجة الدفعة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Advanced semantic search
     */
    public function advancedSemanticSearch(Request $request, string $orgId)
    {
        Gate::authorize('ai.semantic_search');

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'intent' => 'nullable|string',
            'direction' => 'nullable|string',
            'purpose' => 'nullable|string',
            'category' => 'nullable|string|in:dev,marketing,org,research',
            'limit' => 'nullable|integer|min:1|max:100',
            'threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'خطأ في التحقق',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->knowledgeRepo->semanticSearchAdvanced(
                $request->input('query'),
                $request->input('intent'),
                $request->input('direction'),
                $request->input('purpose'),
                $request->input('category'),
                $request->input('limit', 10),
                $request->input('threshold', 0.3)
            );

            return response()->json([
                'success' => true,
                'query' => $request->input('query'),
                'results' => $results,
                'count' => $results->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('فشل البحث الدلالي المتقدم: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل البحث الدلالي المتقدم',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register new knowledge entry
     */
    public function registerKnowledge(Request $request, string $orgId)
    {
        Gate::authorize('ai.manage_knowledge');

        $validator = Validator::make($request->all(), [
            'domain' => 'required|string|max:255',
            'category' => 'required|string|in:dev,marketing,org,research',
            'topic' => 'required|string|max:500',
            'content' => 'required|string',
            'tier' => 'nullable|integer|min:1|max:5',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'خطأ في التحقق',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $knowledgeId = $this->knowledgeRepo->registerKnowledge(
                $request->input('domain'),
                $request->input('category'),
                $request->input('topic'),
                $request->input('content'),
                $request->input('tier', 2),
                $request->input('keywords', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل المعرفة بنجاح',
                'knowledge_id' => $knowledgeId,
            ], 201);

        } catch (\Exception $e) {
            Log::error('فشل تسجيل المعرفة: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل تسجيل المعرفة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto analyze knowledge
     */
    public function analyzeKnowledge(Request $request, string $orgId)
    {
        Gate::authorize('ai.view_insights');

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'domain' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:dev,marketing,org,research',
            'max_batches' => 'nullable|integer|min:1|max:20',
            'batch_limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'خطأ في التحقق',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $analysis = $this->knowledgeRepo->autoAnalyzeKnowledge(
                $request->input('query'),
                $request->input('domain'),
                $request->input('category', 'dev'),
                $request->input('max_batches', 5),
                $request->input('batch_limit', 20)
            );

            return response()->json([
                'success' => true,
                'query' => $request->input('query'),
                'analysis' => $analysis,
            ]);

        } catch (\Exception $e) {
            Log::error('فشل تحليل المعرفة: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل تحليل المعرفة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Smart context loader for RAG
     */
    public function loadContext(Request $request, string $orgId)
    {
        Gate::authorize('ai.semantic_search');

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'domain' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:dev,marketing,org,research',
            'token_limit' => 'nullable|integer|min:100|max:50000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'خطأ في التحقق',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $context = $this->knowledgeRepo->smartContextLoader(
                $request->input('query'),
                $request->input('domain'),
                $request->input('category', 'dev'),
                $request->input('token_limit', 5000)
            );

            return response()->json([
                'success' => true,
                'query' => $request->input('query'),
                'context' => $context,
            ]);

        } catch (\Exception $e) {
            Log::error('فشل تحميل السياق: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل تحميل السياق',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system report
     */
    public function systemReport(Request $request)
    {
        Gate::authorize('ai.view_insights');

        try {
            $report = $this->knowledgeRepo->generateSystemReport();

            return response()->json([
                'success' => true,
                'report' => $report,
            ]);

        } catch (\Exception $e) {
            Log::error('فشل إنشاء تقرير النظام: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل إنشاء تقرير النظام',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup old embeddings
     */
    public function cleanupEmbeddings(Request $request)
    {
        Gate::authorize('ai.manage_knowledge');

        try {
            $success = $this->knowledgeRepo->cleanupOldEmbeddings();

            return response()->json([
                'success' => $success,
                'message' => $success ? 'تم تنظيف التضمينات القديمة بنجاح' : 'فشل تنظيف التضمينات',
            ]);

        } catch (\Exception $e) {
            Log::error('فشل تنظيف التضمينات: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل تنظيف التضمينات',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
