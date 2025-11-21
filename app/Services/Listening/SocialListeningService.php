<?php

namespace App\Services\Listening;

use App\Models\Listening\MonitoringKeyword;
use App\Models\Listening\SocialMention;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialListeningService
{
    public function __construct(
        protected SentimentAnalysisService $sentimentService,
        protected TrendDetectionService $trendService,
        protected AlertService $alertService
    ) {}

    /**
     * Create monitoring keyword
     */
    public function createKeyword(string $orgId, string $userId, array $data): MonitoringKeyword
    {
        $keyword = MonitoringKeyword::create([
            'org_id' => $orgId,
            'created_by' => $userId,
            'keyword' => $data['keyword'],
            'keyword_type' => $data['keyword_type'] ?? 'keyword',
            'variations' => $data['variations'] ?? [],
            'case_sensitive' => $data['case_sensitive'] ?? false,
            'platforms' => $data['platforms'] ?? ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube'],
            'enable_alerts' => $data['enable_alerts'] ?? false,
            'alert_threshold' => $data['alert_threshold'] ?? 'medium',
            'alert_conditions' => $data['alert_conditions'] ?? [],
            'language_filters' => $data['language_filters'] ?? ['en'],
            'location_filters' => $data['location_filters'] ?? [],
            'exclude_keywords' => $data['exclude_keywords'] ?? [],
        ]);

        Log::info('Monitoring keyword created', [
            'keyword_id' => $keyword->keyword_id,
            'keyword' => $keyword->keyword,
            'org_id' => $orgId,
        ]);

        return $keyword;
    }

    /**
     * Update monitoring keyword
     */
    public function updateKeyword(MonitoringKeyword $keyword, array $data): MonitoringKeyword
    {
        $keyword->update($data);

        return $keyword->fresh();
    }

    /**
     * Capture social mention
     */
    public function captureMention(string $orgId, array $mentionData): SocialMention
    {
        DB::beginTransaction();

        try {
            // Find matching keyword
            $keyword = $this->findMatchingKeyword($orgId, $mentionData['content'], $mentionData['platform']);

            if (!$keyword) {
                throw new \Exception('No matching keyword found for mention');
            }

            // Create mention
            $mention = SocialMention::create([
                'org_id' => $orgId,
                'keyword_id' => $keyword->keyword_id,
                'platform' => $mentionData['platform'],
                'platform_post_id' => $mentionData['platform_post_id'],
                'post_url' => $mentionData['post_url'] ?? null,
                'mention_type' => $mentionData['mention_type'] ?? 'keyword',
                'author_username' => $mentionData['author_username'],
                'author_display_name' => $mentionData['author_display_name'] ?? null,
                'author_profile_url' => $mentionData['author_profile_url'] ?? null,
                'author_profile_image' => $mentionData['author_profile_image'] ?? null,
                'author_followers_count' => $mentionData['author_followers_count'] ?? 0,
                'author_is_verified' => $mentionData['author_is_verified'] ?? false,
                'content' => $mentionData['content'],
                'media_urls' => $mentionData['media_urls'] ?? [],
                'hashtags' => $mentionData['hashtags'] ?? [],
                'mentioned_accounts' => $mentionData['mentioned_accounts'] ?? [],
                'language' => $mentionData['language'] ?? 'en',
                'likes_count' => $mentionData['likes_count'] ?? 0,
                'comments_count' => $mentionData['comments_count'] ?? 0,
                'shares_count' => $mentionData['shares_count'] ?? 0,
                'views_count' => $mentionData['views_count'] ?? 0,
                'published_at' => $mentionData['published_at'] ?? now(),
                'raw_data' => $mentionData['raw_data'] ?? [],
            ]);

            // Update keyword mention count
            $keyword->incrementMentionCount();

            // Analyze sentiment
            $this->sentimentService->analyzeMention($mention);

            // Check if alert should be triggered
            if ($keyword->enable_alerts && $keyword->shouldTriggerAlert($mentionData)) {
                $this->alertService->processAlert($keyword, $mention);
            }

            // Update trends
            $this->trendService->processMention($mention);

            DB::commit();

            Log::info('Social mention captured', [
                'mention_id' => $mention->mention_id,
                'keyword_id' => $keyword->keyword_id,
                'platform' => $mention->platform,
            ]);

            return $mention;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to capture mention', [
                'error' => $e->getMessage(),
                'org_id' => $orgId,
            ]);
            throw $e;
        }
    }

    /**
     * Bulk capture mentions
     */
    public function bulkCaptureMentions(string $orgId, array $mentions): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($mentions as $mentionData) {
            try {
                $this->captureMention($orgId, $mentionData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'mention' => $mentionData['platform_post_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Find matching keyword for content
     */
    protected function findMatchingKeyword(string $orgId, string $content, string $platform): ?MonitoringKeyword
    {
        $keywords = MonitoringKeyword::where('org_id', $orgId)
            ->active()
            ->forPlatform($platform)
            ->get();

        foreach ($keywords as $keyword) {
            if ($keyword->matchesWithExclusions($content)) {
                return $keyword;
            }
        }

        return null;
    }

    /**
     * Get mentions for keyword
     */
    public function getMentionsForKeyword(
        string $keywordId,
        ?string $startDate = null,
        ?string $endDate = null,
        array $filters = []
    ): Collection {
        $query = SocialMention::where('keyword_id', $keywordId);

        if ($startDate) {
            $query->where('published_at', '>=', Carbon::parse($startDate));
        }

        if ($endDate) {
            $query->where('published_at', '<=', Carbon::parse($endDate));
        }

        if (isset($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (isset($filters['sentiment'])) {
            $query->where('sentiment', $filters['sentiment']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['requires_response'])) {
            $query->where('requires_response', $filters['requires_response']);
        }

        return $query->recentFirst()->get();
    }

    /**
     * Search mentions
     */
    public function searchMentions(string $orgId, array $criteria): Collection
    {
        $query = SocialMention::where('org_id', $orgId);

        if (isset($criteria['keyword'])) {
            $query->whereHas('keyword', function($q) use ($criteria) {
                $q->where('keyword', 'ILIKE', "%{$criteria['keyword']}%");
            });
        }

        if (isset($criteria['content'])) {
            $query->where('content', 'ILIKE', "%{$criteria['content']}%");
        }

        if (isset($criteria['author'])) {
            $query->where('author_username', 'ILIKE', "%{$criteria['author']}%");
        }

        if (isset($criteria['platform'])) {
            $query->where('platform', $criteria['platform']);
        }

        if (isset($criteria['sentiment'])) {
            $query->where('sentiment', $criteria['sentiment']);
        }

        if (isset($criteria['start_date'])) {
            $query->where('published_at', '>=', Carbon::parse($criteria['start_date']));
        }

        if (isset($criteria['end_date'])) {
            $query->where('published_at', '<=', Carbon::parse($criteria['end_date']));
        }

        if (isset($criteria['min_engagement'])) {
            $query->where('engagement_rate', '>=', $criteria['min_engagement']);
        }

        if (isset($criteria['influencers_only']) && $criteria['influencers_only']) {
            $query->fromInfluencers();
        }

        return $query->recentFirst()->get();
    }

    /**
     * Get listening statistics
     */
    public function getStatistics(string $orgId, ?string $keywordId = null, int $days = 30): array
    {
        $query = SocialMention::where('org_id', $orgId)
            ->where('published_at', '>=', now()->subDays($days));

        if ($keywordId) {
            $query->where('keyword_id', $keywordId);
        }

        $totalMentions = $query->count();
        $positiveMentions = (clone $query)->where('sentiment', 'positive')->count();
        $negativeMentions = (clone $query)->where('sentiment', 'negative')->count();
        $neutralMentions = (clone $query)->where('sentiment', 'neutral')->count();

        $avgEngagementRate = (clone $query)->avg('engagement_rate') ?? 0;
        $totalEngagement = (clone $query)->sum(DB::raw('likes_count + comments_count + shares_count'));

        $platformBreakdown = (clone $query)
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        $influencerMentions = (clone $query)->fromInfluencers()->count();

        $needingResponse = SocialMention::where('org_id', $orgId)
            ->needsResponse()
            ->count();

        return [
            'total_mentions' => $totalMentions,
            'sentiment_breakdown' => [
                'positive' => $positiveMentions,
                'negative' => $negativeMentions,
                'neutral' => $neutralMentions,
            ],
            'sentiment_percentages' => [
                'positive' => $totalMentions > 0 ? round(($positiveMentions / $totalMentions) * 100, 2) : 0,
                'negative' => $totalMentions > 0 ? round(($negativeMentions / $totalMentions) * 100, 2) : 0,
                'neutral' => $totalMentions > 0 ? round(($neutralMentions / $totalMentions) * 100, 2) : 0,
            ],
            'avg_engagement_rate' => round($avgEngagementRate, 2),
            'total_engagement' => $totalEngagement,
            'platform_breakdown' => $platformBreakdown,
            'influencer_mentions' => $influencerMentions,
            'needing_response' => $needingResponse,
            'period_days' => $days,
        ];
    }

    /**
     * Get sentiment timeline
     */
    public function getSentimentTimeline(
        string $orgId,
        ?string $keywordId = null,
        int $days = 30
    ): array {
        $query = DB::table('cmis.social_mentions')
            ->where('org_id', $orgId)
            ->where('published_at', '>=', now()->subDays($days));

        if ($keywordId) {
            $query->where('keyword_id', $keywordId);
        }

        $timeline = $query
            ->select(
                DB::raw('DATE(published_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("COUNT(CASE WHEN sentiment = 'positive' THEN 1 END) as positive"),
                DB::raw("COUNT(CASE WHEN sentiment = 'negative' THEN 1 END) as negative"),
                DB::raw("COUNT(CASE WHEN sentiment = 'neutral' THEN 1 END) as neutral"),
                DB::raw('AVG(sentiment_score) as avg_sentiment')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return $timeline;
    }

    /**
     * Sync platform metrics
     */
    public function syncMentionMetrics(SocialMention $mention): void
    {
        // This would call the platform API to get latest metrics
        // For now, this is a placeholder

        Log::info('Syncing metrics for mention', [
            'mention_id' => $mention->mention_id,
            'platform' => $mention->platform,
        ]);

        // In real implementation, call platform API here
        // $metrics = PlatformAPIService::getPostMetrics($mention->platform, $mention->platform_post_id);
        // $mention->updateMetrics($metrics);
    }

    /**
     * Get top authors
     */
    public function getTopAuthors(string $orgId, ?string $keywordId = null, int $limit = 10): array
    {
        $query = SocialMention::where('org_id', $orgId);

        if ($keywordId) {
            $query->where('keyword_id', $keywordId);
        }

        return $query
            ->select(
                'author_username',
                'author_display_name',
                'author_followers_count',
                'author_is_verified',
                DB::raw('COUNT(*) as mention_count'),
                DB::raw('AVG(engagement_rate) as avg_engagement')
            )
            ->groupBy('author_username', 'author_display_name', 'author_followers_count', 'author_is_verified')
            ->orderBy('mention_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
