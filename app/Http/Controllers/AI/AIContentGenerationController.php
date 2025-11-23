<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Traits\HandlesAsyncJobs;
use App\Jobs\AI\GenerateContentJob;
use App\Models\AiGeneratedCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * AI Content Generation Controller
 *
 * Handles AI-powered content generation using various AI models
 */
class AIContentGenerationController extends Controller
{
    use HandlesAsyncJobs, ApiResponse;

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
     * Generate AI content
     *
     * Supports both synchronous and asynchronous generation.
     * Use async=true for queued processing (recommended for production).
     */
    public function generate(Request $request, string $orgId)
    : \Illuminate\Http\JsonResponse {
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
            'async' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation Error');
        }

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

        // Check if async processing is requested (default: true for rate limiting)
        if ($this->shouldProcessAsync($request, true)) {
            // Dispatch job for async processing
            $job = new GenerateContentJob(
                $orgId,
                $request->user()->user_id,
                $prompt,
                $contentType,
                [
                    'topic' => $topic,
                    'objective' => $objective,
                    'language' => $language,
                    'tone' => $tone,
                    'model' => $model,
                    'max_tokens' => $maxTokens,
                    'context' => $context,
                ]
            );

            dispatch($job);

            return $this->asyncJobAccepted(
                $job->getJobId(),
                'AI content generation started',
                [
                    'content_type' => $contentType,
                    'model' => $model,
                ]
            );
        }

        // Synchronous generation (for backward compatibility)
        try {
            $generatedContent = $this->callAIModel($model, $prompt, $maxTokens);

            if (!$generatedContent) {
                return $this->serverError('Unable to generate content. Please try again.');
            }

            // Store generation history if it's a campaign
            if ($contentType === 'campaign') {
                $this->storeGeneratedCampaign($orgId, $generatedContent, $model, $objective);
            }

            return $this->created([
                'content' => $generatedContent,
                'model' => $model,
                'tokens_used' => strlen($generatedContent) / 4, // Rough estimate
                'generated_at' => now()->toIso8601String(),
            ], 'Content generated successfully');

        } catch (\Exception $e) {
            Log::error('AI generation failed', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to generate content');
        }
    }

    /**
     * Get content generation history
     */
    public function history(Request $request, string $orgId)
    : \Illuminate\Http\JsonResponse {
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

            return $this->paginated($history, 'Generation history retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch history');
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
        $apiKey = config('services.gemini.api_key');
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
        $apiKey = config('services.ai.openai_key');
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
            return !empty(config('services.gemini.api_key'));
        } elseif (str_starts_with($model, 'gpt')) {
            return !empty(config('services.ai.openai_key'));
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
