<?php

namespace App\Services;

use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Repositories\Contracts\SocialMediaRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for analyzing best posting times based on historical data
 * Implements Sprint 2.3: AI-Suggested Timing
 *
 * Features:
 * - Historical engagement analysis
 * - Day of week recommendations
 * - Hour of day recommendations
 * - Audience activity patterns
 * - Comparative analysis across time periods
 */
class BestTimeAnalyzerService
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
     * Analyze best times to post for social account
     *
     * @param string $socialAccountId
     * @param int $lookbackDays Number of days to analyze (default: 30)
     * @param int $topN Number of top slots to return (default: 10)
     * @return array
     */
    public function analyzeBestTimes(
        string $socialAccountId,
        int $lookbackDays = 30,
        int $topN = 10
    ): array {
        $cacheKey = "best_times:{$socialAccountId}:{$lookbackDays}:{$topN}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($socialAccountId, $lookbackDays, $topN) {
            try {
                // Use repository method to get best posting times
                $results = $this->socialMediaRepo->analyzeBestPostingTimes($socialAccountId, $lookbackDays);

                if ($results->isEmpty()) {
                    return $this->getDefaultRecommendations($lookbackDays);
                }

                // Process results into recommendations
                $recommendations = $this->processTimeSlotData($results, $topN);

                return [
                    'success' => true,
                    'recommended_times' => $recommendations,
                    'analysis_period' => [
                        'start' => Carbon::now()->subDays($lookbackDays)->toDateString(),
                        'end' => Carbon::now()->toDateString(),
                        'days' => $lookbackDays,
                        'posts_analyzed' => $results->count()
                    ],
                    'metadata' => [
                        'confidence' => $this->calculateConfidence($results->count(), $lookbackDays),
                        'sample_size' => $results->count(),
                        'generated_at' => now()->toISOString()
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('Best time analysis failed', [
                    'social_account_id' => $socialAccountId,
                    'error' => $e->getMessage()
                ]);

                return $this->getDefaultRecommendations($lookbackDays);
            }
        });
    }

    /**
     * Get recommendations for specific metrics
     *
     * @param string $socialAccountId
     * @param int $lookbackDays
     * @return array
     */
    public function getRecommendations(string $socialAccountId, int $lookbackDays = 30): array
    {
        $analysis = $this->analyzeBestTimes($socialAccountId, $lookbackDays);

        if (!$analysis['success'] || empty($analysis['recommended_times'])) {
            return [
                'best_day' => null,
                'best_time' => null,
                'best_slot' => null,
                'confidence' => 'low'
            ];
        }

        $topSlot = $analysis['recommended_times'][0];

        return [
            'best_day' => $topSlot['day'],
            'best_time' => $topSlot['time'],
            'best_slot' => $topSlot['day'] . ' at ' . $topSlot['time'],
            'engagement_score' => $topSlot['avg_engagement_rate'],
            'confidence' => $analysis['metadata']['confidence'],
            'recommendation' => $this->generateRecommendationText($topSlot)
        ];
    }

    /**
     * Compare actual posting times vs recommended times
     *
     * @param string $socialAccountId
     * @param array $dateRange ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @return array
     */
    public function compareActualVsRecommended(string $socialAccountId, array $dateRange): array
    {
        try {
            // Get recommended times
            $recommended = $this->analyzeBestTimes($socialAccountId);
            $recommendedSlots = collect($recommended['recommended_times'])
                ->take(5)
                ->pluck('time', 'day')
                ->toArray();

            // Get actual posting times
            $actualPosts = DB::table('cmis.social_posts')
                ->where('social_account_id', $socialAccountId)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    DB::raw("TO_CHAR(created_at, 'Day') as day"),
                    DB::raw("TO_CHAR(created_at, 'HH24:MI') as time"),
                    DB::raw('COUNT(*) as post_count')
                )
                ->groupBy('day', 'time')
                ->orderByDesc('post_count')
                ->limit(10)
                ->get();

            // Calculate alignment score
            $alignmentScore = $this->calculateAlignmentScore($actualPosts, $recommendedSlots);

            return [
                'success' => true,
                'alignment_score' => $alignmentScore,
                'recommended_slots' => $recommendedSlots,
                'actual_popular_times' => $actualPosts,
                'insights' => $this->generateComparisonInsights($alignmentScore)
            ];

        } catch (\Exception $e) {
            Log::error('Comparison analysis failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get audience activity patterns
     *
     * @param string $socialAccountId
     * @return array
     */
    public function getAudienceActivityPatterns(string $socialAccountId): array
    {
        try {
            // This would analyze when the audience is most active
            // Based on follower engagement data from platform APIs

            return [
                'hourly_activity' => $this->getHourlyActivity($socialAccountId),
                'daily_activity' => $this->getDailyActivity($socialAccountId),
                'peak_hours' => $this->getPeakHours($socialAccountId),
                'quiet_hours' => $this->getQuietHours($socialAccountId)
            ];

        } catch (\Exception $e) {
            Log::error('Audience activity analysis failed', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Process time slot data into recommendations
     *
     * @param Collection $data
     * @param int $topN
     * @return array
     */
    protected function processTimeSlotData(Collection $data, int $topN): array
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $data->map(function ($slot) use ($days) {
            $dayIndex = $slot->day_of_week ?? 0;

            return [
                'day' => $days[$dayIndex] ?? 'Unknown',
                'day_index' => $dayIndex,
                'time' => sprintf('%02d:00', $slot->hour ?? 0),
                'hour' => $slot->hour ?? 0,
                'avg_engagement_rate' => round($slot->avg_engagement_rate ?? 0, 2),
                'avg_likes' => round($slot->avg_likes ?? 0, 0),
                'avg_comments' => round($slot->avg_comments ?? 0, 0),
                'avg_shares' => round($slot->avg_shares ?? 0, 0),
                'post_count' => $slot->post_count ?? 0,
                'score' => round(($slot->avg_engagement_rate ?? 0) / 100, 2) // Normalize to 0-1
            ];
        })
        ->sortByDesc('avg_engagement_rate')
        ->take($topN)
        ->values()
        ->toArray();
    }

    /**
     * Calculate confidence level based on sample size
     *
     * @param int $sampleSize
     * @param int $lookbackDays
     * @return string
     */
    protected function calculateConfidence(int $sampleSize, int $lookbackDays): string
    {
        $avgPostsPerDay = $lookbackDays > 0 ? $sampleSize / $lookbackDays : 0;

        if ($sampleSize < 10 || $avgPostsPerDay < 0.3) {
            return 'low';
        } elseif ($sampleSize < 50 || $avgPostsPerDay < 1) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Calculate alignment score between actual and recommended times
     *
     * @param Collection $actualPosts
     * @param array $recommendedSlots
     * @return float
     */
    protected function calculateAlignmentScore(Collection $actualPosts, array $recommendedSlots): float
    {
        if ($actualPosts->isEmpty() || empty($recommendedSlots)) {
            return 0.0;
        }

        $matches = 0;
        $total = $actualPosts->count();

        foreach ($actualPosts as $post) {
            $day = trim($post->day);
            $hour = substr($post->time, 0, 2);

            if (isset($recommendedSlots[$day])) {
                $recommendedHour = substr($recommendedSlots[$day], 0, 2);
                // Allow ±1 hour tolerance
                if (abs((int)$hour - (int)$recommendedHour) <= 1) {
                    $matches++;
                }
            }
        }

        return round(($matches / $total) * 100, 1);
    }

    /**
     * Generate recommendation text
     *
     * @param array $topSlot
     * @return string
     */
    protected function generateRecommendationText(array $topSlot): string
    {
        return sprintf(
            "Post on %s at %s for best engagement (%.1f%% avg engagement rate)",
            $topSlot['day'],
            $topSlot['time'],
            $topSlot['avg_engagement_rate']
        );
    }

    /**
     * Generate comparison insights
     *
     * @param float $alignmentScore
     * @return string
     */
    protected function generateComparisonInsights(float $alignmentScore): string
    {
        if ($alignmentScore >= 70) {
            return "Great! You're posting at optimal times {$alignmentScore}% of the time.";
        } elseif ($alignmentScore >= 40) {
            return "Good progress. Consider adjusting your schedule to match recommended times more closely.";
        } else {
            return "Your posting schedule has significant room for improvement. Try posting at recommended times to boost engagement.";
        }
    }

    /**
     * Get default recommendations when no data available
     *
     * @param int $lookbackDays
     * @return array
     */
    protected function getDefaultRecommendations(int $lookbackDays): array
    {
        return [
            'success' => false,
            'message' => 'Insufficient data for analysis. Using industry defaults.',
            'recommended_times' => [
                ['day' => 'Monday', 'time' => '09:00', 'score' => 0, 'source' => 'default'],
                ['day' => 'Tuesday', 'time' => '14:00', 'score' => 0, 'source' => 'default'],
                ['day' => 'Wednesday', 'time' => '11:00', 'score' => 0, 'source' => 'default'],
                ['day' => 'Thursday', 'time' => '15:00', 'score' => 0, 'source' => 'default'],
                ['day' => 'Friday', 'time' => '10:00', 'score' => 0, 'source' => 'default'],
            ],
            'analysis_period' => [
                'start' => Carbon::now()->subDays($lookbackDays)->toDateString(),
                'end' => Carbon::now()->toDateString(),
                'days' => $lookbackDays,
                'posts_analyzed' => 0
            ],
            'metadata' => [
                'confidence' => 'none',
                'sample_size' => 0
            ]
        ];
    }

    /**
     * Get hourly activity distribution
     *
     * @param string $socialAccountId
     * @return array
     */
    protected function getHourlyActivity(string $socialAccountId): array
    {
        // Placeholder - would integrate with platform API data
        return array_fill(0, 24, 0);
    }

    /**
     * Get daily activity distribution
     *
     * @param string $socialAccountId
     * @return array
     */
    protected function getDailyActivity(string $socialAccountId): array
    {
        // Placeholder - would integrate with platform API data
        return array_fill(0, 7, 0);
    }

    /**
     * Get peak activity hours
     *
     * @param string $socialAccountId
     * @return array
     */
    protected function getPeakHours(string $socialAccountId): array
    {
        return ['09:00', '14:00', '18:00'];
    }

    /**
     * Get quiet hours (low activity)
     *
     * @param string $socialAccountId
     * @return array
     */
    protected function getQuietHours(string $socialAccountId): array
    {
        return ['02:00', '03:00', '04:00', '05:00'];
    }
}
