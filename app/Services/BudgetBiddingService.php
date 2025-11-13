<?php

namespace App\Services;

use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdSet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for budget and bidding management
 * Implements Sprint 4.4: Budget & Bidding
 *
 * Features:
 * - Budget allocation and management
 * - Bid strategy optimization
 * - Spend tracking and pacing
 * - ROI calculation
 * - Budget recommendations
 * - Automated budget optimization
 */
class BudgetBiddingService
{
    /**
     * Update campaign budget
     *
     * @param string $campaignId
     * @param array $budgetData
     * @return array
     */
    public function updateCampaignBudget(string $campaignId, array $budgetData): array
    {
        try {
            DB::beginTransaction();

            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            // Validate budget type change
            if (isset($budgetData['budget_type']) && $budgetData['budget_type'] !== $campaign->budget_type) {
                $this->validateBudgetTypeChange($campaign, $budgetData['budget_type']);
            }

            // Update budget
            $campaign->update([
                'budget_type' => $budgetData['budget_type'] ?? $campaign->budget_type,
                'daily_budget' => $budgetData['daily_budget'] ?? $campaign->daily_budget,
                'lifetime_budget' => $budgetData['lifetime_budget'] ?? $campaign->lifetime_budget,
            ]);

            DB::commit();

            Log::info('Campaign budget updated', [
                'campaign_id' => $campaignId,
                'budget_type' => $campaign->budget_type,
                'daily_budget' => $campaign->daily_budget,
                'lifetime_budget' => $campaign->lifetime_budget
            ]);

            return [
                'success' => true,
                'data' => $campaign->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update campaign budget', [
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
     * Update bid strategy
     *
     * @param string $campaignId
     * @param array $bidData
     * @return array
     */
    public function updateBidStrategy(string $campaignId, array $bidData): array
    {
        try {
            DB::beginTransaction();

            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            // Update bid strategy
            $campaign->update([
                'bid_strategy' => $bidData['bid_strategy'] ?? $campaign->bid_strategy,
            ]);

            // Store bid amount in metadata if provided
            if (isset($bidData['bid_amount'])) {
                $metadata = $campaign->metadata ?? [];
                $metadata['bid_amount'] = $bidData['bid_amount'];
                $metadata['bid_updated_at'] = now()->toIso8601String();
                $campaign->metadata = $metadata;
                $campaign->save();
            }

            DB::commit();

            Log::info('Bid strategy updated', [
                'campaign_id' => $campaignId,
                'bid_strategy' => $campaign->bid_strategy
            ]);

            return [
                'success' => true,
                'data' => $campaign->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update bid strategy', [
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
     * Get spend tracking and pacing
     *
     * @param string $campaignId
     * @return array
     */
    public function getSpendTracking(string $campaignId): array
    {
        try {
            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            // Get spend metrics
            $spendMetrics = $this->calculateSpendMetrics($campaignId);

            // Calculate pacing
            $pacing = $this->calculateBudgetPacing($campaign, $spendMetrics);

            // Get daily spend history
            $dailySpend = $this->getDailySpendHistory($campaignId, 30);

            return [
                'success' => true,
                'data' => [
                    'campaign' => [
                        'campaign_id' => $campaign->ad_campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                        'budget_type' => $campaign->budget_type,
                        'daily_budget' => $campaign->daily_budget,
                        'lifetime_budget' => $campaign->lifetime_budget,
                        'start_time' => $campaign->start_time,
                        'end_time' => $campaign->end_time
                    ],
                    'spend' => $spendMetrics,
                    'pacing' => $pacing,
                    'daily_history' => $dailySpend
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get spend tracking', [
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
     * Calculate ROI and performance metrics
     *
     * @param string $campaignId
     * @return array
     */
    public function calculateROI(string $campaignId): array
    {
        try {
            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            // Get metrics from ad_metrics table
            $metrics = DB::table('cmis_ads.ad_metrics')
                ->where('entity_id', $campaignId)
                ->where('entity_type', 'campaign')
                ->selectRaw('SUM(spend) as total_spend')
                ->selectRaw('SUM(revenue) as total_revenue')
                ->selectRaw('SUM(conversions) as total_conversions')
                ->selectRaw('SUM(clicks) as total_clicks')
                ->selectRaw('SUM(impressions) as total_impressions')
                ->first();

            $totalSpend = $metrics->total_spend ?? 0;
            $totalRevenue = $metrics->total_revenue ?? 0;
            $totalConversions = $metrics->total_conversions ?? 0;

            // Calculate ROI metrics
            $roi = $totalSpend > 0 ? (($totalRevenue - $totalSpend) / $totalSpend) * 100 : 0;
            $roas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;
            $cpa = $totalConversions > 0 ? $totalSpend / $totalConversions : 0;
            $cpc = $metrics->total_clicks > 0 ? $totalSpend / $metrics->total_clicks : 0;
            $cpm = $metrics->total_impressions > 0 ? ($totalSpend / $metrics->total_impressions) * 1000 : 0;

            return [
                'success' => true,
                'data' => [
                    'campaign_id' => $campaignId,
                    'total_spend' => round($totalSpend, 2),
                    'total_revenue' => round($totalRevenue, 2),
                    'total_conversions' => $totalConversions,
                    'roi' => round($roi, 2),
                    'roas' => round($roas, 2),
                    'cpa' => round($cpa, 2),
                    'cpc' => round($cpc, 2),
                    'cpm' => round($cpm, 2),
                    'profit' => round($totalRevenue - $totalSpend, 2),
                    'performance_rating' => $this->getPerformanceRating($roi, $roas)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate ROI', [
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
     * Get budget recommendations
     *
     * @param string $campaignId
     * @return array
     */
    public function getBudgetRecommendations(string $campaignId): array
    {
        try {
            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)->first();
            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            $roi = $this->calculateROI($campaignId);
            if (!$roi['success']) {
                throw new \Exception('Failed to calculate ROI for recommendations');
            }

            $recommendations = [];

            // Analyze performance and provide recommendations
            $roiValue = $roi['data']['roi'];
            $roasValue = $roi['data']['roas'];
            $currentBudget = $campaign->budget_type === 'daily'
                ? $campaign->daily_budget
                : $campaign->lifetime_budget;

            // Budget increase recommendation
            if ($roiValue > 50) {
                $recommendations[] = [
                    'type' => 'budget_increase',
                    'priority' => 'high',
                    'title' => 'Increase Budget for High-Performing Campaign',
                    'message' => sprintf(
                        'Campaign has %+.1f%% ROI. Consider increasing budget by 20-50%% to maximize returns.',
                        $roiValue
                    ),
                    'suggested_budget' => round($currentBudget * 1.3, 2),
                    'expected_impact' => 'Higher conversions and revenue'
                ];
            }

            // Budget decrease recommendation
            if ($roiValue < -20) {
                $recommendations[] = [
                    'type' => 'budget_decrease',
                    'priority' => 'high',
                    'title' => 'Reduce Budget for Underperforming Campaign',
                    'message' => sprintf(
                        'Campaign has %+.1f%% ROI. Consider reducing budget by 30-50%% or pausing.',
                        $roiValue
                    ),
                    'suggested_budget' => round($currentBudget * 0.6, 2),
                    'expected_impact' => 'Reduced losses'
                ];
            }

            // Bid strategy recommendation
            if ($roasValue < 1 && $campaign->bid_strategy === 'lowest_cost') {
                $recommendations[] = [
                    'type' => 'bid_strategy',
                    'priority' => 'medium',
                    'title' => 'Switch to Target Cost Bidding',
                    'message' => 'ROAS below 1.0. Switch to target cost bidding for better cost control.',
                    'suggested_bid_strategy' => 'target_cost',
                    'expected_impact' => 'Better cost control and efficiency'
                ];
            }

            // Pacing recommendation
            $spendTracking = $this->getSpendTracking($campaignId);
            if ($spendTracking['success']) {
                $pacingStatus = $spendTracking['data']['pacing']['status'] ?? 'on_pace';

                if ($pacingStatus === 'overspending') {
                    $recommendations[] = [
                        'type' => 'pacing',
                        'priority' => 'high',
                        'title' => 'Budget Overspending Detected',
                        'message' => 'Campaign is spending faster than planned. Consider adjusting bid strategy or budget.',
                        'expected_impact' => 'Better budget utilization'
                    ];
                } elseif ($pacingStatus === 'underspending') {
                    $recommendations[] = [
                        'type' => 'pacing',
                        'priority' => 'medium',
                        'title' => 'Budget Underspending Detected',
                        'message' => 'Campaign is not spending enough. Consider increasing bids or expanding targeting.',
                        'expected_impact' => 'Improved budget utilization'
                    ];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'campaign_id' => $campaignId,
                    'current_metrics' => $roi['data'],
                    'recommendations' => $recommendations,
                    'recommendation_count' => count($recommendations)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get budget recommendations', [
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
     * Optimize budget allocation across campaigns
     *
     * @param string $adAccountId
     * @param array $options
     * @return array
     */
    public function optimizeBudgetAllocation(string $adAccountId, array $options = []): array
    {
        try {
            // Get all active campaigns for account
            $campaigns = AdCampaign::where('ad_account_id', $adAccountId)
                ->where('campaign_status', 'active')
                ->get();

            if ($campaigns->isEmpty()) {
                throw new \Exception('No active campaigns found');
            }

            $totalBudget = $options['total_budget'] ?? 0;
            $optimizationGoal = $options['goal'] ?? 'roi'; // roi, conversions, reach

            $campaignPerformance = [];

            // Calculate performance for each campaign
            foreach ($campaigns as $campaign) {
                $roi = $this->calculateROI($campaign->ad_campaign_id);
                if ($roi['success']) {
                    $campaignPerformance[] = [
                        'campaign_id' => $campaign->ad_campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                        'current_budget' => $campaign->budget_type === 'daily'
                            ? $campaign->daily_budget
                            : $campaign->lifetime_budget,
                        'roi' => $roi['data']['roi'],
                        'roas' => $roi['data']['roas'],
                        'conversions' => $roi['data']['total_conversions'],
                        'performance_score' => $this->calculatePerformanceScore($roi['data'], $optimizationGoal)
                    ];
                }
            }

            // Sort by performance score
            usort($campaignPerformance, fn($a, $b) => $b['performance_score'] <=> $a['performance_score']);

            // Allocate budget based on performance
            $allocations = $this->allocateBudgetByPerformance($campaignPerformance, $totalBudget);

            return [
                'success' => true,
                'data' => [
                    'total_budget' => $totalBudget,
                    'optimization_goal' => $optimizationGoal,
                    'campaign_count' => count($campaignPerformance),
                    'allocations' => $allocations,
                    'note' => 'Review allocations before applying changes'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to optimize budget allocation', [
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate spend metrics for campaign
     *
     * @param string $campaignId
     * @return array
     */
    protected function calculateSpendMetrics(string $campaignId): array
    {
        $metrics = DB::table('cmis_ads.ad_metrics')
            ->where('entity_id', $campaignId)
            ->where('entity_type', 'campaign')
            ->selectRaw('SUM(spend) as total_spend')
            ->selectRaw('AVG(spend) as avg_daily_spend')
            ->selectRaw('MAX(spend) as max_daily_spend')
            ->selectRaw('MIN(spend) as min_daily_spend')
            ->selectRaw('COUNT(DISTINCT date) as days_active')
            ->first();

        return [
            'total_spend' => round($metrics->total_spend ?? 0, 2),
            'avg_daily_spend' => round($metrics->avg_daily_spend ?? 0, 2),
            'max_daily_spend' => round($metrics->max_daily_spend ?? 0, 2),
            'min_daily_spend' => round($metrics->min_daily_spend ?? 0, 2),
            'days_active' => $metrics->days_active ?? 0
        ];
    }

    /**
     * Calculate budget pacing
     *
     * @param AdCampaign $campaign
     * @param array $spendMetrics
     * @return array
     */
    protected function calculateBudgetPacing(AdCampaign $campaign, array $spendMetrics): array
    {
        if ($campaign->budget_type === 'daily') {
            $budgetPerDay = $campaign->daily_budget;
            $avgSpendPerDay = $spendMetrics['avg_daily_spend'];

            $pacingPercent = $budgetPerDay > 0
                ? ($avgSpendPerDay / $budgetPerDay) * 100
                : 0;

            $status = 'on_pace';
            if ($pacingPercent > 110) {
                $status = 'overspending';
            } elseif ($pacingPercent < 90) {
                $status = 'underspending';
            }

            return [
                'status' => $status,
                'pacing_percent' => round($pacingPercent, 1),
                'daily_budget' => $budgetPerDay,
                'avg_daily_spend' => $avgSpendPerDay,
                'message' => $this->getPacingMessage($status, $pacingPercent)
            ];
        } else {
            // Lifetime budget pacing
            $lifetimeBudget = $campaign->lifetime_budget;
            $totalSpend = $spendMetrics['total_spend'];

            if (!$campaign->start_time || !$campaign->end_time) {
                return [
                    'status' => 'unknown',
                    'message' => 'Campaign dates not set'
                ];
            }

            $totalDays = Carbon::parse($campaign->start_time)->diffInDays(Carbon::parse($campaign->end_time)) + 1;
            $daysElapsed = Carbon::parse($campaign->start_time)->diffInDays(Carbon::now()) + 1;
            $expectedSpend = ($lifetimeBudget / $totalDays) * $daysElapsed;

            $pacingPercent = $expectedSpend > 0
                ? ($totalSpend / $expectedSpend) * 100
                : 0;

            $status = 'on_pace';
            if ($pacingPercent > 110) {
                $status = 'overspending';
            } elseif ($pacingPercent < 90) {
                $status = 'underspending';
            }

            return [
                'status' => $status,
                'pacing_percent' => round($pacingPercent, 1),
                'lifetime_budget' => $lifetimeBudget,
                'total_spend' => $totalSpend,
                'expected_spend' => round($expectedSpend, 2),
                'remaining_budget' => round($lifetimeBudget - $totalSpend, 2),
                'days_elapsed' => $daysElapsed,
                'total_days' => $totalDays,
                'message' => $this->getPacingMessage($status, $pacingPercent)
            ];
        }
    }

    /**
     * Get daily spend history
     *
     * @param string $campaignId
     * @param int $days
     * @return array
     */
    protected function getDailySpendHistory(string $campaignId, int $days): array
    {
        $history = DB::table('cmis_ads.ad_metrics')
            ->where('entity_id', $campaignId)
            ->where('entity_type', 'campaign')
            ->where('date', '>=', Carbon::now()->subDays($days))
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($metric) {
                return [
                    'date' => $metric->date,
                    'spend' => round($metric->spend ?? 0, 2),
                    'impressions' => $metric->impressions ?? 0,
                    'clicks' => $metric->clicks ?? 0,
                    'conversions' => $metric->conversions ?? 0
                ];
            });

        return $history->toArray();
    }

    /**
     * Validate budget type change
     *
     * @param AdCampaign $campaign
     * @param string $newBudgetType
     * @return void
     * @throws \Exception
     */
    protected function validateBudgetTypeChange(AdCampaign $campaign, string $newBudgetType): void
    {
        if ($campaign->campaign_status === 'active') {
            throw new \Exception('Cannot change budget type for active campaign. Pause campaign first.');
        }
    }

    /**
     * Get performance rating
     *
     * @param float $roi
     * @param float $roas
     * @return string
     */
    protected function getPerformanceRating(float $roi, float $roas): string
    {
        if ($roi > 100 || $roas > 3) return 'excellent';
        if ($roi > 50 || $roas > 2) return 'good';
        if ($roi > 0 || $roas > 1) return 'fair';
        return 'poor';
    }

    /**
     * Get pacing message
     *
     * @param string $status
     * @param float $pacingPercent
     * @return string
     */
    protected function getPacingMessage(string $status, float $pacingPercent): string
    {
        switch ($status) {
            case 'overspending':
                return sprintf('Campaign is overspending at %.1f%% of budget. Consider reducing bids or budget.', $pacingPercent);
            case 'underspending':
                return sprintf('Campaign is underspending at %.1f%% of budget. Consider increasing bids or budget.', $pacingPercent);
            default:
                return sprintf('Campaign is on pace at %.1f%% of budget.', $pacingPercent);
        }
    }

    /**
     * Calculate performance score
     *
     * @param array $metrics
     * @param string $goal
     * @return float
     */
    protected function calculatePerformanceScore(array $metrics, string $goal): float
    {
        switch ($goal) {
            case 'roi':
                return $metrics['roi'];
            case 'roas':
                return $metrics['roas'] * 100;
            case 'conversions':
                return $metrics['total_conversions'];
            default:
                return $metrics['roi'];
        }
    }

    /**
     * Allocate budget by performance
     *
     * @param array $campaignPerformance
     * @param float $totalBudget
     * @return array
     */
    protected function allocateBudgetByPerformance(array $campaignPerformance, float $totalBudget): array
    {
        if (empty($campaignPerformance) || $totalBudget <= 0) {
            return [];
        }

        $totalScore = array_sum(array_column($campaignPerformance, 'performance_score'));

        return array_map(function ($campaign) use ($totalBudget, $totalScore) {
            $allocation = $totalScore > 0
                ? ($campaign['performance_score'] / $totalScore) * $totalBudget
                : $totalBudget / count($campaign);

            return [
                'campaign_id' => $campaign['campaign_id'],
                'campaign_name' => $campaign['campaign_name'],
                'current_budget' => $campaign['current_budget'],
                'recommended_budget' => round($allocation, 2),
                'change' => round($allocation - $campaign['current_budget'], 2),
                'change_percent' => $campaign['current_budget'] > 0
                    ? round((($allocation - $campaign['current_budget']) / $campaign['current_budget']) * 100, 1)
                    : 0,
                'performance_score' => round($campaign['performance_score'], 2)
            ];
        }, $campaignPerformance);
    }
}
