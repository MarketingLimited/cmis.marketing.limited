<?php

namespace App\Services;

use App\Repositories\Contracts\SocialMediaRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for AI-powered insights and recommendations
 * Implements Sprint 3.3: AI Insights
 *
 * Features:
 * - Content optimization recommendations
 * - Anomaly detection (unusual metric patterns)
 * - Predictive analytics (engagement forecasting)
 * - Smart observations and insights
 * - Competitive intelligence
 * - Best practices suggestions
 */
class AIInsightsService
{
    protected SocialMediaRepositoryInterface $socialMediaRepo;

    public function __construct(SocialMediaRepositoryInterface $socialMediaRepo)
    {
        $this->socialMediaRepo = $socialMediaRepo;
    }

    /**
     * Get AI-powered insights for account
     *
     * @param string $accountId
     * @param array $filters
     * @return array
     */
    public function getAccountInsights(string $accountId, array $filters = []): array
    {
        $cacheKey = "ai_insights:{$accountId}:" . md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($accountId, $filters) {
            try {
                $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
                $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

                $insights = [];

                // Content optimization recommendations
                $insights['content_recommendations'] = $this->generateContentRecommendations($accountId, $startDate, $endDate);

                // Anomaly detection
                $insights['anomalies'] = $this->detectAnomalies($accountId, $startDate, $endDate);

                // Performance predictions
                $insights['predictions'] = $this->generatePredictions($accountId);

                // Smart observations
                $insights['observations'] = $this->generateObservations($accountId, $startDate, $endDate);

                // Optimization opportunities
                $insights['optimization_opportunities'] = $this->identifyOptimizationOpportunities($accountId, $startDate, $endDate);

                return [
                    'success' => true,
                    'insights' => $insights,
                    'generated_at' => now()->toIso8601String(),
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('Failed to get AI insights', [
                    'account_id' => $accountId,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Generate content recommendations
     *
     * @param string $accountId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function generateContentRecommendations(string $accountId, string $startDate, string $endDate): array
    {
        $recommendations = [];

        // Analyze content type performance
        $contentPerformance = DB::table('cmis.social_posts as sp')
            ->join('cmis.post_metrics as pm', 'sp.post_id', '=', 'pm.post_id')
            ->where('sp.social_account_id', $accountId)
            ->where('sp.status', 'published')
            ->whereBetween('sp.published_at', [$startDate, $endDate])
            ->selectRaw('sp.media_type')
            ->selectRaw('AVG(CASE WHEN pm.impressions > 0 THEN ((pm.likes + pm.comments + pm.shares)::float / pm.impressions) * 100 ELSE 0 END) as avg_engagement_rate')
            ->selectRaw('COUNT(*) as post_count')
            ->groupBy('sp.media_type')
            ->get();

        // Find best performing content type
        $bestType = $contentPerformance->sortByDesc('avg_engagement_rate')->first();
        if ($bestType && $bestType->post_count >= 5) {
            $recommendations[] = [
                'type' => 'content_type',
                'priority' => 'high',
                'title' => 'Optimize Content Type Mix',
                'message' => sprintf(
                    '%s posts perform %.1f%% better than average. Consider creating more %s content.',
                    ucfirst($bestType->media_type ?? 'text'),
                    $bestType->avg_engagement_rate,
                    $bestType->media_type ?? 'text'
                ),
                'impact' => 'high',
                'effort' => 'medium'
            ];
        }

        // Analyze posting frequency
        $totalPosts = DB::table('cmis.social_posts')
            ->where('social_account_id', $accountId)
            ->where('status', 'published')
            ->whereBetween('published_at', [$startDate, $endDate])
            ->count();

        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $avgPostsPerDay = $totalPosts / $days;

        if ($avgPostsPerDay < 1) {
            $recommendations[] = [
                'type' => 'posting_frequency',
                'priority' => 'high',
                'title' => 'Increase Posting Frequency',
                'message' => sprintf(
                    'You\'re posting %.1f times per day. Industry best practice is 1-3 posts per day to maintain engagement.',
                    $avgPostsPerDay
                ),
                'impact' => 'high',
                'effort' => 'medium'
            ];
        } elseif ($avgPostsPerDay > 5) {
            $recommendations[] = [
                'type' => 'posting_frequency',
                'priority' => 'medium',
                'title' => 'Consider Reducing Post Frequency',
                'message' => sprintf(
                    'You\'re posting %.1f times per day. This may lead to audience fatigue. Consider optimizing for quality over quantity.',
                    $avgPostsPerDay
                ),
                'impact' => 'medium',
                'effort' => 'low'
            ];
        }

        // Analyze hashtag usage
        $postsWithHashtags = DB::table('cmis.social_posts')
            ->where('social_account_id', $accountId)
            ->where('status', 'published')
            ->whereBetween('published_at', [$startDate, $endDate])
            ->whereRaw("content ~ '#\\w+'")
            ->count();

        $hashtagUsageRate = $totalPosts > 0 ? ($postsWithHashtags / $totalPosts) * 100 : 0;

        if ($hashtagUsageRate < 50) {
            $recommendations[] = [
                'type' => 'hashtags',
                'priority' => 'medium',
                'title' => 'Increase Hashtag Usage',
                'message' => sprintf(
                    'Only %.1f%% of your posts use hashtags. Strategic hashtag use can increase discoverability by 30-50%%.',
                    $hashtagUsageRate
                ),
                'impact' => 'high',
                'effort' => 'low'
            ];
        }

        // Analyze content length
        $avgContentLength = DB::table('cmis.social_posts')
            ->where('social_account_id', $accountId)
            ->where('status', 'published')
            ->whereBetween('published_at', [$startDate, $endDate])
            ->whereNotNull('content')
            ->selectRaw('AVG(LENGTH(content)) as avg_length')
            ->first();

        if ($avgContentLength && $avgContentLength->avg_length < 50) {
            $recommendations[] = [
                'type' => 'content_length',
                'priority' => 'low',
                'title' => 'Expand Content Depth',
                'message' => sprintf(
                    'Your average post length is %.0f characters. Posts with 100-150 characters typically see higher engagement.',
                    $avgContentLength->avg_length
                ),
                'impact' => 'medium',
                'effort' => 'low'
            ];
        }

        return $recommendations;
    }

    /**
     * Detect anomalies in metrics
     *
     * @param string $accountId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function detectAnomalies(string $accountId, string $startDate, string $endDate): array
    {
        $anomalies = [];

        try {
            // Get daily metrics
            $dailyMetrics = DB::table('cmis.social_posts as sp')
                ->join('cmis.post_metrics as pm', 'sp.post_id', '=', 'pm.post_id')
                ->where('sp.social_account_id', $accountId)
                ->where('sp.status', 'published')
                ->whereBetween('sp.published_at', [$startDate, $endDate])
                ->selectRaw('DATE(sp.published_at) as date')
                ->selectRaw('AVG(CASE WHEN pm.impressions > 0 THEN ((pm.likes + pm.comments + pm.shares)::float / pm.impressions) * 100 ELSE 0 END) as engagement_rate')
                ->selectRaw('SUM(pm.reach) as total_reach')
                ->selectRaw('COUNT(*) as post_count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            if ($dailyMetrics->count() >= 7) {
                // Calculate statistics for engagement rate
                $engagementRates = $dailyMetrics->pluck('engagement_rate')->filter(fn($r) => $r > 0);
                $mean = $engagementRates->avg();
                $stdDev = $this->calculateStandardDeviation($engagementRates->toArray(), $mean);

                // Detect anomalies (2 standard deviations from mean)
                foreach ($dailyMetrics as $metric) {
                    if ($metric->engagement_rate > 0) {
                        $zScore = abs(($metric->engagement_rate - $mean) / ($stdDev ?: 1));

                        if ($zScore > 2) {
                            $anomalies[] = [
                                'date' => $metric->date,
                                'type' => $metric->engagement_rate > $mean ? 'spike' : 'drop',
                                'metric' => 'engagement_rate',
                                'value' => round($metric->engagement_rate, 2),
                                'expected_range' => [
                                    'min' => round($mean - 2 * $stdDev, 2),
                                    'max' => round($mean + 2 * $stdDev, 2)
                                ],
                                'severity' => $zScore > 3 ? 'high' : 'medium',
                                'message' => sprintf(
                                    'Unusual %s in engagement rate on %s (%.2f%% vs %.2f%% average)',
                                    $metric->engagement_rate > $mean ? 'spike' : 'drop',
                                    $metric->date,
                                    $metric->engagement_rate,
                                    $mean
                                )
                            ];
                        }
                    }
                }

                // Detect reach anomalies
                $reaches = $dailyMetrics->pluck('total_reach')->filter(fn($r) => $r > 0);
                if ($reaches->count() >= 7) {
                    $reachMean = $reaches->avg();
                    $reachStdDev = $this->calculateStandardDeviation($reaches->toArray(), $reachMean);

                    foreach ($dailyMetrics as $metric) {
                        if ($metric->total_reach > 0) {
                            $zScore = abs(($metric->total_reach - $reachMean) / ($reachStdDev ?: 1));

                            if ($zScore > 2.5) {
                                $anomalies[] = [
                                    'date' => $metric->date,
                                    'type' => $metric->total_reach > $reachMean ? 'spike' : 'drop',
                                    'metric' => 'reach',
                                    'value' => $metric->total_reach,
                                    'expected_range' => [
                                        'min' => round($reachMean - 2.5 * $reachStdDev, 0),
                                        'max' => round($reachMean + 2.5 * $reachStdDev, 0)
                                    ],
                                    'severity' => $zScore > 3 ? 'high' : 'medium',
                                    'message' => sprintf(
                                        'Unusual %s in reach on %s (%d vs %d average)',
                                        $metric->total_reach > $reachMean ? 'spike' : 'drop',
                                        $metric->date,
                                        $metric->total_reach,
                                        round($reachMean)
                                    )
                                ];
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to detect anomalies', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
        }

        return $anomalies;
    }

    /**
     * Generate predictions
     *
     * @param string $accountId
     * @return array
     */
    protected function generatePredictions(string $accountId): array
    {
        $predictions = [];

        try {
            // Get last 30 days of metrics
            $historicalData = DB::table('cmis.social_posts as sp')
                ->join('cmis.post_metrics as pm', 'sp.post_id', '=', 'pm.post_id')
                ->where('sp.social_account_id', $accountId)
                ->where('sp.status', 'published')
                ->where('sp.published_at', '>=', Carbon::now()->subDays(30))
                ->selectRaw('DATE(sp.published_at) as date')
                ->selectRaw('AVG(pm.likes + pm.comments + pm.shares) as avg_engagement')
                ->selectRaw('AVG(pm.reach) as avg_reach')
                ->selectRaw('COUNT(*) as post_count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            if ($historicalData->count() >= 7) {
                // Simple linear trend prediction for next 7 days
                $engagements = $historicalData->pluck('avg_engagement')->toArray();
                $trend = $this->calculateTrend($engagements);

                $lastEngagement = end($engagements);
                $predictedEngagement = $lastEngagement + ($trend * 7);

                $predictions[] = [
                    'metric' => 'engagement',
                    'timeframe' => 'next_7_days',
                    'predicted_value' => round($predictedEngagement, 2),
                    'current_value' => round($lastEngagement, 2),
                    'trend' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
                    'confidence' => $this->calculatePredictionConfidence($historicalData->count()),
                    'message' => sprintf(
                        'Engagement is predicted to %s by %.1f%% over the next week',
                        $trend > 0 ? 'increase' : ($trend < 0 ? 'decrease' : 'remain stable'),
                        abs(($predictedEngagement - $lastEngagement) / $lastEngagement * 100)
                    )
                ];

                // Predict follower growth
                $account = DB::table('cmis.social_accounts')
                    ->where('social_account_id', $accountId)
                    ->first();

                if ($account) {
                    $avgPostsPerDay = $historicalData->avg('post_count');
                    $avgEngagementRate = $historicalData->avg('avg_engagement') / ($historicalData->avg('avg_reach') ?: 1) * 100;

                    // Simple follower growth prediction based on engagement
                    $predictedGrowthRate = $avgEngagementRate * 0.05; // 5% of engagement converts to followers
                    $predictedFollowers = round($account->followers_count * (1 + $predictedGrowthRate / 100));

                    $predictions[] = [
                        'metric' => 'followers',
                        'timeframe' => 'next_30_days',
                        'predicted_value' => $predictedFollowers,
                        'current_value' => $account->followers_count,
                        'growth_rate' => round($predictedGrowthRate, 2),
                        'confidence' => 'medium',
                        'message' => sprintf(
                            'Follower count predicted to reach %d (%.1f%% growth) in the next 30 days',
                            $predictedFollowers,
                            $predictedGrowthRate
                        )
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate predictions', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
        }

        return $predictions;
    }

    /**
     * Generate smart observations
     *
     * @param string $accountId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function generateObservations(string $accountId, string $startDate, string $endDate): array
    {
        $observations = [];

        try {
            // Analyze day-of-week performance
            $dayPerformance = DB::table('cmis.social_posts as sp')
                ->join('cmis.post_metrics as pm', 'sp.post_id', '=', 'pm.post_id')
                ->where('sp.social_account_id', $accountId)
                ->where('sp.status', 'published')
                ->whereBetween('sp.published_at', [$startDate, $endDate])
                ->selectRaw('EXTRACT(DOW FROM sp.published_at) as day')
                ->selectRaw('AVG(CASE WHEN pm.impressions > 0 THEN ((pm.likes + pm.comments + pm.shares)::float / pm.impressions) * 100 ELSE 0 END) as engagement_rate')
                ->groupBy('day')
                ->get();

            if ($dayPerformance->count() >= 5) {
                $bestDay = $dayPerformance->sortByDesc('engagement_rate')->first();
                $worstDay = $dayPerformance->sortBy('engagement_rate')->first();
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                $observations[] = [
                    'category' => 'timing',
                    'importance' => 'high',
                    'title' => 'Best Day Performance Pattern',
                    'message' => sprintf(
                        '%s shows %.1f%% higher engagement than %s. Consider posting more on %s.',
                        $days[(int)$bestDay->day],
                        (($bestDay->engagement_rate - $worstDay->engagement_rate) / $worstDay->engagement_rate) * 100,
                        $days[(int)$worstDay->day],
                        $days[(int)$bestDay->day]
                    ),
                    'data' => [
                        'best_day' => $days[(int)$bestDay->day],
                        'best_rate' => round($bestDay->engagement_rate, 2),
                        'worst_day' => $days[(int)$worstDay->day],
                        'worst_rate' => round($worstDay->engagement_rate, 2)
                    ]
                ];
            }

            // Analyze engagement velocity
            $recentEngagement = DB::table('cmis.social_posts as sp')
                ->join('cmis.post_metrics as pm', 'sp.post_id', '=', 'pm.post_id')
                ->where('sp.social_account_id', $accountId)
                ->where('sp.status', 'published')
                ->where('sp.published_at', '>=', Carbon::parse($endDate)->subDays(7))
                ->whereBetween('sp.published_at', [$startDate, $endDate])
                ->selectRaw('AVG(pm.likes + pm.comments + pm.shares) as avg_engagement')
                ->first();

            $olderEngagement = DB::table('cmis.social_posts as sp')
                ->join('cmis.post_metrics as pm', 'sp.post_id', '=', 'pm.post_id')
                ->where('sp.social_account_id', $accountId)
                ->where('sp.status', 'published')
                ->where('sp.published_at', '<', Carbon::parse($endDate)->subDays(7))
                ->whereBetween('sp.published_at', [$startDate, $endDate])
                ->selectRaw('AVG(pm.likes + pm.comments + pm.shares) as avg_engagement')
                ->first();

            if ($recentEngagement && $olderEngagement && $olderEngagement->avg_engagement > 0) {
                $changePercent = (($recentEngagement->avg_engagement - $olderEngagement->avg_engagement) / $olderEngagement->avg_engagement) * 100;

                if (abs($changePercent) > 15) {
                    $observations[] = [
                        'category' => 'performance',
                        'importance' => 'high',
                        'title' => 'Engagement Momentum',
                        'message' => sprintf(
                            'Your engagement has %s by %.1f%% in the last 7 days compared to the previous period.',
                            $changePercent > 0 ? 'increased' : 'decreased',
                            abs($changePercent)
                        ),
                        'data' => [
                            'trend' => $changePercent > 0 ? 'positive' : 'negative',
                            'change_percent' => round($changePercent, 1),
                            'recent_avg' => round($recentEngagement->avg_engagement, 2),
                            'previous_avg' => round($olderEngagement->avg_engagement, 2)
                        ]
                    ];
                }
            }

            // Analyze response rate
            $totalComments = DB::table('cmis.social_posts as sp')
                ->join('cmis.post_metrics as pm', 'sp.post_id', '=', 'pm.post_id')
                ->where('sp.social_account_id', $accountId)
                ->where('sp.status', 'published')
                ->whereBetween('sp.published_at', [$startDate, $endDate])
                ->sum('pm.comments');

            if ($totalComments > 10) {
                $observations[] = [
                    'category' => 'community',
                    'importance' => 'medium',
                    'title' => 'Community Engagement',
                    'message' => sprintf(
                        'You received %d comments in this period. Responding to comments can increase engagement by 20-40%%.',
                        $totalComments
                    ),
                    'data' => [
                        'total_comments' => $totalComments,
                        'recommendation' => 'Prioritize responding to comments within 24 hours'
                    ]
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate observations', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
        }

        return $observations;
    }

    /**
     * Identify optimization opportunities
     *
     * @param string $accountId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function identifyOptimizationOpportunities(string $accountId, string $startDate, string $endDate): array
    {
        $opportunities = [];

        try {
            // Check posting consistency
            $postingDays = DB::table('cmis.social_posts')
                ->where('social_account_id', $accountId)
                ->where('status', 'published')
                ->whereBetween('published_at', [$startDate, $endDate])
                ->selectRaw('COUNT(DISTINCT DATE(published_at)) as unique_days')
                ->first();

            $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $postingConsistency = ($postingDays->unique_days / $totalDays) * 100;

            if ($postingConsistency < 70) {
                $opportunities[] = [
                    'category' => 'consistency',
                    'priority' => 'high',
                    'title' => 'Improve Posting Consistency',
                    'description' => sprintf(
                        'You posted on only %.0f%% of days. Consistent posting schedules improve algorithm performance and audience retention.',
                        $postingConsistency
                    ),
                    'potential_impact' => '+25-35% reach increase',
                    'action_items' => [
                        'Set up a content calendar',
                        'Use publishing queues for automated scheduling',
                        'Batch-create content in advance'
                    ]
                ];
            }

            // Check for unused optimal times
            $bestTimes = DB::table('cmis.social_posts as sp')
                ->join('cmis.post_metrics as pm', 'sp.post_id', '=', 'pm.post_id')
                ->where('sp.social_account_id', $accountId)
                ->where('sp.status', 'published')
                ->whereBetween('sp.published_at', [$startDate, $endDate])
                ->selectRaw('EXTRACT(HOUR FROM sp.published_at) as hour')
                ->selectRaw('AVG(CASE WHEN pm.impressions > 0 THEN ((pm.likes + pm.comments + pm.shares)::float / pm.impressions) * 100 ELSE 0 END) as engagement_rate')
                ->groupBy('hour')
                ->havingRaw('COUNT(*) >= 3')
                ->orderByDesc('engagement_rate')
                ->limit(3)
                ->get();

            if ($bestTimes->count() >= 2) {
                $opportunities[] = [
                    'category' => 'timing',
                    'priority' => 'medium',
                    'title' => 'Leverage Peak Engagement Hours',
                    'description' => sprintf(
                        'Posts at %s show highest engagement. Schedule more content during these times.',
                        $bestTimes->pluck('hour')->map(fn($h) => sprintf('%02d:00', $h))->join(', ')
                    ),
                    'potential_impact' => '+15-25% engagement increase',
                    'action_items' => [
                        'Review your publishing queue settings',
                        'Shift post timing to peak hours',
                        'Use AI-suggested best times feature'
                    ]
                ];
            }

            // Check media usage
            $mediaUsage = DB::table('cmis.social_posts')
                ->where('social_account_id', $accountId)
                ->where('status', 'published')
                ->whereBetween('published_at', [$startDate, $endDate])
                ->selectRaw('CASE WHEN media_type IS NOT NULL AND media_type != \'text\' THEN \'with_media\' ELSE \'text_only\' END as has_media')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('has_media')
                ->get()
                ->pluck('count', 'has_media');

            $totalPosts = $mediaUsage->sum();
            $mediaRate = $totalPosts > 0 ? (($mediaUsage->get('with_media', 0) / $totalPosts) * 100) : 0;

            if ($mediaRate < 60) {
                $opportunities[] = [
                    'category' => 'content',
                    'priority' => 'high',
                    'title' => 'Increase Visual Content',
                    'description' => sprintf(
                        'Only %.0f%% of your posts include media. Visual content gets 2-3x more engagement than text-only posts.',
                        $mediaRate
                    ),
                    'potential_impact' => '+40-60% engagement increase',
                    'action_items' => [
                        'Add images or videos to at least 70% of posts',
                        'Use design tools for branded graphics',
                        'Repurpose existing content into visual formats'
                    ]
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to identify optimization opportunities', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
        }

        return $opportunities;
    }

    /**
     * Get competitive intelligence insights
     *
     * @param string $accountId
     * @param array $filters
     * @return array
     */
    public function getCompetitiveInsights(string $accountId, array $filters = []): array
    {
        // Placeholder for competitive analysis
        // Would integrate with third-party APIs (Brandwatch, Mention, etc.)

        return [
            'success' => true,
            'note' => 'Competitive intelligence requires third-party integrations',
            'available_integrations' => [
                'brandwatch' => 'Social listening and competitor tracking',
                'mention' => 'Brand monitoring and competitive analysis',
                'sprout_social' => 'Competitor benchmarking'
            ],
            'data' => []
        ];
    }

    /**
     * Calculate standard deviation
     *
     * @param array $values
     * @param float $mean
     * @return float
     */
    protected function calculateStandardDeviation(array $values, float $mean): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $variance = array_reduce($values, function ($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0) / count($values);

        return sqrt($variance);
    }

    /**
     * Calculate trend from time series data
     *
     * @param array $values
     * @return float
     */
    protected function calculateTrend(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        // Simple linear regression slope
        $n = count($values);
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumXX = 0;

        foreach ($values as $i => $y) {
            $x = $i + 1;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);

        return $slope;
    }

    /**
     * Calculate prediction confidence based on data points
     *
     * @param int $dataPoints
     * @return string
     */
    protected function calculatePredictionConfidence(int $dataPoints): string
    {
        if ($dataPoints < 7) {
            return 'low';
        } elseif ($dataPoints < 14) {
            return 'medium';
        } elseif ($dataPoints < 30) {
            return 'high';
        } else {
            return 'very_high';
        }
    }
}
