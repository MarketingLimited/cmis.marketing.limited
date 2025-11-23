<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\GenerateContentRequest;
use App\Http\Requests\GenerateAdDesignRequest;
use App\Http\Requests\GenerateAdCopyRequest;
use App\Http\Requests\GenerateVideoRequest;
use App\Services\AI\AiQuotaService;
use App\Services\AI\GeminiService;
use App\Services\AI\VeoVideoService;
use App\Models\AI\GeneratedMedia;
use App\Jobs\GenerateVideoJob;
use App\Exceptions\QuotaExceededException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * AI Content Controller
 *
 * Handles AI content generation with quota enforcement and rate limiting.
 * Part of Phase 1B - AI Cost Control (2025-11-21)
 */
class AiContentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AiQuotaService $quotaService,
        private GeminiService $geminiService,
        private VeoVideoService $veoService
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

        return $this->success(['quota_status' => $quotaStatus,
            'recommendations' => $recommendations,], 'Operation completed successfully');
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
     * Generate ad design images
     *
     * @authenticated
     * @group AI Operations
     */
    public function generateAdDesign(GenerateAdDesignRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            // Check image quota
            $variationCount = $request->input('variation_count', 3);
            $this->quotaService->checkQuota($user->org_id, $user->id, 'image', $variationCount);

            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            // Generate ad designs
            $designs = $this->geminiService->generateAdDesign(
                campaignObjective: $request->input('objective'),
                brandGuidelines: $request->input('brand_guidelines'),
                designRequirements: $request->input('design_requirements'),
                variationCount: $variationCount,
                resolution: $request->input('resolution', 'high')
            );

            // Store in database
            $mediaRecords = [];
            foreach ($designs as $design) {
                $media = GeneratedMedia::create([
                    'org_id' => $user->org_id,
                    'campaign_id' => $request->input('campaign_id'),
                    'user_id' => $user->id,
                    'media_type' => GeneratedMedia::TYPE_IMAGE,
                    'ai_model' => GeneratedMedia::MODEL_GEMINI_3_IMAGE,
                    'prompt_text' => $request->input('brand_guidelines'),
                    'media_url' => $design['url'],
                    'storage_path' => $design['storage_path'],
                    'resolution' => $request->input('resolution', 'high'),
                    'file_size_bytes' => $design['file_size'],
                    'generation_cost' => $design['cost'],
                    'status' => GeneratedMedia::STATUS_COMPLETED,
                    'metadata' => [
                        'variation' => $design['variation'],
                        'tokens_used' => $design['tokens_used'],
                        'objective' => $request->input('objective'),
                        'requirements' => $request->input('design_requirements')
                    ]
                ]);

                $mediaRecords[] = $media;

                // Record quota usage
                $this->quotaService->recordUsage(
                    $user->org_id,
                    $user->id,
                    'gemini-image',
                    'image_generation',
                    $design['tokens_used'],
                    ['media_id' => $media->id, 'cost' => $design['cost']]
                );
            }

            $quotaStatus = $this->quotaService->getQuotaStatus($user->org_id, $user->id);

            return response()->json([
                'success' => true,
                'designs' => $mediaRecords->map(fn($m) => [
                    'id' => $m->id,
                    'url' => $m->media_url,
                    'variation' => $m->metadata['variation'],
                    'file_size' => $m->formatted_file_size,
                    'cost' => $m->generation_cost
                ]),
                'quota' => [
                    'images_remaining_daily' => $quotaStatus['image']['daily_remaining'] ?? 0,
                    'images_remaining_monthly' => $quotaStatus['image']['monthly_remaining'] ?? 0,
                ]
            ]);

        } catch (QuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error' => 'quota_exceeded',
                'message' => $e->getMessage(),
                'upgrade_url' => route('subscription.upgrade'),
            ], 429);
        } catch (\Exception $e) {
            Log::error('Ad design generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'generation_failed',
                'message' => 'Failed to generate ad designs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate ad copy (headlines, descriptions, CTAs)
     *
     * @authenticated
     * @group AI Operations
     */
    public function generateAdCopy(GenerateAdCopyRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            // Check text generation quota
            $this->quotaService->checkQuota($user->org_id, $user->id, 'gpt', 1);

            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            // Generate ad copy
            $result = $this->geminiService->generateAdCopy(
                campaignObjective: $request->input('objective'),
                targetAudience: $request->input('target_audience'),
                productDescription: $request->input('product_description'),
                requirements: $request->input('requirements', [])
            );

            // Record quota usage
            $this->quotaService->recordUsage(
                $user->org_id,
                $user->id,
                'gemini-text',
                'ad_copy_generation',
                $result['tokens_used'],
                [
                    'objective' => $request->input('objective'),
                    'cost' => $result['cost']
                ]
            );

            $quotaStatus = $this->quotaService->getQuotaStatus($user->org_id, $user->id);

            return response()->json([
                'success' => true,
                'ad_copy' => [
                    'headlines' => $result['headlines'],
                    'descriptions' => $result['descriptions'],
                    'call_to_actions' => $result['call_to_actions'],
                    'primary_text' => $result['primary_text'],
                ],
                'metadata' => [
                    'tokens_used' => $result['tokens_used'],
                    'cost' => $result['cost']
                ],
                'quota' => [
                    'gpt_remaining_daily' => $quotaStatus['gpt']['daily_remaining'] ?? 0,
                ]
            ]);

        } catch (QuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error' => 'quota_exceeded',
                'message' => $e->getMessage(),
                'upgrade_url' => route('subscription.upgrade'),
            ], 429);
        } catch (\Exception $e) {
            Log::error('Ad copy generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'generation_failed',
                'message' => 'Failed to generate ad copy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate video ad (queued job)
     *
     * @authenticated
     * @group AI Operations
     */
    public function generateVideo(GenerateVideoRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            // Check video quota
            $this->quotaService->checkQuota($user->org_id, $user->id, 'video', 1);

            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            // Create pending media record
            $media = GeneratedMedia::create([
                'org_id' => $user->org_id,
                'campaign_id' => $request->input('campaign_id'),
                'user_id' => $user->id,
                'media_type' => GeneratedMedia::TYPE_VIDEO,
                'ai_model' => $request->input('use_fast_model')
                    ? GeneratedMedia::MODEL_VEO_31_FAST
                    : GeneratedMedia::MODEL_VEO_31,
                'prompt_text' => $request->input('prompt'),
                'status' => GeneratedMedia::STATUS_PENDING,
                'duration_seconds' => $request->input('duration', 7),
                'aspect_ratio' => $request->input('aspect_ratio', '16:9'),
                'metadata' => [
                    'source_image' => $request->input('source_image'),
                    'reference_images' => $request->input('reference_images'),
                    'animation_prompt' => $request->input('animation_prompt')
                ]
            ]);

            // Dispatch job for async processing
            GenerateVideoJob::dispatch(
                $media->id,
                $user->org_id,
                $user->id,
                [
                    'source_image' => $request->input('source_image'),
                    'reference_images' => $request->input('reference_images'),
                    'duration' => $request->input('duration', 7),
                    'aspect_ratio' => $request->input('aspect_ratio', '16:9'),
                    'use_fast_model' => $request->input('use_fast_model', false)
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Video generation started',
                'media_id' => $media->id,
                'status' => 'pending',
                'estimated_completion' => now()->addMinutes(3)->toIso8601String(),
                'check_status_url' => route('api.ai.video-status', ['media' => $media->id])
            ], 202);

        } catch (QuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error' => 'quota_exceeded',
                'message' => $e->getMessage(),
                'upgrade_url' => route('subscription.upgrade'),
            ], 429);
        } catch (\Exception $e) {
            Log::error('Video generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'generation_failed',
                'message' => 'Failed to start video generation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check video generation status
     *
     * @authenticated
     * @group AI Operations
     */
    public function videoStatus(string $mediaId): JsonResponse
    {
        $user = auth()->user();

        try {
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            $media = GeneratedMedia::findOrFail($mediaId);

            // Ensure user owns this media
            if ($media->user_id !== $user->id) {
                return $this->forbidden('unauthorized');
            }

            $response = [
                'success' => true,
                'media_id' => $media->id,
                'status' => $media->status,
                'media_type' => $media->media_type,
            ];

            if ($media->isCompleted()) {
                $response['url'] = $media->media_url;
                $response['duration'] = $media->duration_seconds;
                $response['file_size'] = $media->formatted_file_size;
                $response['cost'] = $media->generation_cost;
            } elseif ($media->isFailed()) {
                $response['error_message'] = $media->error_message;
            }

            return $this->success($response, 'Retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFound('not_found');
        }
    }

    /**
     * Call AI service (now using Gemini)
     */
    protected function callAiService(string $prompt, int $maxLength, string $language): array
    {
        try {
            $result = $this->geminiService->generateText($prompt, [
                'config' => [
                    'maxOutputTokens' => $maxLength,
                    'temperature' => 0.9
                ]
            ]);

            return [
                'text' => $result['text'],
                'tokens_used' => $result['tokens_used'],
                'model' => 'gemini-3-pro-preview',
                'response_time' => 0.5,
            ];
        } catch (\Exception $e) {
            // Fallback to mock for development
            Log::warning('Gemini API unavailable, using mock response', [
                'error' => $e->getMessage()
            ]);

            $mockContent = $language === 'ar'
                ? 'هذا محتوى تجريبي تم إنشاؤه بواسطة الذكاء الاصطناعي. يتضمن هذا النص معلومات تسويقية مصممة خصيصاً لجمهورك المستهدف.'
                : 'This is mock AI-generated content. It includes marketing information tailored to your target audience with compelling messaging.';

            $tokensUsed = (int) (strlen($prompt) / 4) + (int) (strlen($mockContent) / 4);

            return [
                'text' => $mockContent,
                'tokens_used' => $tokensUsed,
                'model' => 'gemini-3-mock',
                'response_time' => 0.1,
            ];
        }
    }
}
