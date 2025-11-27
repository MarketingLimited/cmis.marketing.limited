<?php

namespace App\Services\AI;

use App\Models\AI\GeneratedMedia;
use App\Models\AI\AiUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GeminiService
{
    private ?string $apiKey = null;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private array $defaultConfig = [
        'temperature' => 1.0,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 2048,
    ];

    public function __construct()
    {
        $this->apiKey = config('services.google.ai_api_key');

        if (empty($this->apiKey)) {
            Log::warning('Google AI API key not configured. Set GOOGLE_AI_API_KEY in .env');
        }
    }

    /**
     * Check if the API key is configured
     */
    private function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new Exception('Google AI API key not configured. Set GOOGLE_AI_API_KEY in .env');
        }
    }

    /**
     * Generate text content (ad copy, headlines, etc.)
     */
    public function generateText(
        string $prompt,
        array $options = []
    ): array {
        $this->ensureApiKeyConfigured();

        $model = $options['model'] ?? config('services.gemini.text_model', 'gemini-2.5-flash');
        $config = array_merge($this->defaultConfig, $options['config'] ?? []);

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/models/{$model}:generateContent", [
                    'key' => $this->apiKey,
                    'contents' => [[
                        'parts' => [[
                            'text' => $prompt
                        ]]
                    ]],
                    'generationConfig' => $config,
                    'safetySettings' => $this->getSafetySettings()
                ]);

            if (!$response->successful()) {
                throw new Exception("Gemini API error: {$response->body()}");
            }

            $data = $response->json();

            return [
                'text' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
                'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                'input_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                'output_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                'finish_reason' => $data['candidates'][0]['finishReason'] ?? 'STOP',
                'metadata' => $data
            ];
        } catch (Exception $e) {
            Log::error('Gemini text generation failed', [
                'prompt' => $prompt,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate image from prompt
     */
    public function generateImage(
        string $prompt,
        array $options = []
    ): array {
        $model = $options['model'] ?? config('services.gemini.image_model', 'gemini-3-pro-image-preview');
        $resolution = $options['resolution'] ?? 'media_resolution_high';
        $config = array_merge($this->defaultConfig, [
            'mediaResolution' => $resolution
        ]);

        try {
            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/models/{$model}:generateContent", [
                    'key' => $this->apiKey,
                    'contents' => [[
                        'parts' => [[
                            'text' => $prompt
                        ]]
                    ]],
                    'generationConfig' => $config,
                    'safetySettings' => $this->getSafetySettings()
                ]);

            if (!$response->successful()) {
                throw new Exception("Gemini Image API error: {$response->body()}");
            }

            $data = $response->json();

            // Extract image data from response
            $imagePart = $data['candidates'][0]['content']['parts'][0] ?? null;

            if (!isset($imagePart['inlineData'])) {
                throw new Exception('No image data in response');
            }

            $imageData = base64_decode($imagePart['inlineData']['data']);
            $mimeType = $imagePart['inlineData']['mimeType'] ?? 'image/png';

            // Store image
            $filename = 'ai-generated/' . uniqid('img_') . '.' . $this->getExtensionFromMime($mimeType);
            Storage::disk('public')->put($filename, $imageData);

            return [
                'url' => Storage::disk('public')->url($filename),
                'storage_path' => $filename,
                'mime_type' => $mimeType,
                'file_size' => strlen($imageData),
                'resolution' => $resolution,
                'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                'metadata' => $data
            ];
        } catch (Exception $e) {
            Log::error('Gemini image generation failed', [
                'prompt' => $prompt,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate ad design with multiple variations
     */
    public function generateAdDesign(
        string $campaignObjective,
        string $brandGuidelines,
        array $designRequirements,
        int $variationCount = 3,
        string $resolution = 'high'
    ): array {
        $prompt = $this->buildAdDesignPrompt($campaignObjective, $brandGuidelines, $designRequirements);
        $results = [];

        $resolutionMap = [
            'low' => 'media_resolution_low',
            'medium' => 'media_resolution_medium',
            'high' => 'media_resolution_high'
        ];

        for ($i = 0; $i < $variationCount; $i++) {
            $variationPrompt = $prompt . "\n\nVariation {$i}: Create a unique design approach.";

            try {
                $image = $this->generateImage($variationPrompt, [
                    'resolution' => $resolutionMap[$resolution] ?? 'media_resolution_high'
                ]);

                $results[] = [
                    'variation' => $i + 1,
                    'url' => $image['url'],
                    'storage_path' => $image['storage_path'],
                    'file_size' => $image['file_size'],
                    'tokens_used' => $image['tokens_used'],
                    'cost' => $this->calculateImageCost($image['tokens_used'], $resolution)
                ];

                // Small delay between generations to respect rate limits
                if ($i < $variationCount - 1) {
                    usleep(500000); // 0.5 seconds
                }
            } catch (Exception $e) {
                Log::warning("Failed to generate variation {$i}", ['error' => $e->getMessage()]);
                continue;
            }
        }

        return $results;
    }

    /**
     * Generate ad copy (headlines, descriptions, CTAs)
     */
    public function generateAdCopy(
        string $campaignObjective,
        string $targetAudience,
        string $productDescription,
        array $requirements = []
    ): array {
        $prompt = $this->buildAdCopyPrompt($campaignObjective, $targetAudience, $productDescription, $requirements);

        try {
            $result = $this->generateText($prompt, [
                'config' => [
                    'temperature' => 0.9,
                    'maxOutputTokens' => 1024
                ]
            ]);

            // Parse structured output
            $text = $result['text'];
            $parsed = $this->parseAdCopyOutput($text);

            return [
                'headlines' => $parsed['headlines'] ?? [],
                'descriptions' => $parsed['descriptions'] ?? [],
                'call_to_actions' => $parsed['ctas'] ?? [],
                'primary_text' => $parsed['primary_text'] ?? '',
                'tokens_used' => $result['tokens_used'],
                'cost' => $this->calculateTextCost($result['tokens_used']),
                'raw_output' => $text
            ];
        } catch (Exception $e) {
            Log::error('Ad copy generation failed', [
                'objective' => $campaignObjective,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Build prompt for ad design generation
     */
    private function buildAdDesignPrompt(
        string $objective,
        string $brandGuidelines,
        array $requirements
    ): string {
        $prompt = "Create a professional advertisement design for a marketing campaign.\n\n";
        $prompt .= "Campaign Objective: {$objective}\n";
        $prompt .= "Brand Guidelines: {$brandGuidelines}\n\n";

        $prompt .= "Design Requirements:\n";
        foreach ($requirements as $requirement) {
            $prompt .= "- {$requirement}\n";
        }

        $prompt .= "\nGenerate a 4K resolution design that is modern, eye-catching, and aligned with the brand identity.";
        $prompt .= " Include all text elements clearly and make sure they are legible.";
        $prompt .= " Use professional color schemes and typography.";

        return $prompt;
    }

    /**
     * Build prompt for ad copy generation
     */
    private function buildAdCopyPrompt(
        string $objective,
        string $targetAudience,
        string $productDescription,
        array $requirements
    ): string {
        $prompt = "Generate compelling advertisement copy for a marketing campaign.\n\n";
        $prompt .= "Campaign Objective: {$objective}\n";
        $prompt .= "Target Audience: {$targetAudience}\n";
        $prompt .= "Product/Service: {$productDescription}\n\n";

        if (!empty($requirements)) {
            $prompt .= "Additional Requirements:\n";
            foreach ($requirements as $req) {
                $prompt .= "- {$req}\n";
            }
        }

        $prompt .= "\nPlease provide the following in a structured format:\n";
        $prompt .= "HEADLINES: (3 variations, max 30 characters each)\n";
        $prompt .= "DESCRIPTIONS: (3 variations, max 90 characters each)\n";
        $prompt .= "PRIMARY_TEXT: (compelling main ad copy, 125-150 words)\n";
        $prompt .= "CTAs: (3 call-to-action options)\n\n";
        $prompt .= "Make the copy persuasive, benefit-focused, and aligned with marketing best practices.";

        return $prompt;
    }

    /**
     * Parse ad copy structured output
     */
    private function parseAdCopyOutput(string $text): array
    {
        $parsed = [
            'headlines' => [],
            'descriptions' => [],
            'ctas' => [],
            'primary_text' => ''
        ];

        // Extract headlines
        if (preg_match('/HEADLINES:(.*?)(?=DESCRIPTIONS:|PRIMARY_TEXT:|CTAs:|$)/s', $text, $matches)) {
            $headlines = array_filter(array_map('trim', explode("\n", $matches[1])));
            $parsed['headlines'] = array_values(array_filter($headlines, fn($h) => !empty($h) && $h !== '-'));
        }

        // Extract descriptions
        if (preg_match('/DESCRIPTIONS:(.*?)(?=PRIMARY_TEXT:|CTAs:|$)/s', $text, $matches)) {
            $descriptions = array_filter(array_map('trim', explode("\n", $matches[1])));
            $parsed['descriptions'] = array_values(array_filter($descriptions, fn($d) => !empty($d) && $d !== '-'));
        }

        // Extract primary text
        if (preg_match('/PRIMARY_TEXT:(.*?)(?=CTAs:|$)/s', $text, $matches)) {
            $parsed['primary_text'] = trim($matches[1]);
        }

        // Extract CTAs
        if (preg_match('/CTAs:(.*?)$/s', $text, $matches)) {
            $ctas = array_filter(array_map('trim', explode("\n", $matches[1])));
            $parsed['ctas'] = array_values(array_filter($ctas, fn($c) => !empty($c) && $c !== '-'));
        }

        return $parsed;
    }

    /**
     * Calculate cost for text generation
     */
    private function calculateTextCost(int $tokens): float
    {
        // Pricing: $2/$12 per million tokens (input/output) for requests under 200k
        // Simplified calculation (average)
        $costPerMillionTokens = 7.0; // Average of input/output
        return ($tokens / 1000000) * $costPerMillionTokens;
    }

    /**
     * Calculate cost for image generation
     */
    private function calculateImageCost(int $tokens, string $resolution): float
    {
        $baseCost = $this->calculateTextCost($tokens);

        // Add image generation cost (varies by resolution)
        $imageCost = match($resolution) {
            'low' => 0.05,
            'medium' => 0.10,
            'high' => 0.20,
            default => 0.20
        };

        return $baseCost + $imageCost;
    }

    /**
     * Get safety settings for content generation
     */
    private function getSafetySettings(): array
    {
        return [
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ]
        ];
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        return match($mimeType) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png'
        };
    }
}
