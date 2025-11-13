<?php

namespace App\Services;

use App\Models\Creative\CreativeBrief;
use App\Models\Creative\CreativeOutput;
use App\Models\Knowledge\DirectionMapping;
use App\Models\Knowledge\IntentMapping;
use App\Models\Knowledge\PurposeMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $contextService;
    protected $embeddingService;

    public function __construct(
        ContextService $contextService,
        EmbeddingService $embeddingService
    ) {
        $this->contextService = $contextService;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Generate content from brief
     */
    public function generateContentFromBrief(CreativeBrief $brief, array $options = []): ?CreativeOutput
    {
        // Validate brief structure
        if (!$brief->isValid()) {
            Log::error('Invalid brief structure', ['brief_id' => $brief->brief_id]);
            return null;
        }

        // Get campaign contexts
        $contexts = $this->contextService->mergeContextsForAI($brief->campaign_id);

        // Prepare prompt
        $prompt = $this->buildPromptFromBrief($brief, $contexts, $options);

        // Generate content
        $generatedContent = $this->callAIAPI($prompt, $options);

        if (!$generatedContent) {
            return null;
        }

        // Create creative output
        return CreativeOutput::create([
            'output_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $brief->org_id,
            'brief_id' => $brief->brief_id,
            'campaign_id' => $brief->campaign_id,
            'output_type' => $options['output_type'] ?? 'text',
            'content' => $generatedContent,
            'model_used' => $options['model'] ?? 'default',
            'prompt_tokens' => $generatedContent['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $generatedContent['usage']['completion_tokens'] ?? 0,
            'quality_score' => null,
            'status' => 'draft',
            'metadata' => [
                'contexts_used' => array_keys($contexts),
                'options' => $options,
            ],
        ]);
    }

    /**
     * Generate content variations
     */
    public function generateVariations(string $content, int $count = 3, array $options = []): array
    {
        $prompt = $this->buildVariationPrompt($content, $options);

        $variations = [];

        for ($i = 0; $i < $count; $i++) {
            $result = $this->callAIAPI($prompt, array_merge($options, ['temperature' => 0.7 + ($i * 0.1)]));

            if ($result && isset($result['content'])) {
                $variations[] = $result['content'];
            }
        }

        return $variations;
    }

    /**
     * Classify intent
     */
    public function classifyIntent(string $text): ?array
    {
        $embedding = $this->embeddingService->generateEmbedding($text);

        if (!$embedding) {
            return null;
        }

        // Perform semantic search against intent mappings
        $intents = IntentMapping::active()->get();

        $scores = [];

        foreach ($intents as $intent) {
            // Calculate similarity (simplified - in production use proper vector similarity)
            $score = $this->calculateSimilarity($text, $intent->example_phrases);
            $scores[$intent->intent_code] = [
                'intent' => $intent,
                'score' => $score,
            ];
        }

        // Sort by score
        uasort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        $topIntent = reset($scores);

        if ($topIntent && $topIntent['score'] >= $topIntent['intent']->confidence_threshold) {
            return [
                'intent_code' => $topIntent['intent']->intent_code,
                'intent_label' => $topIntent['intent']->intent_label,
                'confidence' => $topIntent['score'],
                'related_intents' => $topIntent['intent']->related_intents,
            ];
        }

        return null;
    }

    /**
     * Get direction for intent
     */
    public function getDirectionForIntent(string $intentCode): ?DirectionMapping
    {
        return DirectionMapping::findByCode($intentCode);
    }

    /**
     * Get purpose mapping
     */
    public function getPurpose(string $purposeCode): ?PurposeMapping
    {
        return PurposeMapping::findByCode($purposeCode);
    }

    /**
     * Optimize content
     */
    public function optimizeContent(string $content, array $criteria = []): ?string
    {
        $prompt = $this->buildOptimizationPrompt($content, $criteria);

        $result = $this->callAIAPI($prompt, ['temperature' => 0.3]);

        return $result['content'] ?? null;
    }

    /**
     * Generate headline variations
     */
    public function generateHeadlines(string $context, int $count = 5): array
    {
        $prompt = "Generate {$count} compelling headlines based on the following context:\n\n{$context}\n\nHeadlines:";

        $result = $this->callAIAPI($prompt, ['temperature' => 0.8, 'max_tokens' => 500]);

        if (!$result || !isset($result['content'])) {
            return [];
        }

        // Parse headlines from response
        $headlines = $this->parseHeadlines($result['content']);

        return array_slice($headlines, 0, $count);
    }

    /**
     * Generate call-to-action
     */
    public function generateCTA(array $context): array
    {
        $prompt = $this->buildCTAPrompt($context);

        $result = $this->callAIAPI($prompt, ['temperature' => 0.7]);

        if (!$result || !isset($result['content'])) {
            return [];
        }

        return $this->parseCTAs($result['content']);
    }

    /**
     * Analyze sentiment
     */
    public function analyzeSentiment(string $text): array
    {
        $prompt = "Analyze the sentiment of the following text and provide a score from -1 (very negative) to 1 (very positive), along with a brief explanation:\n\n{$text}";

        $result = $this->callAIAPI($prompt, ['temperature' => 0.2]);

        if (!$result || !isset($result['content'])) {
            return ['score' => 0, 'sentiment' => 'neutral', 'explanation' => 'Unable to analyze'];
        }

        return $this->parseSentimentAnalysis($result['content']);
    }

    /**
     * Build prompt from brief
     */
    protected function buildPromptFromBrief(CreativeBrief $brief, array $contexts, array $options): string
    {
        $briefData = $brief->brief_data;

        $prompt = "Generate marketing content based on the following brief:\n\n";

        // Add brief details
        if (isset($briefData['objective'])) {
            $prompt .= "Objective: {$briefData['objective']}\n";
        }

        if (isset($briefData['target_audience'])) {
            $prompt .= "Target Audience: {$briefData['target_audience']}\n";
        }

        // Add context information
        if ($contexts['brand_voice']) {
            $prompt .= "Brand Voice: {$contexts['brand_voice']}\n";
        }

        if ($contexts['tone']) {
            $prompt .= "Tone: {$contexts['tone']}\n";
        }

        if ($contexts['value_proposition']) {
            $prompt .= "Value Proposition: {$contexts['value_proposition']}\n";
        }

        // Add specific instructions
        if (isset($options['format'])) {
            $prompt .= "\nFormat: {$options['format']}\n";
        }

        if (isset($options['length'])) {
            $prompt .= "Length: {$options['length']} words\n";
        }

        $prompt .= "\nGenerate the content:";

        return $prompt;
    }

    /**
     * Build variation prompt
     */
    protected function buildVariationPrompt(string $content, array $options): string
    {
        $prompt = "Create a variation of the following content while maintaining the core message:\n\n{$content}\n\n";

        if (isset($options['tone'])) {
            $prompt .= "Tone: {$options['tone']}\n";
        }

        $prompt .= "Variation:";

        return $prompt;
    }

    /**
     * Build optimization prompt
     */
    protected function buildOptimizationPrompt(string $content, array $criteria): string
    {
        $prompt = "Optimize the following content for:\n";

        foreach ($criteria as $criterion => $value) {
            $prompt .= "- {$criterion}: {$value}\n";
        }

        $prompt .= "\nOriginal content:\n{$content}\n\nOptimized content:";

        return $prompt;
    }

    /**
     * Build CTA prompt
     */
    protected function buildCTAPrompt(array $context): string
    {
        $prompt = "Generate 3 compelling call-to-action statements based on:\n\n";

        foreach ($context as $key => $value) {
            $prompt .= "{$key}: {$value}\n";
        }

        $prompt .= "\nCTAs:";

        return $prompt;
    }

    /**
     * Call AI API
     */
    protected function callAIAPI(string $prompt, array $options = []): ?array
    {
        // This is a placeholder - implement actual API calls
        // For OpenAI, Anthropic, etc.

        try {
            // Example for OpenAI
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $options['model'] ?? 'gpt-4',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'content' => $data['choices'][0]['message']['content'] ?? '',
                    'usage' => $data['usage'] ?? [],
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('AI API call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Calculate similarity
     */
    protected function calculateSimilarity(string $text, array $examples): float
    {
        // Simplified similarity - in production use proper vector similarity
        $text = strtolower($text);
        $score = 0;

        foreach ($examples as $example) {
            $example = strtolower($example);
            similar_text($text, $example, $percent);
            $score = max($score, $percent / 100);
        }

        return $score;
    }

    /**
     * Parse headlines
     */
    protected function parseHeadlines(string $content): array
    {
        $lines = explode("\n", $content);
        $headlines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            // Remove numbering like "1.", "2.", etc.
            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);

            if (!empty($line) && strlen($line) > 10) {
                $headlines[] = $line;
            }
        }

        return $headlines;
    }

    /**
     * Parse CTAs
     */
    protected function parseCTAs(string $content): array
    {
        return $this->parseHeadlines($content); // Similar parsing logic
    }

    /**
     * Parse sentiment analysis
     */
    protected function parseSentimentAnalysis(string $content): array
    {
        // Default values
        $result = [
            'score' => 0,
            'sentiment' => 'neutral',
            'explanation' => $content,
        ];

        // Try to extract score
        if (preg_match('/(-?\d+\.?\d*)\s*(very positive|positive|neutral|negative|very negative)?/i', $content, $matches)) {
            $result['score'] = floatval($matches[1]);

            if (isset($matches[2])) {
                $result['sentiment'] = strtolower($matches[2]);
            } elseif ($result['score'] > 0.5) {
                $result['sentiment'] = 'positive';
            } elseif ($result['score'] < -0.5) {
                $result['sentiment'] = 'negative';
            }
        }

        return $result;
    }
}
