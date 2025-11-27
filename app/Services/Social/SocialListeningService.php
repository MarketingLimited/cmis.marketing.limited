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
}
