<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\SocialMention;
use App\Models\Social\SocialSentiment;
use App\Models\Social\SocialTrend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocialListeningController extends Controller
{
    use ApiResponse;

    /**
     * Display social mentions dashboard
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $mentions = SocialMention::where('org_id', $orgId)
            ->when($request->platform, fn($q) => $q->where('platform', $request->platform))
            ->when($request->sentiment, fn($q) => $q->where('sentiment', $request->sentiment))
            ->when($request->search, fn($q) => $q->where('content', 'like', "%{$request->search}%"))
            ->when($request->start_date, fn($q) => $q->where('mentioned_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('mentioned_at', '<=', $request->end_date))
            ->with('sentimentAnalysis')
            ->latest('mentioned_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($mentions, 'Social mentions retrieved successfully');
        }

        return view('social.listening.index', compact('mentions'));
    }

    /**
     * Get sentiment analysis summary
     */
    public function sentimentSummary(Request $request)
    {
        $orgId = session('current_org_id');

        $mentions = SocialMention::where('org_id', $orgId)
            ->when($request->start_date, fn($q) => $q->where('mentioned_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('mentioned_at', '<=', $request->end_date))
            ->get();

        $totalMentions = $mentions->count();
        $positiveMentions = $mentions->where('sentiment', 'positive')->count();
        $negativeMentions = $mentions->where('sentiment', 'negative')->count();
        $neutralMentions = $mentions->where('sentiment', 'neutral')->count();

        $avgSentimentScore = $mentions->avg('sentiment_score') ?? 0;

        $summary = [
            'total_mentions' => $totalMentions,
            'positive_mentions' => $positiveMentions,
            'negative_mentions' => $negativeMentions,
            'neutral_mentions' => $neutralMentions,
            'positive_percentage' => $totalMentions > 0 ? round(($positiveMentions / $totalMentions) * 100, 2) : 0,
            'negative_percentage' => $totalMentions > 0 ? round(($negativeMentions / $totalMentions) * 100, 2) : 0,
            'avg_sentiment_score' => round($avgSentimentScore, 2),
            'sentiment_trend' => $positiveMentions > $negativeMentions ? 'positive' : ($negativeMentions > $positiveMentions ? 'negative' : 'neutral'),
        ];

        return $this->success($summary, 'Sentiment summary retrieved successfully');
    }

    /**
     * Get trending topics
     */
    public function trendingTopics(Request $request)
    {
        $orgId = session('current_org_id');

        $trends = SocialTrend::where('org_id', $orgId)
            ->when($request->platform, fn($q) => $q->where('platform', $request->platform))
            ->when($request->trend_type, fn($q) => $q->where('trend_type', $request->trend_type))
            ->orderBy('volume', 'desc')
            ->orderBy('detected_at', 'desc')
            ->limit($request->get('limit', 20))
            ->get();

        return $this->success($trends, 'Trending topics retrieved successfully');
    }

    /**
     * Get mention volume over time
     */
    public function mentionVolume(Request $request)
    {
        $orgId = session('current_org_id');

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'interval' => 'nullable|in:hour,day,week,month',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $interval = $request->interval ?? 'day';
        $startDate = $request->start_date ?? now()->subDays(30);
        $endDate = $request->end_date ?? now();

        $mentions = SocialMention::where('org_id', $orgId)
            ->whereBetween('mentioned_at', [$startDate, $endDate])
            ->get();

        $volumeData = $mentions->groupBy(function ($mention) use ($interval) {
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

        return $this->success($volumeData, 'Mention volume retrieved successfully');
    }

    /**
     * Get top influencers mentioning brand
     */
    public function topInfluencers(Request $request)
    {
        $orgId = session('current_org_id');

        $mentions = SocialMention::where('org_id', $orgId)
            ->when($request->start_date, fn($q) => $q->where('mentioned_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('mentioned_at', '<=', $request->end_date))
            ->whereNotNull('author_followers')
            ->orderBy('author_followers', 'desc')
            ->limit(20)
            ->get();

        $influencers = $mentions->groupBy('author_username')->map(function ($group) {
            $first = $group->first();
            return [
                'username' => $first->author_username,
                'followers' => $first->author_followers,
                'mention_count' => $group->count(),
                'avg_sentiment' => round($group->avg('sentiment_score'), 2),
                'platform' => $first->platform,
            ];
        })->sortByDesc('mention_count')->take($request->get('limit', 10))->values();

        return $this->success($influencers, 'Top influencers retrieved successfully');
    }

    /**
     * Get platform distribution
     */
    public function platformDistribution(Request $request)
    {
        $orgId = session('current_org_id');

        $mentions = SocialMention::where('org_id', $orgId)
            ->when($request->start_date, fn($q) => $q->where('mentioned_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('mentioned_at', '<=', $request->end_date))
            ->get();

        $distribution = $mentions->groupBy('platform')->map(function ($group) {
            return [
                'count' => $group->count(),
                'engagement' => $group->sum('engagement'),
                'reach' => $group->sum('reach'),
            ];
        });

        return $this->success($distribution, 'Platform distribution retrieved successfully');
    }

    /**
     * Get keyword analysis
     */
    public function keywordAnalysis(Request $request)
    {
        $orgId = session('current_org_id');

        $mentions = SocialMention::where('org_id', $orgId)
            ->when($request->start_date, fn($q) => $q->where('mentioned_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('mentioned_at', '<=', $request->end_date))
            ->get();

        // Extract keywords from mentions (simplified - would use NLP in production)
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
        $topKeywords = array_slice($keywords, 0, 50);

        return $this->success($topKeywords, 'Keyword analysis retrieved successfully');
    }

    /**
     * Get engagement metrics
     */
    public function engagementMetrics(Request $request)
    {
        $orgId = session('current_org_id');

        $mentions = SocialMention::where('org_id', $orgId)
            ->when($request->start_date, fn($q) => $q->where('mentioned_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('mentioned_at', '<=', $request->end_date))
            ->get();

        $metrics = [
            'total_engagement' => $mentions->sum('engagement'),
            'total_reach' => $mentions->sum('reach'),
            'avg_engagement_per_mention' => round($mentions->avg('engagement'), 2),
            'engagement_rate' => $mentions->sum('reach') > 0
                ? round(($mentions->sum('engagement') / $mentions->sum('reach')) * 100, 2)
                : 0,
            'top_performing_mention' => $mentions->sortByDesc('engagement')->first(),
        ];

        return $this->success($metrics, 'Engagement metrics retrieved successfully');
    }

    /**
     * Export social listening report
     */
    public function exportReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'format' => 'nullable|in:csv,json,pdf',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $mentions = SocialMention::where('org_id', $orgId)
            ->when($request->start_date, fn($q) => $q->where('mentioned_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('mentioned_at', '<=', $request->end_date))
            ->get();

        $report = [
            'period' => [
                'start_date' => $request->start_date ?? 'All time',
                'end_date' => $request->end_date ?? now()->toDateString(),
            ],
            'sentiment_summary' => [
                'total_mentions' => $mentions->count(),
                'positive' => $mentions->where('sentiment', 'positive')->count(),
                'negative' => $mentions->where('sentiment', 'negative')->count(),
                'neutral' => $mentions->where('sentiment', 'neutral')->count(),
            ],
            'platform_breakdown' => $mentions->groupBy('platform')->map->count(),
            'top_mentions' => $mentions->sortByDesc('engagement')->take(10)->map(fn($m) => [
                'content' => $m->content,
                'platform' => $m->platform,
                'author' => $m->author_username,
                'engagement' => $m->engagement,
                'sentiment' => $m->sentiment,
            ])->values(),
            'exported_at' => now()->toIso8601String(),
        ];

        return $this->success($report, 'Report exported successfully');
    }

    /**
     * Get competitor comparison
     */
    public function competitorComparison(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competitor_keywords' => 'required|array',
            'competitor_keywords.*' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        // Get our brand mentions
        $ourMentions = SocialMention::where('org_id', $orgId)
            ->when($request->start_date, fn($q) => $q->where('mentioned_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('mentioned_at', '<=', $request->end_date))
            ->get();

        $comparison = [
            'our_brand' => [
                'mention_count' => $ourMentions->count(),
                'positive_sentiment' => $ourMentions->where('sentiment', 'positive')->count(),
                'engagement' => $ourMentions->sum('engagement'),
                'reach' => $ourMentions->sum('reach'),
            ],
            'competitors' => [],
        ];

        // In production, this would query mentions for competitor keywords
        // For now, returning structure
        foreach ($request->competitor_keywords as $keyword) {
            $comparison['competitors'][$keyword] = [
                'mention_count' => 0,
                'positive_sentiment' => 0,
                'engagement' => 0,
                'reach' => 0,
            ];
        }

        return $this->success($comparison, 'Competitor comparison retrieved successfully');
    }
}
