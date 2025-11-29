<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\SocialMention;
use App\Models\Social\SocialSentiment;
use App\Models\Social\SocialTrend;
use App\Services\Social\SocialListeningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocialListeningController extends Controller
{
    use ApiResponse;

    private SocialListeningService $listeningService;

    public function __construct(SocialListeningService $listeningService)
    {
        $this->listeningService = $listeningService;
    }

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

        $summary = $this->listeningService->getSentimentSummary(
            $orgId,
            $request->start_date,
            $request->end_date
        );

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
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'interval' => 'nullable|in:hour,day,week,month',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');
        $interval = $request->interval ?? 'day';
        $startDate = $request->start_date ?? now()->subDays(30);
        $endDate = $request->end_date ?? now();

        $volumeData = $this->listeningService->getMentionVolume(
            $orgId,
            $startDate,
            $endDate,
            $interval
        );

        return $this->success($volumeData, 'Mention volume retrieved successfully');
    }

    /**
     * Get top influencers mentioning brand
     */
    public function topInfluencers(Request $request)
    {
        $orgId = session('current_org_id');

        $influencers = $this->listeningService->getTopInfluencers(
            $orgId,
            $request->start_date,
            $request->end_date,
            $request->get('limit', 10)
        );

        return $this->success($influencers, 'Top influencers retrieved successfully');
    }

    /**
     * Get platform distribution
     */
    public function platformDistribution(Request $request)
    {
        $orgId = session('current_org_id');

        $distribution = $this->listeningService->getPlatformDistribution(
            $orgId,
            $request->start_date,
            $request->end_date
        );

        return $this->success($distribution, 'Platform distribution retrieved successfully');
    }

    /**
     * Get keyword analysis
     */
    public function keywordAnalysis(Request $request)
    {
        $orgId = session('current_org_id');

        $keywords = $this->listeningService->analyzeKeywords(
            $orgId,
            $request->start_date,
            $request->end_date,
            $request->get('limit', 50)
        );

        return $this->success($keywords, 'Keyword analysis retrieved successfully');
    }

    /**
     * Get engagement metrics
     */
    public function engagementMetrics(Request $request)
    {
        $orgId = session('current_org_id');

        $metrics = $this->listeningService->getEngagementMetrics(
            $orgId,
            $request->start_date,
            $request->end_date
        );

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

        $comparison = $this->listeningService->getCompetitorComparison(
            $orgId,
            $request->competitor_keywords,
            $request->start_date,
            $request->end_date
        );

        return $this->success($comparison, 'Competitor comparison retrieved successfully');
    }
}
