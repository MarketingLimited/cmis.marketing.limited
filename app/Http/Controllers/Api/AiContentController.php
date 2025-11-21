<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\GenerateContentRequest;
use App\Services\AI\AiQuotaService;
use App\Exceptions\QuotaExceededException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * AI Content Controller
 *
 * Handles AI content generation with quota enforcement and rate limiting.
 * Part of Phase 1B - AI Cost Control (2025-11-21)
 */
class AiContentController extends Controller
{
    public function __construct(
        private AiQuotaService $quotaService
    ) {}

    /**
     * Generate AI content
     *
     * @authenticated
     * @group AI Operations
     */
    public function generate(GenerateContentRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            // Extract validated and sanitized data
            $contentType = $request->input('content_type');
            $prompt = $request->input('prompt');
            $context = $request->input('context', []);
            $tone = $request->input('tone', 'professional');
            $language = $request->input('language', 'en');
            $maxLength = $request->input('max_length', 500);
            $marketingPrinciple = $request->input('marketing_principle');

            // Build AI prompt
            $aiPrompt = $this->buildAiPrompt($contentType, $prompt, $context, $tone, $language, $marketingPrinciple);

            // Call AI service (mocked for now - integrate with actual AI service)
            $result = $this->callAiService($aiPrompt, $maxLength, $language);

            // Record usage after successful generation
            $this->quotaService->recordUsage(
                $user->org_id,
                $user->id,
                'gpt',
                'content_generation',
                $result['tokens_used'],
                [
                    'model' => $result['model'],
                    'content_type' => $contentType,
                    'language' => $language,
                    'response_time' => $result['response_time'],
                ]
            );

            // Get updated quota status
            $quotaStatus = $this->quotaService->getQuotaStatus($user->org_id, $user->id);

            return response()->json([
                'success' => true,
                'content' => $result['text'],
                'metadata' => [
                    'tokens_used' => $result['tokens_used'],
                    'model' => $result['model'],
                    'language' => $language,
                    'content_type' => $contentType,
                ],
                'quota' => [
                    'daily_remaining' => $quotaStatus['gpt']['daily_remaining'] ?? 0,
                    'daily_percentage' => $quotaStatus['gpt']['daily_percentage'] ?? 0,
                    'monthly_remaining' => $quotaStatus['gpt']['monthly_remaining'] ?? 0,
                ],
            ]);

        } catch (QuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error' => 'quota_exceeded',
                'message' => $e->getMessage(),
                'quota_type' => $e->getQuotaType(),
                'upgrade_url' => route('subscription.upgrade'),
            ], 429);

        } catch (\Exception $e) {
            Log::error('AI content generation failed', [
                'user_id' => $user->id,
                'org_id' => $user->org_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'generation_failed',
                'message' => __('ai.generation_error'),
            ], 500);
        }
    }

    /**
     * Generate multiple AI content variations
     *
     * @authenticated
     * @group AI Operations
     */
    public function generateBatch(GenerateContentRequest $request): JsonResponse
    {
        $user = auth()->user();
        $variations = $request->input('variations', 3);

        // Validate variations count
        if ($variations < 1 || $variations > 5) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_variations_count',
                'message' => 'Variations must be between 1 and 5',
            ], 422);
        }

        try {
            // Check quota for batch operation
            $this->quotaService->checkQuota($user->org_id, $user->id, 'gpt', $variations);

            $results = [];
            $totalTokens = 0;

            for ($i = 0; $i < $variations; $i++) {
                $contentType = $request->input('content_type');
                $prompt = $request->input('prompt');
                $context = $request->input('context', []);
                $tone = $request->input('tone', 'professional');
                $language = $request->input('language', 'en');
                $maxLength = $request->input('max_length', 500);
                $marketingPrinciple = $request->input('marketing_principle');

                // Add variation instruction
                $aiPrompt = $this->buildAiPrompt($contentType, $prompt, $context, $tone, $language, $marketingPrinciple);
                $aiPrompt .= "\n\nGenerate a unique variation (Variation " . ($i + 1) . " of {$variations}).";

                $result = $this->callAiService($aiPrompt, $maxLength, $language);

                $results[] = [
                    'variation' => $i + 1,
                    'content' => $result['text'],
                    'tokens_used' => $result['tokens_used'],
                ];

                $totalTokens += $result['tokens_used'];

                // Record individual usage
                $this->quotaService->recordUsage(
                    $user->org_id,
                    $user->id,
                    'gpt',
                    'content_generation_batch',
                    $result['tokens_used'],
                    [
                        'model' => $result['model'],
                        'variation' => $i + 1,
                        'total_variations' => $variations,
                    ]
                );
            }

            $quotaStatus = $this->quotaService->getQuotaStatus($user->org_id, $user->id);

            return response()->json([
                'success' => true,
                'variations' => $results,
                'metadata' => [
                    'total_tokens_used' => $totalTokens,
                    'variations_count' => $variations,
                ],
                'quota' => [
                    'daily_remaining' => $quotaStatus['gpt']['daily_remaining'] ?? 0,
                    'monthly_remaining' => $quotaStatus['gpt']['monthly_remaining'] ?? 0,
                ],
            ]);

        } catch (QuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error' => 'quota_exceeded',
                'message' => $e->getMessage(),
                'quota_type' => $e->getQuotaType(),
            ], 429);
        }
    }

    /**
     * Get AI usage statistics
     *
     * @authenticated
     * @group AI Operations
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();

        $quotaStatus = $this->quotaService->getQuotaStatus($user->org_id, $user->id);
        $recommendations = $this->quotaService->getRecommendations($user->org_id, $user->id);

        return response()->json([
            'success' => true,
            'quota_status' => $quotaStatus,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Build AI prompt from request parameters
     */
    protected function buildAiPrompt(
        string $contentType,
        string $prompt,
        array $context,
        string $tone,
        string $language,
        ?string $marketingPrinciple = null
    ): string {
        $aiPrompt = "Generate {$contentType} content in {$language} language.\n\n";

        $aiPrompt .= "Tone: {$tone}\n\n";

        if ($marketingPrinciple) {
            $aiPrompt .= "Marketing Principle: Apply the '{$marketingPrinciple}' principle.\n\n";
        }

        if (!empty($context['brand_voice'])) {
            $aiPrompt .= "Brand Voice: {$context['brand_voice']}\n\n";
        }

        if (!empty($context['target_audience'])) {
            $aiPrompt .= "Target Audience: {$context['target_audience']}\n\n";
        }

        if (!empty($context['key_points'])) {
            $aiPrompt .= "Key Points to Include:\n";
            foreach ($context['key_points'] as $point) {
                $aiPrompt .= "- {$point}\n";
            }
            $aiPrompt .= "\n";
        }

        $aiPrompt .= "User Prompt:\n{$prompt}\n\n";

        $aiPrompt .= "Please generate compelling, natural-sounding content that aligns with the requirements above.";

        return $aiPrompt;
    }

    /**
     * Call AI service (mocked - replace with actual implementation)
     *
     * TODO: Integrate with actual AI service (Google Gemini, OpenAI, etc.)
     */
    protected function callAiService(string $prompt, int $maxLength, string $language): array
    {
        // Simulate API call delay
        usleep(100000); // 100ms

        // Mock response based on language
        $mockContent = $language === 'ar'
            ? 'هذا محتوى تجريبي تم إنشاؤه بواسطة الذكاء الاصطناعي. يتضمن هذا النص معلومات تسويقية مصممة خصيصاً لجمهورك المستهدف.'
            : 'This is mock AI-generated content. It includes marketing information tailored to your target audience with compelling messaging.';

        // Estimate tokens (rough approximation)
        $tokensUsed = (int) (strlen($prompt) / 4) + (int) (strlen($mockContent) / 4);

        return [
            'text' => $mockContent,
            'tokens_used' => $tokensUsed,
            'model' => 'gpt-4-mock',
            'response_time' => 0.1,
        ];
    }
}
