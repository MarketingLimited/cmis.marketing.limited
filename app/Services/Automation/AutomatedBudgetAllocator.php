<?php

namespace App\Services\Automation;

use App\Models\AdPlatform\AdCampaign;
use App\Services\AI\CampaignOptimizationService as AICampaignOptimizationService;
use App\Services\AI\PredictiveAnalyticsService;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

/**
 * Automated Budget Allocator (Phase 4 - Advanced Automation)
 *
 * AI-powered budget reallocation across campaigns:
 * - Performance-based budget distribution
 * - Portfolio optimization
 * - ROI-maximization algorithms
 * - Constraint-based allocation
 * - Multi-objective optimization (reach, conversions, ROI)
 */
class AutomatedBudgetAllocator
{
    protected AICampaignOptimizationService $aiOptimizer;
    protected PredictiveAnalyticsService $predictive;

    // Allocation strategies
    const STRATEGY_ROI_MAXIMIZATION = 'roi_maximization';
    const STRATEGY_EQUAL_DISTRIBUTION = 'equal_distribution';
    const STRATEGY_PERFORMANCE_WEIGHTED = 'performance_weighted';
    const STRATEGY_PREDICTIVE = 'predictive';

    // Constraints
    const MIN_CAMPAIGN_BUDGET = 10.0;
    const MAX_BUDGET_SHIFT_PERCENTAGE = 30.0;

    public function __construct(
        AICampaignOptimizationService $aiOptimizer,
        PredictiveAnalyticsService $predictive
    ) {
        $this->aiOptimizer = $aiOptimizer;
        $this->predictive = $predictive;
    }

    /**
     * Reallocate budget across organization campaigns
     *
     * @param string $orgId
     * @param float $totalBudget
     * @param string $strategy
     * @param array $constraints
     * @return array
     */
    public function reallocateBudget(
        string $orgId,
        float $totalBudget,
        string $strategy = self::STRATEGY_PERFORMANCE_WEIGHTED,
        array $constraints = []
    ): array {
        try {
            // Get active campaigns
            $campaigns = AdCampaign::where('org_id', $orgId)
                ->whereIn('status', ['active', 'scheduled'])
                ->get();

            if ($campaigns->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No active campaigns found'
                ];
            }

            // Analyze each campaign
            $campaignData = [];
            foreach ($campaigns as $campaign) {
                $analysis = $this->aiOptimizer->analyzeCampaign($campaign);

                $campaignData[] = [
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->name,
                    'current_budget' => $campaign->budget ?? 0,
                    'performance_score' => $analysis['performance_score'] ?? 50,
                    'roi' => $analysis['kpis']['roi']['value'] ?? 0,
                    'ctr' => $analysis['kpis']['ctr']['value'] ?? 0,
                    'conversion_rate' => $analysis['kpis']['conversion_rate']['value'] ?? 0,
                    'predicted_roi' => $this->getPredictedROI($campaign),
                ];
            }

            // Calculate new budget allocation
            $allocation = $this->calculateAllocation(
                $campaignData,
                $totalBudget,
                $strategy,
                $constraints
            );

            // Apply budget changes
            $results = $this->applyBudgetAllocation($orgId, $allocation);

            return [
                'success' => true,
                'strategy' => $strategy,
                'total_budget' => $totalBudget,
                'campaigns_updated' => count($results['updated']),
                'allocation' => $allocation,
                'changes' => $results['changes']
            ];

        } catch (\Exception $e) {
            Log::error('Budget reallocation error', [
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
     * Calculate budget allocation based on strategy
     *
     * @param array $campaignData
     * @param float $totalBudget
     * @param string $strategy
     * @param array $constraints
     * @return array
     */
    protected function calculateAllocation(
        array $campaignData,
        float $totalBudget,
        string $strategy,
        array $constraints
    ): array {
        return match ($strategy) {
            self::STRATEGY_ROI_MAXIMIZATION => $this->allocateByROI($campaignData, $totalBudget, $constraints),
            self::STRATEGY_EQUAL_DISTRIBUTION => $this->allocateEqually($campaignData, $totalBudget),
            self::STRATEGY_PERFORMANCE_WEIGHTED => $this->allocateByPerformance($campaignData, $totalBudget, $constraints),
            self::STRATEGY_PREDICTIVE => $this->allocateByPrediction($campaignData, $totalBudget, $constraints),
            default => $this->allocateByPerformance($campaignData, $totalBudget, $constraints)
        };
    }

    /**
     * Allocate budget based on ROI maximization
     *
     * @param array $campaignData
     * @param float $totalBudget
     * @param array $constraints
     * @return array
     */
    protected function allocateByROI(array $campaignData, float $totalBudget, array $constraints): array
    {
        // Sort by ROI descending
        usort($campaignData, fn($a, $b) => $b['roi'] <=> $a['roi']);

        $allocation = [];
        $remainingBudget = $totalBudget;

        // Allocate more budget to high ROI campaigns
        $totalROI = array_sum(array_column($campaignData, 'roi'));

        if ($totalROI <= 0) {
            // Fallback to equal distribution if no ROI data
            return $this->allocateEqually($campaignData, $totalBudget);
        }

        foreach ($campaignData as $campaign) {
            $roiWeight = $campaign['roi'] / $totalROI;
            $proposedBudget = $totalBudget * $roiWeight;

            // Apply minimum budget constraint
            $proposedBudget = max($proposedBudget, self::MIN_CAMPAIGN_BUDGET);

            // Apply maximum shift constraint
            $maxShift = $campaign['current_budget'] * (1 + self::MAX_BUDGET_SHIFT_PERCENTAGE / 100);
            $minShift = $campaign['current_budget'] * (1 - self::MAX_BUDGET_SHIFT_PERCENTAGE / 100);
            $proposedBudget = max($minShift, min($maxShift, $proposedBudget));

            $allocation[] = [
                'campaign_id' => $campaign['campaign_id'],
                'campaign_name' => $campaign['campaign_name'],
                'current_budget' => $campaign['current_budget'],
                'new_budget' => round($proposedBudget, 2),
                'change_amount' => round($proposedBudget - $campaign['current_budget'], 2),
                'change_percentage' => $campaign['current_budget'] > 0
                    ? round((($proposedBudget - $campaign['current_budget']) / $campaign['current_budget']) * 100, 2)
                    : 0,
                'roi' => $campaign['roi'],
                'allocation_reason' => 'ROI-based allocation'
            ];

            $remainingBudget -= $proposedBudget;
        }

        // Distribute remaining budget proportionally
        if ($remainingBudget > 0) {
            $this->distributeRemainingBudget($allocation, $remainingBudget);
        }

        return $allocation;
    }

    /**
     * Allocate budget equally across campaigns
     *
     * @param array $campaignData
     * @param float $totalBudget
     * @return array
     */
    protected function allocateEqually(array $campaignData, float $totalBudget): array
    {
        $campaignCount = count($campaignData);
        $budgetPerCampaign = $totalBudget / $campaignCount;

        $allocation = [];

        foreach ($campaignData as $campaign) {
            $allocation[] = [
                'campaign_id' => $campaign['campaign_id'],
                'campaign_name' => $campaign['campaign_name'],
                'current_budget' => $campaign['current_budget'],
                'new_budget' => round($budgetPerCampaign, 2),
                'change_amount' => round($budgetPerCampaign - $campaign['current_budget'], 2),
                'change_percentage' => $campaign['current_budget'] > 0
                    ? round((($budgetPerCampaign - $campaign['current_budget']) / $campaign['current_budget']) * 100, 2)
                    : 0,
                'allocation_reason' => 'Equal distribution'
            ];
        }

        return $allocation;
    }

    /**
     * Allocate budget based on performance scores
     *
     * @param array $campaignData
     * @param float $totalBudget
     * @param array $constraints
     * @return array
     */
    protected function allocateByPerformance(array $campaignData, float $totalBudget, array $constraints): array
    {
        // Calculate total weighted performance
        $totalWeightedScore = array_sum(array_column($campaignData, 'performance_score'));

        if ($totalWeightedScore <= 0) {
            return $this->allocateEqually($campaignData, $totalBudget);
        }

        $allocation = [];

        foreach ($campaignData as $campaign) {
            $performanceWeight = $campaign['performance_score'] / $totalWeightedScore;
            $proposedBudget = $totalBudget * $performanceWeight;

            // Apply constraints
            $proposedBudget = max($proposedBudget, self::MIN_CAMPAIGN_BUDGET);

            // Apply maximum shift constraint
            if ($campaign['current_budget'] > 0) {
                $maxShift = $campaign['current_budget'] * (1 + self::MAX_BUDGET_SHIFT_PERCENTAGE / 100);
                $minShift = $campaign['current_budget'] * (1 - self::MAX_BUDGET_SHIFT_PERCENTAGE / 100);
                $proposedBudget = max($minShift, min($maxShift, $proposedBudget));
            }

            $allocation[] = [
                'campaign_id' => $campaign['campaign_id'],
                'campaign_name' => $campaign['campaign_name'],
                'current_budget' => $campaign['current_budget'],
                'new_budget' => round($proposedBudget, 2),
                'change_amount' => round($proposedBudget - $campaign['current_budget'], 2),
                'change_percentage' => $campaign['current_budget'] > 0
                    ? round((($proposedBudget - $campaign['current_budget']) / $campaign['current_budget']) * 100, 2)
                    : 0,
                'performance_score' => $campaign['performance_score'],
                'allocation_reason' => 'Performance-weighted allocation'
            ];
        }

        return $allocation;
    }

    /**
     * Allocate budget based on predictive analytics
     *
     * @param array $campaignData
     * @param float $totalBudget
     * @param array $constraints
     * @return array
     */
    protected function allocateByPrediction(array $campaignData, float $totalBudget, array $constraints): array
    {
        $totalPredictedROI = array_sum(array_column($campaignData, 'predicted_roi'));

        if ($totalPredictedROI <= 0) {
            return $this->allocateByPerformance($campaignData, $totalBudget, $constraints);
        }

        $allocation = [];

        foreach ($campaignData as $campaign) {
            $predictionWeight = $campaign['predicted_roi'] / $totalPredictedROI;
            $proposedBudget = $totalBudget * $predictionWeight;

            // Apply constraints
            $proposedBudget = max($proposedBudget, self::MIN_CAMPAIGN_BUDGET);

            if ($campaign['current_budget'] > 0) {
                $maxShift = $campaign['current_budget'] * (1 + self::MAX_BUDGET_SHIFT_PERCENTAGE / 100);
                $minShift = $campaign['current_budget'] * (1 - self::MAX_BUDGET_SHIFT_PERCENTAGE / 100);
                $proposedBudget = max($minShift, min($maxShift, $proposedBudget));
            }

            $allocation[] = [
                'campaign_id' => $campaign['campaign_id'],
                'campaign_name' => $campaign['campaign_name'],
                'current_budget' => $campaign['current_budget'],
                'new_budget' => round($proposedBudget, 2),
                'change_amount' => round($proposedBudget - $campaign['current_budget'], 2),
                'change_percentage' => $campaign['current_budget'] > 0
                    ? round((($proposedBudget - $campaign['current_budget']) / $campaign['current_budget']) * 100, 2)
                    : 0,
                'predicted_roi' => $campaign['predicted_roi'],
                'allocation_reason' => 'Predictive analytics-based allocation'
            ];
        }

        return $allocation;
    }

    /**
     * Apply budget allocation to campaigns
     *
     * @param string $orgId
     * @param array $allocation
     * @return array
     */
    protected function applyBudgetAllocation(string $orgId, array $allocation): array
    {
        $updated = [];
        $changes = [];

        try {
            DB::beginTransaction();

            foreach ($allocation as $item) {
                $campaign = AdCampaign::where('campaign_id', $item['campaign_id'])
                    ->where('org_id', $orgId)
                    ->first();

                if (!$campaign) {
                    Log::warning('Campaign not found for budget allocation', [
                        'campaign_id' => $item['campaign_id']
                    ]);
                    continue;
                }

                $oldBudget = $campaign->budget ?? 0;
                $newBudget = $item['new_budget'];

                // Only update if there's a significant change (> 1%)
                if (abs($newBudget - $oldBudget) / max($oldBudget, 1) > 0.01) {
                    $campaign->update(['budget' => $newBudget]);

                    $updated[] = $item['campaign_id'];
                    $changes[] = [
                        'campaign_id' => $item['campaign_id'],
                        'campaign_name' => $item['campaign_name'],
                        'old_budget' => $oldBudget,
                        'new_budget' => $newBudget,
                        'change_amount' => $newBudget - $oldBudget,
                        'change_percentage' => $oldBudget > 0
                            ? round((($newBudget - $oldBudget) / $oldBudget) * 100, 2)
                            : 0
                    ];

                    // Log the change
                    $this->logBudgetChange($campaign->campaign_id, $oldBudget, $newBudget, $item['allocation_reason']);
                }
            }

            DB::commit();

            return [
                'updated' => $updated,
                'changes' => $changes
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to apply budget allocation', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return [
                'updated' => [],
                'changes' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get predicted ROI for campaign
     *
     * @param AdCampaign $campaign
     * @return float
     */
    protected function getPredictedROI(AdCampaign $campaign): float
    {
        try {
            $forecast = $this->predictive->forecastCampaign($campaign, 7);
            return $forecast['predictions']['predicted_roi'] ?? 0.0;

        } catch (\Exception $e) {
            Log::error('Predicted ROI calculation error', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }

    /**
     * Distribute remaining budget proportionally
     *
     * @param array &$allocation
     * @param float $remainingBudget
     * @return void
     */
    protected function distributeRemainingBudget(array &$allocation, float $remainingBudget): void
    {
        $totalCurrentBudget = array_sum(array_column($allocation, 'new_budget'));

        if ($totalCurrentBudget <= 0) return;

        foreach ($allocation as &$item) {
            $proportion = $item['new_budget'] / $totalCurrentBudget;
            $additionalBudget = $remainingBudget * $proportion;

            $item['new_budget'] += $additionalBudget;
            $item['change_amount'] = $item['new_budget'] - $item['current_budget'];

            if ($item['current_budget'] > 0) {
                $item['change_percentage'] = round(
                    (($item['new_budget'] - $item['current_budget']) / $item['current_budget']) * 100,
                    2
                );
            }
        }
    }

    /**
     * Log budget change
     *
     * @param string $campaignId
     * @param float $oldBudget
     * @param float $newBudget
     * @param string $reason
     * @return void
     */
    protected function logBudgetChange(string $campaignId, float $oldBudget, float $newBudget, string $reason): void
    {
        DB::table('cmis_automation.budget_allocation_log')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'campaign_id' => $campaignId,
            'old_budget' => $oldBudget,
            'new_budget' => $newBudget,
            'change_amount' => $newBudget - $oldBudget,
            'change_percentage' => $oldBudget > 0 ? (($newBudget - $oldBudget) / $oldBudget) * 100 : 0,
            'reason' => $reason,
            'allocated_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }

    /**
     * Get budget allocation history
     *
     * @param string $orgId
     * @param int $limit
     * @return array
     */
    public function getAllocationHistory(string $orgId, int $limit = 50): array
    {
        $history = DB::table('cmis_automation.budget_allocation_log as log')
            ->join('cmis.campaigns as c', 'log.campaign_id', '=', 'c.campaign_id')
            ->where('c.org_id', $orgId)
            ->select([
                'log.id',
                'log.campaign_id',
                'c.name as campaign_name',
                'log.old_budget',
                'log.new_budget',
                'log.change_amount',
                'log.change_percentage',
                'log.reason',
                'log.allocated_at'
            ])
            ->orderBy('log.allocated_at', 'desc')
            ->limit($limit)
            ->get();

        return $history->toArray();
    }

    /**
     * Simulate budget allocation (preview without applying)
     *
     * @param string $orgId
     * @param float $totalBudget
     * @param string $strategy
     * @return array
     */
    public function simulateAllocation(string $orgId, float $totalBudget, string $strategy): array
    {
        $campaigns = AdCampaign::where('org_id', $orgId)
            ->whereIn('status', ['active', 'scheduled'])
            ->get();

        if ($campaigns->isEmpty()) {
            return [
                'success' => false,
                'error' => 'No active campaigns found'
            ];
        }

        $campaignData = [];
        foreach ($campaigns as $campaign) {
            $analysis = $this->aiOptimizer->analyzeCampaign($campaign);

            $campaignData[] = [
                'campaign_id' => $campaign->campaign_id,
                'campaign_name' => $campaign->name,
                'current_budget' => $campaign->budget ?? 0,
                'performance_score' => $analysis['performance_score'] ?? 50,
                'roi' => $analysis['kpis']['roi']['value'] ?? 0,
                'predicted_roi' => $this->getPredictedROI($campaign),
            ];
        }

        $allocation = $this->calculateAllocation($campaignData, $totalBudget, $strategy, []);

        return [
            'success' => true,
            'simulation' => true,
            'strategy' => $strategy,
            'total_budget' => $totalBudget,
            'allocation' => $allocation
        ];
    }
}
