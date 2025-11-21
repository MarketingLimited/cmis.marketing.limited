<?php

namespace App\Services\Optimization;

use App\Models\Campaign\Campaign;
use App\Models\Optimization\OptimizationInsight;
use App\Models\Optimization\OptimizationRun;
use App\Models\Optimization\BudgetAllocation;
use App\Models\Optimization\AudienceOverlap;
use App\Models\Optimization\CreativePerformance;
use App\Models\Analytics\Anomaly;
use App\Models\Analytics\Forecast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsightGenerator
{
    /**
     * Generate optimization insights for an organization.
     */
    public function generateInsights(string $orgId, ?array $campaignIds = null): array
    {
        $insights = [];

        // Generate budget optimization insights
        $insights = array_merge($insights, $this->generateBudgetInsights($orgId, $campaignIds));

        // Generate creative performance insights
        $insights = array_merge($insights, $this->generateCreativeInsights($orgId, $campaignIds));

        // Generate audience overlap insights
        $insights = array_merge($insights, $this->generateOverlapInsights($orgId, $campaignIds));

        // Generate anomaly-based insights
        $insights = array_merge($insights, $this->generateAnomalyInsights($orgId, $campaignIds));

        // Generate forecast-based insights
        $insights = array_merge($insights, $this->generateForecastInsights($orgId, $campaignIds));

        // Prioritize and deduplicate insights
        $insights = $this->prioritizeInsights($insights);

        return $insights;
    }

    /**
     * Generate budget optimization insights.
     */
    protected function generateBudgetInsights(string $orgId, ?array $campaignIds): array
    {
        $insights = [];

        // Find budget allocations with high potential
        $query = BudgetAllocation::where('org_id', $orgId)
            ->where('status', 'pending')
            ->where('confidence_level', '>=', 0.75)
            ->where('improvement_percentage', '>', 10);

        if ($campaignIds) {
            $query->whereIn('campaign_id', $campaignIds);
        }

        $allocations = $query->get();

        foreach ($allocations as $allocation) {
            $impact = $allocation->budget_change * ($allocation->expected_roas ?? 1.0);

            $insights[] = $this->createInsight([
                'org_id' => $orgId,
                'campaign_id' => $allocation->campaign_id,
                'insight_type' => 'opportunity',
                'category' => 'budget',
                'priority' => $this->determinePriority($impact, $allocation->confidence_level),
                'title' => $allocation->isIncrease()
                    ? 'Budget Increase Opportunity Detected'
                    : 'Budget Reallocation Recommended',
                'description' => $allocation->justification,
                'impact_estimate' => $impact,
                'confidence_score' => $allocation->confidence_level,
                'supporting_data' => [
                    'allocation_id' => $allocation->allocation_id,
                    'current_budget' => $allocation->current_budget,
                    'recommended_budget' => $allocation->recommended_budget,
                    'expected_roas' => $allocation->expected_roas,
                ],
                'recommendations' => [
                    [
                        'action' => 'apply_budget_allocation',
                        'params' => [
                            'allocation_id' => $allocation->allocation_id,
                            'new_budget' => $allocation->recommended_budget,
                        ],
                    ],
                ],
                'automated_action' => [
                    'type' => 'update_budget',
                    'campaign_id' => $allocation->campaign_id,
                    'new_budget' => $allocation->recommended_budget,
                ],
            ]);
        }

        return $insights;
    }

    /**
     * Generate creative performance insights.
     */
    protected function generateCreativeInsights(string $orgId, ?array $campaignIds): array
    {
        $insights = [];

        // Find fatigued creatives
        $query = CreativePerformance::where('org_id', $orgId)
            ->where('fatigue_score', '>', 0.7)
            ->where('recommendation', 'refresh');

        if ($campaignIds) {
            $query->whereIn('campaign_id', $campaignIds);
        }

        $fatigued = $query->get();

        foreach ($fatigued as $creative) {
            $insights[] = $this->createInsight([
                'org_id' => $orgId,
                'campaign_id' => $creative->campaign_id,
                'insight_type' => 'risk',
                'category' => 'creative',
                'priority' => 'high',
                'title' => 'Creative Fatigue Detected',
                'description' => sprintf(
                    'Creative has been running for %d days with declining performance (fatigue score: %.2f). Refreshing creative is recommended.',
                    $creative->freshness_days,
                    $creative->fatigue_score
                ),
                'impact_estimate' => $creative->spend * 0.2, // Potential savings
                'confidence_score' => $creative->recommendation_confidence,
                'supporting_data' => [
                    'creative_id' => $creative->creative_id,
                    'fatigue_score' => $creative->fatigue_score,
                    'performance_score' => $creative->performance_score,
                    'freshness_days' => $creative->freshness_days,
                ],
                'recommendations' => [
                    [
                        'action' => 'refresh_creative',
                        'description' => 'Create new creative variation to combat fatigue',
                    ],
                ],
            ]);
        }

        // Find high-performing creatives to scale
        $highPerformers = CreativePerformance::where('org_id', $orgId)
            ->where('performance_score', '>=', 0.8)
            ->where('recommendation', 'scale_up')
            ->limit(10)
            ->get();

        foreach ($highPerformers as $creative) {
            $insights[] = $this->createInsight([
                'org_id' => $orgId,
                'campaign_id' => $creative->campaign_id,
                'insight_type' => 'opportunity',
                'category' => 'creative',
                'priority' => 'high',
                'title' => 'High-Performing Creative Identified',
                'description' => sprintf(
                    'Creative has exceptional performance (score: %.2f, ROAS: %.2f). Consider scaling budget.',
                    $creative->performance_score,
                    $creative->roas
                ),
                'impact_estimate' => $creative->revenue * 0.5, // Potential additional revenue
                'confidence_score' => 0.85,
                'supporting_data' => [
                    'creative_id' => $creative->creative_id,
                    'performance_score' => $creative->performance_score,
                    'roas' => $creative->roas,
                    'conversions' => $creative->conversions,
                ],
                'recommendations' => [
                    [
                        'action' => 'increase_budget',
                        'description' => 'Increase budget for this creative to maximize returns',
                    ],
                ],
            ]);
        }

        return $insights;
    }

    /**
     * Generate audience overlap insights.
     */
    protected function generateOverlapInsights(string $orgId, ?array $campaignIds): array
    {
        $insights = [];

        // Find critical overlaps
        $query = AudienceOverlap::where('org_id', $orgId)
            ->where('status', 'active')
            ->where('overlap_percentage', '>=', 50);

        if ($campaignIds) {
            $query->where(function ($q) use ($campaignIds) {
                $q->whereIn('campaign_a_id', $campaignIds)
                  ->orWhereIn('campaign_b_id', $campaignIds);
            });
        }

        $overlaps = $query->get();

        foreach ($overlaps as $overlap) {
            $insights[] = $this->createInsight([
                'org_id' => $orgId,
                'campaign_id' => $overlap->campaign_a_id,
                'insight_type' => 'risk',
                'category' => 'targeting',
                'priority' => $overlap->severity === 'critical' ? 'critical' : 'high',
                'title' => 'Significant Audience Overlap Detected',
                'description' => sprintf(
                    'Two campaigns are competing for the same audience (%.1f%% overlap), leading to wasted spend of $%.2f.',
                    $overlap->overlap_percentage,
                    $overlap->wasted_spend_estimate
                ),
                'impact_estimate' => $overlap->wasted_spend_estimate,
                'confidence_score' => 0.9,
                'supporting_data' => [
                    'overlap_id' => $overlap->overlap_id,
                    'campaign_a_id' => $overlap->campaign_a_id,
                    'campaign_b_id' => $overlap->campaign_b_id,
                    'overlap_percentage' => $overlap->overlap_percentage,
                    'frequency_inflation' => $overlap->frequency_inflation,
                ],
                'recommendations' => $overlap->recommendations,
            ]);
        }

        return $insights;
    }

    /**
     * Generate anomaly-based insights.
     */
    protected function generateAnomalyInsights(string $orgId, ?array $campaignIds): array
    {
        $insights = [];

        // Find unacknowledged anomalies
        $query = Anomaly::where('org_id', $orgId)
            ->where('status', 'detected')
            ->where('severity', '!=', 'low')
            ->where('detected_at', '>=', now()->subDays(7));

        if ($campaignIds) {
            $query->whereIn('entity_id', $campaignIds);
        }

        $anomalies = $query->get();

        foreach ($anomalies as $anomaly) {
            $insights[] = $this->createInsight([
                'org_id' => $orgId,
                'campaign_id' => $anomaly->entity_type === 'campaign' ? $anomaly->entity_id : null,
                'insight_type' => 'anomaly',
                'category' => $this->mapMetricToCategory($anomaly->metric_name),
                'priority' => $anomaly->severity,
                'title' => 'Performance Anomaly Detected',
                'description' => sprintf(
                    '%s for %s is %.1f standard deviations from expected (%.2f expected, %.2f actual).',
                    $anomaly->metric_name,
                    $anomaly->entity_type,
                    $anomaly->deviation_magnitude,
                    $anomaly->expected_value,
                    $anomaly->actual_value
                ),
                'impact_estimate' => abs($anomaly->actual_value - $anomaly->expected_value),
                'confidence_score' => $anomaly->confidence,
                'supporting_data' => [
                    'anomaly_id' => $anomaly->anomaly_id,
                    'metric_name' => $anomaly->metric_name,
                    'expected_value' => $anomaly->expected_value,
                    'actual_value' => $anomaly->actual_value,
                ],
                'recommendations' => [
                    [
                        'action' => 'investigate_anomaly',
                        'description' => 'Review campaign settings and recent changes',
                    ],
                ],
                'automated_action' => [
                    'type' => 'acknowledge_anomaly',
                    'anomaly_id' => $anomaly->anomaly_id,
                ],
            ]);
        }

        return $insights;
    }

    /**
     * Generate forecast-based insights.
     */
    protected function generateForecastInsights(string $orgId, ?array $campaignIds): array
    {
        $insights = [];

        // Find forecasts predicting significant changes
        $query = Forecast::where('org_id', $orgId)
            ->where('forecast_date', '>=', now())
            ->where('forecast_date', '<=', now()->addDays(7));

        if ($campaignIds) {
            $query->whereIn('entity_id', $campaignIds);
        }

        $forecasts = $query->get();

        foreach ($forecasts as $forecast) {
            // Calculate expected change from current baseline
            $currentValue = $this->getCurrentMetricValue($forecast->entity_id, $forecast->metric_name);
            $changePercent = $currentValue > 0
                ? (($forecast->predicted_value - $currentValue) / $currentValue) * 100
                : 0;

            // Only generate insights for significant changes (>15%)
            if (abs($changePercent) >= 15) {
                $insights[] = $this->createInsight([
                    'org_id' => $orgId,
                    'campaign_id' => $forecast->entity_type === 'campaign' ? $forecast->entity_id : null,
                    'insight_type' => $changePercent > 0 ? 'opportunity' : 'risk',
                    'category' => $this->mapMetricToCategory($forecast->metric_name),
                    'priority' => abs($changePercent) > 30 ? 'high' : 'medium',
                    'title' => $changePercent > 0
                        ? 'Positive Trend Forecast'
                        : 'Negative Trend Forecast',
                    'description' => sprintf(
                        '%s is forecasted to %s by %.1f%% in the next 7 days.',
                        $forecast->metric_name,
                        $changePercent > 0 ? 'increase' : 'decrease',
                        abs($changePercent)
                    ),
                    'impact_estimate' => abs($forecast->predicted_value - $currentValue),
                    'confidence_score' => $forecast->confidence_score,
                    'supporting_data' => [
                        'forecast_id' => $forecast->forecast_id,
                        'current_value' => $currentValue,
                        'predicted_value' => $forecast->predicted_value,
                        'change_percent' => $changePercent,
                    ],
                    'recommendations' => [
                        [
                            'action' => $changePercent > 0 ? 'prepare_to_scale' : 'prepare_contingency',
                            'description' => $changePercent > 0
                                ? 'Prepare budget allocation to capitalize on positive trend'
                                : 'Review campaign settings to mitigate negative trend',
                        ],
                    ],
                    'expires_at' => $forecast->forecast_date,
                ]);
            }
        }

        return $insights;
    }

    /**
     * Create insight record.
     */
    protected function createInsight(array $data): OptimizationInsight
    {
        return OptimizationInsight::create(array_merge([
            'status' => 'pending',
            'generated_at' => now(),
        ], $data));
    }

    /**
     * Prioritize insights and remove duplicates.
     */
    protected function prioritizeInsights(array $insights): array
    {
        // Sort by priority and impact
        usort($insights, function ($a, $b) {
            $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            $aPriority = $priorityOrder[$a->priority] ?? 5;
            $bPriority = $priorityOrder[$b->priority] ?? 5;

            if ($aPriority !== $bPriority) {
                return $aPriority <=> $bPriority;
            }

            return ($b->impact_estimate ?? 0) <=> ($a->impact_estimate ?? 0);
        });

        return $insights;
    }

    /**
     * Determine priority based on impact and confidence.
     */
    protected function determinePriority(float $impact, float $confidence): string
    {
        $score = $impact * $confidence;

        if ($score >= 1000) {
            return 'critical';
        } elseif ($score >= 500) {
            return 'high';
        } elseif ($score >= 100) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Map metric name to insight category.
     */
    protected function mapMetricToCategory(string $metricName): string
    {
        $metricName = strtolower($metricName);

        if (str_contains($metricName, 'budget') || str_contains($metricName, 'spend')) {
            return 'budget';
        } elseif (str_contains($metricName, 'ctr') || str_contains($metricName, 'click')) {
            return 'creative';
        } elseif (str_contains($metricName, 'audience') || str_contains($metricName, 'reach')) {
            return 'targeting';
        } elseif (str_contains($metricName, 'bid') || str_contains($metricName, 'cpc')) {
            return 'bidding';
        }

        return 'platform';
    }

    /**
     * Get current metric value for comparison.
     */
    protected function getCurrentMetricValue(string $entityId, string $metricName): float
    {
        // Fetch latest metric value from analytics
        $metric = DB::table('cmis_analytics.campaign_metrics')
            ->where('campaign_id', $entityId)
            ->orderBy('date', 'desc')
            ->first();

        if (!$metric) {
            return 0;
        }

        return $metric->{strtolower($metricName)} ?? 0;
    }
}
