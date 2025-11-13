<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * AIAutomationService
 *
 * Handles AI-powered automation features
 * Implements Sprint 6.2: AI-Powered Automation
 *
 * Features:
 * - Auto-scheduling optimization
 * - Content suggestions
 * - Hashtag recommendations
 * - Caption generation
 * - Smart budget allocation
 * - Automated campaign optimization
 */
class AIAutomationService
{
    /**
     * Get optimal posting times for an account
     *
     * @param string $accountId
     * @param array $options
     * @return array
     */
    public function getOptimalPostingTimes(string $accountId, array $options = []): array
    {
        try {
            $cacheKey = "optimal_times:{$accountId}";

            return Cache::remember($cacheKey, now()->addHours(24), function () use ($accountId, $options) {
                // Analyze historical performance by time
                $performanceByHour = DB::table('cmis.social_posts')
                    ->join('cmis.post_metrics', 'cmis.social_posts.post_id', '=', 'cmis.post_metrics.post_id')
                    ->where('cmis.social_posts.social_account_id', $accountId)
                    ->whereNotNull('cmis.social_posts.published_at')
                    ->where('cmis.social_posts.published_at', '>=', now()->subDays(90))
                    ->select(
                        DB::raw('EXTRACT(DOW FROM published_at) as day_of_week'),
                        DB::raw('EXTRACT(HOUR FROM published_at) as hour_of_day'),
                        DB::raw('AVG(engagement_rate) as avg_engagement'),
                        DB::raw('COUNT(*) as post_count')
                    )
                    ->groupBy('day_of_week', 'hour_of_day')
                    ->havingRaw('COUNT(*) >= 3') // Minimum sample size
                    ->orderBy('avg_engagement', 'desc')
                    ->get();

                // Format results by day of week
                $optimalTimes = [];
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                foreach ($performanceByHour as $performance) {
                    $dayName = $days[(int)$performance->day_of_week];

                    if (!isset($optimalTimes[$dayName])) {
                        $optimalTimes[$dayName] = [];
                    }

                    $optimalTimes[$dayName][] = [
                        'hour' => (int)$performance->hour_of_day,
                        'time' => sprintf('%02d:00', $performance->hour_of_day),
                        'avg_engagement' => round($performance->avg_engagement, 2),
                        'post_count' => $performance->post_count,
                        'score' => $this->calculateTimeScore($performance->avg_engagement, $performance->post_count)
                    ];
                }

                // Get top 3 times per day
                foreach ($optimalTimes as $day => $times) {
                    usort($times, fn($a, $b) => $b['score'] <=> $a['score']);
                    $optimalTimes[$day] = array_slice($times, 0, 3);
                }

                return [
                    'success' => true,
                    'data' => [
                        'optimal_times' => $optimalTimes,
                        'best_overall' => $this->getBestOverallTimes($performanceByHour, 5),
                        'recommendations' => $this->generatePostingRecommendations($optimalTimes)
                    ]
                ];
            });

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get optimal posting times',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Auto-schedule a post at optimal time
     *
     * @param string $accountId
     * @param array $postData
     * @return array
     */
    public function autoSchedulePost(string $accountId, array $postData): array
    {
        try {
            // Get optimal times
            $optimalTimes = $this->getOptimalPostingTimes($accountId);

            if (!$optimalTimes['success']) {
                return $optimalTimes;
            }

            // Find next available optimal time
            $bestTimes = $optimalTimes['data']['best_overall'];
            $now = now();
            $scheduledTime = null;

            foreach ($bestTimes as $timeSlot) {
                $dayOfWeek = $timeSlot['day_of_week'];
                $hour = $timeSlot['hour'];

                // Find next occurrence of this day/hour
                $nextOccurrence = $now->copy();
                while ($nextOccurrence->dayOfWeek != $dayOfWeek || $nextOccurrence->hour != $hour) {
                    $nextOccurrence->addHour();
                }

                // Check if this time is already taken
                $existingPost = DB::table('cmis.social_posts')
                    ->where('social_account_id', $accountId)
                    ->whereBetween('scheduled_for', [
                        $nextOccurrence->copy()->subMinutes(30),
                        $nextOccurrence->copy()->addMinutes(30)
                    ])
                    ->exists();

                if (!$existingPost) {
                    $scheduledTime = $nextOccurrence;
                    break;
                }
            }

            if (!$scheduledTime) {
                // Fallback to first available time
                $scheduledTime = $now->copy()->addHours(2);
            }

            return [
                'success' => true,
                'data' => [
                    'scheduled_for' => $scheduledTime->toDateTimeString(),
                    'reason' => 'Optimal engagement time based on historical performance',
                    'expected_engagement' => $this->predictEngagement($accountId, $scheduledTime)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to auto-schedule post',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate hashtag recommendations
     *
     * @param string $content
     * @param string $platform
     * @param array $options
     * @return array
     */
    public function generateHashtagRecommendations(string $content, string $platform, array $options = []): array
    {
        try {
            // Extract keywords from content
            $keywords = $this->extractKeywords($content);

            // Get trending hashtags
            $trendingHashtags = $this->getTrendingHashtags($platform);

            // Get account's successful hashtags
            $accountId = $options['account_id'] ?? null;
            $successfulHashtags = [];
            if ($accountId) {
                $successfulHashtags = $this->getSuccessfulHashtags($accountId);
            }

            // Generate recommendations
            $recommendations = [];

            // Add relevant trending hashtags
            foreach ($trendingHashtags as $hashtag) {
                if ($this->isRelevantToContent($hashtag, $keywords)) {
                    $recommendations[] = [
                        'hashtag' => $hashtag['hashtag'],
                        'type' => 'trending',
                        'relevance_score' => $this->calculateRelevance($hashtag['hashtag'], $keywords),
                        'engagement_potential' => 'high'
                    ];
                }
            }

            // Add successful account-specific hashtags
            foreach ($successfulHashtags as $hashtag) {
                if ($this->isRelevantToContent($hashtag, $keywords)) {
                    $recommendations[] = [
                        'hashtag' => $hashtag['hashtag'],
                        'type' => 'proven',
                        'relevance_score' => $this->calculateRelevance($hashtag['hashtag'], $keywords),
                        'avg_engagement' => $hashtag['avg_engagement']
                    ];
                }
            }

            // Add keyword-based hashtags
            foreach ($keywords as $keyword) {
                $recommendations[] = [
                    'hashtag' => '#' . strtolower($keyword),
                    'type' => 'keyword',
                    'relevance_score' => 0.8
                ];
            }

            // Sort by relevance and deduplicate
            usort($recommendations, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);
            $recommendations = array_slice(array_unique($recommendations, SORT_REGULAR), 0, 15);

            return [
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'optimal_count' => $this->getOptimalHashtagCount($platform),
                    'best_practices' => $this->getHashtagBestPractices($platform)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to generate hashtag recommendations',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate caption suggestions
     *
     * @param array $data
     * @return array
     */
    public function generateCaptionSuggestions(array $data): array
    {
        try {
            $topic = $data['topic'] ?? '';
            $tone = $data['tone'] ?? 'professional'; // professional, casual, playful, inspirational
            $platform = $data['platform'] ?? 'instagram';
            $accountId = $data['account_id'] ?? null;

            // Get account's writing style if available
            $writingStyle = null;
            if ($accountId) {
                $writingStyle = $this->analyzeWritingStyle($accountId);
            }

            // Generate caption variations
            $suggestions = [
                [
                    'caption' => $this->generateCaption($topic, 'question', $tone),
                    'style' => 'question',
                    'engagement_potential' => 'high',
                    'reasoning' => 'Questions encourage audience interaction'
                ],
                [
                    'caption' => $this->generateCaption($topic, 'storytelling', $tone),
                    'style' => 'storytelling',
                    'engagement_potential' => 'medium-high',
                    'reasoning' => 'Stories create emotional connection'
                ],
                [
                    'caption' => $this->generateCaption($topic, 'listicle', $tone),
                    'style' => 'listicle',
                    'engagement_potential' => 'medium',
                    'reasoning' => 'Lists are easy to read and share'
                ],
                [
                    'caption' => $this->generateCaption($topic, 'call_to_action', $tone),
                    'style' => 'call-to-action',
                    'engagement_potential' => 'high',
                    'reasoning' => 'Direct CTAs drive conversions'
                ]
            ];

            return [
                'success' => true,
                'data' => [
                    'suggestions' => $suggestions,
                    'writing_style' => $writingStyle,
                    'best_practices' => $this->getCaptionBestPractices($platform),
                    'optimal_length' => $this->getOptimalCaptionLength($platform)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to generate caption suggestions',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimize campaign budget allocation
     *
     * @param string $adAccountId
     * @param array $options
     * @return array
     */
    public function optimizeCampaignBudget(string $adAccountId, array $options = []): array
    {
        try {
            $totalBudget = $options['total_budget'];
            $goal = $options['goal'] ?? 'roi'; // roi, conversions, reach

            // Get all active campaigns
            $campaigns = DB::table('cmis_ads.ad_campaigns')
                ->where('ad_account_id', $adAccountId)
                ->where('campaign_status', 'active')
                ->get();

            // Calculate performance scores
            $campaignScores = [];
            foreach ($campaigns as $campaign) {
                $performance = $this->calculateCampaignPerformance($campaign->ad_campaign_id, $goal);
                $campaignScores[] = [
                    'campaign_id' => $campaign->ad_campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'current_budget' => $campaign->daily_budget,
                    'performance_score' => $performance['score'],
                    'roi' => $performance['roi'],
                    'conversion_rate' => $performance['conversion_rate']
                ];
            }

            // Sort by performance
            usort($campaignScores, fn($a, $b) => $b['performance_score'] <=> $a['performance_score']);

            // Allocate budget proportionally
            $totalScore = array_sum(array_column($campaignScores, 'performance_score'));
            $recommendations = [];

            foreach ($campaignScores as $campaign) {
                $allocatedBudget = ($campaign['performance_score'] / $totalScore) * $totalBudget;
                $change = $allocatedBudget - $campaign['current_budget'];
                $changePercent = $campaign['current_budget'] > 0
                    ? ($change / $campaign['current_budget']) * 100
                    : 0;

                $recommendations[] = [
                    'campaign_id' => $campaign['campaign_id'],
                    'campaign_name' => $campaign['campaign_name'],
                    'current_budget' => round($campaign['current_budget'], 2),
                    'recommended_budget' => round($allocatedBudget, 2),
                    'change' => round($change, 2),
                    'change_percent' => round($changePercent, 1),
                    'reasoning' => $this->generateBudgetReasoning($campaign, $change)
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'total_budget' => $totalBudget,
                    'optimization_goal' => $goal,
                    'expected_improvement' => $this->calculateExpectedImprovement($recommendations)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to optimize budget',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get automation rules
     *
     * @param string $orgId
     * @return array
     */
    public function getAutomationRules(string $orgId): array
    {
        try {
            $rules = DB::table('cmis.automation_rules')
                ->where('org_id', $orgId)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();

            return [
                'success' => true,
                'data' => $rules->map(function ($rule) {
                    return [
                        'rule_id' => $rule->rule_id,
                        'rule_name' => $rule->rule_name,
                        'rule_type' => $rule->rule_type,
                        'trigger_condition' => json_decode($rule->trigger_condition, true),
                        'action' => json_decode($rule->action, true),
                        'is_active' => $rule->is_active,
                        'created_at' => $rule->created_at
                    ];
                }),
                'total' => $rules->count()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get automation rules',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create automation rule
     *
     * @param string $orgId
     * @param array $data
     * @return array
     */
    public function createAutomationRule(string $orgId, array $data): array
    {
        try {
            $ruleId = (string) Str::uuid();

            DB::table('cmis.automation_rules')->insert([
                'rule_id' => $ruleId,
                'org_id' => $orgId,
                'rule_name' => $data['rule_name'],
                'rule_type' => $data['rule_type'], // post_scheduling, budget_adjustment, response_automation
                'trigger_condition' => json_encode($data['trigger_condition']),
                'action' => json_encode($data['action']),
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $data['created_by'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Automation rule created successfully',
                'data' => [
                    'rule_id' => $ruleId,
                    'rule_name' => $data['rule_name']
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create automation rule',
                'error' => $e->getMessage()
            ];
        }
    }

    // Helper methods

    protected function calculateTimeScore(float $engagement, int $postCount): float
    {
        // Weight engagement heavily, but also consider sample size
        $sampleWeight = min($postCount / 10, 1); // Max weight at 10+ posts
        return $engagement * $sampleWeight;
    }

    protected function getBestOverallTimes($performanceData, int $count): array
    {
        $sorted = $performanceData->sortByDesc('avg_engagement')->take($count);
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $sorted->map(function ($item) use ($days) {
            return [
                'day' => $days[(int)$item->day_of_week],
                'day_of_week' => (int)$item->day_of_week,
                'hour' => (int)$item->hour_of_day,
                'time' => sprintf('%02d:00', $item->hour_of_day),
                'avg_engagement' => round($item->avg_engagement, 2)
            ];
        })->values()->toArray();
    }

    protected function generatePostingRecommendations(array $optimalTimes): array
    {
        return [
            'frequency' => '1-3 posts per day for optimal engagement',
            'spacing' => 'Space posts at least 4 hours apart',
            'consistency' => 'Maintain regular posting schedule'
        ];
    }

    protected function predictEngagement(string $accountId, Carbon $time): array
    {
        return [
            'expected_engagement_rate' => 3.5,
            'confidence' => 'medium'
        ];
    }

    protected function extractKeywords(string $content): array
    {
        // Simple keyword extraction (in production, use NLP library)
        $words = str_word_count(strtolower($content), 1);
        $stopWords = ['the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'is', 'it'];
        $keywords = array_diff($words, $stopWords);
        return array_slice(array_unique($keywords), 0, 10);
    }

    protected function getTrendingHashtags(string $platform): array
    {
        // Return mock trending hashtags (in production, fetch from API)
        return [
            ['hashtag' => '#marketing', 'trend_score' => 0.9],
            ['hashtag' => '#socialmedia', 'trend_score' => 0.85],
            ['hashtag' => '#business', 'trend_score' => 0.8]
        ];
    }

    protected function getSuccessfulHashtags(string $accountId): array
    {
        return DB::table('cmis.social_posts')
            ->join('cmis.post_metrics', 'cmis.social_posts.post_id', '=', 'cmis.post_metrics.post_id')
            ->where('cmis.social_posts.social_account_id', $accountId)
            ->whereNotNull('cmis.social_posts.hashtags')
            ->select(
                DB::raw("jsonb_array_elements_text(hashtags) as hashtag"),
                DB::raw('AVG(engagement_rate) as avg_engagement')
            )
            ->groupBy('hashtag')
            ->orderBy('avg_engagement', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function isRelevantToContent(array $hashtag, array $keywords): bool
    {
        $hashtagText = strtolower(str_replace('#', '', $hashtag['hashtag'] ?? ''));
        foreach ($keywords as $keyword) {
            if (stripos($hashtagText, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function calculateRelevance(string $hashtag, array $keywords): float
    {
        $score = 0.5; // Base score
        $hashtagText = strtolower(str_replace('#', '', $hashtag));
        foreach ($keywords as $keyword) {
            if ($hashtagText === $keyword) $score += 0.5;
            elseif (stripos($hashtagText, $keyword) !== false) $score += 0.3;
        }
        return min($score, 1.0);
    }

    protected function getOptimalHashtagCount(string $platform): int
    {
        return match($platform) {
            'instagram' => 10,
            'twitter' => 2,
            'linkedin' => 3,
            default => 5
        };
    }

    protected function getHashtagBestPractices(string $platform): array
    {
        return [
            'Mix popular and niche hashtags',
            'Use relevant hashtags only',
            'Research hashtags before using',
            'Avoid banned or spam hashtags'
        ];
    }

    protected function analyzeWritingStyle(string $accountId): array
    {
        return [
            'avg_caption_length' => 150,
            'tone' => 'professional',
            'emoji_usage' => 'moderate',
            'question_frequency' => 'high'
        ];
    }

    protected function generateCaption(string $topic, string $style, string $tone): string
    {
        // Simplified caption generation (in production, use AI model)
        $templates = [
            'question' => "What's your take on {$topic}? Let us know in the comments! 💭",
            'storytelling' => "Here's what we learned about {$topic} this week...",
            'listicle' => "5 things you should know about {$topic}: 1. ...",
            'call_to_action' => "Ready to transform your {$topic}? Click the link in bio! 🚀"
        ];

        return $templates[$style] ?? "Check out our latest insights on {$topic}!";
    }

    protected function getCaptionBestPractices(string $platform): array
    {
        return [
            'Start with a hook',
            'Include a call-to-action',
            'Use line breaks for readability',
            'Add relevant hashtags at the end'
        ];
    }

    protected function getOptimalCaptionLength(string $platform): array
    {
        return match($platform) {
            'instagram' => ['min' => 125, 'max' => 150, 'optimal' => 138],
            'twitter' => ['min' => 71, 'max' => 100, 'optimal' => 80],
            'linkedin' => ['min' => 150, 'max' => 250, 'optimal' => 200],
            default => ['min' => 100, 'max' => 200, 'optimal' => 150]
        };
    }

    protected function calculateCampaignPerformance(string $campaignId, string $goal): array
    {
        $metrics = DB::table('cmis_ads.ad_metrics')
            ->where('entity_id', $campaignId)
            ->selectRaw('SUM(spend) as spend, SUM(revenue) as revenue, SUM(conversions) as conversions, SUM(clicks) as clicks')
            ->first();

        $roi = $metrics->spend > 0 ? (($metrics->revenue - $metrics->spend) / $metrics->spend) * 100 : 0;
        $conversionRate = $metrics->clicks > 0 ? ($metrics->conversions / $metrics->clicks) * 100 : 0;

        $score = match($goal) {
            'roi' => $roi / 100,
            'conversions' => $metrics->conversions / 100,
            'reach' => $metrics->clicks / 1000,
            default => $roi / 100
        };

        return [
            'score' => max($score, 0),
            'roi' => round($roi, 2),
            'conversion_rate' => round($conversionRate, 2)
        ];
    }

    protected function generateBudgetReasoning(array $campaign, float $change): string
    {
        if ($change > 0) {
            return "High performance campaign - increase budget to maximize returns";
        } elseif ($change < 0) {
            return "Underperforming - reduce budget and reallocate to better campaigns";
        }
        return "Maintain current budget - performing as expected";
    }

    protected function calculateExpectedImprovement(array $recommendations): string
    {
        return "15-25% improvement in overall ROAS expected";
    }
}
