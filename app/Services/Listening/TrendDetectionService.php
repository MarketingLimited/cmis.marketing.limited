<?php

namespace App\Services\Listening;

use App\Models\Listening\SocialMention;
use App\Models\Listening\TrendingTopic;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrendDetectionService
{
    /**
     * Process mention for trend detection
     */
    public function processMention(SocialMention $mention): void
    {
        // Extract potential trending topics
        $topics = array_merge(
            $mention->hashtags,
            $this->extractTopicsFromContent($mention->content)
        );

        foreach ($topics as $topic) {
            $this->updateOrCreateTrend($mention->org_id, $topic, $mention);
        }
    }

    /**
     * Extract topics from content
     */
    protected function extractTopicsFromContent(string $content): array
    {
        // Simple keyword extraction
        // In production, use NLP for better extraction

        $stopWords = ['the', 'is', 'at', 'which', 'on', 'and', 'or', 'but'];
        $words = str_word_count(strtolower($content), 1);
        $words = array_filter($words, fn($word) => !in_array($word, $stopWords) && strlen($word) > 3);

        return array_slice(array_unique($words), 0, 5);
    }

    /**
     * Update or create trending topic
     */
    protected function updateOrCreateTrend(string $orgId, string $topic, SocialMention $mention): void
    {
        $trend = TrendingTopic::where('org_id', $orgId)
            ->where('topic', $topic)
            ->first();

        if ($trend) {
            $trend->incrementMentions();
            $this->updateTrendMetrics($trend, $mention);
        } else {
            $trend = TrendingTopic::create([
                'org_id' => $orgId,
                'topic' => $topic,
                'topic_type' => str_starts_with($topic, '#') ? 'hashtag' : 'keyword',
                'mention_count' => 1,
                'platform_distribution' => [$mention->platform => 1],
                'first_seen_at' => $mention->published_at,
                'last_seen_at' => $mention->published_at,
            ]);
        }

        $trend->calculateGrowthRate();
        $trend->updateTrendVelocity();
        $trend->calculateRelevanceScore();
    }

    /**
     * Update trend metrics
     */
    protected function updateTrendMetrics(TrendingTopic $trend, SocialMention $mention): void
    {
        // Update platform distribution
        $distribution = $trend->platform_distribution;
        $distribution[$mention->platform] = ($distribution[$mention->platform] ?? 0) + 1;
        $trend->updatePlatformDistribution($distribution);

        // Update sentiment
        $avgSentiment = DB::table('cmis.social_mentions')
            ->join('cmis.trending_topics', function($join) use ($trend) {
                $join->on(DB::raw("cmis.social_mentions.content ILIKE '%' || cmis.trending_topics.topic || '%'"), DB::raw('true'));
            })
            ->where('cmis.trending_topics.trend_id', $trend->trend_id)
            ->whereNotNull('cmis.social_mentions.sentiment_score')
            ->avg('cmis.social_mentions.sentiment_score');

        if ($avgSentiment !== null) {
            $sentiment = $avgSentiment > 0.2 ? 'positive' : ($avgSentiment < -0.2 ? 'negative' : 'neutral');
            $trend->updateSentiment($sentiment, $avgSentiment);
        }

        $trend->updatePeak();
    }

    /**
     * Detect emerging trends
     */
    public function detectEmergingTrends(string $orgId, int $hours = 24): Collection
    {
        // Get mentions from last 24 hours
        $mentions = SocialMention::where('org_id', $orgId)
            ->where('published_at', '>=', now()->subHours($hours))
            ->get();

        // Extract all topics
        $topicCounts = [];

        foreach ($mentions as $mention) {
            $topics = array_merge(
                $mention->hashtags,
                $this->extractTopicsFromContent($mention->content)
            );

            foreach ($topics as $topic) {
                $topicCounts[$topic] = ($topicCounts[$topic] ?? 0) + 1;
            }
        }

        // Filter topics with significant mentions
        $emergingTopics = array_filter($topicCounts, fn($count) => $count >= 5);
        arsort($emergingTopics);

        // Create or update trends
        $trends = collect();

        foreach (array_slice($emergingTopics, 0, 20, true) as $topic => $count) {
            $trend = $this->createOrUpdateEmergingTrend($orgId, $topic, $mentions);
            if ($trend) {
                $trends->push($trend);
            }
        }

        return $trends;
    }

    /**
     * Create or update emerging trend
     */
    protected function createOrUpdateEmergingTrend(string $orgId, string $topic, Collection $mentions): ?TrendingTopic
    {
        $relevantMentions = $mentions->filter(function($mention) use ($topic) {
            return str_contains(strtolower($mention->content), strtolower($topic)) ||
                   in_array($topic, $mention->hashtags);
        });

        if ($relevantMentions->isEmpty()) {
            return null;
        }

        $trend = TrendingTopic::firstOrCreate(
            [
                'org_id' => $orgId,
                'topic' => $topic,
            ],
            [
                'topic_type' => str_starts_with($topic, '#') ? 'hashtag' : 'keyword',
                'first_seen_at' => $relevantMentions->min('published_at'),
                'last_seen_at' => now(),
            ]
        );

        // Update metrics
        $trend->update([
            'mention_count' => $relevantMentions->count(),
            'mention_count_24h' => $relevantMentions->count(),
        ]);

        // Calculate platform distribution
        $platformDist = $relevantMentions->groupBy('platform')
            ->map->count()
            ->toArray();
        $trend->updatePlatformDistribution($platformDist);

        $trend->calculateGrowthRate();
        $trend->updateTrendVelocity();
        $trend->calculateRelevanceScore();

        return $trend;
    }

    /**
     * Get trending topics
     */
    public function getTrendingTopics(string $orgId, array $filters = []): Collection
    {
        $query = TrendingTopic::where('org_id', $orgId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['velocity'])) {
            $query->where('trend_velocity', $filters['velocity']);
        }

        if (isset($filters['min_relevance'])) {
            $query->where('relevance_score', '>=', $filters['min_relevance']);
        }

        if (isset($filters['topic_type'])) {
            $query->where('topic_type', $filters['topic_type']);
        }

        if (isset($filters['opportunities_only']) && $filters['opportunities_only']) {
            $query->where('is_opportunity', true);
        }

        return $query->byRelevance()->get();
    }

    /**
     * Analyze trend opportunity
     */
    public function analyzeTrendOpportunity(TrendingTopic $trend): array
    {
        $analysis = [
            'is_opportunity' => false,
            'opportunity_score' => 0,
            'reasons' => [],
            'recommendations' => [],
        ];

        // Check growth rate
        if ($trend->growth_rate > 100) {
            $analysis['opportunity_score'] += 30;
            $analysis['reasons'][] = 'Rapid growth detected';
            $analysis['recommendations'][] = 'Act quickly to capitalize on trending topic';
        }

        // Check positive sentiment
        if ($trend->overall_sentiment === 'positive') {
            $analysis['opportunity_score'] += 20;
            $analysis['reasons'][] = 'Positive sentiment';
            $analysis['recommendations'][] = 'Align brand messaging with positive sentiment';
        }

        // Check relevance
        if ($trend->relevance_score > 70) {
            $analysis['opportunity_score'] += 25;
            $analysis['reasons'][] = 'High relevance to brand';
        }

        // Check viral potential
        if ($trend->isViral()) {
            $analysis['opportunity_score'] += 25;
            $analysis['reasons'][] = 'Viral potential';
            $analysis['recommendations'][] = 'Create content to ride the viral wave';
        }

        $analysis['is_opportunity'] = $analysis['opportunity_score'] >= 50;

        if ($analysis['is_opportunity']) {
            $trend->markAsOpportunity();
        }

        return $analysis;
    }

    /**
     * Get trend timeline
     */
    public function getTrendTimeline(TrendingTopic $trend, int $days = 7): array
    {
        $timeline = [];
        $startDate = Carbon::parse($trend->first_seen_at);
        $endDate = min(now(), $startDate->copy()->addDays($days));

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $timeline[] = [
                'date' => $date->toDateString(),
                'mention_count' => 0, // Would query actual data
                'sentiment' => 'neutral',
            ];
        }

        return $timeline;
    }

    /**
     * Compare trends
     */
    public function compareTrends(array $trendIds): array
    {
        $trends = TrendingTopic::whereIn('trend_id', $trendIds)->get();

        return $trends->map(function($trend) {
            return [
                'topic' => $trend->topic,
                'mention_count' => $trend->mention_count,
                'growth_rate' => $trend->growth_rate,
                'velocity' => $trend->trend_velocity,
                'sentiment' => $trend->overall_sentiment,
                'relevance_score' => $trend->relevance_score,
            ];
        })->toArray();
    }
}
