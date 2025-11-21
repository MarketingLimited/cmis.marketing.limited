<?php

namespace App\Services\Optimization;

use App\Models\Campaign\Campaign;
use App\Models\Optimization\CreativePerformance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreativeAnalyzer
{
    /**
     * Analyze creative performance for campaigns.
     */
    public function analyzeCreatives(string $orgId, ?array $campaignIds = null): array
    {
        // Get campaigns to analyze
        $query = Campaign::where('org_id', $orgId)->where('status', 'active');

        if ($campaignIds) {
            $query->whereIn('campaign_id', $campaignIds);
        }

        $campaigns = $query->get();
        $performanceRecords = [];

        foreach ($campaigns as $campaign) {
            $creatives = $this->getCampaignCreatives($campaign);

            foreach ($creatives as $creative) {
                $performance = $this->analyzeCreative($orgId, $campaign, $creative);
                if ($performance) {
                    $performanceRecords[] = $performance;
                }
            }
        }

        return $performanceRecords;
    }

    /**
     * Analyze individual creative performance.
     */
    protected function analyzeCreative(string $orgId, Campaign $campaign, array $creative): ?CreativePerformance
    {
        try {
            // Fetch creative metrics
            $metrics = $this->getCreativeMetrics($creative['creative_id']);

            if (!$metrics) {
                return null;
            }

            // Extract visual and text features
            $visualFeatures = $this->extractVisualFeatures($creative);
            $textFeatures = $this->extractTextFeatures($creative);

            // Calculate performance scores
            $performanceScore = $this->calculatePerformanceScore($metrics);
            $fatigueScore = $this->calculateFatigueScore($metrics, $creative);

            // Generate recommendation
            $recommendation = $this->generateRecommendation($performanceScore, $fatigueScore, $metrics);

            // Create or update performance record
            return CreativePerformance::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'campaign_id' => $campaign->campaign_id,
                    'creative_id' => $creative['creative_id'],
                ],
                [
                    'creative_type' => $creative['type'] ?? 'image',
                    'creative_url' => $creative['url'] ?? null,
                    'creative_metadata' => $creative['metadata'] ?? [],
                    'impressions' => $metrics['impressions'] ?? 0,
                    'clicks' => $metrics['clicks'] ?? 0,
                    'conversions' => $metrics['conversions'] ?? 0,
                    'spend' => $metrics['spend'] ?? 0,
                    'revenue' => $metrics['revenue'] ?? 0,
                    'ctr' => $metrics['ctr'] ?? 0,
                    'cvr' => $metrics['cvr'] ?? 0,
                    'cpc' => $metrics['cpc'] ?? 0,
                    'cpa' => $metrics['cpa'] ?? 0,
                    'roas' => $metrics['roas'] ?? 0,
                    'engagement_rate' => $metrics['engagement_rate'] ?? 0,
                    'video_view_rate' => $metrics['video_view_rate'] ?? null,
                    'completion_rate' => $metrics['completion_rate'] ?? null,
                    'visual_features' => $visualFeatures,
                    'text_features' => $textFeatures,
                    'performance_score' => $performanceScore,
                    'fatigue_score' => $fatigueScore,
                    'freshness_days' => $this->calculateFreshness($creative),
                    'recommendation' => $recommendation['action'],
                    'recommendation_confidence' => $recommendation['confidence'],
                    'analyzed_at' => now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error('Creative analysis failed', [
                'creative_id' => $creative['creative_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get creatives for a campaign.
     */
    protected function getCampaignCreatives(Campaign $campaign): array
    {
        // Fetch creatives from platform integration tables
        $creatives = DB::table('cmis_platform.platform_ads')
            ->where('campaign_id', $campaign->campaign_id)
            ->where('status', 'active')
            ->get();

        return $creatives->map(function ($ad) {
            return [
                'creative_id' => $ad->ad_id,
                'type' => $ad->ad_type ?? 'image',
                'url' => $ad->creative_url ?? null,
                'metadata' => json_decode($ad->creative_metadata ?? '{}', true),
                'created_at' => $ad->created_at,
            ];
        })->toArray();
    }

    /**
     * Get metrics for a creative.
     */
    protected function getCreativeMetrics(string $creativeId): ?array
    {
        // Fetch metrics from analytics tables (last 30 days)
        $metrics = DB::table('cmis_analytics.ad_performance')
            ->where('ad_id', $creativeId)
            ->where('date', '>=', now()->subDays(30))
            ->selectRaw('
                SUM(impressions) as impressions,
                SUM(clicks) as clicks,
                SUM(conversions) as conversions,
                SUM(spend) as spend,
                SUM(revenue) as revenue,
                AVG(ctr) as ctr,
                AVG(cvr) as cvr,
                AVG(cpc) as cpc,
                AVG(cpa) as cpa,
                AVG(roas) as roas,
                AVG(engagement_rate) as engagement_rate,
                AVG(video_view_rate) as video_view_rate,
                AVG(completion_rate) as completion_rate
            ')
            ->first();

        if (!$metrics || $metrics->impressions === 0) {
            return null;
        }

        return (array) $metrics;
    }

    /**
     * Extract visual features from creative.
     */
    protected function extractVisualFeatures(array $creative): array
    {
        // Simplified feature extraction
        // In production, this should use computer vision APIs (e.g., Google Vision, AWS Rekognition)
        $features = [];

        $metadata = $creative['metadata'] ?? [];

        // Extract dominant color
        $features['dominant_color'] = $metadata['dominant_color'] ?? 'unknown';

        // Check for faces
        $features['has_faces'] = $metadata['has_faces'] ?? false;

        // Check for text overlay
        $features['has_text'] = $metadata['has_text_overlay'] ?? false;

        // Aspect ratio
        $features['aspect_ratio'] = $metadata['aspect_ratio'] ?? '1:1';

        // Brightness/contrast
        $features['brightness'] = $metadata['brightness'] ?? 'medium';
        $features['contrast'] = $metadata['contrast'] ?? 'medium';

        // Image complexity
        $features['complexity'] = $metadata['complexity'] ?? 'medium'; // low, medium, high

        return $features;
    }

    /**
     * Extract text features from creative.
     */
    protected function extractTextFeatures(array $creative): array
    {
        $features = [];
        $metadata = $creative['metadata'] ?? [];

        // Headline analysis
        $headline = $metadata['headline'] ?? '';
        $features['headline_length'] = strlen($headline);
        $features['has_headline'] = !empty($headline);

        // Call-to-action
        $cta = $metadata['cta'] ?? '';
        $features['has_cta'] = !empty($cta);
        $features['cta_type'] = $this->detectCTAType($cta);

        // Description
        $description = $metadata['description'] ?? '';
        $features['description_length'] = strlen($description);

        // Sentiment analysis (simplified)
        $features['sentiment'] = $this->analyzeSentiment($headline . ' ' . $description);

        // Use of emojis
        $features['has_emoji'] = $this->hasEmoji($headline . ' ' . $description);

        // Use of numbers
        $features['has_numbers'] = preg_match('/\d+/', $headline . ' ' . $description) > 0;

        return $features;
    }

    /**
     * Detect CTA type from text.
     */
    protected function detectCTAType(string $cta): string
    {
        $cta = strtolower($cta);

        if (str_contains($cta, 'shop') || str_contains($cta, 'buy')) {
            return 'purchase';
        } elseif (str_contains($cta, 'learn') || str_contains($cta, 'read')) {
            return 'learn_more';
        } elseif (str_contains($cta, 'sign up') || str_contains($cta, 'register')) {
            return 'sign_up';
        } elseif (str_contains($cta, 'download')) {
            return 'download';
        } elseif (str_contains($cta, 'contact')) {
            return 'contact';
        }

        return 'other';
    }

    /**
     * Analyze sentiment (simplified).
     */
    protected function analyzeSentiment(string $text): string
    {
        $text = strtolower($text);

        $positiveWords = ['great', 'amazing', 'best', 'free', 'new', 'save', 'exclusive', 'limited'];
        $negativeWords = ['don\'t', 'not', 'never', 'stop', 'avoid'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($text, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($text, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Check if text contains emoji.
     */
    protected function hasEmoji(string $text): bool
    {
        return preg_match('/[\x{1F600}-\x{1F64F}]/u', $text) > 0 ||
               preg_match('/[\x{1F300}-\x{1F5FF}]/u', $text) > 0;
    }

    /**
     * Calculate performance score.
     */
    protected function calculatePerformanceScore(array $metrics): float
    {
        $weights = [
            'roas' => 0.3,
            'cvr' => 0.25,
            'ctr' => 0.2,
            'engagement_rate' => 0.15,
            'cost_efficiency' => 0.1,
        ];

        $scores = [];

        // Normalize ROAS (assume good ROAS is 3.0+)
        if (isset($metrics['roas'])) {
            $scores['roas'] = min($metrics['roas'] / 3.0, 1.0);
        }

        // CVR (assume 10% is excellent)
        if (isset($metrics['cvr'])) {
            $scores['cvr'] = min($metrics['cvr'] * 10, 1.0);
        }

        // CTR (assume 5% is excellent)
        if (isset($metrics['ctr'])) {
            $scores['ctr'] = min($metrics['ctr'] * 20, 1.0);
        }

        // Engagement rate
        if (isset($metrics['engagement_rate'])) {
            $scores['engagement_rate'] = min($metrics['engagement_rate'] * 10, 1.0);
        }

        // Cost efficiency (inverse of CPA, normalized)
        if (isset($metrics['cpa']) && $metrics['cpa'] > 0) {
            $scores['cost_efficiency'] = min(50 / $metrics['cpa'], 1.0); // Assume $50 CPA is baseline
        }

        // Calculate weighted average
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($weights as $metric => $weight) {
            if (isset($scores[$metric])) {
                $totalScore += $scores[$metric] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($totalScore / $totalWeight, 4) : 0.0;
    }

    /**
     * Calculate fatigue score.
     */
    protected function calculateFatigueScore(array $metrics, array $creative): float
    {
        // Higher fatigue = more stale creative
        $impressionFactor = min(($metrics['impressions'] ?? 0) / 100000, 1.0);
        $ageFactor = min($this->calculateFreshness($creative) / 60, 1.0);

        // CTR decline indicator
        $ctrDecline = isset($metrics['ctr']) && $metrics['ctr'] < 0.5 ? 0.3 : 0;

        return round(($impressionFactor * 0.4) + ($ageFactor * 0.4) + $ctrDecline, 4);
    }

    /**
     * Calculate creative freshness in days.
     */
    protected function calculateFreshness(array $creative): int
    {
        $createdAt = isset($creative['created_at']) ? strtotime($creative['created_at']) : time();
        $days = (time() - $createdAt) / 86400;

        return (int) $days;
    }

    /**
     * Generate recommendation based on performance and fatigue.
     */
    protected function generateRecommendation(float $performanceScore, float $fatigueScore, array $metrics): array
    {
        // High performance, low fatigue
        if ($performanceScore >= 0.7 && $fatigueScore < 0.3) {
            return [
                'action' => 'scale_up',
                'confidence' => 0.9,
            ];
        }

        // High performance, high fatigue
        if ($performanceScore >= 0.7 && $fatigueScore >= 0.7) {
            return [
                'action' => 'test_variation',
                'confidence' => 0.85,
            ];
        }

        // Medium performance, low fatigue
        if ($performanceScore >= 0.5 && $performanceScore < 0.7 && $fatigueScore < 0.5) {
            return [
                'action' => 'maintain',
                'confidence' => 0.75,
            ];
        }

        // Low performance or high fatigue
        if ($performanceScore < 0.5 || $fatigueScore >= 0.7) {
            return [
                'action' => 'refresh',
                'confidence' => 0.8,
            ];
        }

        // Very poor performance
        if ($performanceScore < 0.3) {
            return [
                'action' => 'pause',
                'confidence' => 0.9,
            ];
        }

        return [
            'action' => 'maintain',
            'confidence' => 0.7,
        ];
    }

    /**
     * Generate creative insights report.
     */
    public function generateCreativeReport(string $orgId, int $days = 30): array
    {
        $performances = CreativePerformance::where('org_id', $orgId)
            ->where('analyzed_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total_creatives' => $performances->count(),
            'high_performers' => $performances->where('performance_score', '>=', 0.7)->count(),
            'fatigued_creatives' => $performances->where('fatigue_score', '>', 0.7)->count(),
            'avg_performance_score' => round($performances->avg('performance_score'), 4),
            'avg_fatigue_score' => round($performances->avg('fatigue_score'), 4),
            'recommendations' => [
                'scale_up' => $performances->where('recommendation', 'scale_up')->count(),
                'maintain' => $performances->where('recommendation', 'maintain')->count(),
                'refresh' => $performances->where('recommendation', 'refresh')->count(),
                'pause' => $performances->where('recommendation', 'pause')->count(),
                'test_variation' => $performances->where('recommendation', 'test_variation')->count(),
            ],
            'top_performers' => $performances->sortByDesc('performance_score')->take(10)->values(),
            'worst_performers' => $performances->sortBy('performance_score')->take(10)->values(),
        ];
    }
}
