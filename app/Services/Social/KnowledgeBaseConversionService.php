<?php

namespace App\Services\Social;

use App\Models\Social\SocialPost;
use App\Models\Social\BrandKnowledgeDimension;
use App\Models\Social\BrandKnowledgeConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Knowledge Base Conversion Service
 *
 * Manages conversion of historical content into usable brand knowledge base.
 * Handles both manual curation and automatic workflows for KB building.
 */
class KnowledgeBaseConversionService
{
    private BrandDNAAnalysisService $brandDNAAnalysis;

    public function __construct(BrandDNAAnalysisService $brandDNAAnalysis)
    {
        $this->brandDNAAnalysis = $brandDNAAnalysis;
    }

    /**
     * Add post(s) to knowledge base manually
     */
    public function addToKnowledgeBase(
        $posts,
        ?string $notes = null,
        ?string $userId = null
    ): array {
        $posts = is_array($posts) ? collect($posts) : collect([$posts]);
        $added = 0;
        $skipped = 0;

        foreach ($posts as $post) {
            if ($post->is_in_knowledge_base) {
                $skipped++;
                continue;
            }

            try {
                $post->addToKnowledgeBase();

                // Update metadata
                $metadata = $post->metadata ?? [];
                $metadata['kb_added_by'] = $userId;
                $metadata['kb_added_at'] = now()->toIso8601String();
                $metadata['kb_notes'] = $notes;
                $post->update(['metadata' => $metadata]);

                $added++;

                // Update config stats
                if ($post->profile_group_id) {
                    $config = BrandKnowledgeConfig::where('org_id', $post->org_id)
                        ->where('profile_group_id', $post->profile_group_id)
                        ->first();

                    if ($config) {
                        $config->markKnowledgeBaseUpdated();
                    }
                }

            } catch (\Exception $e) {
                Log::warning("Failed to add post {$post->id} to KB", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'added' => $added,
            'skipped' => $skipped,
            'total' => $posts->count(),
        ];
    }

    /**
     * Remove post(s) from knowledge base
     */
    public function removeFromKnowledgeBase($posts, ?string $reason = null): array
    {
        $posts = is_array($posts) ? collect($posts) : collect([$posts]);
        $removed = 0;

        foreach ($posts as $post) {
            if (!$post->is_in_knowledge_base) {
                continue;
            }

            try {
                $post->removeFromKnowledgeBase();

                // Update metadata
                $metadata = $post->metadata ?? [];
                $metadata['kb_removed_at'] = now()->toIso8601String();
                $metadata['kb_removal_reason'] = $reason;
                $post->update(['metadata' => $metadata]);

                $removed++;

            } catch (\Exception $e) {
                Log::warning("Failed to remove post {$post->id} from KB", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'removed' => $removed,
            'total' => $posts->count(),
        ];
    }

    /**
     * Auto-add high-performing posts to knowledge base
     */
    public function autoAddSuccessPosts(
        string $orgId,
        string $profileGroupId,
        array $options = []
    ): array {
        $minSuccessScore = $options['min_success_score'] ?? 0.7;
        $minPercentile = $options['min_percentile'] ?? 75;
        $limit = $options['limit'] ?? 50;

        // Get high-performing posts not yet in KB
        $successPosts = SocialPost::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->where('is_historical', true)
            ->where('is_analyzed', true)
            ->where('is_in_knowledge_base', false)
            ->where('success_score', '>=', $minSuccessScore)
            ->where('success_label', SocialPost::SUCCESS_HIGH_PERFORMER)
            ->orderBy('success_score', 'desc')
            ->limit($limit)
            ->get();

        return $this->addToKnowledgeBase(
            $successPosts,
            "Auto-added as high performer (score >= {$minSuccessScore})",
            'system'
        );
    }

    /**
     * Build complete knowledge base from analyzed posts
     */
    public function buildKnowledgeBase(
        string $orgId,
        string $profileGroupId,
        array $options = []
    ): array {
        $strategy = $options['strategy'] ?? 'balanced'; // 'balanced', 'quality', 'quantity'

        try {
            DB::beginTransaction();

            // 1. Add high performers
            $highPerformers = $this->autoAddSuccessPosts($orgId, $profileGroupId, [
                'min_success_score' => $this->getSuccessThreshold($strategy),
                'limit' => $this->getQuantityLimit($strategy),
            ]);

            // 2. Consolidate core brand DNA
            $coreDNA = $this->brandDNAAnalysis->consolidateCoreDNA(
                $orgId,
                $profileGroupId,
                $this->getFrequencyThreshold($strategy)
            );

            // 3. Mark dimensions as core DNA
            $this->markCoreDimensions($orgId, $profileGroupId, $coreDNA);

            // 4. Update KB config
            $config = BrandKnowledgeConfig::where('org_id', $orgId)
                ->where('profile_group_id', $profileGroupId)
                ->first();

            if ($config) {
                $config->markKnowledgeBaseBuilt();
            }

            DB::commit();

            return [
                'success' => true,
                'posts_added' => $highPerformers['added'],
                'core_dimensions' => count($coreDNA),
                'strategy' => $strategy,
                'kb_summary' => $this->getKnowledgeBaseSummary($orgId, $profileGroupId),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('KB build failed', [
                'org_id' => $orgId,
                'profile_group_id' => $profileGroupId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get success score threshold based on strategy
     */
    private function getSuccessThreshold(string $strategy): float
    {
        return match($strategy) {
            'quality' => 0.80,
            'balanced' => 0.70,
            'quantity' => 0.60,
            default => 0.70,
        };
    }

    /**
     * Get quantity limit based on strategy
     */
    private function getQuantityLimit(string $strategy): int
    {
        return match($strategy) {
            'quality' => 30,
            'balanced' => 50,
            'quantity' => 100,
            default => 50,
        };
    }

    /**
     * Get frequency threshold based on strategy
     */
    private function getFrequencyThreshold(string $strategy): int
    {
        return match($strategy) {
            'quality' => 5,
            'balanced' => 3,
            'quantity' => 2,
            default => 3,
        };
    }

    /**
     * Mark dimensions as core brand DNA
     */
    private function markCoreDimensions(string $orgId, string $profileGroupId, array $coreDNA): void
    {
        foreach ($coreDNA as $dimension) {
            BrandKnowledgeDimension::where('org_id', $orgId)
                ->where('profile_group_id', $profileGroupId)
                ->where('dimension_category', $dimension['category'])
                ->where('dimension_type', $dimension['type'])
                ->where('dimension_value', $dimension['value'])
                ->update(['is_core_dna' => true]);
        }
    }

    /**
     * Get comprehensive KB summary
     */
    public function getKnowledgeBaseSummary(string $orgId, string $profileGroupId): array
    {
        $kbPosts = SocialPost::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->where('is_in_knowledge_base', true)
            ->get();

        $coreDimensions = BrandKnowledgeDimension::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->where('is_core_dna', true)
            ->get();

        $brandDNA = $this->brandDNAAnalysis->getBrandDNASummary($orgId, $profileGroupId);

        return [
            'total_kb_posts' => $kbPosts->count(),
            'avg_success_score' => round($kbPosts->avg('success_score'), 4),
            'high_performers' => $kbPosts->where('success_label', SocialPost::SUCCESS_HIGH_PERFORMER)->count(),
            'total_dimensions' => $brandDNA['total_dimensions'],
            'core_dimensions' => $coreDimensions->count(),
            'platforms' => $kbPosts->pluck('platform')->unique()->values()->toArray(),
            'date_range' => [
                'oldest' => $kbPosts->min('published_at'),
                'newest' => $kbPosts->max('published_at'),
            ],
            'brand_dna' => [
                'top_objectives' => $brandDNA['top_objectives'],
                'top_tones' => $brandDNA['top_tones'],
                'top_hooks' => $brandDNA['top_hooks'],
                'top_ctas' => $brandDNA['top_ctas'],
                'top_themes' => $brandDNA['top_themes'],
            ],
        ];
    }

    /**
     * Query KB for content inspiration
     */
    public function queryKnowledgeBase(
        string $orgId,
        string $profileGroupId,
        array $criteria
    ): Collection {
        $query = SocialPost::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->where('is_in_knowledge_base', true);

        // Filter by objective
        if (isset($criteria['objective'])) {
            $query->whereJsonContains('extracted_objectives', $criteria['objective']);
        }

        // Filter by tone
        if (isset($criteria['tone'])) {
            $query->whereJsonContains('extracted_tones', $criteria['tone']);
        }

        // Filter by platform
        if (isset($criteria['platform'])) {
            $query->where('platform', $criteria['platform']);
        }

        // Filter by success score
        if (isset($criteria['min_success_score'])) {
            $query->where('success_score', '>=', $criteria['min_success_score']);
        }

        // Filter by content type
        if (isset($criteria['content_type'])) {
            $query->where('post_type', $criteria['content_type']);
        }

        // Sort by success score (default)
        $sortBy = $criteria['sort_by'] ?? 'success_score';
        $sortOrder = $criteria['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Limit results
        $limit = $criteria['limit'] ?? 20;

        return $query->limit($limit)->get();
    }

    /**
     * Get KB recommendations for campaign objective
     */
    public function getRecommendationsForCampaign(
        string $orgId,
        string $profileGroupId,
        string $campaignObjective,
        ?string $platform = null,
        int $limit = 10
    ): array {
        $criteria = [
            'objective' => $campaignObjective,
            'min_success_score' => 0.7,
            'limit' => $limit,
        ];

        if ($platform) {
            $criteria['platform'] = $platform;
        }

        $posts = $this->queryKnowledgeBase($orgId, $profileGroupId, $criteria);

        // Extract patterns from successful posts
        $patterns = [
            'common_tones' => $this->extractCommonValues($posts, 'extracted_tones'),
            'common_hooks' => $this->extractCommonValues($posts, 'extracted_hooks'),
            'common_ctas' => $this->extractCommonValues($posts, 'extracted_ctas'),
            'common_themes' => $this->extractCommonValues($posts, 'extracted_entities'),
            'avg_success_score' => round($posts->avg('success_score'), 4),
        ];

        return [
            'objective' => $campaignObjective,
            'example_posts' => $posts->take(5)->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => substr($post->content, 0, 200) . '...',
                    'success_score' => $post->success_score,
                    'permalink' => $post->permalink,
                    'published_at' => $post->published_at,
                ];
            }),
            'success_patterns' => $patterns,
            'recommendations' => $this->generateRecommendations($patterns),
        ];
    }

    /**
     * Extract common values from posts
     */
    private function extractCommonValues(Collection $posts, string $field): array
    {
        $allValues = [];

        foreach ($posts as $post) {
            $values = $post->$field ?? [];
            if (is_array($values)) {
                $allValues = array_merge($allValues, $values);
            }
        }

        // Count frequencies
        $frequencies = array_count_values($allValues);
        arsort($frequencies);

        return array_slice($frequencies, 0, 5, true);
    }

    /**
     * Generate recommendations based on patterns
     */
    private function generateRecommendations(array $patterns): array
    {
        $recommendations = [];

        // Tone recommendations
        if (!empty($patterns['common_tones'])) {
            $topTones = array_keys(array_slice($patterns['common_tones'], 0, 3));
            $recommendations[] = [
                'type' => 'tone',
                'suggestion' => 'Use ' . implode(', ', $topTones) . ' tone in your content',
                'confidence' => 'high',
            ];
        }

        // Hook recommendations
        if (!empty($patterns['common_hooks'])) {
            $topHooks = array_keys(array_slice($patterns['common_hooks'], 0, 3));
            $recommendations[] = [
                'type' => 'hook',
                'suggestion' => 'Start with ' . implode(' or ', $topHooks) . ' style hooks',
                'confidence' => 'high',
            ];
        }

        // CTA recommendations
        if (!empty($patterns['common_ctas'])) {
            $topCTAs = array_keys(array_slice($patterns['common_ctas'], 0, 3));
            $recommendations[] = [
                'type' => 'cta',
                'suggestion' => 'Use CTAs like: ' . implode(', ', $topCTAs),
                'confidence' => 'medium',
            ];
        }

        return $recommendations;
    }

    /**
     * Validate and curate KB content
     */
    public function curateKnowledgeBase(
        string $orgId,
        string $profileGroupId,
        array $options = []
    ): array {
        $removeUnderperformers = $options['remove_underperformers'] ?? true;
        $minScore = $options['min_score'] ?? 0.5;
        $validateDimensions = $options['validate_dimensions'] ?? true;

        $results = [
            'posts_removed' => 0,
            'dimensions_validated' => 0,
            'issues_found' => [],
        ];

        // Remove underperforming posts
        if ($removeUnderperformers) {
            $underperformers = SocialPost::where('org_id', $orgId)
                ->where('profile_group_id', $profileGroupId)
                ->where('is_in_knowledge_base', true)
                ->where('success_score', '<', $minScore)
                ->get();

            $removed = $this->removeFromKnowledgeBase(
                $underperformers,
                "Removed during curation: success score below {$minScore}"
            );

            $results['posts_removed'] = $removed['removed'];
        }

        // Validate dimensions
        if ($validateDimensions) {
            $lowConfidenceDimensions = BrandKnowledgeDimension::where('org_id', $orgId)
                ->where('profile_group_id', $profileGroupId)
                ->where('is_core_dna', true)
                ->where('confidence_score', '<', 0.6)
                ->get();

            foreach ($lowConfidenceDimensions as $dimension) {
                $dimension->update(['is_core_dna' => false]);
                $results['dimensions_validated']++;
                $results['issues_found'][] = "Low confidence dimension demoted: {$dimension->dimension_type} = {$dimension->dimension_value}";
            }
        }

        return $results;
    }

    /**
     * Export KB for external use
     */
    public function exportKnowledgeBase(string $orgId, string $profileGroupId, string $format = 'json'): array
    {
        $summary = $this->getKnowledgeBaseSummary($orgId, $profileGroupId);

        $kbPosts = SocialPost::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->where('is_in_knowledge_base', true)
            ->with(['mediaAssets', 'brandKnowledgeDimensions'])
            ->get();

        $export = [
            'summary' => $summary,
            'posts' => $kbPosts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'platform' => $post->platform,
                    'published_at' => $post->published_at,
                    'success_score' => $post->success_score,
                    'success_label' => $post->success_label,
                    'extracted_dimensions' => $post->getAllExtractedDimensions(),
                    'media_count' => $post->mediaAssets->count(),
                    'dimensions_count' => $post->brandKnowledgeDimensions->count(),
                ];
            }),
            'exported_at' => now()->toIso8601String(),
        ];

        return match($format) {
            'json' => $export,
            'csv' => $this->convertToCSV($export),
            default => $export,
        };
    }

    /**
     * Convert export data to CSV format
     */
    private function convertToCSV(array $data): string
    {
        // Simplified CSV conversion
        // Would be expanded for production use
        return json_encode($data);
    }
}
