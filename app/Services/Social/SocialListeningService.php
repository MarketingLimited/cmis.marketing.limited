<?php

namespace App\Services\Social;

use App\Models\Social\SocialMention;
use App\Models\Social\SocialSentiment;
use App\Models\Social\SocialTrend;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SocialListeningService
{
    /**
     * Analyze sentiment for mention content
     */
    public function analyzeSentiment(string $content): array
    {
        // Simplified sentiment analysis - in production would use ML/NLP service
        $positiveWords = ['love', 'great', 'excellent', 'awesome', 'amazing', 'fantastic', 'wonderful', 'best', 'perfect', 'good'];
        $negativeWords = ['hate', 'terrible', 'awful', 'horrible', 'bad', 'worst', 'poor', 'disappointing', 'useless', 'broken'];

        $content = strtolower($content);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($content, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($content, $word);
        }

        $totalSentimentWords = $positiveCount + $negativeCount;

        if ($totalSentimentWords === 0) {
            return [
                'sentiment' => 'neutral',
                'score' => 0,
                'confidence' => 0.5,
            ];
        }

        $score = ($positiveCount - $negativeCount) / $totalSentimentWords;

        $sentiment = match (true) {
            $score > 0.2 => 'positive',
            $score < -0.2 => 'negative',
            default => 'neutral',
        };

        return [
            'sentiment' => $sentiment,
            'score' => round($score, 2),
            'confidence' => min(1.0, $totalSentimentWords / 5),
        ];
    }

    /**
     * Detect trending topics from mentions
     */
    public function detectTrends(string $orgId, int $hours = 24): Collection
    {
        $mentions = SocialMention::where('org_id', $orgId)
            ->where('mentioned_at', '>=', now()->subHours($hours))
            ->get();

        // Extract hashtags and topics
        $topics = [];

        foreach ($mentions as $mention) {
            // Extract hashtags
            preg_match_all('/#(\w+)/', $mention->content, $hashtags);

            foreach ($hashtags[1] as $hashtag) {
                $hashtag = strtolower($hashtag);

                if (!isset($topics[$hashtag])) {
                    $topics[$hashtag] = [
                        'topic' => $hashtag,
                        'volume' => 0,
                        'platforms' => [],
                        'sentiment_scores' => [],
                    ];
                }

                $topics[$hashtag]['volume']++;
                $topics[$hashtag]['platforms'][] = $mention->platform;
                $topics[$hashtag]['sentiment_scores'][] = $mention->sentiment_score ?? 0;
            }
        }

        // Calculate metrics for each topic
        $trends = collect($topics)->map(function ($data) {
            return [
                'topic' => $data['topic'],
                'volume' => $data['volume'],
                'platforms' => array_unique($data['platforms']),
                'avg_sentiment' => round(array_sum($data['sentiment_scores']) / max(1, count($data['sentiment_scores'])), 2),
            ];
        })->sortByDesc('volume')->values();

        return $trends;
    }

    /**
     * Calculate share of voice
     */
    public function calculateShareOfVoice(string $orgId, array $competitors, int $days = 30): array
    {
        $ourMentions = SocialMention::where('org_id', $orgId)
            ->where('mentioned_at', '>=', now()->subDays($days))
            ->count();

        // In production, would query competitor mentions from social platforms
        $totalMentions = $ourMentions;

        $shareOfVoice = [
            'our_brand' => [
                'mentions' => $ourMentions,
                'percentage' => $totalMentions > 0 ? round(($ourMentions / $totalMentions) * 100, 2) : 0,
            ],
            'competitors' => [],
            'period_days' => $days,
        ];

        foreach ($competitors as $competitor) {
            // Placeholder - would fetch real data from social platforms
            $competitorMentions = 0;
            $totalMentions += $competitorMentions;

            $shareOfVoice['competitors'][$competitor] = [
                'mentions' => $competitorMentions,
                'percentage' => $totalMentions > 0 ? round(($competitorMentions / $totalMentions) * 100, 2) : 0,
            ];
        }

        return $shareOfVoice;
    }

    /**
     * Identify crisis situations
     */
    public function detectCrisis(string $orgId, array $thresholds = []): ?array
    {
        $defaultThresholds = [
            'negative_spike_threshold' => 0.3, // 30% increase in negative mentions
            'volume_spike_threshold' => 2.0,    // 2x normal volume
            'sentiment_drop_threshold' => -0.3, // Drop in sentiment score
        ];

        $thresholds = array_merge($defaultThresholds, $thresholds);

        // Get recent mentions (last hour)
        $recentMentions = SocialMention::where('org_id', $orgId)
            ->where('mentioned_at', '>=', now()->subHour())
            ->get();

        // Get baseline (previous 24 hours)
        $baselineMentions = SocialMention::where('org_id', $orgId)
            ->whereBetween('mentioned_at', [now()->subDay(), now()->subHour()])
            ->get();

        $recentNegative = $recentMentions->where('sentiment', 'negative')->count();
        $recentTotal = $recentMentions->count();
        $baselineNegative = $baselineMentions->where('sentiment', 'negative')->count();
        $baselineTotal = $baselineMentions->count();

        $baselineAvgVolume = $baselineTotal / 24; // Average per hour
        $recentNegativeRate = $recentTotal > 0 ? $recentNegative / $recentTotal : 0;
        $baselineNegativeRate = $baselineTotal > 0 ? $baselineNegative / $baselineTotal : 0;

        $negativeSpikeDetected = $recentNegativeRate > ($baselineNegativeRate + $thresholds['negative_spike_threshold']);
        $volumeSpikeDetected = $recentTotal > ($baselineAvgVolume * $thresholds['volume_spike_threshold']);

        if ($negativeSpikeDetected || $volumeSpikeDetected) {
            return [
                'crisis_detected' => true,
                'severity' => $negativeSpikeDetected && $volumeSpikeDetected ? 'high' : 'medium',
                'recent_negative_mentions' => $recentNegative,
                'recent_total_mentions' => $recentTotal,
                'negative_rate' => round($recentNegativeRate * 100, 2),
                'baseline_negative_rate' => round($baselineNegativeRate * 100, 2),
                'detected_at' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Generate insights from mentions
     */
    public function generateInsights(string $orgId, int $days = 7): array
    {
        $mentions = SocialMention::where('org_id', $orgId)
            ->where('mentioned_at', '>=', now()->subDays($days))
            ->get();

        $insights = [];

        // Sentiment shift
        $firstHalfMentions = $mentions->filter(fn($m) => $m->mentioned_at < now()->subDays($days / 2));
        $secondHalfMentions = $mentions->filter(fn($m) => $m->mentioned_at >= now()->subDays($days / 2));

        $firstHalfSentiment = $firstHalfMentions->avg('sentiment_score') ?? 0;
        $secondHalfSentiment = $secondHalfMentions->avg('sentiment_score') ?? 0;

        if (abs($secondHalfSentiment - $firstHalfSentiment) > 0.2) {
            $insights[] = [
                'type' => 'sentiment_shift',
                'message' => $secondHalfSentiment > $firstHalfSentiment
                    ? 'Sentiment has improved in recent days'
                    : 'Sentiment has declined in recent days',
                'priority' => 'medium',
            ];
        }

        // Platform dominance
        $platformCounts = $mentions->groupBy('platform')->map->count()->sortDesc();
        $topPlatform = $platformCounts->keys()->first();

        if ($topPlatform && $platformCounts[$topPlatform] > $mentions->count() * 0.5) {
            $insights[] = [
                'type' => 'platform_dominance',
                'message' => "Over 50% of mentions are on {$topPlatform}",
                'priority' => 'low',
            ];
        }

        // High engagement opportunities
        $highEngagementMentions = $mentions->sortByDesc('engagement')->take(5);

        if ($highEngagementMentions->isNotEmpty()) {
            $insights[] = [
                'type' => 'engagement_opportunity',
                'message' => 'Identified high-engagement mentions worth responding to',
                'priority' => 'high',
                'mention_ids' => $highEngagementMentions->pluck('mention_id')->toArray(),
            ];
        }

        return $insights;
    }

    /**
     * Get sentiment analysis summary
     */
    public function getSentimentSummary(string $orgId, ?string $startDate = null, ?string $endDate = null): array
    {
        $mentions = SocialMention::where('org_id', $orgId)
            ->when($startDate, fn($q) => $q->where('mentioned_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('mentioned_at', '<=', $endDate))
            ->get();

        return $this->calculateSentimentSummary($mentions);
    }

    /**
     * Calculate sentiment summary from mentions
     */
    private function calculateSentimentSummary(Collection $mentions): array
    {
        $totalMentions = $mentions->count();
        $positiveMentions = $mentions->where('sentiment', 'positive')->count();
        $negativeMentions = $mentions->where('sentiment', 'negative')->count();
        $neutralMentions = $mentions->where('sentiment', 'neutral')->count();
        $avgSentimentScore = $mentions->avg('sentiment_score') ?? 0;

        return [
            'total_mentions' => $totalMentions,
            'positive_mentions' => $positiveMentions,
            'negative_mentions' => $negativeMentions,
            'neutral_mentions' => $neutralMentions,
            'positive_percentage' => $totalMentions > 0 ? round(($positiveMentions / $totalMentions) * 100, 2) : 0,
            'negative_percentage' => $totalMentions > 0 ? round(($negativeMentions / $totalMentions) * 100, 2) : 0,
            'avg_sentiment_score' => round($avgSentimentScore, 2),
            'sentiment_trend' => $this->determineSentimentTrend($positiveMentions, $negativeMentions),
        ];
    }

    /**
     * Determine overall sentiment trend
     */
    private function determineSentimentTrend(int $positiveMentions, int $negativeMentions): string
    {
        if ($positiveMentions > $negativeMentions) {
            return 'positive';
        }
        if ($negativeMentions > $positiveMentions) {
            return 'negative';
        }
        return 'neutral';
    }

    /**
     * Get mention volume over time
     */
    public function getMentionVolume(string $orgId, string $startDate, string $endDate, string $interval = 'day'): Collection
    {
        $mentions = SocialMention::where('org_id', $orgId)
            ->whereBetween('mentioned_at', [$startDate, $endDate])
            ->get();

        return $mentions->groupBy(function ($mention) use ($interval) {
            return match ($interval) {
                'hour' => $mention->mentioned_at->format('Y-m-d H:00'),
                'week' => $mention->mentioned_at->startOfWeek()->format('Y-m-d'),
                'month' => $mention->mentioned_at->format('Y-m'),
                default => $mention->mentioned_at->format('Y-m-d'),
            };
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'positive' => $group->where('sentiment', 'positive')->count(),
                'negative' => $group->where('sentiment', 'negative')->count(),
                'neutral' => $group->where('sentiment', 'neutral')->count(),
            ];
        });
    }

    /**
     * Get top influencers mentioning brand
     */
    public function getTopInfluencers(string $orgId, ?string $startDate = null, ?string $endDate = null, int $limit = 10): Collection
    {
        $mentions = SocialMention::where('org_id', $orgId)
            ->when($startDate, fn($q) => $q->where('mentioned_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('mentioned_at', '<=', $endDate))
            ->whereNotNull('author_followers')
            ->orderBy('author_followers', 'desc')
            ->limit(20)
            ->get();

        return $mentions->groupBy('author_username')->map(function ($group) {
            $first = $group->first();
            return [
                'username' => $first->author_username,
                'followers' => $first->author_followers,
                'mention_count' => $group->count(),
                'avg_sentiment' => round($group->avg('sentiment_score'), 2),
                'platform' => $first->platform,
            ];
        })->sortByDesc('mention_count')->take($limit)->values();
    }

    /**
     * Get platform distribution
     */
    public function getPlatformDistribution(string $orgId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $mentions = SocialMention::where('org_id', $orgId)
            ->when($startDate, fn($q) => $q->where('mentioned_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('mentioned_at', '<=', $endDate))
            ->get();

        return $mentions->groupBy('platform')->map(function ($group) {
            return [
                'count' => $group->count(),
                'engagement' => $group->sum('engagement'),
                'reach' => $group->sum('reach'),
            ];
        });
    }

    /**
     * Analyze keywords from mentions
     */
    public function analyzeKeywords(string $orgId, ?string $startDate = null, ?string $endDate = null, int $limit = 50): array
    {
        $mentions = SocialMention::where('org_id', $orgId)
            ->when($startDate, fn($q) => $q->where('mentioned_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('mentioned_at', '<=', $endDate))
            ->get();

        return $this->extractTopKeywords($mentions, $limit);
    }

    /**
     * Extract top keywords from mentions
     */
    private function extractTopKeywords(Collection $mentions, int $limit): array
    {
        $keywords = [];
        foreach ($mentions as $mention) {
            $words = str_word_count(strtolower($mention->content), 1);
            foreach ($words as $word) {
                if (strlen($word) > 4) { // Only count words longer than 4 characters
                    if (!isset($keywords[$word])) {
                        $keywords[$word] = 0;
                    }
                    $keywords[$word]++;
                }
            }
        }

        arsort($keywords);
        return array_slice($keywords, 0, $limit);
    }

    /**
     * Get engagement metrics
     */
    public function getEngagementMetrics(string $orgId, ?string $startDate = null, ?string $endDate = null): array
    {
        $mentions = SocialMention::where('org_id', $orgId)
            ->when($startDate, fn($q) => $q->where('mentioned_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('mentioned_at', '<=', $endDate))
            ->get();

        return [
            'total_engagement' => $mentions->sum('engagement'),
            'total_reach' => $mentions->sum('reach'),
            'avg_engagement_per_mention' => round($mentions->avg('engagement'), 2),
            'engagement_rate' => $this->calculateEngagementRate($mentions),
            'top_performing_mention' => $mentions->sortByDesc('engagement')->first(),
        ];
    }

    /**
     * Calculate engagement rate
     */
    private function calculateEngagementRate(Collection $mentions): float
    {
        $totalReach = $mentions->sum('reach');
        if ($totalReach <= 0) {
            return 0;
        }
        return round(($mentions->sum('engagement') / $totalReach) * 100, 2);
    }

    /**
     * Get competitor comparison data
     */
    public function getCompetitorComparison(string $orgId, array $competitorBrands, ?string $startDate = null, ?string $endDate = null): array
    {
        $data = [];
        foreach ($competitorBrands as $brand) {
            $mentions = SocialMention::where('org_id', $orgId)
                ->where('content', 'like', "%{$brand}%")
                ->when($startDate, fn($q) => $q->where('mentioned_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('mentioned_at', '<=', $endDate))
                ->get();

            $data[$brand] = [
                'mention_count' => $mentions->count(),
                'total_engagement' => $mentions->sum('engagement'),
                'total_reach' => $mentions->sum('reach'),
                'avg_sentiment' => round($mentions->avg('sentiment_score'), 2),
                'sentiment_breakdown' => [
                    'positive' => $mentions->where('sentiment', 'positive')->count(),
                    'negative' => $mentions->where('sentiment', 'negative')->count(),
                    'neutral' => $mentions->where('sentiment', 'neutral')->count(),
                ],
            ];
        }

        return $data;
    }
}
