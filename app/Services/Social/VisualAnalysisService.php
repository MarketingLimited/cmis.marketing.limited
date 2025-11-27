<?php

namespace App\Services\Social;

use App\Models\Social\MediaAsset;
use App\Services\AI\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Visual Analysis Service
 *
 * Analyzes visual content (images/videos) using Google Gemini Vision
 * to extract design elements, layout, color palettes, typography, and more.
 */
class VisualAnalysisService
{
    private GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Analyze a media asset comprehensively
     */
    public function analyzeMediaAsset(MediaAsset $asset): array
    {
        try {
            $asset->startAnalysis();

            // Get image data
            $imageData = $this->getImageData($asset);

            if (!$imageData) {
                throw new \Exception('Could not retrieve image data');
            }

            // Run comprehensive visual analysis
            $analysis = $this->runComprehensiveAnalysis($imageData, $asset->media_type);

            // Process and structure the results
            $processedResults = $this->processAnalysisResults($analysis);

            // Mark as complete
            $asset->completeAnalysis($processedResults);

            return $processedResults;

        } catch (\Exception $e) {
            Log::error('Visual analysis failed for asset: ' . $asset->asset_id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $asset->failAnalysis($e->getMessage());

            throw $e;
        }
    }

    /**
     * Run comprehensive visual analysis using Gemini
     */
    private function runComprehensiveAnalysis(string $imageData, string $mediaType): array
    {
        $prompt = $this->buildAnalysisPrompt($mediaType);

        $response = $this->gemini->analyzeImage($imageData, $prompt);

        // Parse JSON response from Gemini
        return $this->parseGeminiResponse($response);
    }

    /**
     * Build comprehensive analysis prompt
     */
    private function buildAnalysisPrompt(string $mediaType): string
    {
        return <<<PROMPT
Analyze this {$mediaType} comprehensively and provide a detailed JSON response with the following structure:

{
  "visual_caption": "A concise 1-sentence description of what you see",
  "scene_description": "Detailed description of the scene, setting, and context",
  "detected_objects": [
    {"object": "object name", "confidence": 0.95}
  ],
  "detected_people": [
    {"description": "person description", "position": "location in frame"}
  ],
  "camera_angle": "front/side/top/low/high angle",
  "depth_of_field": "shallow/deep",
  "lighting": "natural/studio/dramatic/soft/harsh",

  "text_blocks": [
    {
      "text": "extracted text",
      "position": "top/center/bottom",
      "size": "large/medium/small",
      "style": "bold/normal/italic",
      "language": "en/ar"
    }
  ],

  "design_prompt": "A detailed prompt that could recreate this design using AI image generation",

  "style_profile": {
    "overall_style": "minimalist/modern/vintage/professional/playful",
    "design_complexity": "simple/moderate/complex",
    "brand_feel": "corporate/casual/luxury/fun"
  },

  "layout_map": {
    "composition": "rule_of_thirds/centered/asymmetric/grid",
    "focal_point": "description of main focus",
    "visual_hierarchy": ["primary element", "secondary element"]
  },

  "element_positions": {
    "headline": {"position": "top_third", "alignment": "center"},
    "cta_button": {"position": "bottom_right", "size": "medium"},
    "product": {"position": "center", "prominence": "high"}
  },

  "color_palette": {
    "dominant": ["#HEX1", "#HEX2", "#HEX3"],
    "accent": ["#HEX4", "#HEX5"],
    "scheme": "monochrome/analogous/complementary/triadic/vibrant/muted",
    "color_psychology": "energy/calm/trust/luxury"
  },

  "typography": {
    "fonts": ["Sans-serif", "Modern"],
    "sizes": ["large_headline", "medium_body", "small_caption"],
    "style": "bold_clean/elegant/playful/professional",
    "readability": "high/medium/low"
  },

  "art_direction": "minimalist/maximalist/elegant/bold/organic/geometric",
  "mood": "energetic/calm/inspiring/professional/friendly/dramatic",
  "visual_message": "What message does this visual convey?",
  "look_and_feel": "Overall aesthetic description",

  "imagery_and_graphics": {
    "image_type": "photograph/illustration/3d_render/mixed",
    "image_style": "realistic/stylized/abstract",
    "graphic_elements": ["icons", "shapes", "patterns"]
  },

  "icons_and_symbols": [
    {"icon": "checkmark", "meaning": "approval/success"}
  ],

  "composition": "balanced/dynamic/static/flowing",
  "background_style": "solid_color/gradient/pattern/image/transparent",

  "highlight_elements": ["element that stands out"],
  "deemphasize_elements": ["element that is subtle"],

  "brand_consistency_score": 0.85,
  "style_deviations": ["Any inconsistencies with typical brand style"]
}

Return ONLY valid JSON, no markdown formatting or explanations.
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
            Log::warning('Failed to parse Gemini response as JSON', [
                'error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 500),
            ]);

            // Return minimal fallback structure
            return [
                'visual_caption' => 'Visual content',
                'scene_description' => 'Unable to parse analysis',
            ];
        }

        return $data;
    }

    /**
     * Process analysis results into database format
     */
    private function processAnalysisResults(array $analysis): array
    {
        return [
            'visual_caption' => $analysis['visual_caption'] ?? null,
            'scene_description' => $analysis['scene_description'] ?? null,
            'detected_objects' => $analysis['detected_objects'] ?? null,
            'detected_people' => $analysis['detected_people'] ?? null,
            'camera_angle' => $analysis['camera_angle'] ?? null,
            'depth_of_field' => $analysis['depth_of_field'] ?? null,
            'lighting' => $analysis['lighting'] ?? null,
            'text_blocks' => $analysis['text_blocks'] ?? null,
            'extracted_text' => $this->extractAllText($analysis['text_blocks'] ?? []),
            'primary_language' => $this->detectPrimaryLanguage($analysis['text_blocks'] ?? []),
            'design_prompt' => $analysis['design_prompt'] ?? null,
            'style_profile' => $analysis['style_profile'] ?? null,
            'layout_map' => $analysis['layout_map'] ?? null,
            'element_positions' => $analysis['element_positions'] ?? null,
            'color_palette' => $analysis['color_palette'] ?? null,
            'typography' => $analysis['typography'] ?? null,
            'art_direction' => $analysis['art_direction'] ?? null,
            'mood' => $analysis['mood'] ?? null,
            'visual_message' => $analysis['visual_message'] ?? null,
            'look_and_feel' => $analysis['look_and_feel'] ?? null,
            'imagery_and_graphics' => $analysis['imagery_and_graphics'] ?? null,
            'icons_and_symbols' => $analysis['icons_and_symbols'] ?? null,
            'composition' => $analysis['composition'] ?? null,
            'background_style' => $analysis['background_style'] ?? null,
            'highlight_elements' => $analysis['highlight_elements'] ?? null,
            'deemphasize_elements' => $analysis['deemphasize_elements'] ?? null,
            'brand_consistency_score' => $analysis['brand_consistency_score'] ?? null,
            'style_deviations' => $analysis['style_deviations'] ?? null,
        ];
    }

    /**
     * Extract all text from text blocks into single string
     */
    private function extractAllText(array $textBlocks): ?string
    {
        if (empty($textBlocks)) {
            return null;
        }

        $texts = array_map(fn($block) => $block['text'] ?? '', $textBlocks);
        return implode("\n", array_filter($texts));
    }

    /**
     * Detect primary language from text blocks
     */
    private function detectPrimaryLanguage(array $textBlocks): ?string
    {
        if (empty($textBlocks)) {
            return null;
        }

        $languages = array_filter(array_map(fn($block) => $block['language'] ?? null, $textBlocks));

        if (empty($languages)) {
            return 'en'; // Default to English
        }

        // Return most common language
        $counts = array_count_values($languages);
        arsort($counts);
        return array_key_first($counts);
    }

    /**
     * Get image data (from storage or URL)
     */
    private function getImageData(MediaAsset $asset): ?string
    {
        // Try storage path first
        if ($asset->storage_path && Storage::exists($asset->storage_path)) {
            return base64_encode(Storage::get($asset->storage_path));
        }

        // Try original URL
        if ($asset->original_url) {
            try {
                $imageContent = file_get_contents($asset->original_url);
                return base64_encode($imageContent);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch image from URL', [
                    'url' => $asset->original_url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Extract color palette from image (fallback method)
     */
    public function extractColorPalette(MediaAsset $asset, int $numColors = 5): array
    {
        // This would use a color extraction library or service
        // For now, return placeholder
        return [
            'dominant' => ['#FF6B6B', '#4ECDC4', '#45B7D1'],
            'accent' => ['#FFA07A', '#98D8C8'],
            'scheme' => 'vibrant',
        ];
    }

    /**
     * Generate design prompt for AI recreation
     */
    public function generateDesignPrompt(MediaAsset $asset): string
    {
        $analysis = [
            'style' => $asset->art_direction ?? 'modern',
            'mood' => $asset->mood ?? 'professional',
            'colors' => $asset->color_palette['scheme'] ?? 'vibrant',
            'composition' => $asset->composition ?? 'balanced',
        ];

        return sprintf(
            "Create a %s, %s design with %s colors. Use %s composition. %s",
            $analysis['style'],
            $analysis['mood'],
            $analysis['colors'],
            $analysis['composition'],
            $asset->scene_description ?? ''
        );
    }
}
