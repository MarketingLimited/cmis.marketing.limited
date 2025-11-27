<?php

namespace App\Services\Social;

use App\Models\Social\SocialPost;
use App\Models\Social\BrandKnowledgeDimension;
use App\Services\AI\GeminiService;
use Illuminate\Support\Facades\Log;

/**
 * Brand DNA Analysis Service
 *
 * Extracts marketing DNA from social post content using Google Gemini.
 * Identifies objectives, tones, hooks, CTAs, emotional triggers, and more.
 */
class BrandDNAAnalysisService
{
    private GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Analyze a social post and extract all marketing dimensions
     */
    public function analyzePost(SocialPost $post): array
    {
        try {
            if (!$post->content) {
                return [
                    'dimensions' => [],
                    'extraction_summary' => 'No content to analyze',
                ];
            }

            // Build comprehensive analysis prompt
            $prompt = $this->buildBrandDNAPrompt($post);

            // Call Gemini API
            $response = $this->gemini->generateText($prompt, [
                'config' => [
                    'temperature' => 0.3, // Lower temperature for more consistent extraction
                    'maxOutputTokens' => 4096,
                ],
            ]);

            // Parse JSON response
            $dimensions = $this->parseGeminiResponse($response['text']);

            // Store extracted dimensions in post
            $this->storeExtractedDimensions($post, $dimensions);

            return [
                'dimensions' => $dimensions,
                'tokens_used' => $response['tokens_used'],
                'extraction_summary' => $this->generateExtractionSummary($dimensions),
            ];

        } catch (\Exception $e) {
            Log::error('Brand DNA analysis failed for post: ' . $post->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Batch analyze multiple posts
     */
    public function analyzePosts(array $posts): array
    {
        $results = [];
        $totalTokens = 0;

        foreach ($posts as $post) {
            try {
                $result = $this->analyzePost($post);
                $results[$post->id] = $result;
                $totalTokens += $result['tokens_used'];

                // Small delay to respect rate limits (30 req/min)
                usleep(2000000); // 2 seconds = 30 requests per minute

            } catch (\Exception $e) {
                Log::warning("Failed to analyze post {$post->id}", [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return [
            'analyzed_count' => count($results),
            'total_tokens' => $totalTokens,
            'results' => $results,
        ];
    }

    /**
     * Build comprehensive brand DNA extraction prompt
     */
    private function buildBrandDNAPrompt(SocialPost $post): string
    {
        $content = $post->content;
        $platform = $post->platform ?? $post->provider ?? 'unknown';

        return <<<PROMPT
Analyze this social media post and extract ALL marketing dimensions comprehensively.
Return ONLY valid JSON, no markdown formatting or explanations.

POST CONTENT:
{$content}

PLATFORM: {$platform}

Extract the following dimensions with confidence scores (0.0-1.0):

{
  "strategy": {
    "marketing_objectives": [
      {"value": "brand_awareness", "confidence": 0.9, "evidence": "specific phrase/element"},
      {"value": "engagement", "confidence": 0.8, "evidence": "..."}
    ],
    "value_propositions": [
      {"value": "convenience", "confidence": 0.85, "evidence": "..."}
    ],
    "positioning": [
      {"value": "premium", "confidence": 0.7, "evidence": "..."}
    ]
  },

  "messaging": {
    "tones": [
      {"value": "professional", "confidence": 0.9, "evidence": "formal language"},
      {"value": "friendly", "confidence": 0.75, "evidence": "casual expressions"}
    ],
    "hooks": [
      {"value": "question_based", "confidence": 0.85, "evidence": "opening question"},
      {"value": "stat_driven", "confidence": 0.8, "evidence": "uses statistics"}
    ],
    "ctas": [
      {"value": "learn_more", "confidence": 0.9, "evidence": "'Learn More' button"},
      {"value": "sign_up", "confidence": 0.7, "evidence": "..."}
    ],
    "emotional_triggers": [
      {"value": "curiosity", "confidence": 0.85, "evidence": "intriguing question"},
      {"value": "urgency", "confidence": 0.6, "evidence": "time-sensitive language"}
    ]
  },

  "creative": {
    "storytelling_style": [
      {"value": "testimonial", "confidence": 0.8, "evidence": "customer story"},
      {"value": "problem_solution", "confidence": 0.75, "evidence": "..."}
    ],
    "content_formats": [
      {"value": "carousel", "confidence": 0.9, "evidence": "multiple images"},
      {"value": "single_image", "confidence": 0.3, "evidence": "..."}
    ],
    "content_types": [
      {"value": "educational", "confidence": 0.8, "evidence": "teaching concept"},
      {"value": "promotional", "confidence": 0.6, "evidence": "..."}
    ]
  },

  "audience": {
    "target_segments": [
      {"value": "small_business_owners", "confidence": 0.85, "evidence": "language used"},
      {"value": "professionals", "confidence": 0.7, "evidence": "..."}
    ],
    "personas": [
      {"value": "busy_professional", "confidence": 0.8, "evidence": "time-saving messaging"}
    ],
    "behaviors": [
      {"value": "solution_seekers", "confidence": 0.75, "evidence": "problem-oriented"}
    ]
  },

  "funnel": {
    "funnel_stage": [
      {"value": "awareness", "confidence": 0.8, "evidence": "educational content"},
      {"value": "consideration", "confidence": 0.6, "evidence": "..."}
    ],
    "intent_signals": [
      {"value": "informational", "confidence": 0.85, "evidence": "how-to content"},
      {"value": "commercial", "confidence": 0.4, "evidence": "..."}
    ]
  },

  "content": {
    "themes": [
      {"value": "innovation", "confidence": 0.9, "evidence": "mentions new technology"},
      {"value": "efficiency", "confidence": 0.75, "evidence": "time-saving benefits"}
    ],
    "topics": [
      {"value": "digital_marketing", "confidence": 0.85, "evidence": "keywords"},
      {"value": "automation", "confidence": 0.7, "evidence": "..."}
    ],
    "narratives": [
      {"value": "transformation", "confidence": 0.8, "evidence": "before/after story"}
    ]
  },

  "format": {
    "post_format": [
      {"value": "carousel", "confidence": 0.9, "evidence": "multiple slides"},
      {"value": "video", "confidence": 0.1, "evidence": "..."}
    ],
    "media_type": [
      {"value": "image", "confidence": 0.9, "evidence": "photo content"},
      {"value": "graphic", "confidence": 0.5, "evidence": "..."}
    ]
  },

  "performance_indicators": {
    "engagement_drivers": [
      {"value": "visual_appeal", "confidence": 0.85, "evidence": "high-quality image"},
      {"value": "relatable_content", "confidence": 0.8, "evidence": "common pain point"}
    ],
    "virality_factors": [
      {"value": "shareability", "confidence": 0.7, "evidence": "useful info to share"},
      {"value": "emotional_resonance", "confidence": 0.65, "evidence": "..."}
    ]
  }
}

IMPORTANT EXTRACTION RULES:
1. Only include dimensions with confidence >= 0.5
2. Provide specific evidence from the post content for each dimension
3. Assign realistic confidence scores based on strength of signals
4. A post can have multiple values per dimension type
5. Consider platform context (Instagram vs LinkedIn vs Twitter, etc.)
6. Look for implicit signals, not just explicit mentions
7. Return empty arrays [] for dimension types not detected

Return ONLY the JSON structure above, no other text.
PROMPT;
    }

    /**
     * Parse Gemini JSON response
     */
    private function parseGeminiResponse(string $response): array
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*|\s*```/', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse Gemini brand DNA response as JSON', [
                'error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 500),
            ]);

            // Return minimal fallback structure
            return [
                'strategy' => [],
                'messaging' => [],
                'creative' => [],
                'audience' => [],
                'funnel' => [],
                'content' => [],
                'format' => [],
                'performance_indicators' => [],
            ];
        }

        return $data;
    }

    /**
     * Store extracted dimensions in post model
     */
    private function storeExtractedDimensions(SocialPost $post, array $dimensions): void
    {
        // Extract specific types for quick access
        $post->update([
            'extracted_objectives' => $this->extractDimensionValues($dimensions, 'strategy', 'marketing_objectives'),
            'extracted_tones' => $this->extractDimensionValues($dimensions, 'messaging', 'tones'),
            'extracted_hooks' => $this->extractDimensionValues($dimensions, 'messaging', 'hooks'),
            'extracted_ctas' => $this->extractDimensionValues($dimensions, 'messaging', 'ctas'),
            'extracted_emotions' => $this->extractDimensionValues($dimensions, 'messaging', 'emotional_triggers'),
            'extracted_entities' => array_merge(
                $this->extractDimensionValues($dimensions, 'audience', 'target_segments'),
                $this->extractDimensionValues($dimensions, 'content', 'themes')
            ),
        ]);
    }

    /**
     * Extract specific dimension values from nested structure
     */
    private function extractDimensionValues(array $dimensions, string $category, string $type): array
    {
        if (!isset($dimensions[$category][$type])) {
            return [];
        }

        return array_map(
            fn($item) => $item['value'] ?? '',
            array_filter(
                $dimensions[$category][$type],
                fn($item) => isset($item['confidence']) && $item['confidence'] >= 0.5
            )
        );
    }

    /**
     * Generate extraction summary
     */
    private function generateExtractionSummary(array $dimensions): string
    {
        $totalDimensions = 0;
        $categories = [];

        foreach ($dimensions as $category => $types) {
            $categoryCount = 0;
            foreach ($types as $type => $values) {
                if (is_array($values)) {
                    $count = count(array_filter($values, fn($v) => ($v['confidence'] ?? 0) >= 0.5));
                    $categoryCount += $count;
                    $totalDimensions += $count;
                }
            }
            if ($categoryCount > 0) {
                $categories[] = $category . ' (' . $categoryCount . ')';
            }
        }

        if ($totalDimensions === 0) {
            return 'No significant marketing dimensions detected';
        }

        return "Extracted {$totalDimensions} dimensions across " . count($categories) . " categories: " . implode(', ', $categories);
    }

    /**
     * Create BrandKnowledgeDimension records from analysis
     */
    public function storeDimensionsAsRecords(
        SocialPost $post,
        array $dimensions,
        ?string $profileGroupId = null
    ): int {
        $stored = 0;

        foreach ($dimensions as $category => $types) {
            foreach ($types as $type => $values) {
                if (!is_array($values)) {
                    continue;
                }

                foreach ($values as $item) {
                    if (!isset($item['value']) || !isset($item['confidence'])) {
                        continue;
                    }

                    if ($item['confidence'] < 0.5) {
                        continue; // Skip low-confidence extractions
                    }

                    BrandKnowledgeDimension::create([
                        'org_id' => $post->org_id,
                        'profile_group_id' => $profileGroupId ?? $post->profile_group_id,
                        'post_id' => $post->id,
                        'dimension_category' => $category,
                        'dimension_type' => $type,
                        'dimension_value' => $item['value'],
                        'dimension_details' => [
                            'evidence' => $item['evidence'] ?? null,
                            'extraction_method' => 'gemini_analysis',
                        ],
                        'confidence_score' => $item['confidence'],
                        'is_core_dna' => $item['confidence'] >= 0.8, // High confidence = core DNA
                        'frequency_count' => 1,
                        'first_seen_at' => now(),
                        'last_seen_at' => now(),
                        'platform' => $post->platform ?? $post->provider,
                        'status' => 'active',
                    ]);

                    $stored++;
                }
            }
        }

        return $stored;
    }

    /**
     * Consolidate dimensions from multiple posts to find core brand DNA
     */
    public function consolidateCoreDNA(string $orgId, string $profileGroupId, int $minFrequency = 3): array
    {
        // Get all dimensions for this profile group
        $dimensions = BrandKnowledgeDimension::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->where('is_core_dna', true)
            ->get()
            ->groupBy(['dimension_category', 'dimension_type', 'dimension_value']);

        $coreDNA = [];

        foreach ($dimensions as $category => $types) {
            foreach ($types as $type => $values) {
                foreach ($values as $value => $records) {
                    $count = $records->count();
                    $avgConfidence = $records->avg('confidence_score');
                    $avgSuccess = $records->avg('avg_success_score');

                    if ($count >= $minFrequency) {
                        $coreDNA[] = [
                            'category' => $category,
                            'type' => $type,
                            'value' => $value,
                            'frequency' => $count,
                            'avg_confidence' => round($avgConfidence, 4),
                            'avg_success_score' => round($avgSuccess, 4),
                            'is_success_driver' => $avgSuccess >= 0.7,
                        ];
                    }
                }
            }
        }

        // Sort by frequency and confidence
        usort($coreDNA, function ($a, $b) {
            $freqDiff = $b['frequency'] - $a['frequency'];
            if ($freqDiff !== 0) return $freqDiff;
            return $b['avg_confidence'] <=> $a['avg_confidence'];
        });

        return $coreDNA;
    }

    /**
     * Get brand DNA summary for a profile group
     */
    public function getBrandDNASummary(string $orgId, string $profileGroupId): array
    {
        $coreDNA = $this->consolidateCoreDNA($orgId, $profileGroupId);

        return [
            'total_dimensions' => BrandKnowledgeDimension::where('org_id', $orgId)
                ->where('profile_group_id', $profileGroupId)
                ->count(),
            'core_dna_count' => count($coreDNA),
            'core_dna' => $coreDNA,
            'top_objectives' => $this->getTopDimensions($orgId, $profileGroupId, 'strategy', 'marketing_objectives', 5),
            'top_tones' => $this->getTopDimensions($orgId, $profileGroupId, 'messaging', 'tones', 5),
            'top_hooks' => $this->getTopDimensions($orgId, $profileGroupId, 'messaging', 'hooks', 5),
            'top_ctas' => $this->getTopDimensions($orgId, $profileGroupId, 'messaging', 'ctas', 5),
            'top_themes' => $this->getTopDimensions($orgId, $profileGroupId, 'content', 'themes', 5),
        ];
    }

    /**
     * Get top dimensions by frequency for a specific type
     */
    private function getTopDimensions(
        string $orgId,
        string $profileGroupId,
        string $category,
        string $type,
        int $limit = 5
    ): array {
        return BrandKnowledgeDimension::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->where('dimension_category', $category)
            ->where('dimension_type', $type)
            ->orderBy('frequency_count', 'desc')
            ->orderBy('avg_success_score', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($d) => [
                'value' => $d->dimension_value,
                'frequency' => $d->frequency_count,
                'confidence' => $d->confidence_score,
                'success_score' => $d->avg_success_score,
            ])
            ->toArray();
    }
}
