<?php

namespace App\Services\Listening;

use App\Models\Listening\CompetitorProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompetitorMonitoringService
{
    /**
     * Create competitor profile
     */
    public function createCompetitor(string $orgId, string $userId, array $data): CompetitorProfile
    {
        $competitor = CompetitorProfile::create([
            'org_id' => $orgId,
            'created_by' => $userId,
            'competitor_name' => $data['competitor_name'],
            'industry' => $data['industry'] ?? null,
            'description' => $data['description'] ?? null,
            'website' => $data['website'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'social_accounts' => $data['social_accounts'] ?? [],
            'monitoring_settings' => $data['monitoring_settings'] ?? [],
            'enable_alerts' => $data['enable_alerts'] ?? false,
        ]);

        Log::info('Competitor profile created', [
            'competitor_id' => $competitor->competitor_id,
            'name' => $competitor->competitor_name,
        ]);

        return $competitor;
    }

    /**
     * Update competitor profile
     */
    public function updateCompetitor(CompetitorProfile $competitor, array $data): CompetitorProfile
    {
        $competitor->update($data);
        return $competitor->fresh();
    }

    /**
     * Analyze competitor social presence
     */
    public function analyzeCompetitor(CompetitorProfile $competitor): array
    {
        $results = [];

        foreach ($competitor->getMonitoredPlatforms() as $platform) {
            try {
                $platformData = $this->analyzePlatform($competitor, $platform);
                $results[$platform] = $platformData;

                // Update competitor metrics
                $competitor->updateFollowerCount($platform, $platformData['followers']);
                $competitor->updatePostingFrequency($platform, $platformData['posting_frequency']);
                $competitor->updateEngagementStats($platform, $platformData['engagement']);

            } catch (\Exception $e) {
                Log::error('Failed to analyze competitor platform', [
                    'competitor_id' => $competitor->competitor_id,
                    'platform' => $platform,
                    'error' => $e->getMessage(),
                ]);
                $results[$platform] = ['error' => $e->getMessage()];
            }
        }

        $competitor->markAsAnalyzed();

        return $results;
    }

    /**
     * Analyze specific platform for competitor
     */
    protected function analyzePlatform(CompetitorProfile $competitor, string $platform): array
    {
        // This would call platform APIs to gather data
        // For now, returning placeholder structure

        Log::info('Analyzing competitor platform', [
            'competitor_id' => $competitor->competitor_id,
            'platform' => $platform,
        ]);

        return [
            'followers' => 0,
            'posting_frequency' => 0,
            'engagement' => [
                'avg_likes' => 0,
                'avg_comments' => 0,
                'avg_shares' => 0,
                'engagement_rate' => 0,
            ],
            'top_content' => [],
            'posting_times' => [],
        ];
    }

    /**
     * Compare competitors
     */
    public function compareCompetitors(string $orgId, array $competitorIds, int $days = 30): array
    {
        $competitors = CompetitorProfile::whereIn('competitor_id', $competitorIds)
            ->where('org_id', $orgId)
            ->get();

        $comparison = [];

        foreach ($competitors as $competitor) {
            $comparison[$competitor->competitor_name] = [
                'total_followers' => $competitor->getTotalFollowers(),
                'avg_posting_frequency' => $competitor->getAveragePostingFrequency(),
                'platforms' => $competitor->getMonitoredPlatforms(),
                'engagement_rates' => [],
                'content_themes' => $competitor->getTopThemes(),
            ];

            foreach ($competitor->getMonitoredPlatforms() as $platform) {
                $comparison[$competitor->competitor_name]['engagement_rates'][$platform] =
                    $competitor->getEngagementRate($platform);
            }
        }

        return $comparison;
    }

    /**
     * Get competitor insights
     */
    public function getInsights(CompetitorProfile $competitor): array
    {
        return [
            'social_presence' => [
                'total_followers' => $competitor->getTotalFollowers(),
                'platform_count' => count($competitor->getMonitoredPlatforms()),
                'platforms' => $competitor->getMonitoredPlatforms(),
            ],
            'content_strategy' => [
                'avg_posting_frequency' => $competitor->getAveragePostingFrequency(),
                'top_themes' => $competitor->getTopThemes(10),
            ],
            'engagement' => [
                'platform_rates' => array_map(
                    fn($platform) => $competitor->getEngagementRate($platform),
                    $competitor->getMonitoredPlatforms()
                ),
            ],
            'growth' => [
                'follower_trends' => array_map(
                    fn($platform) => $competitor->getFollowerGrowth($platform),
                    $competitor->getMonitoredPlatforms()
                ),
            ],
        ];
    }

    /**
     * Get competitor activity feed
     */
    public function getActivityFeed(string $orgId, int $limit = 50): Collection
    {
        // This would aggregate recent posts from competitors
        // Returning empty collection for now

        return collect([]);
    }

    /**
     * Detect competitive threats
     */
    public function detectThreats(string $orgId): array
    {
        $competitors = CompetitorProfile::where('org_id', $orgId)
            ->active()
            ->get();

        $threats = [];

        foreach ($competitors as $competitor) {
            $insights = $this->getInsights($competitor);

            // Check for high growth
            $avgGrowth = collect($insights['growth']['follower_trends'])
                ->filter()
                ->avg();

            if ($avgGrowth && $avgGrowth > 20) {
                $threats[] = [
                    'competitor' => $competitor->competitor_name,
                    'threat_type' => 'rapid_growth',
                    'severity' => 'high',
                    'details' => "Growing at {$avgGrowth}% rate",
                ];
            }

            // Check for high engagement
            $avgEngagement = collect($insights['engagement']['platform_rates'])
                ->filter()
                ->avg();

            if ($avgEngagement && $avgEngagement > 5) {
                $threats[] = [
                    'competitor' => $competitor->competitor_name,
                    'threat_type' => 'high_engagement',
                    'severity' => 'medium',
                    'details' => "Engagement rate: {$avgEngagement}%",
                ];
            }
        }

        return $threats;
    }

    /**
     * Get competitor benchmarks
     */
    public function getBenchmarks(string $orgId): array
    {
        $competitors = CompetitorProfile::where('org_id', $orgId)
            ->active()
            ->get();

        if ($competitors->isEmpty()) {
            return [];
        }

        $allFollowers = $competitors->map(fn($c) => $c->getTotalFollowers());
        $allFrequencies = $competitors->map(fn($c) => $c->getAveragePostingFrequency());

        return [
            'followers' => [
                'min' => $allFollowers->min(),
                'max' => $allFollowers->max(),
                'avg' => $allFollowers->avg(),
                'median' => $allFollowers->median(),
            ],
            'posting_frequency' => [
                'min' => $allFrequencies->min(),
                'max' => $allFrequencies->max(),
                'avg' => $allFrequencies->avg(),
                'median' => $allFrequencies->median(),
            ],
        ];
    }
}
