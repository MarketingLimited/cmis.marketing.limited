<?php

namespace App\Services\Social;

use App\Services\AI\GeminiService;
use App\Services\Social\KnowledgeBaseConversionService;
use App\Services\Social\BrandDNAAnalysisService;
use Illuminate\Support\Facades\Log;

/**
 * Knowledge Base Enhanced Content Generation Service
 *
 * Generates AI content informed by brand knowledge base and historical performance data.
 * Uses successful patterns, brand DNA, and proven messaging to create on-brand content.
 */
class KnowledgeBaseContentGenerationService
{
    private GeminiService $gemini;
    private KnowledgeBaseConversionService $kbService;
    private BrandDNAAnalysisService $brandDNAService;

    public function __construct(
        GeminiService $gemini,
        KnowledgeBaseConversionService $kbService,
        BrandDNAAnalysisService $brandDNAService
    ) {
        $this->gemini = $gemini;
        $this->kbService = $kbService;
        $this->brandDNAService = $brandDNAService;
    }

    /**
     * Generate content using knowledge base insights
     */
    public function generateContent(
        string $orgId,
        string $profileGroupId,
        array $parameters
    ): array {
        try {
            // 1. Get KB recommendations for the campaign objective
            $recommendations = $this->getKBRecommendations($orgId, $profileGroupId, $parameters);

            // 2. Build enhanced prompt with brand DNA
            $prompt = $this->buildKBEnhancedPrompt($parameters, $recommendations);

            // 3. Generate content with Gemini
            $result = $this->gemini->generateText($prompt, [
                'config' => [
                    'temperature' => $parameters['creativity'] ?? 0.8,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            // 4. Parse and structure the output
            $content = $this->parseContentOutput($result['text']);

            return [
                'success' => true,
                'content' => $content,
                'kb_insights_used' => [
                    'recommendations' => $recommendations,
                    'brand_dna_applied' => true,
                ],
                'tokens_used' => $result['tokens_used'],
            ];

        } catch (\Exception $e) {
            Log::error('KB-enhanced content generation failed', [
                'org_id' => $orgId,
                'profile_group_id' => $profileGroupId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate social post using KB insights
     */
    public function generateSocialPost(
        string $orgId,
        string $profileGroupId,
        string $objective,
        string $platform,
        ?string $topic = null,
        array $options = []
    ): array {
        $parameters = array_merge([
            'objective' => $objective,
            'platform' => $platform,
            'topic' => $topic,
            'include_cta' => true,
            'include_hashtags' => $platform !== 'linkedin',
        ], $options);

        return $this->generateContent($orgId, $profileGroupId, $parameters);
    }

    /**
     * Generate ad copy using KB insights
     */
    public function generateAdCopy(
        string $orgId,
        string $profileGroupId,
        string $objective,
        string $productDescription,
        array $options = []
    ): array {
        // Get recommendations
        $recommendations = $this->kbService->getRecommendationsForCampaign(
            $orgId,
            $profileGroupId,
            $objective,
            $options['platform'] ?? null
        );

        // Build enhanced prompt
        $prompt = $this->buildAdCopyPrompt($objective, $productDescription, $recommendations, $options);

        $result = $this->gemini->generateText($prompt, [
            'config' => [
                'temperature' => 0.85,
                'maxOutputTokens' => 1024,
            ],
        ]);

        $content = $this->parseAdCopyOutput($result['text']);

        return [
            'headlines' => $content['headlines'],
            'descriptions' => $content['descriptions'],
            'ctas' => $content['ctas'],
            'primary_text' => $content['primary_text'],
            'kb_insights' => $recommendations,
            'tokens_used' => $result['tokens_used'],
        ];
    }

    /**
     * Generate content variations based on successful patterns
     */
    public function generateVariations(
        string $orgId,
        string $profileGroupId,
        string $originalContent,
        int $count = 3,
        ?string $objective = null
    ): array {
        // Get brand DNA
        $brandDNA = $this->brandDNAService->getBrandDNASummary($orgId, $profileGroupId);

        $prompt = $this->buildVariationsPrompt($originalContent, $brandDNA, $count, $objective);

        $result = $this->gemini->generateText($prompt, [
            'config' => [
                'temperature' => 0.9,
                'maxOutputTokens' => 2048,
            ],
        ]);

        $variations = $this->parseVariations($result['text'], $count);

        return [
            'original' => $originalContent,
            'variations' => $variations,
            'brand_dna_applied' => true,
            'tokens_used' => $result['tokens_used'],
        ];
    }

    /**
     * Get KB recommendations for content generation
     */
    private function getKBRecommendations(
        string $orgId,
        string $profileGroupId,
        array $parameters
    ): array {
        $objective = $parameters['objective'] ?? null;
        $platform = $parameters['platform'] ?? null;

        if (!$objective) {
            return [
                'example_posts' => [],
                'success_patterns' => [],
                'recommendations' => [],
            ];
        }

        return $this->kbService->getRecommendationsForCampaign(
            $orgId,
            $profileGroupId,
            $objective,
            $platform
        );
    }

    /**
     * Build KB-enhanced prompt for content generation
     */
    private function buildKBEnhancedPrompt(array $parameters, array $recommendations): string
    {
        $objective = $parameters['objective'] ?? 'engagement';
        $platform = $parameters['platform'] ?? 'social media';
        $topic = $parameters['topic'] ?? '';

        $prompt = "Generate compelling {$platform} content for a marketing campaign.\n\n";
        $prompt .= "CAMPAIGN OBJECTIVE: {$objective}\n";
        if ($topic) {
            $prompt .= "TOPIC: {$topic}\n";
        }

        // Add KB insights
        if (!empty($recommendations['success_patterns'])) {
            $prompt .= "\n=== BRAND DNA & SUCCESSFUL PATTERNS ===\n";
            $prompt .= "Based on analysis of your historical high-performing content:\n\n";

            // Add tone guidance
            if (!empty($recommendations['success_patterns']['common_tones'])) {
                $tones = array_keys($recommendations['success_patterns']['common_tones']);
                $prompt .= "PROVEN TONES: " . implode(', ', array_slice($tones, 0, 3)) . "\n";
            }

            // Add hook guidance
            if (!empty($recommendations['success_patterns']['common_hooks'])) {
                $hooks = array_keys($recommendations['success_patterns']['common_hooks']);
                $prompt .= "EFFECTIVE HOOKS: " . implode(', ', array_slice($hooks, 0, 3)) . "\n";
            }

            // Add CTA guidance
            if (!empty($recommendations['success_patterns']['common_ctas'])) {
                $ctas = array_keys($recommendations['success_patterns']['common_ctas']);
                $prompt .= "SUCCESSFUL CTAs: " . implode(', ', array_slice($ctas, 0, 3)) . "\n";
            }
        }

        // Add example posts
        if (!empty($recommendations['example_posts'])) {
            $prompt .= "\n=== HIGH-PERFORMING REFERENCE EXAMPLES ===\n";
            foreach (array_slice($recommendations['example_posts'], 0, 3) as $i => $post) {
                $prompt .= "Example " . ($i + 1) . " (Success Score: " . ($post['success_score'] * 100) . "%):\n";
                $prompt .= $post['content'] . "\n\n";
            }
        }

        // Add recommendations
        if (!empty($recommendations['recommendations'])) {
            $prompt .= "\n=== AI RECOMMENDATIONS ===\n";
            foreach ($recommendations['recommendations'] as $rec) {
                $prompt .= "• {$rec['suggestion']}\n";
            }
        }

        $prompt .= "\n=== GENERATION INSTRUCTIONS ===\n";
        $prompt .= "Create new content that:\n";
        $prompt .= "1. Aligns with the proven tones, hooks, and CTAs identified above\n";
        $prompt .= "2. Maintains the brand voice evident in the example posts\n";
        $prompt .= "3. Is optimized for {$platform} best practices\n";
        $prompt .= "4. Focuses on the campaign objective: {$objective}\n";
        $prompt .= "5. Is fresh and unique while staying on-brand\n\n";

        if ($parameters['include_hashtags'] ?? false) {
            $prompt .= "Include 3-5 relevant hashtags.\n";
        }

        if ($parameters['include_cta'] ?? false) {
            $prompt .= "Include a clear call-to-action based on the successful CTA patterns.\n";
        }

        $prompt .= "\nGenerate the content now:";

        return $prompt;
    }

    /**
     * Build ad copy prompt with KB insights
     */
    private function buildAdCopyPrompt(
        string $objective,
        string $productDescription,
        array $recommendations,
        array $options
    ): string {
        $prompt = "Generate professional ad copy for a marketing campaign.\n\n";
        $prompt .= "OBJECTIVE: {$objective}\n";
        $prompt .= "PRODUCT/SERVICE: {$productDescription}\n\n";

        // Add KB insights
        if (!empty($recommendations['success_patterns'])) {
            $prompt .= "=== YOUR BRAND'S PROVEN SUCCESS PATTERNS ===\n";

            if (!empty($recommendations['success_patterns']['common_tones'])) {
                $tones = array_keys($recommendations['success_patterns']['common_tones']);
                $prompt .= "Successful Tones: " . implode(', ', $tones) . "\n";
            }

            if (!empty($recommendations['success_patterns']['common_hooks'])) {
                $hooks = array_keys($recommendations['success_patterns']['common_hooks']);
                $prompt .= "Effective Opening Hooks: " . implode(', ', $hooks) . "\n";
            }
        }

        $prompt .= "\n=== GENERATE ===\n";
        $prompt .= "Create ad copy with:\n";
        $prompt .= "HEADLINES: 3 variations (max 30 characters each)\n";
        $prompt .= "DESCRIPTIONS: 3 variations (max 90 characters each)\n";
        $prompt .= "PRIMARY_TEXT: 1 compelling main copy (125-150 words)\n";
        $prompt .= "CTAS: 3 call-to-action options\n\n";

        $prompt .= "Use the proven success patterns above to inform your writing.\n";
        $prompt .= "Be persuasive, benefit-focused, and on-brand.\n\n";
        $prompt .= "Format your output with clear section headers.";

        return $prompt;
    }

    /**
     * Build variations prompt
     */
    private function buildVariationsPrompt(
        string $originalContent,
        array $brandDNA,
        int $count,
        ?string $objective
    ): string {
        $prompt = "Create {$count} variations of the following content.\n\n";
        $prompt .= "ORIGINAL CONTENT:\n{$originalContent}\n\n";

        if (!empty($brandDNA['top_tones'])) {
            $tones = array_column(array_slice($brandDNA['top_tones'], 0, 3), 'value');
            $prompt .= "BRAND TONES: " . implode(', ', $tones) . "\n";
        }

        if (!empty($brandDNA['top_hooks'])) {
            $hooks = array_column(array_slice($brandDNA['top_hooks'], 0, 3), 'value');
            $prompt .= "PREFERRED HOOKS: " . implode(', ', $hooks) . "\n";
        }

        $prompt .= "\nCreate {$count} variations that:\n";
        $prompt .= "1. Maintain the core message and intent\n";
        $prompt .= "2. Use different opening hooks and structures\n";
        $prompt .= "3. Stay consistent with the brand tones listed above\n";
        $prompt .= "4. Each should feel distinct yet on-brand\n\n";

        $prompt .= "Format: Number each variation (1, 2, 3, etc.) on separate lines.";

        return $prompt;
    }

    /**
     * Parse general content output
     */
    private function parseContentOutput(string $text): array
    {
        return [
            'text' => trim($text),
            'word_count' => str_word_count($text),
            'character_count' => strlen($text),
        ];
    }

    /**
     * Parse ad copy output
     */
    private function parseAdCopyOutput(string $text): array
    {
        $parsed = [
            'headlines' => [],
            'descriptions' => [],
            'ctas' => [],
            'primary_text' => '',
        ];

        // Extract headlines
        if (preg_match('/HEADLINES?:(.*?)(?=DESCRIPTIONS?:|PRIMARY_TEXT:|CTAs?:|$)/is', $text, $matches)) {
            $headlines = array_filter(array_map('trim', explode("\n", $matches[1])));
            $parsed['headlines'] = array_values(array_filter($headlines, fn($h) => !empty($h) && $h !== '-'));
        }

        // Extract descriptions
        if (preg_match('/DESCRIPTIONS?:(.*?)(?=PRIMARY_TEXT:|CTAs?:|$)/is', $text, $matches)) {
            $descriptions = array_filter(array_map('trim', explode("\n", $matches[1])));
            $parsed['descriptions'] = array_values(array_filter($descriptions, fn($d) => !empty($d) && $d !== '-'));
        }

        // Extract primary text
        if (preg_match('/PRIMARY_TEXT:(.*?)(?=CTAs?:|$)/is', $text, $matches)) {
            $parsed['primary_text'] = trim($matches[1]);
        }

        // Extract CTAs
        if (preg_match('/CTAs?:(.*?)$/is', $text, $matches)) {
            $ctas = array_filter(array_map('trim', explode("\n", $matches[1])));
            $parsed['ctas'] = array_values(array_filter($ctas, fn($c) => !empty($c) && $c !== '-'));
        }

        return $parsed;
    }

    /**
     * Parse variations
     */
    private function parseVariations(string $text, int $expectedCount): array
    {
        $variations = [];

        // Try numbered format first
        if (preg_match_all('/(\d+)[.:\)]?\s*(.+?)(?=\d+[.:\)]|$)/s', $text, $matches)) {
            foreach ($matches[2] as $variation) {
                $cleaned = trim($variation);
                if (!empty($cleaned)) {
                    $variations[] = $cleaned;
                }
            }
        }

        // Fallback: split by double newlines
        if (empty($variations)) {
            $parts = preg_split('/\n\s*\n/', $text);
            foreach ($parts as $part) {
                $cleaned = trim($part);
                if (!empty($cleaned)) {
                    $variations[] = $cleaned;
                }
            }
        }

        return array_slice($variations, 0, $expectedCount);
    }

    /**
     * Get content suggestions based on KB
     */
    public function getContentSuggestions(
        string $orgId,
        string $profileGroupId,
        string $objective,
        ?string $platform = null
    ): array {
        $recommendations = $this->kbService->getRecommendationsForCampaign(
            $orgId,
            $profileGroupId,
            $objective,
            $platform
        );

        $brandDNA = $this->brandDNAService->getBrandDNASummary($orgId, $profileGroupId);

        return [
            'objective' => $objective,
            'platform' => $platform,
            'recommended_tones' => array_column(array_slice($brandDNA['top_tones'] ?? [], 0, 5), 'value'),
            'recommended_hooks' => array_column(array_slice($brandDNA['top_hooks'] ?? [], 0, 5), 'value'),
            'recommended_ctas' => array_column(array_slice($brandDNA['top_ctas'] ?? [], 0, 5), 'value'),
            'recommended_themes' => array_column(array_slice($brandDNA['top_themes'] ?? [], 0, 5), 'value'),
            'example_posts' => $recommendations['example_posts'] ?? [],
            'ai_recommendations' => $recommendations['recommendations'] ?? [],
        ];
    }

    /**
     * Analyze content against brand DNA
     */
    public function analyzeContentFit(
        string $orgId,
        string $profileGroupId,
        string $content
    ): array {
        $brandDNA = $this->brandDNAService->getBrandDNASummary($orgId, $profileGroupId);

        // Build analysis prompt
        $prompt = "Analyze if this content aligns with the brand's proven DNA.\n\n";
        $prompt .= "BRAND DNA (from historical high-performing content):\n";

        if (!empty($brandDNA['top_tones'])) {
            $tones = array_column(array_slice($brandDNA['top_tones'], 0, 5), 'value');
            $prompt .= "Proven Tones: " . implode(', ', $tones) . "\n";
        }

        if (!empty($brandDNA['top_objectives'])) {
            $objectives = array_column(array_slice($brandDNA['top_objectives'], 0, 5), 'value');
            $prompt .= "Common Objectives: " . implode(', ', $objectives) . "\n";
        }

        $prompt .= "\nCONTENT TO ANALYZE:\n{$content}\n\n";
        $prompt .= "Provide:\n";
        $prompt .= "1. ALIGNMENT_SCORE: 0-100% how well this matches the brand DNA\n";
        $prompt .= "2. MATCHES: What aspects align well\n";
        $prompt .= "3. GAPS: What's missing or off-brand\n";
        $prompt .= "4. SUGGESTIONS: How to improve alignment\n";

        $result = $this->gemini->generateText($prompt, [
            'config' => ['temperature' => 0.3],
        ]);

        return [
            'content' => $content,
            'analysis' => $result['text'],
            'brand_dna_reference' => [
                'top_tones' => $brandDNA['top_tones'] ?? [],
                'top_objectives' => $brandDNA['top_objectives'] ?? [],
            ],
        ];
    }
}
