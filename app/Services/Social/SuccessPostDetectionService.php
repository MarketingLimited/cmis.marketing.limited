<?php

namespace App\Services\Social;

use App\Models\Social\SocialPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Success Post Detection Service
 *
 * Analyzes social posts to identify high-performing content based on
 * normalized metrics, percentile ranking, and platform-specific benchmarks.
 */
class SuccessPostDetectionService
{
    /**
     * Platform metric weights for success scoring
     */
    private const METRIC_WEIGHTS = [
        'engagement_rate' => 0.40,
        'reach' => 0.20,
        'impressions' => 0.15,
        'saves' => 0.10,
        'shares' => 0.10,
        'comments' => 0.05,
    ];

    /**
     * Analyze a single post and calculate success score
     */
    public function analyzePost(SocialPost $post, ?int $percentileThreshold = 75): array
    {
        if (!$post->platform_metrics) {
            return [
                'success_score' => 0.0,
                'success_label' => SocialPost::SUCCESS_LOW_PERFORMER,
                'success_hypothesis' => 'No metrics available for analysis',
                'percentile_rank' => 0,
            ];
        }

        // Calculate normalized success score
        $successScore = $this->calculateSuccessScore($post);

        // Get percentile rank among similar posts
        $percentile = $this->calculatePercentileRank($post, $successScore);

        // Determine success label
        $label = $this->determineSuccessLabel($percentile, $percentileThreshold);

        // Generate hypothesis
        $hypothesis = $this->generateSuccessHypothesis($post, $percentile);

        return [
            'success_score' => round($successScore, 4),
            'success_label' => $label,
            'success_hypothesis' => $hypothesis,
            'percentile_rank' => $percentile,
        ];
    }

    /**
     * Batch analyze multiple posts
     */
    public function analyzePosts(Collection $posts, int $percentileThreshold = 75): array
    {
        $results = [];

        foreach ($posts as $post) {
            $results[$post->id] = $this->analyzePost($post, $percentileThreshold);
        }

        return $results;
    }

    /**
     * Calculate normalized success score (0-1) based on weighted metrics
     */
    private function calculateSuccessScore(SocialPost $post): float
    {
        $metrics = $post->platform_metrics;
        $platform = $post->platform ?? $post->provider;

        $score = 0.0;

        // Engagement Rate (most important)
        $engagementRate = $this->calculateEngagementRate($metrics);
        $score += $this->normalizeMetric($engagementRate, 0, 15) * self::METRIC_WEIGHTS['engagement_rate'];

        // Reach
        if (isset($metrics['reach'])) {
            $score += $this->normalizeMetric($metrics['reach'], 0, 50000) * self::METRIC_WEIGHTS['reach'];
        }

        // Impressions
        if (isset($metrics['impressions'])) {
            $score += $this->normalizeMetric($metrics['impressions'], 0, 100000) * self::METRIC_WEIGHTS['impressions'];
        }

        // Saves (Instagram/TikTok)
        if (isset($metrics['saves'])) {
            $score += $this->normalizeMetric($metrics['saves'], 0, 1000) * self::METRIC_WEIGHTS['saves'];
        }

        // Shares
        if (isset($metrics['shares'])) {
            $score += $this->normalizeMetric($metrics['shares'], 0, 500) * self::METRIC_WEIGHTS['shares'];
        }

        // Comments
        if (isset($metrics['comments'])) {
            $score += $this->normalizeMetric($metrics['comments'], 0, 500) * self::METRIC_WEIGHTS['comments'];
        }

        return min(1.0, max(0.0, $score));
    }

    /**
     * Calculate engagement rate from raw metrics
     */
    private function calculateEngagementRate(array $metrics): float
    {
        $reach = $metrics['reach'] ?? $metrics['impressions'] ?? 0;

        if ($reach === 0) {
            return 0.0;
        }

        $engagements = ($metrics['likes'] ?? 0)
            + ($metrics['comments'] ?? 0)
            + ($metrics['shares'] ?? 0)
            + ($metrics['saves'] ?? 0);

        return ($engagements / $reach) * 100;
    }

    /**
     * Normalize metric value to 0-1 range
     */
    private function normalizeMetric(float $value, float $min, float $max): float
    {
        if ($max === $min) {
            return 0.0;
        }

        return min(1.0, max(0.0, ($value - $min) / ($max - $min)));
    }

    /**
     * Calculate percentile rank compared to similar posts
     */
    private function calculatePercentileRank(SocialPost $post, float $successScore): int
    {
        $platform = $post->platform ?? $post->provider;
        $profileGroupId = $post->profile_group_id;

        // Get all success scores for similar posts
        $scores = DB::table('cmis.social_posts')
            ->where('org_id', $post->org_id)
            ->where('is_historical', true)
            ->when($profileGroupId, function ($q) use ($profileGroupId) {
                return $q->where('profile_group_id', $profileGroupId);
            })
            ->when($platform, function ($q) use ($platform) {
                return $q->where('provider', $platform);
            })
            ->whereNotNull('success_score')
            ->pluck('success_score')
            ->sort()
            ->values();

        if ($scores->isEmpty()) {
            return 50; // Default to median if no comparison data
        }

        // Count how many posts have lower scores
        $lowerCount = $scores->filter(fn($s) => $s < $successScore)->count();

        // Calculate percentile
        $percentile = ($lowerCount / $scores->count()) * 100;

        return (int) round($percentile);
    }

    /**
     * Determine success label based on percentile rank
     */
    private function determineSuccessLabel(int $percentile, int $threshold): string
    {
        if ($percentile >= $threshold) {
            return SocialPost::SUCCESS_HIGH_PERFORMER;
        } elseif ($percentile >= 40) {
            return SocialPost::SUCCESS_AVERAGE;
        } else {
            return SocialPost::SUCCESS_LOW_PERFORMER;
        }
    }

    /**
     * Generate hypothesis explaining why post succeeded or failed
     */
    private function generateSuccessHypothesis(SocialPost $post, int $percentile): string
    {
        $metrics = $post->platform_metrics;
        $engagementRate = $this->calculateEngagementRate($metrics);

        $factors = [];

        // High engagement rate
        if ($engagementRate > 5) {
            $factors[] = 'high engagement rate (' . round($engagementRate, 2) . '%)';
        }

        // Strong saves (Instagram/TikTok)
        if (isset($metrics['saves']) && $metrics['saves'] > 100) {
            $factors[] = 'strong save rate (' . $metrics['saves'] . ' saves)';
        }

        // Viral shares
        if (isset($metrics['shares']) && $metrics['shares'] > 50) {
            $factors[] = 'viral sharing (' . $metrics['shares'] . ' shares)';
        }

        // High reach
        if (isset($metrics['reach']) && $metrics['reach'] > 10000) {
            $factors[] = 'exceptional reach (' . number_format($metrics['reach']) . ')';
        }

        // Active comments
        if (isset($metrics['comments']) && $metrics['comments'] > 50) {
            $factors[] = 'active discussion (' . $metrics['comments'] . ' comments)';
        }

        if ($percentile >= 75) {
            if (empty($factors)) {
                return 'Top performer with balanced metrics across engagement, reach, and interactions.';
            }
            return 'Top performer driven by: ' . implode(', ', $factors) . '.';
        } elseif ($percentile >= 40) {
            return 'Average performance with standard engagement metrics.';
        } else {
            return 'Below-average performance. Consider testing different content formats or posting times.';
        }
    }

    /**
     * Get success benchmark statistics for a profile group
     */
    public function getBenchmarkStats(string $orgId, ?string $profileGroupId = null, ?string $platform = null): array
    {
        $query = DB::table('cmis.social_posts')
            ->where('org_id', $orgId)
            ->where('is_historical', true)
            ->whereNotNull('success_score');

        if ($profileGroupId) {
            $query->where('profile_group_id', $profileGroupId);
        }

        if ($platform) {
            $query->where('provider', $platform);
        }

        $scores = $query->pluck('success_score');

        if ($scores->isEmpty()) {
            return [
                'total_posts' => 0,
                'avg_score' => 0.0,
                'median_score' => 0.0,
                'p75_score' => 0.0,
                'p90_score' => 0.0,
                'high_performers' => 0,
            ];
        }

        $sorted = $scores->sort()->values();

        return [
            'total_posts' => $scores->count(),
            'avg_score' => round($scores->avg(), 4),
            'median_score' => round($sorted[$sorted->count() / 2] ?? 0, 4),
            'p75_score' => round($sorted[(int)($sorted->count() * 0.75)] ?? 0, 4),
            'p90_score' => round($sorted[(int)($sorted->count() * 0.90)] ?? 0, 4),
            'high_performers' => $scores->filter(fn($s) => $s >= 0.7)->count(),
        ];
    }
}
