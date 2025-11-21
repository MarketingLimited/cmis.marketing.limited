<?php

namespace App\Services\Analytics;

use App\Models\Campaign\Campaign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AI Insights Service (Phase 11)
 *
 * Analyzes campaign performance and generates automated recommendations
 * using rule-based AI and statistical analysis
 *
 * Features:
 * - Performance trend analysis
 * - Budget optimization recommendations
 * - Targeting suggestions
 * - Creative performance insights
 * - Anomaly detection reasoning
 * - Predictive insights
 */
class AIInsightsService
{
    /**
     * Insight severity levels
     */
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_LOW = 'low';

    /**
     * Insight types
     */
    const TYPE_BUDGET = 'budget';
    const TYPE_PERFORMANCE = 'performance';
    const TYPE_TARGETING = 'targeting';
    const TYPE_CREATIVE = 'creative';
    const TYPE_ANOMALY = 'anomaly';
    const TYPE_OPPORTUNITY = 'opportunity';

    /**
     * Generate comprehensive insights for a campaign
     *
     * @param string $campaignId Campaign UUID
     * @param array $options Analysis options
     * @return array Insights and recommendations
     */
    public function generateCampaignInsights(string $campaignId, array $options = []): array
    {
        $campaign = Campaign::findOrFail($campaignId);
        $metrics = $this->getCampaignMetrics($campaignId, $options['days'] ?? 30);

        $insights = [];

        // Performance analysis
        $insights = array_merge($insights, $this->analyzePerformanceTrends($campaign, $metrics));

        // Budget analysis
        $insights = array_merge($insights, $this->analyzeBudget($campaign, $metrics));

        // ROI analysis
        $insights = array_merge($insights, $this->analyzeROI($campaign, $metrics));

        // Conversion optimization
        $insights = array_merge($insights, $this->analyzeConversions($campaign, $metrics));

        // Anomaly detection
        $insights = array_merge($insights, $this->detectAnomalies($campaign, $metrics));

        // Opportunity identification
        $insights = array_merge($insights, $this->identifyOpportunities($campaign, $metrics));

        // Sort by severity
        usort($insights, function ($a, $b) {
            $severityOrder = [
                self::SEVERITY_CRITICAL => 0,
                self::SEVERITY_HIGH => 1,
                self::SEVERITY_MEDIUM => 2,
                self::SEVERITY_LOW => 3
            ];
            return $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']];
        });

        return [
            'campaign_id' => $campaignId,
            'campaign_name' => $campaign->name,
            'analysis_period' => $options['days'] ?? 30,
            'generated_at' => now()->toIso8601String(),
            'insights' => $insights,
            'summary' => $this->generateSummary($insights),
            'recommended_actions' => $this->generateActionPlan($insights)
        ];
    }

    /**
     * Analyze performance trends
     *
     * @param Campaign $campaign
     * @param array $metrics
     * @return array Insights
     */
    protected function analyzePerformanceTrends(Campaign $campaign, array $metrics): array
    {
        $insights = [];

        // Check CTR trend
        $ctrTrend = $this->calculateTrend($metrics, 'ctr');
        if ($ctrTrend['direction'] === 'declining' && abs($ctrTrend['change']) > 20) {
            $insights[] = [
                'type' => self::TYPE_PERFORMANCE,
                'severity' => self::SEVERITY_HIGH,
                'title' => 'Declining Click-Through Rate',
                'message' => sprintf(
                    'CTR has declined by %.1f%% over the past week. Your ads may need refreshing.',
                    abs($ctrTrend['change'])
                ),
                'metrics' => [
                    'current_ctr' => $metrics['current']['ctr'] ?? 0,
                    'previous_ctr' => $metrics['previous']['ctr'] ?? 0,
                    'change_percent' => $ctrTrend['change']
                ],
                'recommendations' => [
                    'Test new ad creative to combat ad fatigue',
                    'Review and update ad copy for relevance',
                    'Consider A/B testing different headlines',
                    'Refresh visual assets with new imagery'
                ]
            ];
        }

        // Check conversion rate
        $conversionTrend = $this->calculateTrend($metrics, 'conversion_rate');
        if ($conversionTrend['direction'] === 'declining' && abs($conversionTrend['change']) > 15) {
            $insights[] = [
                'type' => self::TYPE_PERFORMANCE,
                'severity' => self::SEVERITY_CRITICAL,
                'title' => 'Conversion Rate Dropping',
                'message' => sprintf(
                    'Conversion rate has dropped by %.1f%%. Investigate landing page and tracking.',
                    abs($conversionTrend['change'])
                ),
                'metrics' => [
                    'current_rate' => $metrics['current']['conversion_rate'] ?? 0,
                    'previous_rate' => $metrics['previous']['conversion_rate'] ?? 0,
                    'change_percent' => $conversionTrend['change']
                ],
                'recommendations' => [
                    'Audit landing page for technical issues',
                    'Verify conversion tracking is working correctly',
                    'Test different landing page variants',
                    'Review user experience and page load speed',
                    'Check for mobile vs desktop performance differences'
                ]
            ];
        }

        // Check impression share
        if (isset($metrics['current']['impression_share']) && $metrics['current']['impression_share'] < 50) {
            $insights[] = [
                'type' => self::TYPE_OPPORTUNITY,
                'severity' => self::SEVERITY_MEDIUM,
                'title' => 'Low Impression Share',
                'message' => sprintf(
                    'Your campaign is only showing for %.1f%% of available impressions.',
                    $metrics['current']['impression_share']
                ),
                'metrics' => [
                    'impression_share' => $metrics['current']['impression_share']
                ],
                'recommendations' => [
                    'Increase daily budget to capture more impressions',
                    'Raise bid amounts to win more auctions',
                    'Expand targeting to reach larger audience',
                    'Improve ad quality score through better relevance'
                ]
            ];
        }

        return $insights;
    }

    /**
     * Analyze budget utilization and pacing
     *
     * @param Campaign $campaign
     * @param array $metrics
     * @return array Insights
     */
    protected function analyzeBudget(Campaign $campaign, array $metrics): array
    {
        $insights = [];

        $totalBudget = $campaign->budget;
        $spentAmount = $metrics['current']['total_spend'] ?? 0;
        $daysElapsed = now()->diffInDays($campaign->start_date);
        $totalDays = $campaign->end_date ? now()->diffInDays($campaign->end_date) : 30;

        $budgetUtilization = ($totalBudget > 0) ? ($spentAmount / $totalBudget) * 100 : 0;
        $timeElapsed = ($totalDays > 0) ? ($daysElapsed / $totalDays) * 100 : 0;

        // Budget pacing analysis
        if ($budgetUtilization > $timeElapsed + 20) {
            $insights[] = [
                'type' => self::TYPE_BUDGET,
                'severity' => self::SEVERITY_HIGH,
                'title' => 'Budget Spending Too Fast',
                'message' => sprintf(
                    'You\'ve spent %.1f%% of budget with %.1f%% of time remaining. Campaign may run out of budget early.',
                    $budgetUtilization,
                    100 - $timeElapsed
                ),
                'metrics' => [
                    'budget_spent' => $spentAmount,
                    'total_budget' => $totalBudget,
                    'utilization_percent' => $budgetUtilization,
                    'time_elapsed_percent' => $timeElapsed,
                    'daily_spend_average' => $spentAmount / max($daysElapsed, 1)
                ],
                'recommendations' => [
                    'Reduce daily budget to extend campaign duration',
                    'Lower bid amounts to decrease spend rate',
                    'Pause underperforming ad sets',
                    'Implement dayparting to control spend timing',
                    'Consider requesting budget increase if performance is good'
                ]
            ];
        } elseif ($budgetUtilization < $timeElapsed - 20 && $daysElapsed > 7) {
            $insights[] = [
                'type' => self::TYPE_BUDGET,
                'severity' => self::SEVERITY_MEDIUM,
                'title' => 'Underspending Budget',
                'message' => sprintf(
                    'Only %.1f%% of budget spent with %.1f%% of time elapsed. You\'re missing opportunities.',
                    $budgetUtilization,
                    $timeElapsed
                ),
                'metrics' => [
                    'budget_spent' => $spentAmount,
                    'total_budget' => $totalBudget,
                    'utilization_percent' => $budgetUtilization,
                    'time_elapsed_percent' => $timeElapsed
                ],
                'recommendations' => [
                    'Increase daily budget to capture more traffic',
                    'Raise bid amounts to win more auctions',
                    'Expand targeting to reach more potential customers',
                    'Add more ad variations to increase coverage'
                ]
            ];
        }

        return $insights;
    }

    /**
     * Analyze ROI and profitability
     *
     * @param Campaign $campaign
     * @param array $metrics
     * @return array Insights
     */
    protected function analyzeROI(Campaign $campaign, array $metrics): array
    {
        $insights = [];

        $roi = $metrics['current']['roi'] ?? 0;
        $roas = $metrics['current']['roas'] ?? 0;

        // Negative ROI alert
        if ($roi < 0) {
            $insights[] = [
                'type' => self::TYPE_PERFORMANCE,
                'severity' => self::SEVERITY_CRITICAL,
                'title' => 'Negative Return on Investment',
                'message' => sprintf(
                    'Campaign ROI is %.1f%%. You\'re losing money on this campaign.',
                    $roi
                ),
                'metrics' => [
                    'roi' => $roi,
                    'roas' => $roas,
                    'revenue' => $metrics['current']['revenue'] ?? 0,
                    'spend' => $metrics['current']['total_spend'] ?? 0
                ],
                'recommendations' => [
                    'Pause campaign immediately to stop losses',
                    'Review targeting to ensure quality traffic',
                    'Analyze conversion funnel for drop-off points',
                    'Consider if product/offer pricing needs adjustment',
                    'Test different audience segments'
                ]
            ];
        } elseif ($roi < 50 && $roi >= 0) {
            $insights[] = [
                'type' => self::TYPE_PERFORMANCE,
                'severity' => self::SEVERITY_HIGH,
                'title' => 'Low Return on Investment',
                'message' => sprintf(
                    'Campaign ROI is only %.1f%%. Significant optimization needed.',
                    $roi
                ),
                'metrics' => [
                    'roi' => $roi,
                    'roas' => $roas
                ],
                'recommendations' => [
                    'Optimize targeting to focus on converting audiences',
                    'Improve landing page conversion rate',
                    'Test different offers or incentives',
                    'Reduce cost per acquisition through better targeting'
                ]
            ];
        } elseif ($roi > 200) {
            $insights[] = [
                'type' => self::TYPE_OPPORTUNITY,
                'severity' => self::SEVERITY_HIGH,
                'title' => 'Excellent ROI - Scale Opportunity',
                'message' => sprintf(
                    'Campaign achieving %.1f%% ROI! Consider scaling investment.',
                    $roi
                ),
                'metrics' => [
                    'roi' => $roi,
                    'roas' => $roas
                ],
                'recommendations' => [
                    'Increase budget to capture more volume',
                    'Duplicate winning elements to other campaigns',
                    'Expand to similar audience segments',
                    'Test scaling to additional platforms',
                    'Document and replicate successful strategies'
                ]
            ];
        }

        return $insights;
    }

    /**
     * Analyze conversion performance
     *
     * @param Campaign $campaign
     * @param array $metrics
     * @return array Insights
     */
    protected function analyzeConversions(Campaign $campaign, array $metrics): array
    {
        $insights = [];

        $conversionRate = $metrics['current']['conversion_rate'] ?? 0;
        $cpa = $metrics['current']['cpa'] ?? 0;

        // Low conversion rate
        if ($conversionRate < 1 && ($metrics['current']['clicks'] ?? 0) > 100) {
            $insights[] = [
                'type' => self::TYPE_PERFORMANCE,
                'severity' => self::SEVERITY_HIGH,
                'title' => 'Low Conversion Rate',
                'message' => sprintf(
                    'Conversion rate is only %.2f%% with significant traffic. Landing page may need optimization.',
                    $conversionRate
                ),
                'metrics' => [
                    'conversion_rate' => $conversionRate,
                    'clicks' => $metrics['current']['clicks'] ?? 0,
                    'conversions' => $metrics['current']['conversions'] ?? 0
                ],
                'recommendations' => [
                    'Run A/B tests on landing page design',
                    'Simplify conversion process (reduce form fields)',
                    'Add trust signals (reviews, testimonials, badges)',
                    'Improve page load speed',
                    'Ensure mobile optimization',
                    'Add clear call-to-action buttons'
                ]
            ];
        }

        // High CPA
        $industryBenchmark = $this->getIndustryBenchmark('cpa', $campaign);
        if ($cpa > $industryBenchmark * 1.5 && $cpa > 0) {
            $insights[] = [
                'type' => self::TYPE_PERFORMANCE,
                'severity' => self::SEVERITY_MEDIUM,
                'title' => 'High Cost Per Acquisition',
                'message' => sprintf(
                    'CPA of $%.2f is %.0f%% above industry benchmark of $%.2f.',
                    $cpa,
                    (($cpa / $industryBenchmark) - 1) * 100,
                    $industryBenchmark
                ),
                'metrics' => [
                    'cpa' => $cpa,
                    'benchmark' => $industryBenchmark,
                    'difference_percent' => (($cpa / $industryBenchmark) - 1) * 100
                ],
                'recommendations' => [
                    'Optimize targeting to reduce wasted spend',
                    'Improve ad relevance and quality score',
                    'Test lower-cost acquisition channels',
                    'Negotiate better rates with platforms if possible'
                ]
            ];
        }

        return $insights;
    }

    /**
     * Detect anomalies in campaign data
     *
     * @param Campaign $campaign
     * @param array $metrics
     * @return array Insights
     */
    protected function detectAnomalies(Campaign $campaign, array $metrics): array
    {
        $insights = [];

        // Sudden spike in CPC
        $cpcChange = $this->calculatePercentChange(
            $metrics['previous']['cpc'] ?? 0,
            $metrics['current']['cpc'] ?? 0
        );

        if ($cpcChange > 50) {
            $insights[] = [
                'type' => self::TYPE_ANOMALY,
                'severity' => self::SEVERITY_HIGH,
                'title' => 'Unusual CPC Increase',
                'message' => sprintf(
                    'Cost per click increased by %.1f%%. May indicate increased competition.',
                    $cpcChange
                ),
                'metrics' => [
                    'current_cpc' => $metrics['current']['cpc'] ?? 0,
                    'previous_cpc' => $metrics['previous']['cpc'] ?? 0,
                    'change_percent' => $cpcChange
                ],
                'recommendations' => [
                    'Check for increased competition in your niche',
                    'Review bid strategy adjustments',
                    'Consider testing alternative keywords/audiences',
                    'Evaluate if ROI still justifies higher CPC'
                ]
            ];
        }

        // Zero conversions with significant spend
        if (($metrics['current']['conversions'] ?? 0) == 0 && ($metrics['current']['total_spend'] ?? 0) > 100) {
            $insights[] = [
                'type' => self::TYPE_ANOMALY,
                'severity' => self::SEVERITY_CRITICAL,
                'title' => 'No Conversions Despite Spend',
                'message' => sprintf(
                    'Spent $%.2f with zero conversions. Conversion tracking may be broken.',
                    $metrics['current']['total_spend']
                ),
                'metrics' => [
                    'spend' => $metrics['current']['total_spend'],
                    'conversions' => 0,
                    'clicks' => $metrics['current']['clicks'] ?? 0
                ],
                'recommendations' => [
                    'URGENT: Verify conversion tracking is installed correctly',
                    'Test conversion pixel/tag firing',
                    'Check if landing page URL is correct',
                    'Pause campaign until tracking verified',
                    'Review analytics for actual conversions vs tracked conversions'
                ]
            ];
        }

        return $insights;
    }

    /**
     * Identify growth opportunities
     *
     * @param Campaign $campaign
     * @param array $metrics
     * @return array Insights
     */
    protected function identifyOpportunities(Campaign $campaign, array $metrics): array
    {
        $insights = [];

        // Good performance on limited budget
        if (($metrics['current']['roi'] ?? 0) > 100 &&
            ($metrics['current']['budget_utilization'] ?? 0) < 50) {
            $insights[] = [
                'type' => self::TYPE_OPPORTUNITY,
                'severity' => self::SEVERITY_MEDIUM,
                'title' => 'Scale Opportunity - Underutilized Budget',
                'message' => 'Campaign is profitable but not using full budget. Opportunity to scale.',
                'metrics' => [
                    'roi' => $metrics['current']['roi'],
                    'budget_utilization' => $metrics['current']['budget_utilization']
                ],
                'recommendations' => [
                    'Increase daily budget by 20-30%',
                    'Monitor performance closely for first week',
                    'Expand to lookalike audiences',
                    'Test additional ad variations'
                ]
            ];
        }

        // High engagement, low conversion
        if (($metrics['current']['ctr'] ?? 0) > 5 && ($metrics['current']['conversion_rate'] ?? 0) < 2) {
            $insights[] = [
                'type' => self::TYPE_OPPORTUNITY,
                'severity' => self::SEVERITY_HIGH,
                'title' => 'High Interest, Low Conversion',
                'message' => 'Strong CTR but weak conversion rate. Landing page optimization could unlock significant gains.',
                'metrics' => [
                    'ctr' => $metrics['current']['ctr'],
                    'conversion_rate' => $metrics['current']['conversion_rate']
                ],
                'recommendations' => [
                    'Focus on landing page optimization (high ROI potential)',
                    'Match landing page messaging to ad copy',
                    'Simplify conversion process',
                    'Add urgency elements (limited time offers)',
                    'Test different pricing strategies'
                ]
            ];
        }

        return $insights;
    }

    /**
     * Generate executive summary
     *
     * @param array $insights
     * @return array Summary
     */
    protected function generateSummary(array $insights): array
    {
        $criticalCount = count(array_filter($insights, fn($i) => $i['severity'] === self::SEVERITY_CRITICAL));
        $highCount = count(array_filter($insights, fn($i) => $i['severity'] === self::SEVERITY_HIGH));
        $opportunityCount = count(array_filter($insights, fn($i) => $i['type'] === self::TYPE_OPPORTUNITY));

        $overallHealth = 'good';
        if ($criticalCount > 0) {
            $overallHealth = 'critical';
        } elseif ($highCount > 2) {
            $overallHealth = 'needs_attention';
        } elseif ($opportunityCount > 0) {
            $overallHealth = 'excellent';
        }

        return [
            'overall_health' => $overallHealth,
            'total_insights' => count($insights),
            'by_severity' => [
                'critical' => $criticalCount,
                'high' => $highCount,
                'medium' => count(array_filter($insights, fn($i) => $i['severity'] === self::SEVERITY_MEDIUM)),
                'low' => count(array_filter($insights, fn($i) => $i['severity'] === self::SEVERITY_LOW))
            ],
            'by_type' => [
                'performance' => count(array_filter($insights, fn($i) => $i['type'] === self::TYPE_PERFORMANCE)),
                'budget' => count(array_filter($insights, fn($i) => $i['type'] === self::TYPE_BUDGET)),
                'opportunities' => $opportunityCount,
                'anomalies' => count(array_filter($insights, fn($i) => $i['type'] === self::TYPE_ANOMALY))
            ]
        ];
    }

    /**
     * Generate prioritized action plan
     *
     * @param array $insights
     * @return array Action plan
     */
    protected function generateActionPlan(array $insights): array
    {
        $actions = [];

        // Extract all recommendations by priority
        foreach ($insights as $insight) {
            if ($insight['severity'] === self::SEVERITY_CRITICAL || $insight['severity'] === self::SEVERITY_HIGH) {
                foreach ($insight['recommendations'] as $recommendation) {
                    $actions[] = [
                        'priority' => $insight['severity'],
                        'action' => $recommendation,
                        'related_to' => $insight['title'],
                        'type' => $insight['type']
                    ];
                }
            }
        }

        return array_slice($actions, 0, 10); // Top 10 actions
    }

    /**
     * Get campaign metrics
     *
     * @param string $campaignId
     * @param int $days
     * @return array Metrics
     */
    protected function getCampaignMetrics(string $campaignId, int $days = 30): array
    {
        // This would query actual metrics from database
        // Simplified for demonstration
        return [
            'current' => [
                'impressions' => 10000,
                'clicks' => 500,
                'conversions' => 50,
                'total_spend' => 1000.00,
                'revenue' => 2000.00,
                'ctr' => 5.0,
                'conversion_rate' => 10.0,
                'cpc' => 2.0,
                'cpa' => 20.0,
                'roi' => 100.0,
                'roas' => 2.0,
                'impression_share' => 45.0,
                'budget_utilization' => 75.0
            ],
            'previous' => [
                'ctr' => 6.0,
                'conversion_rate' => 12.0,
                'cpc' => 1.5
            ]
        ];
    }

    /**
     * Calculate trend direction and magnitude
     *
     * @param array $metrics
     * @param string $metricName
     * @return array Trend data
     */
    protected function calculateTrend(array $metrics, string $metricName): array
    {
        $current = $metrics['current'][$metricName] ?? 0;
        $previous = $metrics['previous'][$metricName] ?? $current;

        $change = $this->calculatePercentChange($previous, $current);

        return [
            'direction' => $change > 5 ? 'improving' : ($change < -5 ? 'declining' : 'stable'),
            'change' => $change
        ];
    }

    /**
     * Calculate percent change
     *
     * @param float $oldValue
     * @param float $newValue
     * @return float Percent change
     */
    protected function calculatePercentChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return (($newValue - $oldValue) / $oldValue) * 100;
    }

    /**
     * Get industry benchmark for metric
     *
     * @param string $metric
     * @param Campaign $campaign
     * @return float Benchmark value
     */
    protected function getIndustryBenchmark(string $metric, Campaign $campaign): float
    {
        // This would query actual industry benchmarks from database
        // Simplified for demonstration
        $benchmarks = [
            'cpa' => 50.0,
            'ctr' => 2.5,
            'conversion_rate' => 3.0
        ];

        return $benchmarks[$metric] ?? 0;
    }
}
