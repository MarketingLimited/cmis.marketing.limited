<?php

namespace App\Services\AI;

use App\Models\Core\Org;
use App\Models\AdPlatform\AdCampaign;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Knowledge Learning Service
 * Learns from historical campaign data to identify patterns and best practices
 */
class KnowledgeLearningService
{
    /**
     * Analyze organization's campaign performance patterns
     */
    public function learnOrganizationPatterns(Org $org): array
    {
        $campaigns = AdCampaign::where('org_id', $org->org_id)
            ->whereIn('status', ['active', 'paused', 'completed'])
            ->get();

        return [
            'org_id' => $org->org_id,
            'total_campaigns_analyzed' => $campaigns->count(),
            'performance_patterns' => $this->identifyPerformancePatterns($campaigns),
            'best_practices' => $this->extractBestPractices($campaigns),
            'success_factors' => $this->analyzeSuccessFactors($campaigns),
            'failure_patterns' => $this->identifyFailurePatterns($campaigns),
            'recommendations' => $this->generateLearningBasedRecommendations($campaigns),
            'insights' => $this->generateAutomatedInsights($campaigns),
        ];
    }

    /**
     * Get decision support for a specific campaign decision
     */
    public function getDecisionSupport(AdCampaign $campaign, string $decisionType, array $options): array
    {
        $historicalContext = $this->getHistoricalContext($campaign);

        return match($decisionType) {
            'budget_adjustment' => $this->supportBudgetDecision($campaign, $options, $historicalContext),
            'pause_or_continue' => $this->supportPauseDecision($campaign, $historicalContext),
            'creative_refresh' => $this->supportCreativeRefreshDecision($campaign, $historicalContext),
            'targeting_adjustment' => $this->supportTargetingDecision($campaign, $options, $historicalContext),
            'bid_strategy' => $this->supportBidStrategyDecision($campaign, $options, $historicalContext),
            default => ['error' => 'Unknown decision type'],
        };
    }

    /**
     * Identify performance patterns across campaigns
     */
    private function identifyPerformancePatterns(mixed $campaigns): array
    {
        $patterns = [];

        // Analyze by platform
        $platformPerformance = [];
        foreach ($campaigns as $campaign) {
            $platform = $campaign->platform ?? 'unknown';
            if (!isset($platformPerformance[$platform])) {
                $platformPerformance[$platform] = [
                    'count' => 0,
                    'total_roi' => 0,
                    'total_ctr' => 0,
                ];
            }

            $metrics = $this->getCampaignAverageMetrics($campaign);
            $platformPerformance[$platform]['count']++;
            $platformPerformance[$platform]['total_roi'] += $metrics['avg_roi'];
            $platformPerformance[$platform]['total_ctr'] += $metrics['avg_ctr'];
        }

        // Calculate platform averages
        $platformPatterns = [];
        foreach ($platformPerformance as $platform => $data) {
            if ($data['count'] > 0) {
                $platformPatterns[$platform] = [
                    'campaigns' => $data['count'],
                    'avg_roi' => round($data['total_roi'] / $data['count'], 2),
                    'avg_ctr' => round($data['total_ctr'] / $data['count'], 2),
                    'performance_rating' => $this->ratePlatformPerformance($data['total_roi'] / $data['count']),
                ];
            }
        }

        $patterns['platform_performance'] = $platformPatterns;

        // Analyze by objective
        $patterns['objective_performance'] = $this->analyzeByObjective($campaigns);

        // Analyze by budget range
        $patterns['budget_performance'] = $this->analyzeByBudgetRange($campaigns);

        // Time-based patterns
        $patterns['temporal_patterns'] = $this->analyzeTemporalPatterns($campaigns);

        return $patterns;
    }

    /**
     * Extract best practices from high-performing campaigns
     */
    private function extractBestPractices(mixed $campaigns): array
    {
        $bestPractices = [];

        // Identify top performers (top 20%)
        $campaignsWithMetrics = [];
        foreach ($campaigns as $campaign) {
            $metrics = $this->getCampaignAverageMetrics($campaign);
            if ($metrics['avg_roi'] > 0) {
                $campaignsWithMetrics[] = [
                    'campaign' => $campaign,
                    'roi' => $metrics['avg_roi'],
                    'metrics' => $metrics,
                ];
            }
        }

        if (empty($campaignsWithMetrics)) {
            return ['message' => 'Insufficient data for best practices analysis'];
        }

        // Sort by ROI
        usort($campaignsWithMetrics, fn($a, $b) => $b['roi'] <=> $a['roi']);

        $topPerformersCount = max(1, (int)(count($campaignsWithMetrics) * 0.2));
        $topPerformers = array_slice($campaignsWithMetrics, 0, $topPerformersCount);

        // Analyze common characteristics
        $totalBudget = 0;
        $platformCounts = [];
        $objectiveCounts = [];
        $avgMetrics = [
            'ctr' => 0,
            'conversion_rate' => 0,
            'roi' => 0,
        ];

        foreach ($topPerformers as $performer) {
            $campaign = $performer['campaign'];
            $metrics = $performer['metrics'];

            $totalBudget += $campaign->daily_budget ?? 0;

            $platform = $campaign->platform ?? 'unknown';
            $platformCounts[$platform] = ($platformCounts[$platform] ?? 0) + 1;

            $objective = $campaign->objective ?? 'unknown';
            $objectiveCounts[$objective] = ($objectiveCounts[$objective] ?? 0) + 1;

            $avgMetrics['ctr'] += $metrics['avg_ctr'];
            $avgMetrics['conversion_rate'] += $metrics['avg_conversion_rate'];
            $avgMetrics['roi'] += $metrics['avg_roi'];
        }

        $count = count($topPerformers);
        $bestPractices['budget_range'] = [
            'min' => round($totalBudget / $count * 0.8, 2),
            'max' => round($totalBudget / $count * 1.2, 2),
            'optimal' => round($totalBudget / $count, 2),
            'recommendation' => 'Based on top performing campaigns',
        ];

        $bestPractices['preferred_platforms'] = $this->rankByCount($platformCounts);
        $bestPractices['preferred_objectives'] = $this->rankByCount($objectiveCounts);

        $bestPractices['performance_benchmarks'] = [
            'target_ctr' => round($avgMetrics['ctr'] / $count, 2),
            'target_conversion_rate' => round($avgMetrics['conversion_rate'] / $count, 2),
            'target_roi' => round($avgMetrics['roi'] / $count, 2),
        ];

        $bestPractices['key_recommendations'] = $this->generateKeyRecommendations($topPerformers);

        return $bestPractices;
    }

    /**
     * Analyze factors that contribute to success
     */
    private function analyzeSuccessFactors(mixed $campaigns): array
    {
        $successFactors = [];

        $successfulCampaigns = [];
        foreach ($campaigns as $campaign) {
            $metrics = $this->getCampaignAverageMetrics($campaign);
            if ($metrics['avg_roi'] >= 100) { // 100% ROI or higher
                $successfulCampaigns[] = [
                    'campaign' => $campaign,
                    'metrics' => $metrics,
                ];
            }
        }

        if (empty($successfulCampaigns)) {
            return ['message' => 'No campaigns with ROI >= 100% found'];
        }

        // Analyze factors
        $successFactors['count'] = count($successfulCampaigns);
        $successFactors['percentage'] = round((count($successfulCampaigns) / count($campaigns)) * 100, 1);

        // Common characteristics
        $factors = [
            'budget_discipline' => 0,
            'consistent_performance' => 0,
            'good_targeting' => 0,
            'creative_quality' => 0,
        ];

        foreach ($successfulCampaigns as $data) {
            $metrics = $data['metrics'];

            // Budget discipline: not overspending
            if ($metrics['budget_utilization'] > 70 && $metrics['budget_utilization'] < 95) {
                $factors['budget_discipline']++;
            }

            // Consistent performance: low variance
            if ($metrics['avg_ctr'] >= 2.0) {
                $factors['consistent_performance']++;
            }

            // Good targeting: high CTR
            if ($metrics['avg_ctr'] >= 3.0) {
                $factors['good_targeting']++;
            }

            // Creative quality: high conversion rate
            if ($metrics['avg_conversion_rate'] >= 3.0) {
                $factors['creative_quality']++;
            }
        }

        $totalCampaigns = count($successfulCampaigns);
        $successFactors['key_factors'] = [
            'budget_discipline' => [
                'occurrence' => round(($factors['budget_discipline'] / $totalCampaigns) * 100, 1) . '%',
                'importance' => $this->calculateImportance($factors['budget_discipline'], $totalCampaigns),
            ],
            'consistent_performance' => [
                'occurrence' => round(($factors['consistent_performance'] / $totalCampaigns) * 100, 1) . '%',
                'importance' => $this->calculateImportance($factors['consistent_performance'], $totalCampaigns),
            ],
            'good_targeting' => [
                'occurrence' => round(($factors['good_targeting'] / $totalCampaigns) * 100, 1) . '%',
                'importance' => $this->calculateImportance($factors['good_targeting'], $totalCampaigns),
            ],
            'creative_quality' => [
                'occurrence' => round(($factors['creative_quality'] / $totalCampaigns) * 100, 1) . '%',
                'importance' => $this->calculateImportance($factors['creative_quality'], $totalCampaigns),
            ],
        ];

        return $successFactors;
    }

    /**
     * Identify patterns in failed/poor-performing campaigns
     */
    private function identifyFailurePatterns(mixed $campaigns): array
    {
        $failurePatterns = [];

        $failedCampaigns = [];
        foreach ($campaigns as $campaign) {
            $metrics = $this->getCampaignAverageMetrics($campaign);
            if ($metrics['avg_roi'] < 0 || $metrics['avg_conversion_rate'] < 1.0) {
                $failedCampaigns[] = [
                    'campaign' => $campaign,
                    'metrics' => $metrics,
                ];
            }
        }

        if (empty($failedCampaigns)) {
            return ['message' => 'No underperforming campaigns found'];
        }

        $failurePatterns['count'] = count($failedCampaigns);
        $failurePatterns['percentage'] = round((count($failedCampaigns) / count($campaigns)) * 100, 1);

        // Common failure reasons
        $reasons = [
            'poor_targeting' => 0,      // Low CTR
            'weak_creative' => 0,       // Low conversion rate despite clicks
            'high_cpc' => 0,           // Cost too high
            'budget_issues' => 0,       // Budget too low or too high
        ];

        foreach ($failedCampaigns as $data) {
            $metrics = $data['metrics'];

            if ($metrics['avg_ctr'] < 1.0) $reasons['poor_targeting']++;
            if ($metrics['avg_ctr'] >= 1.0 && $metrics['avg_conversion_rate'] < 1.0) $reasons['weak_creative']++;
            if ($metrics['avg_cpc'] > 2.0) $reasons['high_cpc']++;
            if ($metrics['budget_utilization'] < 30 || $metrics['budget_utilization'] > 100) $reasons['budget_issues']++;
        }

        $failurePatterns['common_reasons'] = $this->rankByCount($reasons, true);
        $failurePatterns['mitigation_strategies'] = $this->generateMitigationStrategies($reasons);

        return $failurePatterns;
    }

    /**
     * Generate recommendations based on learned patterns
     */
    private function generateLearningBasedRecommendations(mixed $campaigns): array
    {
        $recommendations = [];

        $avgPerformance = $this->calculateAveragePerformance($campaigns);

        // Platform recommendations
        $platformPerformance = [];
        foreach ($campaigns as $campaign) {
            $platform = $campaign->platform ?? 'unknown';
            $metrics = $this->getCampaignAverageMetrics($campaign);

            if (!isset($platformPerformance[$platform])) {
                $platformPerformance[$platform] = ['roi' => 0, 'count' => 0];
            }
            $platformPerformance[$platform]['roi'] += $metrics['avg_roi'];
            $platformPerformance[$platform]['count']++;
        }

        $bestPlatform = null;
        $bestROI = -999999;
        foreach ($platformPerformance as $platform => $data) {
            $avgROI = $data['roi'] / $data['count'];
            if ($avgROI > $bestROI && $data['count'] >= 2) {
                $bestROI = $avgROI;
                $bestPlatform = $platform;
            }
        }

        if ($bestPlatform) {
            $recommendations[] = [
                'type' => 'platform_focus',
                'priority' => 'high',
                'recommendation' => "Focus more budget on {$bestPlatform}",
                'reason' => "Highest average ROI: " . round($bestROI, 2) . "%",
                'confidence' => $this->calculateConfidenceLevel($platformPerformance[$bestPlatform]['count']),
            ];
        }

        // Budget optimization recommendation
        if ($avgPerformance['avg_roi'] > 50) {
            $recommendations[] = [
                'type' => 'scale_budget',
                'priority' => 'high',
                'recommendation' => 'Consider increasing overall marketing budget',
                'reason' => 'Strong positive ROI across campaigns (' . round($avgPerformance['avg_roi'], 2) . '%)',
                'expected_impact' => 'Potential to scale revenue proportionally',
            ];
        }

        // Targeting recommendations
        if ($avgPerformance['avg_ctr'] < 2.0) {
            $recommendations[] = [
                'type' => 'improve_targeting',
                'priority' => 'high',
                'recommendation' => 'Refine audience targeting',
                'reason' => 'Below-average CTR (' . round($avgPerformance['avg_ctr'], 2) . '%)',
                'action_items' => [
                    'Analyze top performing audience segments',
                    'Narrow targeting parameters',
                    'Test new audience combinations',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Generate automated insights from data
     */
    private function generateAutomatedInsights(mixed $campaigns): array
    {
        $insights = [];

        if ($campaigns->isEmpty()) {
            return ['message' => 'No campaigns to analyze'];
        }

        // Performance distribution insight
        $roi_distribution = ['negative' => 0, 'low' => 0, 'medium' => 0, 'high' => 0];
        foreach ($campaigns as $campaign) {
            $metrics = $this->getCampaignAverageMetrics($campaign);
            $roi = $metrics['avg_roi'];

            if ($roi < 0) $roi_distribution['negative']++;
            elseif ($roi < 50) $roi_distribution['low']++;
            elseif ($roi < 100) $roi_distribution['medium']++;
            else $roi_distribution['high']++;
        }

        $insights[] = [
            'category' => 'performance_distribution',
            'insight' => 'Campaign ROI Distribution',
            'data' => $roi_distribution,
            'interpretation' => $this->interpretROIDistribution($roi_distribution, $campaigns->count()),
        ];

        // Opportunity identification
        $opportunities = $this->identifyOpportunities($campaigns);
        if (!empty($opportunities)) {
            $insights[] = [
                'category' => 'opportunities',
                'insight' => 'Growth Opportunities Identified',
                'data' => $opportunities,
                'actionable' => true,
            ];
        }

        // Risk alerts
        $risks = $this->identifyRisks($campaigns);
        if (!empty($risks)) {
            $insights[] = [
                'category' => 'risks',
                'insight' => 'Risk Alerts',
                'data' => $risks,
                'priority' => 'high',
            ];
        }

        return $insights;
    }

    /**
     * Support budget adjustment decision
     */
    private function supportBudgetDecision(AdCampaign $campaign, array $options, array $context): array
    {
        $currentMetrics = $this->getCampaignAverageMetrics($campaign);
        $currentBudget = $campaign->daily_budget ?? 0;
        $proposedBudget = $options['proposed_budget'] ?? $currentBudget;

        $change = (($proposedBudget - $currentBudget) / $currentBudget) * 100;

        $recommendation = [
            'decision' => 'budget_adjustment',
            'current_budget' => $currentBudget,
            'proposed_budget' => $proposedBudget,
            'change_percentage' => round($change, 2),
            'analysis' => [],
        ];

        // Analyze based on current performance
        if ($currentMetrics['avg_roi'] >= 100) {
            $recommendation['recommendation'] = 'APPROVE';
            $recommendation['confidence'] = 'high';
            $recommendation['analysis'][] = 'Campaign showing strong ROI - good candidate for budget increase';
        } elseif ($currentMetrics['avg_roi'] >= 50) {
            $recommendation['recommendation'] = 'APPROVE_WITH_MONITORING';
            $recommendation['confidence'] = 'medium';
            $recommendation['analysis'][] = 'Positive ROI - increase budget but monitor closely';
        } else {
            $recommendation['recommendation'] = 'REJECT';
            $recommendation['confidence'] = 'high';
            $recommendation['analysis'][] = 'Poor ROI - optimize campaign before increasing budget';
        }

        // Historical context
        if (!empty($context['similar_campaigns'])) {
            $recommendation['analysis'][] = 'Similar campaigns historically performed with ' .
                round($context['avg_similar_roi'], 2) . '% ROI';
        }

        return $recommendation;
    }

    /**
     * Support pause or continue decision
     */
    private function supportPauseDecision(AdCampaign $campaign, array $context): array
    {
        $metrics = $this->getCampaignAverageMetrics($campaign);

        $decision = [
            'decision' => 'pause_or_continue',
            'current_performance' => $metrics,
        ];

        $score = 0;

        // Performance scoring
        if ($metrics['avg_roi'] >= 50) $score += 3;
        elseif ($metrics['avg_roi'] >= 0) $score += 1;
        else $score -= 2;

        if ($metrics['avg_ctr'] >= 2.0) $score += 2;
        elseif ($metrics['avg_ctr'] >= 1.0) $score += 1;
        else $score -= 1;

        if ($metrics['avg_conversion_rate'] >= 2.0) $score += 2;
        elseif ($metrics['avg_conversion_rate'] >= 1.0) $score += 1;

        if ($score >= 5) {
            $decision['recommendation'] = 'CONTINUE';
            $decision['confidence'] = 'high';
            $decision['reason'] = 'Campaign performing well across key metrics';
        } elseif ($score >= 2) {
            $decision['recommendation'] = 'CONTINUE_WITH_OPTIMIZATION';
            $decision['confidence'] = 'medium';
            $decision['reason'] = 'Moderate performance - optimize while running';
        } else {
            $decision['recommendation'] = 'PAUSE';
            $decision['confidence'] = 'high';
            $decision['reason'] = 'Poor performance - pause and optimize before restarting';
        }

        $decision['performance_score'] = $score;

        return $decision;
    }

    /**
     * Support creative refresh decision
     */
    private function supportCreativeRefreshDecision(AdCampaign $campaign, array $context): array
    {
        $metrics = $this->getCampaignAverageMetrics($campaign);

        $decision = [
            'decision' => 'creative_refresh',
            'indicators' => [],
        ];

        $refreshScore = 0;

        // Low CTR suggests creative fatigue
        if ($metrics['avg_ctr'] < 1.5) {
            $decision['indicators'][] = 'Low CTR indicates creative may not be engaging';
            $refreshScore += 3;
        }

        // Poor conversion despite clicks suggests creative-landing page mismatch
        if ($metrics['avg_ctr'] >= 1.5 && $metrics['avg_conversion_rate'] < 2.0) {
            $decision['indicators'][] = 'Clicks present but low conversions - creative may set wrong expectations';
            $refreshScore += 2;
        }

        // Campaign age
        $campaignAge = isset($campaign->created_at) ? Carbon::parse($campaign->created_at)->diffInDays(now()) : 0;
        if ($campaignAge > 60) {
            $decision['indicators'][] = 'Campaign running for ' . $campaignAge . ' days - creative refresh recommended';
            $refreshScore += 2;
        }

        if ($refreshScore >= 4) {
            $decision['recommendation'] = 'REFRESH_CREATIVE';
            $decision['priority'] = 'high';
            $decision['urgency'] = 'Immediate action recommended';
        } elseif ($refreshScore >= 2) {
            $decision['recommendation'] = 'CONSIDER_REFRESH';
            $decision['priority'] = 'medium';
            $decision['urgency'] = 'Plan refresh within 2 weeks';
        } else {
            $decision['recommendation'] = 'NO_REFRESH_NEEDED';
            $decision['priority'] = 'low';
            $decision['urgency'] = 'Current creative performing adequately';
        }

        return $decision;
    }

    /**
     * Support targeting adjustment decision
     */
    private function supportTargetingDecision(AdCampaign $campaign, array $options, array $context): array
    {
        $metrics = $this->getCampaignAverageMetrics($campaign);

        return [
            'decision' => 'targeting_adjustment',
            'current_ctr' => $metrics['avg_ctr'],
            'recommendation' => $metrics['avg_ctr'] < 2.0 ? 'ADJUST_TARGETING' : 'MAINTAIN_TARGETING',
            'suggested_actions' => $metrics['avg_ctr'] < 2.0 ? [
                'Narrow audience to more relevant segments',
                'Test different demographic parameters',
                'Analyze top performing segments and double down',
            ] : [
                'Current targeting performing well',
                'Consider slight expansion to scale',
            ],
        ];
    }

    /**
     * Support bid strategy decision
     */
    private function supportBidStrategyDecision(AdCampaign $campaign, array $options, array $context): array
    {
        $metrics = $this->getCampaignAverageMetrics($campaign);

        $currentStrategy = $campaign->bid_strategy ?? 'unknown';
        $proposedStrategy = $options['proposed_strategy'] ?? $currentStrategy;

        return [
            'decision' => 'bid_strategy',
            'current_strategy' => $currentStrategy,
            'proposed_strategy' => $proposedStrategy,
            'current_cpc' => $metrics['avg_cpc'],
            'recommendation' => $this->recommendBidStrategy($metrics, $proposedStrategy),
        ];
    }

    /**
     * Get historical context for a campaign
     */
    private function getHistoricalContext(AdCampaign $campaign): array
    {
        // Find similar campaigns
        $similarCampaigns = AdCampaign::where('org_id', $campaign->org_id)
            ->where('platform', $campaign->platform)
            ->where('objective', $campaign->objective)
            ->where('campaign_id', '!=', $campaign->campaign_id)
            ->limit(10)
            ->get();

        $totalROI = 0;
        $count = 0;

        foreach ($similarCampaigns as $similar) {
            $metrics = $this->getCampaignAverageMetrics($similar);
            $totalROI += $metrics['avg_roi'];
            $count++;
        }

        return [
            'similar_campaigns_count' => $count,
            'similar_campaigns' => $similarCampaigns->pluck('campaign_id'),
            'avg_similar_roi' => $count > 0 ? $totalROI / $count : 0,
        ];
    }

    /**
     * Helper: Get campaign average metrics
     */
    private function getCampaignAverageMetrics(AdCampaign $campaign): array
    {
        $metrics = DB::table('cmis.ad_metrics')
            ->where('campaign_id', $campaign->campaign_id)
            ->where('date', '>=', Carbon::now()->subDays(30))
            ->select([
                DB::raw('AVG(ctr) as avg_ctr'),
                DB::raw('AVG(cpc) as avg_cpc'),
                DB::raw('AVG(conversion_rate) as avg_conversion_rate'),
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(revenue) as total_revenue'),
                DB::raw('SUM(conversions) as total_conversions'),
            ])
            ->first();

        $avgCTR = $metrics->avg_ctr ?? 0;
        $avgCPC = $metrics->avg_cpc ?? 0;
        $avgConversionRate = $metrics->avg_conversion_rate ?? 0;
        $totalSpend = $metrics->total_spend ?? 0;
        $totalRevenue = $metrics->total_revenue ?? 0;

        $roi = $totalSpend > 0 ? (($totalRevenue - $totalSpend) / $totalSpend) * 100 : 0;
        $budgetUtilization = ($campaign->daily_budget ?? 0) > 0
            ? ($totalSpend / 30 / $campaign->daily_budget) * 100
            : 0;

        return [
            'avg_ctr' => round($avgCTR, 2),
            'avg_cpc' => round($avgCPC, 2),
            'avg_conversion_rate' => round($avgConversionRate, 2),
            'avg_roi' => round($roi, 2),
            'budget_utilization' => round($budgetUtilization, 2),
            'total_conversions' => $metrics->total_conversions ?? 0,
        ];
    }

    /**
     * Helper methods
     */
    private function ratePlatformPerformance(float $avgROI): string
    {
        if ($avgROI >= 100) return 'excellent';
        if ($avgROI >= 50) return 'good';
        if ($avgROI >= 0) return 'fair';
        return 'poor';
    }

    private function analyzeByObjective(mixed $campaigns): array
    {
        $objectives = [];
        foreach ($campaigns as $campaign) {
            $objective = $campaign->objective ?? 'unknown';
            if (!isset($objectives[$objective])) {
                $objectives[$objective] = ['count' => 0, 'total_roi' => 0];
            }
            $metrics = $this->getCampaignAverageMetrics($campaign);
            $objectives[$objective]['count']++;
            $objectives[$objective]['total_roi'] += $metrics['avg_roi'];
        }

        $result = [];
        foreach ($objectives as $objective => $data) {
            $result[$objective] = [
                'campaigns' => $data['count'],
                'avg_roi' => round($data['total_roi'] / $data['count'], 2),
            ];
        }

        return $result;
    }

    private function analyzeByBudgetRange(mixed $campaigns): array
    {
        $ranges = [
            'low' => ['min' => 0, 'max' => 50, 'count' => 0, 'total_roi' => 0],
            'medium' => ['min' => 50, 'max' => 200, 'count' => 0, 'total_roi' => 0],
            'high' => ['min' => 200, 'max' => PHP_INT_MAX, 'count' => 0, 'total_roi' => 0],
        ];

        foreach ($campaigns as $campaign) {
            $budget = $campaign->daily_budget ?? 0;
            $metrics = $this->getCampaignAverageMetrics($campaign);

            foreach ($ranges as $name => &$range) {
                if ($budget >= $range['min'] && $budget < $range['max']) {
                    $range['count']++;
                    $range['total_roi'] += $metrics['avg_roi'];
                    break;
                }
            }
        }

        $result = [];
        foreach ($ranges as $name => $range) {
            if ($range['count'] > 0) {
                $result[$name] = [
                    'campaigns' => $range['count'],
                    'avg_roi' => round($range['total_roi'] / $range['count'], 2),
                ];
            }
        }

        return $result;
    }

    private function analyzeTemporalPatterns(mixed $campaigns): array
    {
        return [
            'message' => 'Temporal pattern analysis requires time-series data',
            'recommendation' => 'Collect more historical data for seasonal analysis',
        ];
    }

    private function rankByCount(array $counts, bool $descending = true): array
    {
        arsort($counts);
        if (!$descending) {
            $counts = array_reverse($counts, true);
        }
        return $counts;
    }

    private function generateKeyRecommendations(array $topPerformers): array
    {
        return [
            'Replicate successful campaign structures',
            'Maintain consistent budget allocation based on top performers',
            'Apply proven creative strategies from high-ROI campaigns',
            'Focus on platforms and objectives showing best results',
        ];
    }

    private function calculateImportance(int $occurrence, int $total): string
    {
        $percentage = ($occurrence / $total) * 100;
        if ($percentage >= 75) return 'critical';
        if ($percentage >= 50) return 'high';
        if ($percentage >= 25) return 'medium';
        return 'low';
    }

    private function generateMitigationStrategies(array $reasons): array
    {
        $strategies = [];

        foreach ($reasons as $reason => $count) {
            if ($count > 0) {
                $strategies[$reason] = match($reason) {
                    'poor_targeting' => 'Refine audience parameters, use lookalike audiences from converters',
                    'weak_creative' => 'A/B test new ad creative, improve messaging and visuals',
                    'high_cpc' => 'Optimize bid strategy, improve quality score, refine targeting',
                    'budget_issues' => 'Adjust daily budget to optimal range based on campaign objectives',
                    default => 'Review and optimize',
                };
            }
        }

        return $strategies;
    }

    private function calculateAveragePerformance(mixed $campaigns): array
    {
        $totalROI = 0;
        $totalCTR = 0;
        $count = 0;

        foreach ($campaigns as $campaign) {
            $metrics = $this->getCampaignAverageMetrics($campaign);
            $totalROI += $metrics['avg_roi'];
            $totalCTR += $metrics['avg_ctr'];
            $count++;
        }

        return [
            'avg_roi' => $count > 0 ? $totalROI / $count : 0,
            'avg_ctr' => $count > 0 ? $totalCTR / $count : 0,
        ];
    }

    private function calculateConfidenceLevel(int $sampleSize): string
    {
        if ($sampleSize >= 10) return 'high';
        if ($sampleSize >= 5) return 'medium';
        return 'low';
    }

    private function interpretROIDistribution(array $distribution, int $total): string
    {
        $highPercentage = ($distribution['high'] / $total) * 100;
        $negativePercentage = ($distribution['negative'] / $total) * 100;

        if ($highPercentage >= 30) {
            return 'Strong portfolio with ' . round($highPercentage, 1) . '% high-performing campaigns';
        } elseif ($negativePercentage >= 30) {
            return 'Portfolio needs optimization - ' . round($negativePercentage, 1) . '% campaigns with negative ROI';
        } else {
            return 'Mixed portfolio with opportunities for improvement';
        }
    }

    private function identifyOpportunities(mixed $campaigns): array
    {
        $opportunities = [];

        foreach ($campaigns as $campaign) {
            $metrics = $this->getCampaignAverageMetrics($campaign);

            // High CTR but low conversions = landing page opportunity
            if ($metrics['avg_ctr'] >= 3.0 && $metrics['avg_conversion_rate'] < 2.0) {
                $opportunities[] = [
                    'campaign_id' => $campaign->campaign_id,
                    'type' => 'landing_page_optimization',
                    'message' => 'High traffic but low conversions - optimize landing page',
                ];
            }

            // Good ROI but low budget utilization = scale opportunity
            if ($metrics['avg_roi'] >= 100 && $metrics['budget_utilization'] < 80) {
                $opportunities[] = [
                    'campaign_id' => $campaign->campaign_id,
                    'type' => 'scale_budget',
                    'message' => 'Excellent ROI with room to increase budget',
                ];
            }
        }

        return $opportunities;
    }

    private function identifyRisks(mixed $campaigns): array
    {
        $risks = [];

        foreach ($campaigns as $campaign) {
            $metrics = $this->getCampaignAverageMetrics($campaign);

            // Negative ROI
            if ($metrics['avg_roi'] < -20) {
                $risks[] = [
                    'campaign_id' => $campaign->campaign_id,
                    'type' => 'high_loss',
                    'severity' => 'critical',
                    'message' => 'Campaign losing money - immediate action needed',
                ];
            }

            // Budget overrun
            if ($metrics['budget_utilization'] > 110) {
                $risks[] = [
                    'campaign_id' => $campaign->campaign_id,
                    'type' => 'budget_overrun',
                    'severity' => 'high',
                    'message' => 'Campaign exceeding budget - review spend controls',
                ];
            }
        }

        return $risks;
    }

    private function recommendBidStrategy(array $metrics, string $proposedStrategy): array
    {
        if ($metrics['avg_conversion_rate'] >= 3.0) {
            return [
                'recommended_strategy' => 'target_cost' or 'maximize_conversions',
                'reason' => 'High conversion rate - optimize for conversions',
                'approval' => $proposedStrategy === 'target_cost' ? 'APPROVE' : 'CONSIDER',
            ];
        } elseif ($metrics['avg_ctr'] < 1.5) {
            return [
                'recommended_strategy' => 'manual_cpc',
                'reason' => 'Low CTR - manual control needed to optimize',
                'approval' => $proposedStrategy === 'manual_cpc' ? 'APPROVE' : 'REJECT',
            ];
        }

        return [
            'recommended_strategy' => 'maintain_current',
            'reason' => 'Current performance is stable',
            'approval' => 'APPROVE',
        ];
    }
}
