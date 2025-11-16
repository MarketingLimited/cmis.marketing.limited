<?php

namespace App\Services;

use App\Services\CampaignAnalyticsService;

/**
 * Analytics Service
 *
 * Wrapper service for analytics operations used by GPT interface.
 * Delegates to CampaignAnalyticsService for actual implementation.
 */
class AnalyticsService
{
    public function __construct(
        protected CampaignAnalyticsService $campaignAnalytics
    ) {}

    /**
     * Get campaign analytics summary
     *
     * @param string $campaignId
     * @param array $options
     * @return array|null
     */
    public function getCampaignSummary(string $campaignId, array $options = []): ?array
    {
        $result = $this->campaignAnalytics->getCampaignAnalytics($campaignId, $options);

        if (!$result['success']) {
            return null;
        }

        return $result['data'];
    }

    /**
     * Get campaign metrics
     *
     * @param string $campaignId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getMetrics(string $campaignId, ?string $startDate = null, ?string $endDate = null): array
    {
        $options = [];
        if ($startDate) {
            $options['start_date'] = $startDate;
        }
        if ($endDate) {
            $options['end_date'] = $endDate;
        }

        $result = $this->campaignAnalytics->getCampaignAnalytics($campaignId, $options);

        return $result['success'] ? $result['data']['metrics'] : [];
    }

    /**
     * Get performance trends
     *
     * @param string $campaignId
     * @param array $options
     * @return array
     */
    public function getTrends(string $campaignId, array $options = []): array
    {
        $result = $this->campaignAnalytics->getCampaignAnalytics($campaignId, $options);

        return $result['success'] ? $result['data']['trends'] : [];
    }

    /**
     * Compare multiple campaigns
     *
     * @param array $campaignIds
     * @param array $options
     * @return array
     */
    public function compareCampaigns(array $campaignIds, array $options = []): array
    {
        $result = $this->campaignAnalytics->compareCampaigns($campaignIds, $options);

        return $result['success'] ? $result['data'] : [];
    }

    /**
     * Get funnel analytics
     *
     * @param string $campaignId
     * @param array $options
     * @return array
     */
    public function getFunnelAnalytics(string $campaignId, array $options = []): array
    {
        $result = $this->campaignAnalytics->getFunnelAnalytics($campaignId, $options);

        return $result['success'] ? $result['data'] : [];
    }

    /**
     * Get attribution analysis
     *
     * @param string $campaignId
     * @param array $options
     * @return array
     */
    public function getAttributionAnalysis(string $campaignId, array $options = []): array
    {
        $result = $this->campaignAnalytics->getAttributionAnalysis($campaignId, $options);

        return $result['success'] ? $result['data'] : [];
    }

    /**
     * Get ad set breakdown
     *
     * @param string $campaignId
     * @param array $options
     * @return array
     */
    public function getAdSetBreakdown(string $campaignId, array $options = []): array
    {
        $result = $this->campaignAnalytics->getAdSetBreakdown($campaignId, $options);

        return $result['success'] ? $result['data'] : [];
    }

    /**
     * Get creative performance breakdown
     *
     * @param string $campaignId
     * @param array $options
     * @return array
     */
    public function getCreativeBreakdown(string $campaignId, array $options = []): array
    {
        $result = $this->campaignAnalytics->getCreativeBreakdown($campaignId, $options);

        return $result['success'] ? $result['data'] : [];
    }

    /**
     * Get insights for a campaign
     *
     * @param string $campaignId
     * @param array $options
     * @return array
     */
    public function getInsights(string $campaignId, array $options = []): array
    {
        $summary = $this->getCampaignSummary($campaignId, $options);

        if (!$summary) {
            return [
                'insights' => [],
                'recommendations' => [],
                'confidence' => 0,
            ];
        }

        $insights = [];
        $recommendations = [];
        $metrics = $summary['metrics'] ?? [];

        // Generate insights based on metrics
        if (isset($metrics['ctr']) && $metrics['ctr'] > 2) {
            $insights[] = "Your click-through rate of {$metrics['ctr']}% is above average.";
        } elseif (isset($metrics['ctr']) && $metrics['ctr'] < 1) {
            $insights[] = "Click-through rate is below average. Consider testing new creatives.";
            $recommendations[] = "A/B test different headlines and images";
        }

        if (isset($metrics['conversion_rate']) && $metrics['conversion_rate'] > 3) {
            $insights[] = "Strong conversion rate indicates effective targeting.";
        } elseif (isset($metrics['conversion_rate']) && $metrics['conversion_rate'] < 1) {
            $insights[] = "Low conversion rate suggests landing page or targeting issues.";
            $recommendations[] = "Review landing page experience";
            $recommendations[] = "Refine audience targeting";
        }

        if (isset($metrics['roas']) && $metrics['roas'] > 2) {
            $insights[] = "Excellent return on ad spend - consider increasing budget.";
            $recommendations[] = "Increase daily budget by 20-30%";
        } elseif (isset($metrics['roas']) && $metrics['roas'] < 1) {
            $insights[] = "Campaign is not profitable. Immediate optimization needed.";
            $recommendations[] = "Pause underperforming ad sets";
            $recommendations[] = "Reduce cost per click through bid optimization";
        }

        // Check trends
        if (isset($summary['trends']) && count($summary['trends']) > 7) {
            $recentTrends = array_slice($summary['trends'], -7);
            $avgRecent = collect($recentTrends)->avg('ctr');
            $avgAll = collect($summary['trends'])->avg('ctr');

            if ($avgRecent > $avgAll * 1.2) {
                $insights[] = "Performance is improving over time.";
            } elseif ($avgRecent < $avgAll * 0.8) {
                $insights[] = "Performance is declining. Review recent changes.";
                $recommendations[] = "Analyze what changed in the last week";
            }
        }

        // Default insights if we couldn't generate any
        if (empty($insights)) {
            $insights[] = "Campaign is active and collecting data.";
            $recommendations[] = "Continue monitoring performance";
        }

        return [
            'insights' => $insights,
            'recommendations' => $recommendations,
            'confidence' => count($insights) > 2 ? 0.85 : 0.65,
            'metrics_summary' => $metrics,
        ];
    }
}
