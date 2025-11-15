<?php

namespace App\Services\AI;

use App\Models\AdPlatform\AdCampaign;
use App\Models\Core\Org;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

/**
 * AI-powered campaign optimization service
 * Analyzes performance and provides automated optimization recommendations
 */
class CampaignOptimizationService
{
    /**
     * Analyze campaign performance and generate optimization recommendations
     */
    public function analyzeCampaign(AdCampaign $campaign): array
    {
        $metrics = $this->getCampaignMetrics($campaign);

        return [
            'campaign_id' => $campaign->campaign_id,
            'campaign_name' => $campaign->name,
            'analysis_date' => now()->toIso8601String(),
            'performance_score' => $this->calculatePerformanceScore($metrics),
            'kpis' => $this->analyzeKPIs($metrics),
            'recommendations' => $this->generateRecommendations($campaign, $metrics),
            'budget_optimization' => $this->analyzeBudget($campaign, $metrics),
            'bid_optimization' => $this->analyzeBidStrategy($campaign, $metrics),
            'audience_insights' => $this->analyzeAudience($campaign, $metrics),
            'predicted_performance' => $this->predictPerformance($campaign, $metrics),
        ];
    }

    /**
     * Get aggregated metrics for campaign
     */
    private function getCampaignMetrics(AdCampaign $campaign): array
    {
        $last30Days = DB::table('cmis.ad_metrics')
            ->where('campaign_id', $campaign->campaign_id)
            ->where('date', '>=', now()->subDays(30))
            ->select([
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(conversions) as total_conversions'),
                DB::raw('SUM(revenue) as total_revenue'),
                DB::raw('AVG(ctr) as avg_ctr'),
                DB::raw('AVG(cpc) as avg_cpc'),
                DB::raw('AVG(roi) as avg_roi'),
            ])
            ->first();

        $last7Days = DB::table('cmis.ad_metrics')
            ->where('campaign_id', $campaign->campaign_id)
            ->where('date', '>=', now()->subDays(7))
            ->select([
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(conversions) as total_conversions'),
            ])
            ->first();

        return [
            'last_30_days' => (array)$last30Days,
            'last_7_days' => (array)$last7Days,
            'campaign_age_days' => now()->diffInDays($campaign->created_at),
            'budget' => $campaign->budget,
            'daily_budget' => $campaign->daily_budget ?? ($campaign->budget / 30),
        ];
    }

    /**
     * Calculate overall performance score (0-100)
     */
    private function calculatePerformanceScore(array $metrics): int
    {
        $score = 50; // Base score

        $metrics30 = $metrics['last_30_days'];

        // CTR score (0-25 points)
        $ctr = $metrics30['avg_ctr'] ?? 0;
        if ($ctr >= 5.0) $score += 25;
        elseif ($ctr >= 3.0) $score += 20;
        elseif ($ctr >= 2.0) $score += 15;
        elseif ($ctr >= 1.0) $score += 10;

        // ROI score (0-25 points)
        $roi = $metrics30['avg_roi'] ?? 0;
        if ($roi >= 400) $score += 25;
        elseif ($roi >= 300) $score += 20;
        elseif ($roi >= 200) $score += 15;
        elseif ($roi >= 100) $score += 10;

        // Conversion score (0-25 points)
        $conversions = $metrics30['total_conversions'] ?? 0;
        if ($conversions >= 100) $score += 25;
        elseif ($conversions >= 50) $score += 20;
        elseif ($conversions >= 20) $score += 15;
        elseif ($conversions >= 5) $score += 10;

        // Budget efficiency (0-25 points)
        $spend = $metrics30['total_spend'] ?? 0;
        $budget = $metrics['budget'] ?? 1;
        $efficiency = ($conversions / max($spend, 1)) * 100;
        if ($efficiency >= 5.0) $score += 25;
        elseif ($efficiency >= 3.0) $score += 20;
        elseif ($efficiency >= 1.0) $score += 15;
        elseif ($efficiency >= 0.5) $score += 10;

        return min(100, max(0, $score));
    }

    /**
     * Analyze KPIs
     */
    private function analyzeKPIs(array $metrics): array
    {
        $metrics30 = $metrics['last_30_days'];

        return [
            'ctr' => [
                'value' => round($metrics30['avg_ctr'] ?? 0, 2),
                'status' => $this->getKPIStatus($metrics30['avg_ctr'] ?? 0, 2.0, 3.0, 5.0),
                'benchmark' => 3.0,
            ],
            'cpc' => [
                'value' => round($metrics30['avg_cpc'] ?? 0, 2),
                'status' => $this->getKPIStatus($metrics30['avg_cpc'] ?? 999, 2.0, 1.5, 1.0, true),
                'benchmark' => 1.5,
            ],
            'roi' => [
                'value' => round($metrics30['avg_roi'] ?? 0, 2),
                'status' => $this->getKPIStatus($metrics30['avg_roi'] ?? 0, 150, 250, 400),
                'benchmark' => 250,
            ],
            'conversion_rate' => [
                'value' => $this->calculateConversionRate($metrics30),
                'status' => $this->getKPIStatus($this->calculateConversionRate($metrics30), 1.0, 2.0, 3.0),
                'benchmark' => 2.0,
            ],
        ];
    }

    /**
     * Generate optimization recommendations
     */
    private function generateRecommendations(AdCampaign $campaign, array $metrics): array
    {
        $recommendations = [];
        $score = $this->calculatePerformanceScore($metrics);
        $metrics30 = $metrics['last_30_days'];

        // Low CTR recommendations
        if (($metrics30['avg_ctr'] ?? 0) < 2.0) {
            $recommendations[] = [
                'type' => 'creative',
                'priority' => 'high',
                'action' => 'Improve ad creative',
                'reason' => 'CTR below benchmark (< 2.0%)',
                'suggestions' => [
                    'Test different ad headlines',
                    'Use more engaging images/videos',
                    'Add strong call-to-action',
                    'Test different ad formats',
                ],
            ];
        }

        // High CPC recommendations
        if (($metrics30['avg_cpc'] ?? 0) > 2.0) {
            $recommendations[] = [
                'type' => 'bidding',
                'priority' => 'high',
                'action' => 'Optimize bidding strategy',
                'reason' => 'CPC above benchmark (> $2.00)',
                'suggestions' => [
                    'Switch to automated bidding',
                    'Refine audience targeting',
                    'Exclude low-performing placements',
                    'Test lower bid amounts',
                ],
            ];
        }

        // Low conversion rate
        $conversionRate = $this->calculateConversionRate($metrics30);
        if ($conversionRate < 1.0) {
            $recommendations[] = [
                'type' => 'targeting',
                'priority' => 'high',
                'action' => 'Improve targeting',
                'reason' => 'Low conversion rate (< 1.0%)',
                'suggestions' => [
                    'Refine audience demographics',
                    'Use lookalike audiences',
                    'Exclude irrelevant interests',
                    'Test different audience segments',
                ],
            ];
        }

        // Budget optimization
        $budgetUsed = ($metrics30['total_spend'] ?? 0) / max($metrics['budget'] ?? 1, 1);
        if ($budgetUsed < 0.5) {
            $recommendations[] = [
                'type' => 'budget',
                'priority' => 'medium',
                'action' => 'Increase budget utilization',
                'reason' => 'Only ' . round($budgetUsed * 100) . '% of budget spent',
                'suggestions' => [
                    'Increase daily budget limits',
                    'Expand audience size',
                    'Add more ad placements',
                    'Test additional ad formats',
                ],
            ];
        }

        // High performance - scale up
        if ($score >= 80) {
            $recommendations[] = [
                'type' => 'scaling',
                'priority' => 'medium',
                'action' => 'Scale successful campaign',
                'reason' => 'Excellent performance (score: ' . $score . '/100)',
                'suggestions' => [
                    'Increase budget by 20-30%',
                    'Expand to similar audiences',
                    'Test additional placements',
                    'Create similar campaigns',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Analyze budget allocation
     */
    private function analyzeBudget(AdCampaign $campaign, array $metrics): array
    {
        $metrics30 = $metrics['last_30_days'];
        $spend = $metrics30['total_spend'] ?? 0;
        $budget = $metrics['budget'] ?? 1;
        $conversions = $metrics30['total_conversions'] ?? 0;

        $costPerConversion = $conversions > 0 ? $spend / $conversions : 0;
        $budgetUsed = ($spend / $budget) * 100;

        return [
            'total_budget' => $budget,
            'spent' => $spend,
            'remaining' => $budget - $spend,
            'budget_used_pct' => round($budgetUsed, 2),
            'cost_per_conversion' => round($costPerConversion, 2),
            'recommended_budget' => $this->calculateRecommendedBudget($metrics),
            'budget_status' => $this->getBudgetStatus($budgetUsed),
        ];
    }

    /**
     * Analyze bid strategy
     */
    private function analyzeBidStrategy(AdCampaign $campaign, array $metrics): array
    {
        $metrics30 = $metrics['last_30_days'];

        return [
            'current_cpc' => round($metrics30['avg_cpc'] ?? 0, 2),
            'recommended_bid_adjustment' => $this->calculateBidAdjustment($metrics),
            'bid_strategy' => 'automated', // Could be enhanced to detect actual strategy
            'optimization_goal' => $this->recommendOptimizationGoal($metrics),
        ];
    }

    /**
     * Analyze audience performance
     */
    private function analyzeAudience(AdCampaign $campaign, array $metrics): array
    {
        return [
            'reach' => $metrics['last_30_days']['total_impressions'] ?? 0,
            'engagement_rate' => round(($metrics['last_30_days']['total_clicks'] ?? 0) /
                max($metrics['last_30_days']['total_impressions'] ?? 1, 1) * 100, 2),
            'recommendations' => [
                'Expand lookalike audiences',
                'Test age/gender segments',
                'Refine interest targeting',
            ],
        ];
    }

    /**
     * Predict future performance
     */
    private function predictPerformance(AdCampaign $campaign, array $metrics): array
    {
        // Simple linear prediction based on last 7 days trend
        $metrics7 = $metrics['last_7_days'];
        $metrics30 = $metrics['last_30_days'];

        $dailySpend7 = ($metrics7['total_spend'] ?? 0) / 7;
        $dailyConversions7 = ($metrics7['total_conversions'] ?? 0) / 7;

        return [
            'next_7_days' => [
                'predicted_spend' => round($dailySpend7 * 7, 2),
                'predicted_conversions' => round($dailyConversions7 * 7),
                'predicted_roi' => round(($metrics30['avg_roi'] ?? 0), 2),
                'confidence' => 'medium',
            ],
            'next_30_days' => [
                'predicted_spend' => round($dailySpend7 * 30, 2),
                'predicted_conversions' => round($dailyConversions7 * 30),
                'predicted_roi' => round(($metrics30['avg_roi'] ?? 0), 2),
                'confidence' => 'low',
            ],
        ];
    }

    // Helper methods

    private function getKPIStatus(float $value, float $poor, float $good, float $excellent, bool $inverse = false): string
    {
        if ($inverse) {
            if ($value <= $excellent) return 'excellent';
            if ($value <= $good) return 'good';
            if ($value <= $poor) return 'fair';
            return 'poor';
        } else {
            if ($value >= $excellent) return 'excellent';
            if ($value >= $good) return 'good';
            if ($value >= $poor) return 'fair';
            return 'poor';
        }
    }

    private function calculateConversionRate(array $metrics): float
    {
        $clicks = $metrics['total_clicks'] ?? 0;
        $conversions = $metrics['total_conversions'] ?? 0;
        return $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0;
    }

    private function getBudgetStatus(float $budgetUsedPct): string
    {
        if ($budgetUsedPct >= 90) return 'critical';
        if ($budgetUsedPct >= 75) return 'warning';
        if ($budgetUsedPct >= 50) return 'healthy';
        return 'underutilized';
    }

    private function calculateRecommendedBudget(array $metrics): float
    {
        $metrics30 = $metrics['last_30_days'];
        $conversions = $metrics30['total_conversions'] ?? 0;
        $spend = $metrics30['total_spend'] ?? 0;
        $roi = $metrics30['avg_roi'] ?? 0;

        // If ROI > 250%, recommend 20% budget increase
        if ($roi > 250) {
            return $metrics['budget'] * 1.2;
        }

        // If ROI < 150%, recommend 20% budget decrease
        if ($roi < 150 && $roi > 0) {
            return $metrics['budget'] * 0.8;
        }

        return $metrics['budget'];
    }

    private function calculateBidAdjustment(array $metrics): string
    {
        $metrics30 = $metrics['last_30_days'];
        $cpc = $metrics30['avg_cpc'] ?? 0;
        $roi = $metrics30['avg_roi'] ?? 0;

        if ($roi > 300) {
            return '+20% (High ROI, increase bids to scale)';
        }

        if ($cpc > 2.5) {
            return '-15% (High CPC, decrease bids)';
        }

        if ($roi < 150) {
            return '-10% (Low ROI, reduce spend)';
        }

        return '0% (Maintain current bids)';
    }

    private function recommendOptimizationGoal(array $metrics): string
    {
        $metrics30 = $metrics['last_30_days'];
        $conversions = $metrics30['total_conversions'] ?? 0;

        if ($conversions < 10) {
            return 'Maximize Clicks (build initial data)';
        }

        if ($conversions >= 50) {
            return 'Target ROAS (optimize for revenue)';
        }

        return 'Target CPA (optimize for conversions)';
    }
}
