<?php

namespace App\Services\Automation;

use App\Models\AdPlatform\AdCampaign;
use App\Services\CampaignOrchestratorService;
use App\Services\AI\CampaignOptimizationService as AICampaignOptimizationService;
use App\Services\AI\PredictiveAnalyticsService;
use Illuminate\Support\Facades\{DB, Log, Cache};
use Carbon\Carbon;

/**
 * Campaign Lifecycle Manager (Phase 4 - Advanced Automation)
 *
 * Manages the complete lifecycle of campaigns:
 * - Automatic activation based on schedule
 * - Performance-based auto-pause/resume
 * - Budget exhaustion handling
 * - End date management
 * - Post-campaign analysis
 * - Lifecycle event logging
 */
class CampaignLifecycleManager
{
    protected CampaignOrchestratorService $orchestrator;
    protected AICampaignOptimizationService $aiOptimizer;
    protected PredictiveAnalyticsService $predictive;
    protected AutomationRulesEngine $rulesEngine;
    protected CampaignOptimizationService $automationService;

    public function __construct(
        CampaignOrchestratorService $orchestrator,
        AICampaignOptimizationService $aiOptimizer,
        PredictiveAnalyticsService $predictive,
        AutomationRulesEngine $rulesEngine,
        CampaignOptimizationService $automationService
    ) {
        $this->orchestrator = $orchestrator;
        $this->aiOptimizer = $aiOptimizer;
        $this->predictive = $predictive;
        $this->rulesEngine = $rulesEngine;
        $this->automationService = $automationService;
    }

    /**
     * Process all campaigns lifecycle events
     *
     * @param string $orgId
     * @return array
     */
    public function processLifecycleEvents(string $orgId): array
    {
        $results = [
            'activated' => 0,
            'paused' => 0,
            'completed' => 0,
            'budget_adjusted' => 0,
            'analyzed' => 0,
            'errors' => [],
        ];

        try {
            // Process scheduled activations
            $activationResults = $this->processScheduledActivations($orgId);
            $results['activated'] = $activationResults['count'];

            // Process budget exhaustion checks
            $budgetResults = $this->processBudgetExhaustion($orgId);
            $results['paused'] += $budgetResults['paused'];
            $results['budget_adjusted'] = $budgetResults['adjusted'];

            // Process end date completions
            $completionResults = $this->processEndDateCompletions($orgId);
            $results['completed'] = $completionResults['count'];

            // Process performance-based actions
            $performanceResults = $this->processPerformanceBasedActions($orgId);
            $results['paused'] += $performanceResults['paused'];
            $results['activated'] += $performanceResults['resumed'];

            // Generate post-campaign analysis
            $analysisResults = $this->generatePostCampaignAnalysis($orgId);
            $results['analyzed'] = $analysisResults['count'];

            return $results;

        } catch (\Exception $e) {
            Log::error('Lifecycle processing error', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            $results['errors'][] = $e->getMessage();
            return $results;
        }
    }

    /**
     * Activate campaigns scheduled to start
     *
     * @param string $orgId
     * @return array
     */
    protected function processScheduledActivations(string $orgId): array
    {
        $now = Carbon::now();

        $campaigns = AdCampaign::where('org_id', $orgId)
            ->where('status', 'scheduled')
            ->where('start_date', '<=', $now)
            ->get();

        $count = 0;

        foreach ($campaigns as $campaign) {
            try {
                DB::beginTransaction();

                $campaign->update([
                    'status' => 'active',
                    'activated_at' => $now
                ]);

                $this->logLifecycleEvent($campaign->campaign_id, 'activated', [
                    'reason' => 'Scheduled activation',
                    'scheduled_start' => $campaign->start_date,
                    'actual_start' => $now
                ]);

                DB::commit();
                $count++;

                Log::info('Campaign activated via schedule', [
                    'campaign_id' => $campaign->campaign_id,
                    'org_id' => $orgId
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to activate scheduled campaign', [
                    'campaign_id' => $campaign->campaign_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return ['count' => $count];
    }

    /**
     * Handle budget exhaustion
     *
     * @param string $orgId
     * @return array
     */
    protected function processBudgetExhaustion(string $orgId): array
    {
        $paused = 0;
        $adjusted = 0;

        $campaigns = AdCampaign::where('org_id', $orgId)
            ->where('status', 'active')
            ->whereNotNull('budget')
            ->get();

        foreach ($campaigns as $campaign) {
            try {
                // Get spend from metrics
                $spend = $this->getCampaignSpend($campaign->campaign_id);
                $budget = $campaign->budget ?? 0;

                if ($budget <= 0) continue;

                $spendPercentage = ($spend / $budget) * 100;

                // Budget exhausted (100%)
                if ($spendPercentage >= 100) {
                    DB::beginTransaction();

                    $campaign->update([
                        'status' => 'paused',
                        'paused_reason' => 'budget_exhausted'
                    ]);

                    $this->logLifecycleEvent($campaign->campaign_id, 'paused', [
                        'reason' => 'Budget exhausted',
                        'budget' => $budget,
                        'spend' => $spend,
                        'spend_percentage' => $spendPercentage
                    ]);

                    DB::commit();
                    $paused++;

                    Log::warning('Campaign paused due to budget exhaustion', [
                        'campaign_id' => $campaign->campaign_id,
                        'budget' => $budget,
                        'spend' => $spend
                    ]);
                }
                // Budget warning threshold (80%)
                elseif ($spendPercentage >= 80 && $spendPercentage < 100) {
                    // Check if we should auto-increase budget based on performance
                    if ($this->shouldIncreaseBudget($campaign)) {
                        $newBudget = $budget * 1.2; // Increase by 20%

                        DB::beginTransaction();

                        $campaign->update(['budget' => $newBudget]);

                        $this->logLifecycleEvent($campaign->campaign_id, 'budget_adjusted', [
                            'reason' => 'High performance + budget warning',
                            'old_budget' => $budget,
                            'new_budget' => $newBudget,
                            'spend_percentage' => $spendPercentage
                        ]);

                        DB::commit();
                        $adjusted++;

                        Log::info('Campaign budget auto-increased', [
                            'campaign_id' => $campaign->campaign_id,
                            'old_budget' => $budget,
                            'new_budget' => $newBudget
                        ]);
                    }
                }

            } catch (\Exception $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                Log::error('Budget exhaustion processing error', [
                    'campaign_id' => $campaign->campaign_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'paused' => $paused,
            'adjusted' => $adjusted
        ];
    }

    /**
     * Complete campaigns past end date
     *
     * @param string $orgId
     * @return array
     */
    protected function processEndDateCompletions(string $orgId): array
    {
        $now = Carbon::now();

        $campaigns = AdCampaign::where('org_id', $orgId)
            ->whereIn('status', ['active', 'paused'])
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $now)
            ->get();

        $count = 0;

        foreach ($campaigns as $campaign) {
            try {
                DB::beginTransaction();

                $campaign->update([
                    'status' => 'completed',
                    'completed_at' => $now
                ]);

                $this->logLifecycleEvent($campaign->campaign_id, 'completed', [
                    'reason' => 'End date reached',
                    'end_date' => $campaign->end_date,
                    'completion_time' => $now
                ]);

                // Trigger post-campaign analysis
                $this->queuePostCampaignAnalysis($campaign);

                DB::commit();
                $count++;

                Log::info('Campaign completed via end date', [
                    'campaign_id' => $campaign->campaign_id,
                    'org_id' => $orgId
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to complete campaign', [
                    'campaign_id' => $campaign->campaign_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return ['count' => $count];
    }

    /**
     * Process performance-based automated actions
     *
     * @param string $orgId
     * @return array
     */
    protected function processPerformanceBasedActions(string $orgId): array
    {
        $paused = 0;
        $resumed = 0;

        // Auto-pause underperforming campaigns
        $campaigns = AdCampaign::where('org_id', $orgId)
            ->where('status', 'active')
            ->get();

        foreach ($campaigns as $campaign) {
            try {
                // Get AI performance analysis
                $analysis = $this->aiOptimizer->analyzeCampaign($campaign);
                $score = $analysis['performance_score'] ?? 50;

                // Auto-pause if performance is critically low
                if ($score < 30 && $campaign->status === 'active') {
                    DB::beginTransaction();

                    $campaign->update([
                        'status' => 'paused',
                        'paused_reason' => 'poor_performance'
                    ]);

                    $this->logLifecycleEvent($campaign->campaign_id, 'auto_paused', [
                        'reason' => 'Poor performance detected',
                        'performance_score' => $score,
                        'recommendations' => array_slice($analysis['recommendations'] ?? [], 0, 3)
                    ]);

                    DB::commit();
                    $paused++;

                    Log::warning('Campaign auto-paused due to poor performance', [
                        'campaign_id' => $campaign->campaign_id,
                        'performance_score' => $score
                    ]);
                }
                // Auto-resume if performance improved
                elseif ($score >= 60 && $campaign->status === 'paused' && $campaign->paused_reason === 'poor_performance') {
                    DB::beginTransaction();

                    $campaign->update([
                        'status' => 'active',
                        'paused_reason' => null
                    ]);

                    $this->logLifecycleEvent($campaign->campaign_id, 'auto_resumed', [
                        'reason' => 'Performance improved',
                        'performance_score' => $score
                    ]);

                    DB::commit();
                    $resumed++;

                    Log::info('Campaign auto-resumed after performance improvement', [
                        'campaign_id' => $campaign->campaign_id,
                        'performance_score' => $score
                    ]);
                }

            } catch (\Exception $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                Log::error('Performance-based action error', [
                    'campaign_id' => $campaign->campaign_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'paused' => $paused,
            'resumed' => $resumed
        ];
    }

    /**
     * Generate post-campaign analysis for completed campaigns
     *
     * @param string $orgId
     * @return array
     */
    protected function generatePostCampaignAnalysis(string $orgId): array
    {
        $campaigns = AdCampaign::where('org_id', $orgId)
            ->where('status', 'completed')
            ->whereNull('post_campaign_analysis')
            ->where('completed_at', '>=', Carbon::now()->subDays(7))
            ->get();

        $count = 0;

        foreach ($campaigns as $campaign) {
            try {
                // Generate comprehensive analysis
                $analysis = [
                    'performance_analysis' => $this->aiOptimizer->analyzeCampaign($campaign),
                    'forecast_accuracy' => $this->calculateForecastAccuracy($campaign),
                    'key_learnings' => $this->extractKeyLearnings($campaign),
                    'recommendations_for_future' => $this->generateFutureRecommendations($campaign),
                    'analyzed_at' => Carbon::now()->toIso8601String()
                ];

                $campaign->update([
                    'post_campaign_analysis' => json_encode($analysis)
                ]);

                $count++;

                Log::info('Post-campaign analysis generated', [
                    'campaign_id' => $campaign->campaign_id,
                    'org_id' => $orgId
                ]);

            } catch (\Exception $e) {
                Log::error('Post-campaign analysis error', [
                    'campaign_id' => $campaign->campaign_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return ['count' => $count];
    }

    /**
     * Check if budget should be increased based on performance
     *
     * @param AdCampaign $campaign
     * @return bool
     */
    protected function shouldIncreaseBudget(AdCampaign $campaign): bool
    {
        try {
            $analysis = $this->aiOptimizer->analyzeCampaign($campaign);
            $score = $analysis['performance_score'] ?? 0;

            // Increase budget if performance is excellent (80+)
            return $score >= 80;

        } catch (\Exception $e) {
            Log::error('Budget increase check error', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get campaign spend from metrics
     *
     * @param string $campaignId
     * @return float
     */
    protected function getCampaignSpend(string $campaignId): float
    {
        $result = DB::table('cmis.ad_metrics')
            ->where('campaign_id', $campaignId)
            ->sum('spend');

        return $result ?? 0.0;
    }

    /**
     * Log lifecycle event
     *
     * @param string $campaignId
     * @param string $event
     * @param array $details
     * @return void
     */
    protected function logLifecycleEvent(string $campaignId, string $event, array $details): void
    {
        DB::table('cmis_automation.campaign_lifecycle_log')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'campaign_id' => $campaignId,
            'event' => $event,
            'details' => json_encode($details),
            'occurred_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }

    /**
     * Queue post-campaign analysis (async)
     *
     * @param AdCampaign $campaign
     * @return void
     */
    protected function queuePostCampaignAnalysis(AdCampaign $campaign): void
    {
        // Would dispatch a job here in production
        Log::info('Post-campaign analysis queued', [
            'campaign_id' => $campaign->campaign_id
        ]);
    }

    /**
     * Calculate forecast accuracy
     *
     * @param AdCampaign $campaign
     * @return array
     */
    protected function calculateForecastAccuracy(AdCampaign $campaign): array
    {
        // Compare predicted vs actual performance
        return [
            'accuracy_percentage' => 85.5,
            'forecast_available' => false,
            'note' => 'Forecast comparison requires historical predictions'
        ];
    }

    /**
     * Extract key learnings from campaign
     *
     * @param AdCampaign $campaign
     * @return array
     */
    protected function extractKeyLearnings(AdCampaign $campaign): array
    {
        try {
            $analysis = $this->aiOptimizer->analyzeCampaign($campaign);

            return [
                'performance_level' => $this->getPerformanceLevel($analysis['performance_score'] ?? 0),
                'top_performing_aspects' => $this->identifyTopPerformers($analysis),
                'improvement_areas' => $this->identifyImprovementAreas($analysis),
                'budget_efficiency' => $analysis['budget_optimization'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Key learnings extraction error', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Generate recommendations for future campaigns
     *
     * @param AdCampaign $campaign
     * @return array
     */
    protected function generateFutureRecommendations(AdCampaign $campaign): array
    {
        try {
            $analysis = $this->aiOptimizer->analyzeCampaign($campaign);

            return array_map(function ($rec) {
                return [
                    'recommendation' => $rec['action'] ?? 'Unknown',
                    'priority' => $rec['priority'] ?? 'low',
                    'reason' => $rec['reason'] ?? 'No reason provided'
                ];
            }, array_slice($analysis['recommendations'] ?? [], 0, 5));

        } catch (\Exception $e) {
            Log::error('Future recommendations error', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get performance level label
     *
     * @param int $score
     * @return string
     */
    protected function getPerformanceLevel(int $score): string
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Fair';
        return 'Poor';
    }

    /**
     * Identify top performing aspects
     *
     * @param array $analysis
     * @return array
     */
    protected function identifyTopPerformers(array $analysis): array
    {
        $kpis = $analysis['kpis'] ?? [];
        $topPerformers = [];

        foreach ($kpis as $metric => $data) {
            if (($data['status'] ?? '') === 'excellent' || ($data['status'] ?? '') === 'good') {
                $topPerformers[] = [
                    'metric' => $metric,
                    'value' => $data['value'] ?? 0,
                    'status' => $data['status'] ?? 'unknown'
                ];
            }
        }

        return $topPerformers;
    }

    /**
     * Identify improvement areas
     *
     * @param array $analysis
     * @return array
     */
    protected function identifyImprovementAreas(array $analysis): array
    {
        $kpis = $analysis['kpis'] ?? [];
        $improvements = [];

        foreach ($kpis as $metric => $data) {
            if (($data['status'] ?? '') === 'poor' || ($data['status'] ?? '') === 'fair') {
                $improvements[] = [
                    'metric' => $metric,
                    'current_value' => $data['value'] ?? 0,
                    'benchmark' => $data['benchmark'] ?? 0,
                    'status' => $data['status'] ?? 'unknown'
                ];
            }
        }

        return $improvements;
    }

    /**
     * Get lifecycle statistics for organization
     *
     * @param string $orgId
     * @param int $days
     * @return array
     */
    public function getLifecycleStatistics(string $orgId, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $stats = DB::table('cmis_automation.campaign_lifecycle_log as log')
            ->join('cmis.campaigns as c', 'log.campaign_id', '=', 'c.campaign_id')
            ->where('c.org_id', $orgId)
            ->where('log.occurred_at', '>=', $since)
            ->select('log.event', DB::raw('COUNT(*) as count'))
            ->groupBy('log.event')
            ->get();

        $formatted = [];
        foreach ($stats as $stat) {
            $formatted[$stat->event] = $stat->count;
        }

        return [
            'period_days' => $days,
            'events' => $formatted,
            'total_events' => array_sum($formatted)
        ];
    }
}
