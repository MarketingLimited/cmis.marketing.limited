<?php

namespace App\Services\AI;

use App\Models\Marketing\AdCampaign;
use App\Models\Core\Org;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Predictive Analytics Service
 * Advanced forecasting and trend analysis for marketing campaigns
 */
class PredictiveAnalyticsService
{
    /**
     * Generate comprehensive forecast for a campaign
     */
    public function forecastCampaign(AdCampaign $campaign, int $days = 30): array
    {
        $historicalData = $this->getHistoricalData($campaign, 90);
        $trends = $this->analyzeTrends($historicalData);

        return [
            'campaign_id' => $campaign->campaign_id,
            'forecast_period' => $days,
            'predictions' => $this->generatePredictions($historicalData, $trends, $days),
            'confidence_level' => $this->calculateConfidence($historicalData),
            'trends' => $trends,
            'budget_recommendations' => $this->generateBudgetRecommendations($campaign, $trends),
            'risk_assessment' => $this->assessRisks($trends),
        ];
    }

    /**
     * Forecast performance for all organization campaigns
     */
    public function forecastOrganization(Org $org, int $days = 30): array
    {
        $campaigns = AdCampaign::where('org_id', $org->org_id)
            ->whereIn('status', ['active', 'paused'])
            ->get();

        $totalPredictions = [
            'total_spend' => 0,
            'total_conversions' => 0,
            'total_revenue' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
        ];

        $campaignForecasts = [];

        foreach ($campaigns as $campaign) {
            $forecast = $this->forecastCampaign($campaign, $days);
            $campaignForecasts[] = $forecast;

            // Aggregate predictions
            $predictions = $forecast['predictions'];
            $totalPredictions['total_spend'] += $predictions['total_spend'];
            $totalPredictions['total_conversions'] += $predictions['total_conversions'];
            $totalPredictions['total_revenue'] += $predictions['total_revenue'];
            $totalPredictions['total_impressions'] += $predictions['total_impressions'];
            $totalPredictions['total_clicks'] += $predictions['total_clicks'];
        }

        // Calculate organization-level metrics
        $totalPredictions['predicted_roi'] = $totalPredictions['total_spend'] > 0
            ? (($totalPredictions['total_revenue'] - $totalPredictions['total_spend']) / $totalPredictions['total_spend']) * 100
            : 0;

        $totalPredictions['predicted_ctr'] = $totalPredictions['total_impressions'] > 0
            ? ($totalPredictions['total_clicks'] / $totalPredictions['total_impressions']) * 100
            : 0;

        $totalPredictions['predicted_conversion_rate'] = $totalPredictions['total_clicks'] > 0
            ? ($totalPredictions['total_conversions'] / $totalPredictions['total_clicks']) * 100
            : 0;

        return [
            'org_id' => $org->org_id,
            'forecast_period' => $days,
            'total_campaigns' => $campaigns->count(),
            'organization_predictions' => $totalPredictions,
            'campaign_forecasts' => $campaignForecasts,
            'recommendations' => $this->generateOrgRecommendations($totalPredictions, $campaignForecasts),
        ];
    }

    /**
     * Get historical performance data
     */
    private function getHistoricalData(AdCampaign $campaign, int $days): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $metrics = DB::table('cmis.ad_metrics')
            ->where('campaign_id', $campaign->campaign_id)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        $data = [];
        foreach ($metrics as $metric) {
            $data[] = [
                'date' => $metric->date,
                'impressions' => $metric->impressions ?? 0,
                'clicks' => $metric->clicks ?? 0,
                'spend' => $metric->spend ?? 0,
                'conversions' => $metric->conversions ?? 0,
                'revenue' => $metric->revenue ?? 0,
                'ctr' => $metric->ctr ?? 0,
                'cpc' => $metric->cpc ?? 0,
                'conversion_rate' => $metric->conversion_rate ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Analyze trends using linear regression and moving averages
     */
    private function analyzeTrends(array $historicalData): array
    {
        if (empty($historicalData)) {
            return $this->getDefaultTrends();
        }

        $n = count($historicalData);

        return [
            'impressions' => $this->calculateTrend($historicalData, 'impressions'),
            'clicks' => $this->calculateTrend($historicalData, 'clicks'),
            'spend' => $this->calculateTrend($historicalData, 'spend'),
            'conversions' => $this->calculateTrend($historicalData, 'conversions'),
            'revenue' => $this->calculateTrend($historicalData, 'revenue'),
            'ctr' => $this->calculateTrend($historicalData, 'ctr'),
            'conversion_rate' => $this->calculateTrend($historicalData, 'conversion_rate'),
            'data_points' => $n,
            'moving_averages' => $this->calculateMovingAverages($historicalData),
        ];
    }

    /**
     * Calculate trend using simple linear regression
     */
    private function calculateTrend(array $data, string $metric): array
    {
        $n = count($data);
        if ($n < 2) {
            return ['slope' => 0, 'direction' => 'stable', 'strength' => 'weak'];
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $index => $point) {
            $x = $index;
            $y = $point[$metric] ?? 0;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        // Calculate slope (m) of the trend line
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        $slope = $denominator != 0
            ? (($n * $sumXY) - ($sumX * $sumY)) / $denominator
            : 0;

        // Determine direction and strength
        $avgValue = $sumY / $n;
        $percentageChange = $avgValue != 0 ? ($slope / $avgValue) * 100 : 0;

        $direction = 'stable';
        if ($slope > 0) $direction = 'increasing';
        if ($slope < 0) $direction = 'decreasing';

        $strength = 'weak';
        $absChange = abs($percentageChange);
        if ($absChange > 10) $strength = 'strong';
        elseif ($absChange > 5) $strength = 'moderate';

        return [
            'slope' => round($slope, 4),
            'direction' => $direction,
            'strength' => $strength,
            'percentage_change' => round($percentageChange, 2),
            'average_value' => round($avgValue, 2),
        ];
    }

    /**
     * Calculate moving averages (7-day and 30-day)
     */
    private function calculateMovingAverages(array $data): array
    {
        $metrics = ['impressions', 'clicks', 'spend', 'conversions', 'revenue', 'ctr'];
        $averages = [];

        foreach ($metrics as $metric) {
            $averages[$metric] = [
                '7_day' => $this->calculateSMA($data, $metric, 7),
                '30_day' => $this->calculateSMA($data, $metric, 30),
            ];
        }

        return $averages;
    }

    /**
     * Calculate Simple Moving Average
     */
    private function calculateSMA(array $data, string $metric, int $period): float
    {
        $n = count($data);
        if ($n < $period) {
            $period = $n;
        }

        $recentData = array_slice($data, -$period);
        $sum = array_sum(array_column($recentData, $metric));

        return $period > 0 ? round($sum / $period, 2) : 0;
    }

    /**
     * Generate predictions based on trends
     */
    private function generatePredictions(array $historicalData, array $trends, int $days): array
    {
        if (empty($historicalData)) {
            return $this->getDefaultPredictions();
        }

        // Use moving averages as baseline
        $ma = $trends['moving_averages'];

        // Calculate daily predictions
        $dailyImpressions = $ma['impressions']['7_day'] * (1 + ($trends['impressions']['slope'] / 100));
        $dailyClicks = $ma['clicks']['7_day'] * (1 + ($trends['clicks']['slope'] / 100));
        $dailySpend = $ma['spend']['7_day'] * (1 + ($trends['spend']['slope'] / 100));
        $dailyConversions = $ma['conversions']['7_day'] * (1 + ($trends['conversions']['slope'] / 100));
        $dailyRevenue = $ma['revenue']['7_day'] * (1 + ($trends['revenue']['slope'] / 100));

        // Project over forecast period
        $totalImpressions = $dailyImpressions * $days;
        $totalClicks = $dailyClicks * $days;
        $totalSpend = $dailySpend * $days;
        $totalConversions = $dailyConversions * $days;
        $totalRevenue = $dailyRevenue * $days;

        // Calculate predicted metrics
        $predictedCTR = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
        $predictedCPC = $totalClicks > 0 ? $totalSpend / $totalClicks : 0;
        $predictedConversionRate = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
        $predictedROI = $totalSpend > 0 ? (($totalRevenue - $totalSpend) / $totalSpend) * 100 : 0;

        return [
            'total_impressions' => round($totalImpressions),
            'total_clicks' => round($totalClicks),
            'total_spend' => round($totalSpend, 2),
            'total_conversions' => round($totalConversions),
            'total_revenue' => round($totalRevenue, 2),
            'predicted_ctr' => round($predictedCTR, 2),
            'predicted_cpc' => round($predictedCPC, 2),
            'predicted_conversion_rate' => round($predictedConversionRate, 2),
            'predicted_roi' => round($predictedROI, 2),
            'daily_averages' => [
                'impressions' => round($dailyImpressions),
                'clicks' => round($dailyClicks),
                'spend' => round($dailySpend, 2),
                'conversions' => round($dailyConversions, 1),
                'revenue' => round($dailyRevenue, 2),
            ],
        ];
    }

    /**
     * Calculate confidence level based on data quality
     */
    private function calculateConfidence(array $historicalData): array
    {
        $dataPoints = count($historicalData);

        // Confidence based on amount of historical data
        $confidence = 0;
        if ($dataPoints >= 90) $confidence = 95;
        elseif ($dataPoints >= 60) $confidence = 85;
        elseif ($dataPoints >= 30) $confidence = 75;
        elseif ($dataPoints >= 14) $confidence = 65;
        elseif ($dataPoints >= 7) $confidence = 50;
        else $confidence = 30;

        // Calculate data consistency (variance)
        if (!empty($historicalData)) {
            $spendValues = array_column($historicalData, 'spend');
            $variance = $this->calculateVariance($spendValues);
            $mean = array_sum($spendValues) / count($spendValues);
            $coefficientOfVariation = $mean != 0 ? ($variance / $mean) * 100 : 100;

            // Reduce confidence if data is too volatile
            if ($coefficientOfVariation > 50) $confidence *= 0.8;
            elseif ($coefficientOfVariation > 30) $confidence *= 0.9;
        }

        $level = 'low';
        if ($confidence >= 80) $level = 'high';
        elseif ($confidence >= 60) $level = 'medium';

        return [
            'percentage' => round($confidence),
            'level' => $level,
            'data_points' => $dataPoints,
            'recommendation' => $this->getConfidenceRecommendation($confidence),
        ];
    }

    /**
     * Calculate variance
     */
    private function calculateVariance(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        $mean = array_sum($values) / $n;
        $sumSquares = 0;

        foreach ($values as $value) {
            $sumSquares += pow($value - $mean, 2);
        }

        return sqrt($sumSquares / ($n - 1));
    }

    /**
     * Generate budget recommendations based on predictions
     */
    private function generateBudgetRecommendations(AdCampaign $campaign, array $trends): array
    {
        $recommendations = [];
        $currentDailyBudget = $campaign->daily_budget ?? 0;

        // Analyze ROI trend
        $roiTrend = $trends['revenue']['direction'] ?? 'stable';
        $spendTrend = $trends['spend']['direction'] ?? 'stable';

        // High ROI + Increasing revenue = Increase budget
        if ($roiTrend === 'increasing' && $trends['revenue']['strength'] === 'strong') {
            $recommendedIncrease = 20; // 20% increase
            $recommendations[] = [
                'type' => 'increase_budget',
                'priority' => 'high',
                'current_daily_budget' => $currentDailyBudget,
                'recommended_daily_budget' => round($currentDailyBudget * 1.2, 2),
                'increase_percentage' => $recommendedIncrease,
                'reason' => 'Strong positive revenue trend detected',
                'expected_impact' => 'Projected to increase conversions by 15-25%',
            ];
        }

        // Decreasing conversions = Reduce budget or optimize
        if (($trends['conversions']['direction'] ?? 'stable') === 'decreasing') {
            $recommendations[] = [
                'type' => 'reduce_budget',
                'priority' => 'medium',
                'current_daily_budget' => $currentDailyBudget,
                'recommended_daily_budget' => round($currentDailyBudget * 0.8, 2),
                'decrease_percentage' => 20,
                'reason' => 'Declining conversion trend detected',
                'alternative' => 'Consider campaign optimization before reducing budget',
            ];
        }

        // Stable performance = Maintain with small test increase
        if ($roiTrend === 'stable' && $spendTrend === 'stable') {
            $recommendations[] = [
                'type' => 'maintain_budget',
                'priority' => 'low',
                'current_daily_budget' => $currentDailyBudget,
                'recommended_daily_budget' => round($currentDailyBudget * 1.05, 2),
                'increase_percentage' => 5,
                'reason' => 'Stable performance - small test increase recommended',
                'monitoring' => 'Monitor closely for 7-14 days',
            ];
        }

        // Budget allocation recommendations
        $recommendations[] = [
            'type' => 'allocation_strategy',
            'strategy' => $this->recommendAllocationStrategy($trends),
        ];

        return $recommendations;
    }

    /**
     * Recommend budget allocation strategy
     */
    private function recommendAllocationStrategy(array $trends): array
    {
        $ctrTrend = $trends['ctr']['direction'] ?? 'stable';
        $conversionTrend = $trends['conversions']['direction'] ?? 'stable';

        if ($ctrTrend === 'increasing' && $conversionTrend === 'increasing') {
            return [
                'name' => 'Aggressive Growth',
                'description' => 'Both CTR and conversions are increasing',
                'allocation' => [
                    'top_performing_keywords' => 60,
                    'testing_new_keywords' => 25,
                    'remarketing' => 15,
                ],
            ];
        }

        if ($ctrTrend === 'decreasing' || $conversionTrend === 'decreasing') {
            return [
                'name' => 'Conservative Optimization',
                'description' => 'Performance declining - focus on proven performers',
                'allocation' => [
                    'top_performing_keywords' => 75,
                    'testing_new_keywords' => 10,
                    'remarketing' => 15,
                ],
            ];
        }

        return [
            'name' => 'Balanced Growth',
            'description' => 'Stable performance - balanced approach',
            'allocation' => [
                'top_performing_keywords' => 50,
                'testing_new_keywords' => 30,
                'remarketing' => 20,
            ],
        ];
    }

    /**
     * Assess risks based on trends
     */
    private function assessRisks(array $trends): array
    {
        $risks = [];

        // Declining CTR risk
        if (($trends['ctr']['direction'] ?? 'stable') === 'decreasing') {
            $risks[] = [
                'type' => 'declining_ctr',
                'severity' => $trends['ctr']['strength'] === 'strong' ? 'high' : 'medium',
                'description' => 'Click-through rate is declining',
                'mitigation' => 'Refresh ad creative, test new headlines and images',
            ];
        }

        // Increasing CPC risk
        if (($trends['spend']['direction'] ?? 'stable') === 'increasing' &&
            ($trends['clicks']['direction'] ?? 'stable') !== 'increasing') {
            $risks[] = [
                'type' => 'rising_costs',
                'severity' => 'medium',
                'description' => 'Cost per click is increasing without proportional click growth',
                'mitigation' => 'Review bid strategy, optimize targeting, pause underperforming keywords',
            ];
        }

        // Declining conversions risk
        if (($trends['conversions']['direction'] ?? 'stable') === 'decreasing') {
            $risks[] = [
                'type' => 'conversion_decline',
                'severity' => 'high',
                'description' => 'Conversion rate is declining',
                'mitigation' => 'Review landing pages, check tracking, analyze user journey',
            ];
        }

        // Low data risk
        if (($trends['data_points'] ?? 0) < 14) {
            $risks[] = [
                'type' => 'insufficient_data',
                'severity' => 'low',
                'description' => 'Limited historical data may affect prediction accuracy',
                'mitigation' => 'Continue monitoring, predictions will improve with more data',
            ];
        }

        return $risks;
    }

    /**
     * Generate organization-level recommendations
     */
    private function generateOrgRecommendations(array $predictions, array $campaignForecasts): array
    {
        $recommendations = [];

        // Portfolio diversification
        $activeCampaigns = count($campaignForecasts);
        if ($activeCampaigns < 3) {
            $recommendations[] = [
                'type' => 'diversification',
                'priority' => 'medium',
                'message' => 'Consider diversifying campaign portfolio',
                'details' => 'Only ' . $activeCampaigns . ' active campaigns. Recommend testing new channels or audiences.',
            ];
        }

        // ROI optimization
        if ($predictions['predicted_roi'] < 50) {
            $recommendations[] = [
                'type' => 'roi_optimization',
                'priority' => 'high',
                'message' => 'Organization ROI below target',
                'current_roi' => round($predictions['predicted_roi'], 2),
                'target_roi' => 100,
                'action' => 'Review underperforming campaigns and reallocate budget to top performers',
            ];
        }

        // Budget reallocation
        $recommendations[] = [
            'type' => 'budget_reallocation',
            'priority' => 'medium',
            'message' => 'Optimize budget allocation across campaigns',
            'strategy' => 'Allocate more budget to campaigns with highest predicted ROI',
        ];

        return $recommendations;
    }

    /**
     * Get confidence recommendation
     */
    private function getConfidenceRecommendation(float $confidence): string
    {
        if ($confidence >= 80) {
            return 'High confidence - predictions are reliable for decision making';
        } elseif ($confidence >= 60) {
            return 'Medium confidence - use predictions as guidance, monitor closely';
        } else {
            return 'Low confidence - gather more data before making major decisions';
        }
    }

    /**
     * Get default trends when no data available
     */
    private function getDefaultTrends(): array
    {
        return [
            'impressions' => ['slope' => 0, 'direction' => 'stable', 'strength' => 'weak', 'percentage_change' => 0, 'average_value' => 0],
            'clicks' => ['slope' => 0, 'direction' => 'stable', 'strength' => 'weak', 'percentage_change' => 0, 'average_value' => 0],
            'spend' => ['slope' => 0, 'direction' => 'stable', 'strength' => 'weak', 'percentage_change' => 0, 'average_value' => 0],
            'conversions' => ['slope' => 0, 'direction' => 'stable', 'strength' => 'weak', 'percentage_change' => 0, 'average_value' => 0],
            'revenue' => ['slope' => 0, 'direction' => 'stable', 'strength' => 'weak', 'percentage_change' => 0, 'average_value' => 0],
            'ctr' => ['slope' => 0, 'direction' => 'stable', 'strength' => 'weak', 'percentage_change' => 0, 'average_value' => 0],
            'conversion_rate' => ['slope' => 0, 'direction' => 'stable', 'strength' => 'weak', 'percentage_change' => 0, 'average_value' => 0],
            'data_points' => 0,
            'moving_averages' => [
                'impressions' => ['7_day' => 0, '30_day' => 0],
                'clicks' => ['7_day' => 0, '30_day' => 0],
                'spend' => ['7_day' => 0, '30_day' => 0],
                'conversions' => ['7_day' => 0, '30_day' => 0],
                'revenue' => ['7_day' => 0, '30_day' => 0],
                'ctr' => ['7_day' => 0, '30_day' => 0],
            ],
        ];
    }

    /**
     * Get default predictions when no data available
     */
    private function getDefaultPredictions(): array
    {
        return [
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_spend' => 0,
            'total_conversions' => 0,
            'total_revenue' => 0,
            'predicted_ctr' => 0,
            'predicted_cpc' => 0,
            'predicted_conversion_rate' => 0,
            'predicted_roi' => 0,
            'daily_averages' => [
                'impressions' => 0,
                'clicks' => 0,
                'spend' => 0,
                'conversions' => 0,
                'revenue' => 0,
            ],
        ];
    }
}
