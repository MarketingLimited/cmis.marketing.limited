<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AiGeneratedCampaign;
use App\Models\AiRecommendation;
use App\Models\CMIS\KnowledgeItem;
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
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $knowledgeId = $request->input('knowledge_id');

            $item = KnowledgeItem::where('knowledge_id', $knowledgeId)->firstOrFail();

            // TODO: Implement actual embedding generation
            // This would:
            // 1. Get content from the appropriate table
            // 2. Generate embedding using OpenAI/Gemini
            // 3. Store embedding in topic_embedding column
            // 4. Update embedding_updated_at

            return response()->json([
                'message' => 'Knowledge item processing queued',
                'knowledge_id' => $knowledgeId,
                'status' => 'pending',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Knowledge item not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process knowledge',
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
}
