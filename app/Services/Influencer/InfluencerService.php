<?php

namespace App\Services\Influencer;

use App\Models\Influencer\Influencer;
use App\Models\Influencer\InfluencerCampaign;
use App\Models\Influencer\InfluencerContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InfluencerService
{
    /**
     * Search for influencers based on criteria
     */
    public function searchInfluencers(array $criteria): Collection
    {
        $query = Influencer::where('org_id', session('current_org_id'));

        if (!empty($criteria['platform'])) {
            $query->where('platform', $criteria['platform']);
        }

        if (!empty($criteria['min_followers'])) {
            $query->where('followers_count', '>=', $criteria['min_followers']);
        }

        if (!empty($criteria['max_followers'])) {
            $query->where('followers_count', '<=', $criteria['max_followers']);
        }

        if (!empty($criteria['min_engagement_rate'])) {
            $query->where('engagement_rate', '>=', $criteria['min_engagement_rate']);
        }

        if (!empty($criteria['category'])) {
            $query->whereJsonContains('categories', $criteria['category']);
        }

        if (!empty($criteria['location'])) {
            $query->where('location', $criteria['location']);
        }

        if (!empty($criteria['language'])) {
            $query->whereJsonContains('languages', $criteria['language']);
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        return $query->get();
    }

    /**
     * Calculate influencer score based on various metrics
     */
    public function calculateInfluencerScore(Influencer $influencer): float
    {
        $weights = [
            'followers' => 0.25,
            'engagement_rate' => 0.35,
            'content_quality' => 0.20,
            'reliability' => 0.20,
        ];

        // Followers score (normalized)
        $followersScore = min(100, ($influencer->followers_count / 1000000) * 100);

        // Engagement rate score
        $engagementScore = min(100, $influencer->engagement_rate * 10);

        // Content quality score (based on average performance)
        $contentQualityScore = $this->getContentQualityScore($influencer);

        // Reliability score (based on campaign completion and timeliness)
        $reliabilityScore = $this->getReliabilityScore($influencer);

        $totalScore = (
            ($followersScore * $weights['followers']) +
            ($engagementScore * $weights['engagement_rate']) +
            ($contentQualityScore * $weights['content_quality']) +
            ($reliabilityScore * $weights['reliability'])
        );

        return round($totalScore, 2);
    }

    /**
     * Get content quality score
     */
    protected function getContentQualityScore(Influencer $influencer): float
    {
        $content = InfluencerContent::where('influencer_id', $influencer->influencer_id)
            ->whereNotNull('published_at')
            ->get();

        if ($content->isEmpty()) {
            return 50; // Default score for new influencers
        }

        $avgEngagement = $content->avg('total_engagement');
        $avgReach = $content->avg('total_reach');

        // Normalize scores
        $engagementScore = min(100, ($avgEngagement / 10000) * 100);
        $reachScore = min(100, ($avgReach / 100000) * 100);

        return round(($engagementScore + $reachScore) / 2, 2);
    }

    /**
     * Get reliability score
     */
    protected function getReliabilityScore(Influencer $influencer): float
    {
        $campaigns = InfluencerCampaign::where('influencer_id', $influencer->influencer_id)->get();

        if ($campaigns->isEmpty()) {
            return 50; // Default score for new influencers
        }

        $completedCampaigns = $campaigns->where('status', 'completed')->count();
        $totalCampaigns = $campaigns->count();

        $completionRate = ($completedCampaigns / $totalCampaigns) * 100;

        // Factor in on-time delivery
        $onTimeCampaigns = $campaigns->filter(function ($campaign) {
            return $campaign->status === 'completed' &&
                   $campaign->end_date &&
                   $campaign->updated_at <= $campaign->end_date;
        })->count();

        $onTimeRate = $totalCampaigns > 0 ? ($onTimeCampaigns / $totalCampaigns) * 100 : 50;

        return round(($completionRate + $onTimeRate) / 2, 2);
    }

    /**
     * Get influencer recommendations for campaign
     */
    public function getRecommendations(array $campaignRequirements): Collection
    {
        $influencers = $this->searchInfluencers($campaignRequirements);

        // Calculate score for each influencer
        $influencers = $influencers->map(function ($influencer) {
            $influencer->recommendation_score = $this->calculateInfluencerScore($influencer);
            return $influencer;
        });

        // Sort by score and return top matches
        return $influencers->sortByDesc('recommendation_score')->take(20);
    }

    /**
     * Get influencer performance metrics
     */
    public function getPerformanceMetrics(Influencer $influencer, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = InfluencerCampaign::where('influencer_id', $influencer->influencer_id);

        if ($startDate) {
            $query->where('start_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('end_date', '<=', $endDate);
        }

        $campaigns = $query->get();

        $totalReach = 0;
        $totalEngagement = 0;
        $totalSpend = 0;
        $totalConversions = 0;

        foreach ($campaigns as $campaign) {
            $totalReach += $campaign->total_reach ?? 0;
            $totalEngagement += $campaign->total_engagement ?? 0;
            $totalSpend += $campaign->budget ?? 0;
            $totalConversions += $campaign->total_conversions ?? 0;
        }

        $engagementRate = $totalReach > 0 ? ($totalEngagement / $totalReach) * 100 : 0;
        $costPerEngagement = $totalEngagement > 0 ? $totalSpend / $totalEngagement : 0;
        $roi = $totalSpend > 0 ? (($totalConversions * 100) - $totalSpend) / $totalSpend * 100 : 0;

        return [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'completed_campaigns' => $campaigns->where('status', 'completed')->count(),
            'total_reach' => $totalReach,
            'total_engagement' => $totalEngagement,
            'total_spend' => $totalSpend,
            'total_conversions' => $totalConversions,
            'engagement_rate' => round($engagementRate, 2),
            'cost_per_engagement' => round($costPerEngagement, 2),
            'roi' => round($roi, 2),
        ];
    }

    /**
     * Analyze audience demographics
     */
    public function analyzeAudienceDemographics(Influencer $influencer): array
    {
        $audienceData = $influencer->audience_demographics ?? [];

        if (empty($audienceData)) {
            return [
                'age_distribution' => [],
                'gender_distribution' => [],
                'location_distribution' => [],
                'interests' => [],
            ];
        }

        return [
            'age_distribution' => $audienceData['age_groups'] ?? [],
            'gender_distribution' => $audienceData['gender'] ?? [],
            'location_distribution' => $audienceData['locations'] ?? [],
            'interests' => $audienceData['interests'] ?? [],
        ];
    }

    /**
     * Get audience overlap between influencers
     */
    public function getAudienceOverlap(Influencer $influencer1, Influencer $influencer2): float
    {
        $audience1 = $influencer1->audience_demographics ?? [];
        $audience2 = $influencer2->audience_demographics ?? [];

        if (empty($audience1) || empty($audience2)) {
            return 0;
        }

        // Calculate overlap based on location and interests
        $locationOverlap = $this->calculateArrayOverlap(
            $audience1['locations'] ?? [],
            $audience2['locations'] ?? []
        );

        $interestOverlap = $this->calculateArrayOverlap(
            $audience1['interests'] ?? [],
            $audience2['interests'] ?? []
        );

        return round(($locationOverlap + $interestOverlap) / 2, 2);
    }

    /**
     * Calculate overlap percentage between two arrays
     */
    protected function calculateArrayOverlap(array $arr1, array $arr2): float
    {
        if (empty($arr1) || empty($arr2)) {
            return 0;
        }

        $keys1 = array_keys($arr1);
        $keys2 = array_keys($arr2);

        $intersection = array_intersect($keys1, $keys2);
        $union = array_unique(array_merge($keys1, $keys2));

        return count($union) > 0 ? (count($intersection) / count($union)) * 100 : 0;
    }

    /**
     * Track influencer engagement trend
     */
    public function getEngagementTrend(Influencer $influencer, int $days = 30): array
    {
        $content = InfluencerContent::where('influencer_id', $influencer->influencer_id)
            ->where('published_at', '>=', now()->subDays($days))
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'asc')
            ->get();

        $trend = [];

        foreach ($content as $item) {
            $date = $item->published_at->format('Y-m-d');

            if (!isset($trend[$date])) {
                $trend[$date] = [
                    'date' => $date,
                    'engagement' => 0,
                    'reach' => 0,
                    'posts' => 0,
                ];
            }

            $trend[$date]['engagement'] += $item->total_engagement ?? 0;
            $trend[$date]['reach'] += $item->total_reach ?? 0;
            $trend[$date]['posts']++;
        }

        return array_values($trend);
    }

    /**
     * Compare multiple influencers
     */
    public function compareInfluencers(array $influencerIds): array
    {
        $influencers = Influencer::whereIn('influencer_id', $influencerIds)->get();

        $comparison = [];

        foreach ($influencers as $influencer) {
            $comparison[] = [
                'influencer_id' => $influencer->influencer_id,
                'name' => $influencer->name,
                'platform' => $influencer->platform,
                'followers' => $influencer->followers_count,
                'engagement_rate' => $influencer->engagement_rate,
                'score' => $this->calculateInfluencerScore($influencer),
                'metrics' => $this->getPerformanceMetrics($influencer),
            ];
        }

        return $comparison;
    }

    /**
     * Suggest optimal posting times
     */
    public function suggestPostingTimes(Influencer $influencer): array
    {
        $content = InfluencerContent::where('influencer_id', $influencer->influencer_id)
            ->whereNotNull('published_at')
            ->get();

        if ($content->isEmpty()) {
            return $this->getDefaultPostingTimes($influencer->platform);
        }

        $hourlyEngagement = [];

        foreach ($content as $item) {
            $hour = $item->published_at->format('H');
            $engagement = $item->total_engagement ?? 0;

            if (!isset($hourlyEngagement[$hour])) {
                $hourlyEngagement[$hour] = [
                    'hour' => $hour,
                    'total_engagement' => 0,
                    'post_count' => 0,
                ];
            }

            $hourlyEngagement[$hour]['total_engagement'] += $engagement;
            $hourlyEngagement[$hour]['post_count']++;
        }

        // Calculate average engagement per post for each hour
        $avgEngagement = array_map(function ($data) {
            return [
                'hour' => $data['hour'],
                'avg_engagement' => $data['post_count'] > 0 ? $data['total_engagement'] / $data['post_count'] : 0,
            ];
        }, $hourlyEngagement);

        // Sort by average engagement
        usort($avgEngagement, fn($a, $b) => $b['avg_engagement'] <=> $a['avg_engagement']);

        return array_slice($avgEngagement, 0, 5);
    }

    /**
     * Get default posting times by platform
     */
    protected function getDefaultPostingTimes(string $platform): array
    {
        $defaults = [
            'instagram' => [
                ['hour' => '09', 'avg_engagement' => 0],
                ['hour' => '12', 'avg_engagement' => 0],
                ['hour' => '18', 'avg_engagement' => 0],
            ],
            'facebook' => [
                ['hour' => '10', 'avg_engagement' => 0],
                ['hour' => '13', 'avg_engagement' => 0],
                ['hour' => '19', 'avg_engagement' => 0],
            ],
            'twitter' => [
                ['hour' => '08', 'avg_engagement' => 0],
                ['hour' => '12', 'avg_engagement' => 0],
                ['hour' => '17', 'avg_engagement' => 0],
            ],
            'tiktok' => [
                ['hour' => '14', 'avg_engagement' => 0],
                ['hour' => '19', 'avg_engagement' => 0],
                ['hour' => '21', 'avg_engagement' => 0],
            ],
            'youtube' => [
                ['hour' => '15', 'avg_engagement' => 0],
                ['hour' => '18', 'avg_engagement' => 0],
                ['hour' => '20', 'avg_engagement' => 0],
            ],
        ];

        return $defaults[$platform] ?? $defaults['instagram'];
    }

    /**
     * Export influencer profile
     */
    public function exportProfile(Influencer $influencer): array
    {
        return [
            'profile' => [
                'name' => $influencer->name,
                'platform' => $influencer->platform,
                'username' => $influencer->username,
                'followers' => $influencer->followers_count,
                'engagement_rate' => $influencer->engagement_rate,
                'bio' => $influencer->bio,
                'location' => $influencer->location,
                'categories' => $influencer->categories,
                'languages' => $influencer->languages,
            ],
            'score' => $this->calculateInfluencerScore($influencer),
            'performance' => $this->getPerformanceMetrics($influencer),
            'audience' => $this->analyzeAudienceDemographics($influencer),
            'optimal_posting_times' => $this->suggestPostingTimes($influencer),
            'exported_at' => now()->toIso8601String(),
        ];
    }
}
