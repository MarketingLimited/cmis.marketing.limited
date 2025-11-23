<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\AiGeneratedCampaign;
use App\Models\AiRecommendation;
use App\Models\CMIS\KnowledgeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

/**
 * AI Dashboard Controller
 *
 * Provides AI dashboard statistics and system insights
 */
class AIDashboardController extends Controller
{
    use ApiResponse;

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
    public function show(Request $request, string $orgId)
    : \Illuminate\Http\JsonResponse {
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

            return $this->success([
                'stats' => $stats,
                'recent_generations' => $recentGenerations,
                'models' => $models,
            ], 'AI dashboard data retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch dashboard data');
        }
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
}
