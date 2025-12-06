<?php

namespace App\Services\Orchestration;

use App\Models\Orchestration\CampaignOrchestration;
use App\Models\Orchestration\OrchestrationPlatform;
use App\Models\Orchestration\OrchestrationWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowEngine
{
    /**
     * Performance thresholds for optimization decisions
     */
    protected const PERFORMANCE_THRESHOLDS = [
        'ctr' => ['poor' => 0.5, 'fair' => 1.0, 'good' => 2.0, 'excellent' => 4.0],
        'cpc' => ['excellent' => 0.5, 'good' => 1.0, 'fair' => 2.0, 'poor' => 3.0],
        'roas' => ['poor' => 1.0, 'fair' => 2.0, 'good' => 3.0, 'excellent' => 5.0],
        'conversion_rate' => ['poor' => 0.5, 'fair' => 1.0, 'good' => 2.0, 'excellent' => 4.0],
    ];

    /**
     * Budget adjustment limits
     */
    protected const MAX_BUDGET_INCREASE = 0.30; // Max 30% increase
    protected const MAX_BUDGET_DECREASE = 0.25; // Max 25% decrease
    protected const MIN_BUDGET_THRESHOLD = 10.0; // Minimum budget to keep platform active

    public function __construct(
        protected CrossPlatformSyncService $syncService
    ) {}

    /**
     * Execute deployment workflow.
     */
    public function executeDeploymentWorkflow(CampaignOrchestration $orchestration): OrchestrationWorkflow
    {
        $platforms = $orchestration->platformMappings;

        $steps = [
            ['name' => 'validate_configuration', 'action' => 'validate'],
            ['name' => 'create_platform_campaigns', 'action' => 'create'],
            ['name' => 'sync_settings', 'action' => 'sync'],
            ['name' => 'activate_campaigns', 'action' => 'activate'],
        ];

        $workflow = OrchestrationWorkflow::create([
            'org_id' => $orchestration->org_id,
            'orchestration_id' => $orchestration->orchestration_id,
            'workflow_type' => 'creation',
            'status' => 'pending',
            'steps' => $steps,
            'total_steps' => count($steps),
            'current_step' => 0,
            'execution_log' => [],
        ]);

        $workflow->start();

        DB::beginTransaction();
        try {
            // Step 1: Validate
            $workflow->logStep('validate_configuration', 'running');
            $this->validateConfiguration($orchestration);
            $workflow->logStep('validate_configuration', 'completed');
            $workflow->advanceStep();

            // Step 2: Create platform campaigns
            $workflow->logStep('create_platform_campaigns', 'running');
            foreach ($platforms as $mapping) {
                $mapping->markAsCreating();
                $platformCampaignId = $this->syncService->createPlatformCampaign($mapping);
                $mapping->markAsActive($platformCampaignId, $orchestration->name);
            }
            $workflow->logStep('create_platform_campaigns', 'completed', [
                'platforms_created' => $platforms->count()
            ]);
            $workflow->advanceStep();

            // Step 3: Sync settings
            $workflow->logStep('sync_settings', 'running');
            foreach ($platforms as $mapping) {
                $this->syncService->syncPlatformMapping($mapping, 'settings');
            }
            $workflow->logStep('sync_settings', 'completed');
            $workflow->advanceStep();

            // Step 4: Activate
            $workflow->logStep('activate_campaigns', 'running');
            $orchestration->activate();
            $orchestration->updatePlatformCounts();
            $workflow->logStep('activate_campaigns', 'completed');
            $workflow->advanceStep();

            $workflow->complete();
            DB::commit();

            return $workflow;

        } catch (\Exception $e) {
            DB::rollBack();
            $workflow->fail($e->getMessage());

            Log::error('Deployment workflow failed', [
                'orchestration_id' => $orchestration->orchestration_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate orchestration configuration.
     */
    protected function validateConfiguration(CampaignOrchestration $orchestration): void
    {
        // Check if all platforms have active connections
        foreach ($orchestration->platforms as $platform) {
            $mapping = $orchestration->platformMappings()
                ->where('platform', $platform)
                ->first();

            if (!$mapping) {
                throw new \Exception("No platform mapping found for: {$platform}");
            }

            if (!$mapping->connection->isActive()) {
                throw new \Exception("Platform connection is not active for: {$platform}");
            }
        }

        // Check budget allocation
        if ($orchestration->hasUnallocatedBudget()) {
            Log::warning('Orchestration has unallocated budget', [
                'orchestration_id' => $orchestration->orchestration_id,
                'total_budget' => $orchestration->total_budget,
                'allocated' => $orchestration->getTotalAllocatedBudget(),
            ]);
        }
    }

    /**
     * Execute optimization workflow.
     */
    public function executeOptimizationWorkflow(CampaignOrchestration $orchestration): OrchestrationWorkflow
    {
        $steps = [
            ['name' => 'fetch_performance', 'action' => 'fetch'],
            ['name' => 'analyze_performance', 'action' => 'analyze'],
            ['name' => 'generate_recommendations', 'action' => 'recommend'],
            ['name' => 'apply_optimizations', 'action' => 'optimize'],
        ];

        $workflow = OrchestrationWorkflow::create([
            'org_id' => $orchestration->org_id,
            'orchestration_id' => $orchestration->orchestration_id,
            'workflow_type' => 'optimization',
            'status' => 'pending',
            'steps' => $steps,
            'total_steps' => count($steps),
            'execution_log' => [],
        ]);

        $workflow->start();

        try {
            // Fetch latest performance
            $workflow->logStep('fetch_performance', 'running');
            foreach ($orchestration->platformMappings as $mapping) {
                $this->syncService->syncPlatformMapping($mapping, 'performance');
            }
            $workflow->logStep('fetch_performance', 'completed');
            $workflow->advanceStep();

            // Analyze performance data
            $workflow->logStep('analyze_performance', 'running');
            $analysisResults = $this->analyzePerformanceData($orchestration);
            $workflow->logStep('analyze_performance', 'completed', [
                'platforms_analyzed' => count($analysisResults['platform_scores']),
                'overall_score' => $analysisResults['overall_score'],
            ]);
            $workflow->advanceStep();

            // Generate recommendations
            $workflow->logStep('generate_recommendations', 'running');
            $recommendations = $this->generateOptimizationRecommendations($orchestration, $analysisResults);
            $workflow->logStep('generate_recommendations', 'completed', [
                'recommendations_count' => count($recommendations),
                'recommendations' => array_map(fn($r) => [
                    'platform' => $r['platform'],
                    'type' => $r['type'],
                    'action' => $r['action'],
                ], $recommendations),
            ]);
            $workflow->advanceStep();

            // Apply optimizations if auto-optimize is enabled
            $workflow->logStep('apply_optimizations', 'running');
            $appliedOptimizations = [];
            $config = $orchestration->orchestration_config ?? [];

            if ($config['auto_optimize'] ?? false) {
                $appliedOptimizations = $this->applyOptimizations($orchestration, $recommendations);
            }

            $workflow->logStep('apply_optimizations', 'completed', [
                'auto_optimize_enabled' => $config['auto_optimize'] ?? false,
                'optimizations_applied' => count($appliedOptimizations),
                'applied' => $appliedOptimizations,
            ]);
            $workflow->advanceStep();

            $workflow->complete();
            return $workflow;

        } catch (\Exception $e) {
            $workflow->fail($e->getMessage());
            throw $e;
        }
    }

    /**
     * Analyze performance data for all platform mappings.
     */
    protected function analyzePerformanceData(CampaignOrchestration $orchestration): array
    {
        $platformScores = [];
        $totalScore = 0;
        $scoreCount = 0;

        foreach ($orchestration->platformMappings as $mapping) {
            $metrics = $this->getPlatformMetrics($mapping);
            $score = $this->calculatePlatformScore($metrics);

            $platformScores[$mapping->platform] = [
                'platform' => $mapping->platform,
                'score' => $score,
                'metrics' => $metrics,
                'status' => $this->getPerformanceStatus($score),
                'budget' => $mapping->budget ?? 0,
                'spend' => $mapping->spend ?? 0,
                'conversions' => $mapping->conversions ?? 0,
                'revenue' => $mapping->revenue ?? 0,
            ];

            $totalScore += $score;
            $scoreCount++;
        }

        return [
            'platform_scores' => $platformScores,
            'overall_score' => $scoreCount > 0 ? round($totalScore / $scoreCount, 1) : 0,
            'total_budget' => $orchestration->total_budget ?? 0,
            'total_spend' => $orchestration->getTotalSpend(),
            'total_revenue' => $orchestration->getTotalRevenue(),
            'roas' => $orchestration->getROAS(),
            'budget_utilization' => $orchestration->getBudgetUtilization(),
        ];
    }

    /**
     * Get performance metrics for a platform mapping.
     */
    protected function getPlatformMetrics(OrchestrationPlatform $mapping): array
    {
        $impressions = $mapping->impressions ?? 0;
        $clicks = $mapping->clicks ?? 0;
        $conversions = $mapping->conversions ?? 0;
        $spend = $mapping->spend ?? 0;
        $revenue = $mapping->revenue ?? 0;

        // Calculate derived metrics
        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
        $cpc = $clicks > 0 ? $spend / $clicks : 0;
        $conversionRate = $clicks > 0 ? ($conversions / $clicks) * 100 : 0;
        $cpa = $conversions > 0 ? $spend / $conversions : 0;
        $roas = $spend > 0 ? $revenue / $spend : 0;

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'spend' => round($spend, 2),
            'revenue' => round($revenue, 2),
            'ctr' => round($ctr, 2),
            'cpc' => round($cpc, 2),
            'conversion_rate' => round($conversionRate, 2),
            'cpa' => round($cpa, 2),
            'roas' => round($roas, 2),
        ];
    }

    /**
     * Calculate overall platform performance score (0-100).
     */
    protected function calculatePlatformScore(array $metrics): int
    {
        $score = 50; // Base score

        // CTR score (0-20 points)
        $ctr = $metrics['ctr'];
        if ($ctr >= self::PERFORMANCE_THRESHOLDS['ctr']['excellent']) $score += 20;
        elseif ($ctr >= self::PERFORMANCE_THRESHOLDS['ctr']['good']) $score += 15;
        elseif ($ctr >= self::PERFORMANCE_THRESHOLDS['ctr']['fair']) $score += 10;
        elseif ($ctr >= self::PERFORMANCE_THRESHOLDS['ctr']['poor']) $score += 5;

        // CPC score (0-15 points) - lower is better
        $cpc = $metrics['cpc'];
        if ($cpc > 0) {
            if ($cpc <= self::PERFORMANCE_THRESHOLDS['cpc']['excellent']) $score += 15;
            elseif ($cpc <= self::PERFORMANCE_THRESHOLDS['cpc']['good']) $score += 12;
            elseif ($cpc <= self::PERFORMANCE_THRESHOLDS['cpc']['fair']) $score += 8;
            elseif ($cpc <= self::PERFORMANCE_THRESHOLDS['cpc']['poor']) $score += 4;
        }

        // ROAS score (0-25 points)
        $roas = $metrics['roas'];
        if ($roas >= self::PERFORMANCE_THRESHOLDS['roas']['excellent']) $score += 25;
        elseif ($roas >= self::PERFORMANCE_THRESHOLDS['roas']['good']) $score += 20;
        elseif ($roas >= self::PERFORMANCE_THRESHOLDS['roas']['fair']) $score += 15;
        elseif ($roas >= self::PERFORMANCE_THRESHOLDS['roas']['poor']) $score += 10;

        // Conversion rate score (0-15 points)
        $convRate = $metrics['conversion_rate'];
        if ($convRate >= self::PERFORMANCE_THRESHOLDS['conversion_rate']['excellent']) $score += 15;
        elseif ($convRate >= self::PERFORMANCE_THRESHOLDS['conversion_rate']['good']) $score += 12;
        elseif ($convRate >= self::PERFORMANCE_THRESHOLDS['conversion_rate']['fair']) $score += 8;
        elseif ($convRate >= self::PERFORMANCE_THRESHOLDS['conversion_rate']['poor']) $score += 4;

        return min(100, max(0, $score));
    }

    /**
     * Get performance status string based on score.
     */
    protected function getPerformanceStatus(int $score): string
    {
        if ($score >= 85) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 55) return 'fair';
        if ($score >= 40) return 'poor';
        return 'critical';
    }

    /**
     * Generate optimization recommendations based on analysis.
     */
    protected function generateOptimizationRecommendations(CampaignOrchestration $orchestration, array $analysis): array
    {
        $recommendations = [];

        foreach ($analysis['platform_scores'] as $platformData) {
            $platform = $platformData['platform'];
            $metrics = $platformData['metrics'];
            $score = $platformData['score'];

            // Low CTR recommendation
            if ($metrics['ctr'] < self::PERFORMANCE_THRESHOLDS['ctr']['fair']) {
                $recommendations[] = [
                    'platform' => $platform,
                    'type' => 'creative',
                    'priority' => 'high',
                    'action' => 'improve_creative',
                    'reason' => "CTR is {$metrics['ctr']}%, below fair threshold of " . self::PERFORMANCE_THRESHOLDS['ctr']['fair'] . '%',
                    'suggestions' => [
                        'Test different ad headlines and descriptions',
                        'Use more engaging visuals',
                        'Add strong call-to-action buttons',
                        'Review audience targeting relevance',
                    ],
                ];
            }

            // High CPC recommendation
            if ($metrics['cpc'] > self::PERFORMANCE_THRESHOLDS['cpc']['fair']) {
                $recommendations[] = [
                    'platform' => $platform,
                    'type' => 'bidding',
                    'priority' => 'high',
                    'action' => 'reduce_cpc',
                    'reason' => "CPC is \${$metrics['cpc']}, above fair threshold of $" . self::PERFORMANCE_THRESHOLDS['cpc']['fair'],
                    'suggestions' => [
                        'Switch to automated bidding strategy',
                        'Reduce max CPC bid by 10-20%',
                        'Improve Quality Score through better ad relevance',
                        'Refine keyword targeting',
                    ],
                ];
            }

            // Low ROAS recommendation
            if ($metrics['roas'] > 0 && $metrics['roas'] < self::PERFORMANCE_THRESHOLDS['roas']['fair']) {
                $recommendations[] = [
                    'platform' => $platform,
                    'type' => 'budget',
                    'priority' => 'high',
                    'action' => 'reduce_budget',
                    'reason' => "ROAS is {$metrics['roas']}x, below fair threshold of " . self::PERFORMANCE_THRESHOLDS['roas']['fair'] . 'x',
                    'budget_adjustment' => -0.2, // Suggest 20% reduction
                    'suggestions' => [
                        'Reduce budget allocation by 20%',
                        'Pause underperforming ad groups',
                        'Focus on high-converting audiences',
                        'Review conversion tracking accuracy',
                    ],
                ];
            }

            // Excellent performance - scale up recommendation
            if ($score >= 80 && $metrics['roas'] >= self::PERFORMANCE_THRESHOLDS['roas']['good']) {
                $recommendations[] = [
                    'platform' => $platform,
                    'type' => 'scaling',
                    'priority' => 'medium',
                    'action' => 'increase_budget',
                    'reason' => "Score is {$score}/100 with ROAS of {$metrics['roas']}x - excellent performance",
                    'budget_adjustment' => 0.25, // Suggest 25% increase
                    'suggestions' => [
                        'Increase budget by 20-25%',
                        'Expand to similar audiences',
                        'Test new placements',
                        'Clone winning campaigns',
                    ],
                ];
            }

            // Low conversion rate recommendation
            if ($metrics['clicks'] > 50 && $metrics['conversion_rate'] < self::PERFORMANCE_THRESHOLDS['conversion_rate']['fair']) {
                $recommendations[] = [
                    'platform' => $platform,
                    'type' => 'targeting',
                    'priority' => 'medium',
                    'action' => 'improve_targeting',
                    'reason' => "Conversion rate is {$metrics['conversion_rate']}% with {$metrics['clicks']} clicks",
                    'suggestions' => [
                        'Review landing page relevance',
                        'Refine audience demographics',
                        'Use retargeting for warm audiences',
                        'Test different call-to-action',
                    ],
                ];
            }

            // Poor overall performance - consider pausing
            if ($score < 40 && $metrics['spend'] > 100) {
                $recommendations[] = [
                    'platform' => $platform,
                    'type' => 'critical',
                    'priority' => 'critical',
                    'action' => 'pause_or_overhaul',
                    'reason' => "Critical score ({$score}/100) with \${$metrics['spend']} spent",
                    'budget_adjustment' => -0.5, // Suggest 50% reduction
                    'suggestions' => [
                        'Consider pausing this platform',
                        'Complete campaign overhaul required',
                        'Reallocate budget to better performing platforms',
                        'Review targeting and creative from scratch',
                    ],
                ];
            }
        }

        // Cross-platform budget reallocation recommendation
        if (count($analysis['platform_scores']) > 1) {
            $this->addBudgetReallocationRecommendation($recommendations, $analysis);
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($priorityOrder[$a['priority']] ?? 4) <=> ($priorityOrder[$b['priority']] ?? 4);
        });

        return $recommendations;
    }

    /**
     * Add budget reallocation recommendation based on cross-platform performance.
     */
    protected function addBudgetReallocationRecommendation(array &$recommendations, array $analysis): void
    {
        $platforms = $analysis['platform_scores'];

        // Find best and worst performers
        $bestPlatform = null;
        $worstPlatform = null;
        $bestScore = 0;
        $worstScore = 100;

        foreach ($platforms as $platformData) {
            if ($platformData['score'] > $bestScore) {
                $bestScore = $platformData['score'];
                $bestPlatform = $platformData['platform'];
            }
            if ($platformData['score'] < $worstScore) {
                $worstScore = $platformData['score'];
                $worstPlatform = $platformData['platform'];
            }
        }

        // If there's significant difference (>20 points), recommend reallocation
        if ($bestPlatform && $worstPlatform && $bestPlatform !== $worstPlatform && ($bestScore - $worstScore) > 20) {
            $recommendations[] = [
                'platform' => 'cross_platform',
                'type' => 'reallocation',
                'priority' => 'medium',
                'action' => 'reallocate_budget',
                'reason' => "Performance gap: {$bestPlatform} ({$bestScore}) vs {$worstPlatform} ({$worstScore})",
                'from_platform' => $worstPlatform,
                'to_platform' => $bestPlatform,
                'suggested_amount_pct' => 15, // Move 15% of budget
                'suggestions' => [
                    "Move 15% of budget from {$worstPlatform} to {$bestPlatform}",
                    'Monitor for 48-72 hours after adjustment',
                    'Review performance before making additional changes',
                ],
            ];
        }
    }

    /**
     * Apply automatic optimizations based on recommendations.
     */
    protected function applyOptimizations(CampaignOrchestration $orchestration, array $recommendations): array
    {
        $applied = [];
        $budgetAllocation = $orchestration->budget_allocation ?? [];
        $totalBudget = $orchestration->total_budget ?? 0;

        foreach ($recommendations as $rec) {
            $platform = $rec['platform'];

            // Skip cross-platform or non-budget recommendations for auto-apply
            if ($platform === 'cross_platform') {
                continue;
            }

            // Only auto-apply budget adjustments
            if (!isset($rec['budget_adjustment'])) {
                continue;
            }

            $currentBudget = $budgetAllocation[$platform] ?? 0;
            if ($currentBudget <= 0) {
                continue;
            }

            $adjustment = $rec['budget_adjustment'];

            // Apply limits
            if ($adjustment > 0) {
                $adjustment = min($adjustment, self::MAX_BUDGET_INCREASE);
            } else {
                $adjustment = max($adjustment, -self::MAX_BUDGET_DECREASE);
            }

            $newBudget = $currentBudget * (1 + $adjustment);

            // Ensure minimum budget threshold
            if ($newBudget < self::MIN_BUDGET_THRESHOLD && $adjustment < 0) {
                $newBudget = self::MIN_BUDGET_THRESHOLD;
            }

            $budgetAllocation[$platform] = round($newBudget, 2);

            $applied[] = [
                'platform' => $platform,
                'action' => $rec['action'],
                'old_budget' => $currentBudget,
                'new_budget' => $newBudget,
                'adjustment_pct' => round($adjustment * 100, 1),
                'reason' => $rec['reason'],
            ];

            Log::info('Auto-optimization applied', [
                'orchestration_id' => $orchestration->orchestration_id,
                'platform' => $platform,
                'action' => $rec['action'],
                'old_budget' => $currentBudget,
                'new_budget' => $newBudget,
            ]);
        }

        // Save updated budget allocation
        if (!empty($applied)) {
            $orchestration->updateBudgetAllocation($budgetAllocation);

            // Sync budget changes to platforms
            foreach ($orchestration->platformMappings as $mapping) {
                if (isset($budgetAllocation[$mapping->platform])) {
                    $this->syncService->updatePlatformBudget($mapping, $budgetAllocation[$mapping->platform]);
                }
            }
        }

        return $applied;
    }
}
