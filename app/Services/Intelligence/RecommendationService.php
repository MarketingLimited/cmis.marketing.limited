<?php

namespace App\Services\Intelligence;

use App\Models\Intelligence\Recommendation;
use App\Models\Intelligence\Anomaly;
use App\Models\Intelligence\TrendAnalysis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Generate recommendations for an entity
     */
    public function generateRecommendations(
        string $entityType,
        string $entityId,
        ?array $types = null,
        float $minConfidence = 0.5
    ): Collection {
        $orgId = session('current_org_id');
        $recommendations = collect();

        // Analyze anomalies
        $anomalyRecommendations = $this->generateFromAnomalies(
            $orgId,
            $entityType,
            $entityId,
            $minConfidence
        );
        $recommendations = $recommendations->merge($anomalyRecommendations);

        // Analyze trends
        $trendRecommendations = $this->generateFromTrends(
            $orgId,
            $entityType,
            $entityId,
            $minConfidence
        );
        $recommendations = $recommendations->merge($trendRecommendations);

        // Analyze performance metrics
        $performanceRecommendations = $this->generateFromPerformance(
            $orgId,
            $entityType,
            $entityId,
            $minConfidence
        );
        $recommendations = $recommendations->merge($performanceRecommendations);

        // Filter by types if specified
        if ($types) {
            $recommendations = $recommendations->whereIn('type', $types);
        }

        // Sort by priority and confidence
        return $recommendations->sortByDesc(function ($rec) {
            $priorityWeight = match ($rec->priority) {
                Recommendation::PRIORITY_URGENT => 4,
                Recommendation::PRIORITY_HIGH => 3,
                Recommendation::PRIORITY_MEDIUM => 2,
                default => 1,
            };
            return ($priorityWeight * 100) + ($rec->confidence_score * 100);
        })->values();
    }

    /**
     * Apply a recommendation
     */
    public function applyRecommendation(Recommendation $recommendation, ?string $userId = null): array
    {
        DB::beginTransaction();

        try {
            // Mark as applied
            $recommendation->apply($userId);

            // Execute the recommendation action
            $result = $this->executeRecommendation($recommendation);

            DB::commit();

            return [
                'success' => true,
                'recommendation' => $recommendation,
                'execution_result' => $result,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Get analytics dashboard data
     */
    public function getAnalytics(string $orgId): array
    {
        $totalRecommendations = Recommendation::where('org_id', $orgId)->count();

        $pendingRecommendations = Recommendation::where('org_id', $orgId)
            ->pending()
            ->count();

        $appliedRecommendations = Recommendation::where('org_id', $orgId)
            ->where('status', Recommendation::STATUS_APPLIED)
            ->count();

        $highPriorityRecommendations = Recommendation::where('org_id', $orgId)
            ->highPriority()
            ->pending()
            ->count();

        $avgConfidence = Recommendation::where('org_id', $orgId)
            ->avg('confidence_score');

        $helpfulRate = $this->calculateHelpfulRate($orgId);

        $recommendationsByType = Recommendation::where('org_id', $orgId)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        $recommendationsByPriority = Recommendation::where('org_id', $orgId)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority');

        return [
            'summary' => [
                'total_recommendations' => $totalRecommendations,
                'pending_recommendations' => $pendingRecommendations,
                'applied_recommendations' => $appliedRecommendations,
                'high_priority_recommendations' => $highPriorityRecommendations,
                'average_confidence' => round($avgConfidence ?? 0, 4),
                'helpful_rate' => $helpfulRate,
            ],
            'by_type' => $recommendationsByType,
            'by_priority' => $recommendationsByPriority,
            'recent_applied' => $this->getRecentApplied($orgId),
            'top_impact' => $this->getTopImpactRecommendations($orgId),
            'trends' => $this->getRecommendationTrends($orgId),
        ];
    }

    /**
     * Get recommendations summary
     */
    public function getSummary(string $orgId, int $days = 30): array
    {
        $dateFrom = now()->subDays($days);

        $total = Recommendation::where('org_id', $orgId)
            ->where('created_at', '>=', $dateFrom)
            ->count();

        $byType = Recommendation::where('org_id', $orgId)
            ->where('created_at', '>=', $dateFrom)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        $byPriority = Recommendation::where('org_id', $orgId)
            ->where('created_at', '>=', $dateFrom)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority');

        $byStatus = Recommendation::where('org_id', $orgId)
            ->where('created_at', '>=', $dateFrom)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'period_days' => $days,
            'total_recommendations' => $total,
            'by_type' => $byType,
            'by_priority' => $byPriority,
            'by_status' => $byStatus,
            'avg_confidence' => $this->getAverageConfidence($orgId, $dateFrom),
            'application_rate' => $this->calculateApplicationRate($orgId, $dateFrom),
        ];
    }

    /**
     * Generate recommendations from anomalies
     */
    protected function generateFromAnomalies(
        string $orgId,
        string $entityType,
        string $entityId,
        float $minConfidence
    ): Collection {
        $recommendations = collect();

        $anomalies = Anomaly::where('org_id', $orgId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->unresolved()
            ->where('detected_at', '>=', now()->subDays(7))
            ->get();

        foreach ($anomalies as $anomaly) {
            $type = $this->determineRecommendationType($anomaly);
            $priority = $this->mapSeverityToPriority($anomaly->severity);

            $recommendation = Recommendation::create([
                'org_id' => $orgId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'type' => $type,
                'priority' => $priority,
                'title' => $this->generateTitle($type, $anomaly),
                'description' => $this->generateDescription($type, $anomaly),
                'rationale' => $this->generateRationale($anomaly),
                'confidence_score' => min(1.0, $anomaly->confidence_score + 0.1),
                'impact_estimate' => $this->estimateImpact($anomaly),
                'status' => Recommendation::STATUS_PENDING,
                'metadata' => [
                    'source' => 'anomaly',
                    'anomaly_id' => $anomaly->anomaly_id,
                ],
                'created_by' => auth()->id(),
            ]);

            if ($recommendation->confidence_score >= $minConfidence) {
                $recommendations->push($recommendation);
            }
        }

        return $recommendations;
    }

    /**
     * Generate recommendations from trends
     */
    protected function generateFromTrends(
        string $orgId,
        string $entityType,
        string $entityId,
        float $minConfidence
    ): Collection {
        $recommendations = collect();

        $trends = TrendAnalysis::where('org_id', $orgId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->significant()
            ->where('analysis_date', '>=', now()->subDays(30))
            ->get();

        foreach ($trends as $trend) {
            // Generate recommendations based on trend direction and pattern
            if ($trend->isDownward() && $trend->isStatisticallySignificant()) {
                $recommendation = Recommendation::create([
                    'org_id' => $orgId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'type' => Recommendation::TYPE_BUDGET_OPTIMIZATION,
                    'priority' => Recommendation::PRIORITY_HIGH,
                    'title' => "Address declining {$trend->metric_name} trend",
                    'description' => "The {$trend->metric_name} metric shows a significant downward trend. Consider adjusting your strategy.",
                    'rationale' => "Statistical analysis shows {$trend->getTrendDescription()}",
                    'confidence_score' => $trend->r_squared ?? 0.7,
                    'impact_estimate' => abs($trend->growth_rate) * 100,
                    'status' => Recommendation::STATUS_PENDING,
                    'metadata' => [
                        'source' => 'trend_analysis',
                        'trend_id' => $trend->trend_id,
                    ],
                    'created_by' => auth()->id(),
                ]);

                if ($recommendation->confidence_score >= $minConfidence) {
                    $recommendations->push($recommendation);
                }
            }
        }

        return $recommendations;
    }

    /**
     * Generate recommendations from performance metrics
     */
    protected function generateFromPerformance(
        string $orgId,
        string $entityType,
        string $entityId,
        float $minConfidence
    ): Collection {
        // This would analyze current performance and generate optimization recommendations
        // Placeholder for now
        return collect();
    }

    /**
     * Execute recommendation action
     */
    protected function executeRecommendation(Recommendation $recommendation): array
    {
        // This would implement actual recommendation execution logic
        // For now, returning success placeholder
        return [
            'executed' => true,
            'message' => 'Recommendation executed successfully',
        ];
    }

    /**
     * Determine recommendation type from anomaly
     */
    protected function determineRecommendationType(Anomaly $anomaly): string
    {
        // Logic to map anomaly to recommendation type
        return match ($anomaly->metric_name) {
            'spend', 'cost' => Recommendation::TYPE_BUDGET_OPTIMIZATION,
            'cpc', 'cpm', 'cpa' => Recommendation::TYPE_BID_ADJUSTMENT,
            'clicks', 'impressions' => Recommendation::TYPE_TARGETING_REFINEMENT,
            'conversions', 'conversion_rate' => Recommendation::TYPE_CREATIVE_REFRESH,
            default => Recommendation::TYPE_OTHER,
        };
    }

    /**
     * Map anomaly severity to recommendation priority
     */
    protected function mapSeverityToPriority(string $severity): string
    {
        return match ($severity) {
            Anomaly::SEVERITY_CRITICAL => Recommendation::PRIORITY_URGENT,
            Anomaly::SEVERITY_HIGH => Recommendation::PRIORITY_HIGH,
            Anomaly::SEVERITY_MEDIUM => Recommendation::PRIORITY_MEDIUM,
            default => Recommendation::PRIORITY_LOW,
        };
    }

    /**
     * Generate recommendation title
     */
    protected function generateTitle(string $type, Anomaly $anomaly): string
    {
        $direction = $anomaly->actual_value > $anomaly->expected_value ? 'spike' : 'drop';
        return ucfirst($type) . ": Investigate {$anomaly->metric_name} {$direction}";
    }

    /**
     * Generate recommendation description
     */
    protected function generateDescription(string $type, Anomaly $anomaly): string
    {
        return "An anomaly was detected in {$anomaly->metric_name}. " . $anomaly->getDeviationDescription();
    }

    /**
     * Generate recommendation rationale
     */
    protected function generateRationale(Anomaly $anomaly): string
    {
        return "Statistical analysis detected an anomaly with {$anomaly->confidence_score * 100}% confidence. " .
               "The metric deviated from expected behavior by {$anomaly->deviation_percentage}%.";
    }

    /**
     * Estimate recommendation impact
     */
    protected function estimateImpact(Anomaly $anomaly): float
    {
        return abs($anomaly->deviation_percentage);
    }

    /**
     * Calculate helpful rate
     */
    protected function calculateHelpfulRate(string $orgId): float
    {
        $withFeedback = Recommendation::where('org_id', $orgId)
            ->whereNotNull('is_helpful')
            ->count();

        if ($withFeedback == 0) {
            return 0;
        }

        $helpful = Recommendation::where('org_id', $orgId)
            ->where('is_helpful', true)
            ->count();

        return round(($helpful / $withFeedback) * 100, 2);
    }

    /**
     * Get recently applied recommendations
     */
    protected function getRecentApplied(string $orgId, int $limit = 10): Collection
    {
        return Recommendation::where('org_id', $orgId)
            ->where('status', Recommendation::STATUS_APPLIED)
            ->with(['entity', 'appliedByUser'])
            ->latest('applied_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top impact recommendations
     */
    protected function getTopImpactRecommendations(string $orgId, int $limit = 10): Collection
    {
        return Recommendation::where('org_id', $orgId)
            ->whereNotNull('actual_impact')
            ->with(['entity'])
            ->orderBy('actual_impact', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recommendation trends
     */
    protected function getRecommendationTrends(string $orgId): array
    {
        $trends = Recommendation::where('org_id', $orgId)
            ->where('created_at', '>=', now()->subDays(90))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as applied_count', [Recommendation::STATUS_APPLIED])
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trends->map(function ($trend) {
            return [
                'date' => $trend->date,
                'total' => $trend->count,
                'applied' => $trend->applied_count,
            ];
        })->toArray();
    }

    /**
     * Get average confidence
     */
    protected function getAverageConfidence(string $orgId, $dateFrom): float
    {
        $avg = Recommendation::where('org_id', $orgId)
            ->where('created_at', '>=', $dateFrom)
            ->avg('confidence_score');

        return round($avg ?? 0, 4);
    }

    /**
     * Calculate application rate
     */
    protected function calculateApplicationRate(string $orgId, $dateFrom): float
    {
        $total = Recommendation::where('org_id', $orgId)
            ->where('created_at', '>=', $dateFrom)
            ->count();

        if ($total == 0) {
            return 0;
        }

        $applied = Recommendation::where('org_id', $orgId)
            ->where('created_at', '>=', $dateFrom)
            ->where('status', Recommendation::STATUS_APPLIED)
            ->count();

        return round(($applied / $total) * 100, 2);
    }
}
