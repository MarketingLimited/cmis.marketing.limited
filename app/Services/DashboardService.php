<?php

namespace App\Services;

use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Repositories\Contracts\SocialMediaRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for analytics dashboard (Hootsuite-style analytics)
 * Implements Sprint 3.1: Dashboard Redesign
 *
 * Features:
 * - Account-level analytics
 * - Organization-wide overview
 * - Engagement metrics
 * - Follower growth tracking
 * - Top performing content
 * - Platform comparison
 * - Real-time data with caching
 */
class DashboardService
{
    protected AnalyticsRepositoryInterface $analyticsRepo;
    protected SocialMediaRepositoryInterface $socialMediaRepo;

    public function __construct(
        AnalyticsRepositoryInterface $analyticsRepo,
        SocialMediaRepositoryInterface $socialMediaRepo
    ) {
        $this->analyticsRepo = $analyticsRepo;
        $this->socialMediaRepo = $socialMediaRepo;
    }

    /**
     * Get comprehensive account dashboard data
     *
     * @param string $accountId
     * @param array $filters ['start_date', 'end_date', 'compare_period']
     * @return array
     */
    public function getAccountDashboard(string $accountId, array $filters = []): array
    {
        $cacheKey = "dashboard:account:{$accountId}:" . md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($accountId, $filters) {
            try {
                $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
                $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

                // Get account metrics
                $metrics = $this->socialMediaRepo->getAccountMetrics($accountId, $startDate, $endDate);

                // Get engagement trends
                $trends = $this->socialMediaRepo->getEngagementTrends($accountId, $filters['period'] ?? 'daily');

                // Get top posts
                $topPosts = $this->getTopPosts($accountId, $startDate, $endDate, 5);

                // Get follower growth
                $followerGrowth = $this->getFollowerGrowth($accountId, $startDate, $endDate);

                // Calculate period-over-period changes
                $comparisons = $this->calculatePeriodComparisons($accountId, $startDate, $endDate);

                return [
                    'overview' => [
                        'followers' => [
                            'current' => $metrics->followers_count ?? 0,
                            'growth' => $comparisons['followers_growth'] ?? 0,
                            'growth_percentage' => $comparisons['followers_growth_pct'] ?? 0,
                            'trend' => $this->determineTrend($comparisons['followers_growth'] ?? 0),
                        ],
                        'engagement' => [
                            'rate' => round($metrics->avg_engagement_rate ?? 0, 2),
                            'change' => $comparisons['engagement_change'] ?? 0,
                            'change_percentage' => $comparisons['engagement_change_pct'] ?? 0,
                            'total_interactions' => $metrics->total_interactions ?? 0,
                        ],
                        'reach' => [
                            'total' => $metrics->total_reach ?? 0,
                            'change' => $comparisons['reach_change'] ?? 0,
                            'change_percentage' => $comparisons['reach_change_pct'] ?? 0,
                            'impressions' => $metrics->total_impressions ?? 0,
                        ],
                        'posts' => [
                            'total' => $metrics->posts_count ?? 0,
                            'published' => $metrics->published_count ?? 0,
                            'scheduled' => $metrics->scheduled_count ?? 0,
                            'avg_per_day' => $this->calculateAvgPostsPerDay($metrics->posts_count ?? 0, $startDate, $endDate),
                        ],
                    ],
                    'engagement_breakdown' => [
                        'likes' => $metrics->total_likes ?? 0,
                        'comments' => $metrics->total_comments ?? 0,
                        'shares' => $metrics->total_shares ?? 0,
                        'clicks' => $metrics->total_clicks ?? 0,
                    ],
                    'trends' => $trends->toArray(),
                    'top_posts' => $topPosts,
                    'follower_growth' => $followerGrowth,
                    'best_times' => $this->getBestPostingTimes($accountId),
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate,
                        'days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
                    ],
                ];

            } catch (\Exception $e) {
                Log::error('Failed to get account dashboard', [
                    'account_id' => $accountId,
                    'error' => $e->getMessage()
                ]);

                return $this->getEmptyDashboard($accountId, $filters);
            }
        });
    }

    /**
     * Get organization-wide overview
     *
     * @param string $orgId
     * @param array $filters
     * @return array
     */
    public function getOrgOverview(string $orgId, array $filters = []): array
    {
        $cacheKey = "dashboard:org:{$orgId}:" . md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($orgId, $filters) {
            try {
                $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
                $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

                // Get all accounts for org
                $accounts = DB::table('cmis.social_accounts')
                    ->where('org_id', $orgId)
                    ->where('is_active', true)
                    ->get();

                $totalFollowers = 0;
                $totalPosts = 0;
                $totalEngagement = 0;
                $accountPerformance = [];

                foreach ($accounts as $account) {
                    $metrics = $this->socialMediaRepo->getAccountMetrics($account->social_account_id, $startDate, $endDate);

                    $totalFollowers += $metrics->followers_count ?? 0;
                    $totalPosts += $metrics->posts_count ?? 0;
                    $totalEngagement += $metrics->total_interactions ?? 0;

                    $accountPerformance[] = [
                        'account_id' => $account->social_account_id,
                        'platform' => $account->platform,
                        'account_name' => $account->account_name,
                        'followers' => $metrics->followers_count ?? 0,
                        'engagement_rate' => round($metrics->avg_engagement_rate ?? 0, 2),
                        'posts' => $metrics->posts_count ?? 0,
                    ];
                }

                // Sort by engagement rate
                usort($accountPerformance, function ($a, $b) {
                    return $b['engagement_rate'] <=> $a['engagement_rate'];
                });

                return [
                    'summary' => [
                        'total_accounts' => count($accounts),
                        'active_accounts' => count($accounts),
                        'total_followers' => $totalFollowers,
                        'total_posts' => $totalPosts,
                        'total_engagement' => $totalEngagement,
                        'avg_engagement_rate' => $totalPosts > 0
                            ? round(($totalEngagement / $totalPosts), 2)
                            : 0,
                    ],
                    'by_platform' => $this->getOrgMetricsByPlatform($orgId, $startDate, $endDate),
                    'top_performing_accounts' => array_slice($accountPerformance, 0, 5),
                    'all_accounts' => $accountPerformance,
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate,
                    ],
                ];

            } catch (\Exception $e) {
                Log::error('Failed to get org overview', [
                    'org_id' => $orgId,
                    'error' => $e->getMessage()
                ]);

                return [
                    'summary' => [
                        'total_accounts' => 0,
                        'total_followers' => 0,
                        'total_posts' => 0,
                        'avg_engagement_rate' => 0,
                    ],
                    'by_platform' => [],
                    'top_performing_accounts' => [],
                ];
            }
        });
    }

    /**
     * Get content performance breakdown
     *
     * @param string $accountId
     * @param array $filters
     * @return array
     */
    public function getContentPerformance(string $accountId, array $filters = []): array
    {
        $cacheKey = "dashboard:content:{$accountId}:" . md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($accountId, $filters) {
            try {
                $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
                $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

                // Performance by content type
                $byType = DB::table('cmis.social_posts')
                    ->where('social_account_id', $accountId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'published')
                    ->select(
                        'post_type',
                        DB::raw('COUNT(*) as count'),
                        DB::raw('AVG(engagement_rate) as avg_engagement'),
                        DB::raw('SUM(likes_count) as total_likes'),
                        DB::raw('SUM(comments_count) as total_comments'),
                        DB::raw('SUM(shares_count) as total_shares')
                    )
                    ->groupBy('post_type')
                    ->get();

                // Top hashtags
                $topHashtags = $this->getTopHashtags($accountId, $startDate, $endDate);

                // Performance by day of week
                $byDayOfWeek = DB::table('cmis.social_posts')
                    ->where('social_account_id', $accountId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'published')
                    ->select(
                        DB::raw('EXTRACT(DOW FROM created_at) as day_of_week'),
                        DB::raw('COUNT(*) as count'),
                        DB::raw('AVG(engagement_rate) as avg_engagement')
                    )
                    ->groupBy('day_of_week')
                    ->orderBy('day_of_week')
                    ->get();

                return [
                    'by_type' => $byType->mapWithKeys(function ($item) {
                        return [$item->post_type => [
                            'count' => $item->count,
                            'avg_engagement' => round($item->avg_engagement ?? 0, 2),
                            'total_likes' => $item->total_likes ?? 0,
                            'total_comments' => $item->total_comments ?? 0,
                            'total_shares' => $item->total_shares ?? 0,
                        ]];
                    })->toArray(),
                    'by_day_of_week' => $byDayOfWeek->map(function ($item) {
                        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        return [
                            'day' => $days[$item->day_of_week] ?? 'Unknown',
                            'count' => $item->count,
                            'avg_engagement' => round($item->avg_engagement ?? 0, 2),
                        ];
                    })->toArray(),
                    'top_hashtags' => $topHashtags,
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate,
                    ],
                ];

            } catch (\Exception $e) {
                Log::error('Failed to get content performance', [
                    'account_id' => $accountId,
                    'error' => $e->getMessage()
                ]);

                return [
                    'by_type' => [],
                    'by_day_of_week' => [],
                    'top_hashtags' => [],
                ];
            }
        });
    }

    /**
     * Get platform comparison data
     *
     * @param string $orgId
     * @param array $filters
     * @return array
     */
    public function getPlatformComparison(string $orgId, array $filters = []): array
    {
        try {
            $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
            $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

            return $this->getOrgMetricsByPlatform($orgId, $startDate, $endDate);

        } catch (\Exception $e) {
            Log::error('Failed to get platform comparison', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get top performing posts
     *
     * @param string $accountId
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return array
     */
    protected function getTopPosts(string $accountId, string $startDate, string $endDate, int $limit = 5): array
    {
        try {
            $posts = DB::table('cmis.social_posts')
                ->where('social_account_id', $accountId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'published')
                ->orderByDesc('engagement_rate')
                ->limit($limit)
                ->get([
                    'post_id',
                    'content',
                    'post_type',
                    'engagement_rate',
                    'likes_count',
                    'comments_count',
                    'shares_count',
                    'created_at'
                ]);

            return $posts->map(function ($post) {
                return [
                    'post_id' => $post->post_id,
                    'content' => substr($post->content, 0, 100) . (strlen($post->content) > 100 ? '...' : ''),
                    'type' => $post->post_type,
                    'engagement_rate' => round($post->engagement_rate ?? 0, 2),
                    'interactions' => [
                        'likes' => $post->likes_count ?? 0,
                        'comments' => $post->comments_count ?? 0,
                        'shares' => $post->shares_count ?? 0,
                    ],
                    'published_at' => $post->created_at,
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get top posts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get follower growth data
     *
     * @param string $accountId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function getFollowerGrowth(string $accountId, string $startDate, string $endDate): array
    {
        try {
            // This would query a follower_history table in production
            // For now, return sample data structure
            return [
                'chart_data' => [],
                'net_growth' => 0,
                'growth_rate' => 0,
            ];

        } catch (\Exception $e) {
            return ['chart_data' => [], 'net_growth' => 0];
        }
    }

    /**
     * Calculate period-over-period comparisons
     *
     * @param string $accountId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function calculatePeriodComparisons(string $accountId, string $startDate, string $endDate): array
    {
        try {
            $currentPeriod = $this->socialMediaRepo->getAccountMetrics($accountId, $startDate, $endDate);

            // Calculate previous period dates
            $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $prevStart = Carbon::parse($startDate)->subDays($days)->toDateString();
            $prevEnd = Carbon::parse($startDate)->subDay()->toDateString();

            $previousPeriod = $this->socialMediaRepo->getAccountMetrics($accountId, $prevStart, $prevEnd);

            return [
                'followers_growth' => ($currentPeriod->followers_count ?? 0) - ($previousPeriod->followers_count ?? 0),
                'followers_growth_pct' => $this->calculatePercentageChange(
                    $previousPeriod->followers_count ?? 0,
                    $currentPeriod->followers_count ?? 0
                ),
                'engagement_change' => ($currentPeriod->avg_engagement_rate ?? 0) - ($previousPeriod->avg_engagement_rate ?? 0),
                'engagement_change_pct' => $this->calculatePercentageChange(
                    $previousPeriod->avg_engagement_rate ?? 0,
                    $currentPeriod->avg_engagement_rate ?? 0
                ),
                'reach_change' => ($currentPeriod->total_reach ?? 0) - ($previousPeriod->total_reach ?? 0),
                'reach_change_pct' => $this->calculatePercentageChange(
                    $previousPeriod->total_reach ?? 0,
                    $currentPeriod->total_reach ?? 0
                ),
            ];

        } catch (\Exception $e) {
            return [
                'followers_growth' => 0,
                'followers_growth_pct' => 0,
                'engagement_change' => 0,
                'engagement_change_pct' => 0,
                'reach_change' => 0,
                'reach_change_pct' => 0,
            ];
        }
    }

    /**
     * Get metrics by platform for organization
     *
     * @param string $orgId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function getOrgMetricsByPlatform(string $orgId, string $startDate, string $endDate): array
    {
        try {
            $platforms = DB::table('cmis.social_accounts as sa')
                ->join('cmis.social_posts as sp', 'sa.social_account_id', '=', 'sp.social_account_id')
                ->where('sa.org_id', $orgId)
                ->whereBetween('sp.created_at', [$startDate, $endDate])
                ->where('sp.status', 'published')
                ->select(
                    'sa.platform',
                    DB::raw('COUNT(DISTINCT sa.social_account_id) as account_count'),
                    DB::raw('COUNT(sp.post_id) as posts_count'),
                    DB::raw('AVG(sp.engagement_rate) as avg_engagement'),
                    DB::raw('SUM(sp.likes_count) as total_likes'),
                    DB::raw('SUM(sp.comments_count) as total_comments'),
                    DB::raw('SUM(sp.shares_count) as total_shares')
                )
                ->groupBy('sa.platform')
                ->get();

            return $platforms->mapWithKeys(function ($item) {
                return [$item->platform => [
                    'account_count' => $item->account_count,
                    'posts_count' => $item->posts_count,
                    'avg_engagement' => round($item->avg_engagement ?? 0, 2),
                    'total_interactions' => ($item->total_likes ?? 0) + ($item->total_comments ?? 0) + ($item->total_shares ?? 0),
                ]];
            })->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get top hashtags
     *
     * @param string $accountId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function getTopHashtags(string $accountId, string $startDate, string $endDate): array
    {
        // This would analyze hashtags from posts
        // Placeholder for now
        return [];
    }

    /**
     * Get best posting times
     *
     * @param string $accountId
     * @return array
     */
    protected function getBestPostingTimes(string $accountId): array
    {
        try {
            $results = $this->socialMediaRepo->analyzeBestPostingTimes($accountId, 30);

            return $results->take(3)->map(function ($slot) {
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                return [
                    'day' => $days[$slot->day_of_week ?? 0] ?? 'Unknown',
                    'time' => sprintf('%02d:00', $slot->hour ?? 0),
                    'engagement_rate' => round($slot->avg_engagement_rate ?? 0, 2),
                ];
            })->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calculate percentage change
     *
     * @param float $old
     * @param float $new
     * @return float
     */
    protected function calculatePercentageChange(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100.0 : 0.0;
        }

        return round((($new - $old) / $old) * 100, 1);
    }

    /**
     * Calculate average posts per day
     *
     * @param int $totalPosts
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    protected function calculateAvgPostsPerDay(int $totalPosts, string $startDate, string $endDate): float
    {
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        return $days > 0 ? round($totalPosts / $days, 1) : 0;
    }

    /**
     * Determine trend direction
     *
     * @param float $change
     * @return string
     */
    protected function determineTrend(float $change): string
    {
        if ($change > 0) {
            return 'up';
        } elseif ($change < 0) {
            return 'down';
        }
        return 'stable';
    }

    /**
     * Get empty dashboard structure
     *
     * @param string $accountId
     * @param array $filters
     * @return array
     */
    protected function getEmptyDashboard(string $accountId, array $filters): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

        return [
            'overview' => [
                'followers' => ['current' => 0, 'growth' => 0, 'growth_percentage' => 0, 'trend' => 'stable'],
                'engagement' => ['rate' => 0, 'change' => 0, 'total_interactions' => 0],
                'reach' => ['total' => 0, 'change' => 0, 'impressions' => 0],
                'posts' => ['total' => 0, 'published' => 0, 'scheduled' => 0, 'avg_per_day' => 0],
            ],
            'engagement_breakdown' => ['likes' => 0, 'comments' => 0, 'shares' => 0, 'clicks' => 0],
            'trends' => [],
            'top_posts' => [],
            'follower_growth' => ['chart_data' => [], 'net_growth' => 0],
            'best_times' => [],
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }
}

