<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

/**
 * ROI Calculation Engine (Phase 7)
 *
 * Calculates return on investment, profitability, and financial metrics
 */
class ROICalculationEngine
{
    /**
     * Calculate ROI for a campaign
     *
     * @param string $campaignId
     * @param array $dateRange
     * @return array
     */
    public function calculateCampaignROI(string $campaignId, array $dateRange = []): array
    {
        try {
            $startDate = $dateRange['start'] ?? Carbon::now()->subDays(30);
            $endDate = $dateRange['end'] ?? Carbon::now();

            // Get campaign performance data
            $performance = DB::table('cmis_analytics.campaign_performance')
                ->where('campaign_id', $campaignId)
                ->whereBetween('date', [$startDate, $endDate])
                ->select(
                    DB::raw('SUM(spend) as total_spend'),
                    DB::raw('SUM(revenue) as total_revenue'),
                    DB::raw('SUM(impressions) as total_impressions'),
                    DB::raw('SUM(clicks) as total_clicks'),
                    DB::raw('SUM(conversions) as total_conversions')
                )
                ->first();

            if (!$performance || $performance->total_spend == 0) {
                return [
                    'success' => false,
                    'error' => 'Insufficient data or zero spend'
                ];
            }

            // Calculate financial metrics
            $totalSpend = (float) $performance->total_spend;
            $totalRevenue = (float) $performance->total_revenue;
            $totalConversions = (int) $performance->total_conversions;

            // ROI calculation: (Revenue - Spend) / Spend * 100
            $roi = (($totalRevenue - $totalSpend) / $totalSpend) * 100;

            // ROAS (Return on Ad Spend): Revenue / Spend
            $roas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;

            // Profit
            $profit = $totalRevenue - $totalSpend;

            // Profit margin: (Profit / Revenue) * 100
            $profitMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;

            // Cost per acquisition
            $cpa = $totalConversions > 0 ? $totalSpend / $totalConversions : 0;

            // Revenue per conversion
            $revenuePerConversion = $totalConversions > 0 ? $totalRevenue / $totalConversions : 0;

            // Break-even analysis
            $breakEvenConversions = $revenuePerConversion > 0
                ? ceil($totalSpend / $revenuePerConversion)
                : 0;

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'financial_metrics' => [
                    'total_spend' => round($totalSpend, 2),
                    'total_revenue' => round($totalRevenue, 2),
                    'profit' => round($profit, 2),
                    'roi_percentage' => round($roi, 2),
                    'roas' => round($roas, 2),
                    'profit_margin_percentage' => round($profitMargin, 2)
                ],
                'performance_metrics' => [
                    'total_conversions' => $totalConversions,
                    'cost_per_acquisition' => round($cpa, 2),
                    'revenue_per_conversion' => round($revenuePerConversion, 2),
                    'break_even_conversions' => $breakEvenConversions
                ],
                'profitability_status' => $this->getProfitabilityStatus($roi),
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate campaign ROI', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate ROI for an organization
     *
     * @param string $orgId
     * @param array $dateRange
     * @return array
     */
    public function calculateOrganizationROI(string $orgId, array $dateRange = []): array
    {
        try {
            $startDate = $dateRange['start'] ?? Carbon::now()->subDays(30);
            $endDate = $dateRange['end'] ?? Carbon::now();

            // Get all campaigns for organization
            $campaigns = DB::table('cmis.campaigns')
                ->where('org_id', $orgId)
                ->pluck('campaign_id');

            // Aggregate performance across all campaigns
            $performance = DB::table('cmis_analytics.campaign_performance')
                ->whereIn('campaign_id', $campaigns)
                ->whereBetween('date', [$startDate, $endDate])
                ->select(
                    DB::raw('SUM(spend) as total_spend'),
                    DB::raw('SUM(revenue) as total_revenue'),
                    DB::raw('SUM(conversions) as total_conversions'),
                    DB::raw('COUNT(DISTINCT campaign_id) as active_campaigns')
                )
                ->first();

            if (!$performance || $performance->total_spend == 0) {
                return [
                    'success' => false,
                    'error' => 'Insufficient data or zero spend'
                ];
            }

            $totalSpend = (float) $performance->total_spend;
            $totalRevenue = (float) $performance->total_revenue;
            $totalConversions = (int) $performance->total_conversions;

            // Calculate organization-level metrics
            $roi = (($totalRevenue - $totalSpend) / $totalSpend) * 100;
            $roas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;
            $profit = $totalRevenue - $totalSpend;

            // Get top and bottom performing campaigns
            $topCampaigns = $this->getTopCampaignsByROI($orgId, $dateRange, 5);
            $bottomCampaigns = $this->getBottomCampaignsByROI($orgId, $dateRange, 5);

            return [
                'success' => true,
                'org_id' => $orgId,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'overall_metrics' => [
                    'total_spend' => round($totalSpend, 2),
                    'total_revenue' => round($totalRevenue, 2),
                    'profit' => round($profit, 2),
                    'roi_percentage' => round($roi, 2),
                    'roas' => round($roas, 2),
                    'active_campaigns' => $performance->active_campaigns
                ],
                'top_campaigns' => $topCampaigns,
                'bottom_campaigns' => $bottomCampaigns,
                'profitability_status' => $this->getProfitabilityStatus($roi),
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate organization ROI', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get profitability status based on ROI
     *
     * @param float $roi
     * @return array
     */
    protected function getProfitabilityStatus(float $roi): array
    {
        if ($roi >= 100) {
            return [
                'status' => 'highly_profitable',
                'description' => 'Excellent ROI - investment generating strong returns',
                'color' => 'green'
            ];
        } elseif ($roi >= 50) {
            return [
                'status' => 'profitable',
                'description' => 'Good ROI - investment performing well',
                'color' => 'light_green'
            ];
        } elseif ($roi >= 0) {
            return [
                'status' => 'break_even',
                'description' => 'Break-even or minimal profit - optimization recommended',
                'color' => 'yellow'
            ];
        } elseif ($roi >= -25) {
            return [
                'status' => 'unprofitable',
                'description' => 'Loss-making - immediate optimization required',
                'color' => 'orange'
            ];
        } else {
            return [
                'status' => 'highly_unprofitable',
                'description' => 'Significant losses - consider pausing or major restructuring',
                'color' => 'red'
            ];
        }
    }

    /**
     * Get top campaigns by ROI
     *
     * @param string $orgId
     * @param array $dateRange
     * @param int $limit
     * @return array
     */
    protected function getTopCampaignsByROI(string $orgId, array $dateRange, int $limit = 5): array
    {
        $startDate = $dateRange['start'] ?? Carbon::now()->subDays(30);
        $endDate = $dateRange['end'] ?? Carbon::now();

        $campaigns = DB::table('cmis.campaigns')
            ->where('org_id', $orgId)
            ->join('cmis_analytics.campaign_performance', 'cmis.campaigns.campaign_id', '=', 'cmis_analytics.campaign_performance.campaign_id')
            ->whereBetween('cmis_analytics.campaign_performance.date', [$startDate, $endDate])
            ->select(
                'cmis.campaigns.campaign_id',
                'cmis.campaigns.name',
                DB::raw('SUM(cmis_analytics.campaign_performance.spend) as total_spend'),
                DB::raw('SUM(cmis_analytics.campaign_performance.revenue) as total_revenue')
            )
            ->groupBy('cmis.campaigns.campaign_id', 'cmis.campaigns.name')
            ->havingRaw('SUM(cmis_analytics.campaign_performance.spend) > 0')
            ->get()
            ->map(function($campaign) {
                $spend = (float) $campaign->total_spend;
                $revenue = (float) $campaign->total_revenue;
                $roi = (($revenue - $spend) / $spend) * 100;

                return [
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->name,
                    'spend' => round($spend, 2),
                    'revenue' => round($revenue, 2),
                    'roi_percentage' => round($roi, 2)
                ];
            })
            ->sortByDesc('roi_percentage')
            ->take($limit)
            ->values()
            ->toArray();

        return $campaigns;
    }

    /**
     * Get bottom campaigns by ROI
     *
     * @param string $orgId
     * @param array $dateRange
     * @param int $limit
     * @return array
     */
    protected function getBottomCampaignsByROI(string $orgId, array $dateRange, int $limit = 5): array
    {
        $startDate = $dateRange['start'] ?? Carbon::now()->subDays(30);
        $endDate = $dateRange['end'] ?? Carbon::now();

        $campaigns = DB::table('cmis.campaigns')
            ->where('org_id', $orgId)
            ->join('cmis_analytics.campaign_performance', 'cmis.campaigns.campaign_id', '=', 'cmis_analytics.campaign_performance.campaign_id')
            ->whereBetween('cmis_analytics.campaign_performance.date', [$startDate, $endDate])
            ->select(
                'cmis.campaigns.campaign_id',
                'cmis.campaigns.name',
                DB::raw('SUM(cmis_analytics.campaign_performance.spend) as total_spend'),
                DB::raw('SUM(cmis_analytics.campaign_performance.revenue) as total_revenue')
            )
            ->groupBy('cmis.campaigns.campaign_id', 'cmis.campaigns.name')
            ->havingRaw('SUM(cmis_analytics.campaign_performance.spend) > 0')
            ->get()
            ->map(function($campaign) {
                $spend = (float) $campaign->total_spend;
                $revenue = (float) $campaign->total_revenue;
                $roi = (($revenue - $spend) / $spend) * 100;

                return [
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->name,
                    'spend' => round($spend, 2),
                    'revenue' => round($revenue, 2),
                    'roi_percentage' => round($roi, 2)
                ];
            })
            ->sortBy('roi_percentage')
            ->take($limit)
            ->values()
            ->toArray();

        return $campaigns;
    }

    /**
     * Calculate lifetime value (LTV)
     *
     * @param string $campaignId
     * @return array
     */
    public function calculateLifetimeValue(string $campaignId): array
    {
        try {
            // Get all-time performance data
            $performance = DB::table('cmis_analytics.campaign_performance')
                ->where('campaign_id', $campaignId)
                ->select(
                    DB::raw('SUM(revenue) as total_revenue'),
                    DB::raw('SUM(conversions) as total_conversions'),
                    DB::raw('COUNT(DISTINCT date) as active_days')
                )
                ->first();

            if (!$performance || $performance->total_conversions == 0) {
                return [
                    'success' => false,
                    'error' => 'Insufficient conversion data'
                ];
            }

            $totalRevenue = (float) $performance->total_revenue;
            $totalConversions = (int) $performance->total_conversions;
            $activeDays = (int) $performance->active_days;

            // Average revenue per customer
            $averageRevenuePerCustomer = $totalRevenue / $totalConversions;

            // Average daily conversions
            $avgDailyConversions = $activeDays > 0 ? $totalConversions / $activeDays : 0;

            // Estimated customer lifetime (simplified - assumes 1 year)
            $estimatedLifetimeDays = 365;

            // Estimated LTV (simple model)
            $estimatedLTV = $averageRevenuePerCustomer;

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'lifetime_metrics' => [
                    'total_customers' => $totalConversions,
                    'total_revenue' => round($totalRevenue, 2),
                    'average_revenue_per_customer' => round($averageRevenuePerCustomer, 2),
                    'estimated_customer_lifetime_days' => $estimatedLifetimeDays,
                    'estimated_ltv' => round($estimatedLTV, 2)
                ],
                'performance' => [
                    'active_days' => $activeDays,
                    'avg_daily_conversions' => round($avgDailyConversions, 2)
                ],
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate lifetime value', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Project future ROI based on current trends
     *
     * @param string $campaignId
     * @param int $daysToProject
     * @return array
     */
    public function projectROI(string $campaignId, int $daysToProject = 30): array
    {
        try {
            // Get historical data (last 30 days)
            $historicalData = DB::table('cmis_analytics.campaign_performance')
                ->where('campaign_id', $campaignId)
                ->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()])
                ->select(
                    DB::raw('AVG(spend) as avg_daily_spend'),
                    DB::raw('AVG(revenue) as avg_daily_revenue'),
                    DB::raw('AVG(conversions) as avg_daily_conversions')
                )
                ->first();

            if (!$historicalData || $historicalData->avg_daily_spend == 0) {
                return [
                    'success' => false,
                    'error' => 'Insufficient historical data'
                ];
            }

            $avgDailySpend = (float) $historicalData->avg_daily_spend;
            $avgDailyRevenue = (float) $historicalData->avg_daily_revenue;

            // Project future metrics
            $projectedSpend = $avgDailySpend * $daysToProject;
            $projectedRevenue = $avgDailyRevenue * $daysToProject;
            $projectedProfit = $projectedRevenue - $projectedSpend;
            $projectedROI = ($projectedProfit / $projectedSpend) * 100;

            // Calculate confidence level (based on data consistency)
            $confidence = $this->calculateProjectionConfidence($campaignId);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'projection_period_days' => $daysToProject,
                'projected_metrics' => [
                    'projected_spend' => round($projectedSpend, 2),
                    'projected_revenue' => round($projectedRevenue, 2),
                    'projected_profit' => round($projectedProfit, 2),
                    'projected_roi_percentage' => round($projectedROI, 2)
                ],
                'daily_averages' => [
                    'avg_daily_spend' => round($avgDailySpend, 2),
                    'avg_daily_revenue' => round($avgDailyRevenue, 2),
                    'avg_daily_conversions' => round((float) $historicalData->avg_daily_conversions, 2)
                ],
                'confidence_level' => $confidence,
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to project ROI', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate projection confidence based on data consistency
     *
     * @param string $campaignId
     * @return array
     */
    protected function calculateProjectionConfidence(string $campaignId): array
    {
        try {
            // Get daily variance in key metrics
            $dailyData = DB::table('cmis_analytics.campaign_performance')
                ->where('campaign_id', $campaignId)
                ->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()])
                ->select('spend', 'revenue')
                ->get();

            if ($dailyData->count() < 7) {
                return [
                    'level' => 'low',
                    'percentage' => 40,
                    'reason' => 'Insufficient data points'
                ];
            }

            // Calculate coefficient of variation
            $spendValues = $dailyData->pluck('spend')->toArray();
            $avgSpend = array_sum($spendValues) / count($spendValues);
            $stdDevSpend = $this->calculateStdDev($spendValues, $avgSpend);
            $cvSpend = $avgSpend > 0 ? ($stdDevSpend / $avgSpend) : 1;

            // Lower CV = higher confidence
            if ($cvSpend < 0.2) {
                return ['level' => 'high', 'percentage' => 90, 'reason' => 'Consistent performance'];
            } elseif ($cvSpend < 0.5) {
                return ['level' => 'medium', 'percentage' => 70, 'reason' => 'Moderate variability'];
            } else {
                return ['level' => 'low', 'percentage' => 50, 'reason' => 'High variability'];
            }

        } catch (\Exception $e) {
            return ['level' => 'low', 'percentage' => 40, 'reason' => 'Error calculating confidence'];
        }
    }

    /**
     * Calculate standard deviation
     *
     * @param array $values
     * @param float $mean
     * @return float
     */
    protected function calculateStdDev(array $values, float $mean): float
    {
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow((float) $value - $mean, 2);
        }

        $variance /= count($values);

        return sqrt($variance);
    }
}
