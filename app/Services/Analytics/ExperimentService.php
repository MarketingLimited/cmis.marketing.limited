<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Experiment;
use App\Models\Analytics\ExperimentVariant;
use App\Models\Analytics\ExperimentResult;
use App\Models\Analytics\ExperimentEvent;
use Illuminate\Support\Facades\DB;

/**
 * Experiment Service (Phase 15)
 *
 * Handles A/B testing experiment lifecycle, variant management,
 * event tracking, and statistical analysis
 */
class ExperimentService
{
    /**
     * Create a new experiment with variants
     */
    public function createExperiment(string $orgId, string $userId, array $data): Experiment
    {
        $experiment = Experiment::create([
            'org_id' => $orgId,
            'created_by' => $userId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'experiment_type' => $data['experiment_type'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'metric' => $data['metric'],
            'metrics' => $data['metrics'] ?? null,
            'hypothesis' => $data['hypothesis'] ?? null,
            'duration_days' => $data['duration_days'] ?? 14,
            'sample_size_per_variant' => $data['sample_size_per_variant'] ?? 1000,
            'confidence_level' => $data['confidence_level'] ?? 95.00,
            'minimum_detectable_effect' => $data['minimum_detectable_effect'] ?? 5.00,
            'traffic_allocation' => $data['traffic_allocation'] ?? 'equal',
            'config' => $data['config'] ?? [],
            'status' => 'draft'
        ]);

        // Create control variant
        $experiment->variants()->create([
            'name' => 'Control',
            'description' => 'Control variant (original)',
            'is_control' => true,
            'traffic_percentage' => 50.00,
            'config' => $data['control_config'] ?? [],
            'status' => 'active'
        ]);

        return $experiment->fresh(['variants']);
    }

    /**
     * Add variant to experiment
     */
    public function addVariant(Experiment $experiment, array $data): ExperimentVariant
    {
        if ($experiment->status !== 'draft') {
            throw new \RuntimeException('Can only add variants to draft experiments');
        }

        return $experiment->variants()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_control' => false,
            'traffic_percentage' => $data['traffic_percentage'] ?? 50.00,
            'config' => $data['config'],
            'status' => 'active'
        ]);
    }

    /**
     * Record an experiment event
     */
    public function recordEvent(
        string $experimentId,
        string $variantId,
        string $eventType,
        array $data = []
    ): ExperimentEvent {
        $event = ExperimentEvent::create([
            'experiment_id' => $experimentId,
            'variant_id' => $variantId,
            'event_type' => $eventType,
            'user_id' => $data['user_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'value' => $data['value'] ?? null,
            'properties' => $data['properties'] ?? null,
            'occurred_at' => now()
        ]);

        // Update variant metrics
        $this->updateVariantMetrics($variantId, $eventType, $data['value'] ?? 0);

        return $event;
    }

    /**
     * Update variant metrics based on event
     */
    protected function updateVariantMetrics(string $variantId, string $eventType, float $value): void
    {
        $variant = ExperimentVariant::find($variantId);

        if (!$variant) {
            return;
        }

        switch ($eventType) {
            case 'impression':
                $variant->increment('impressions');
                break;
            case 'click':
                $variant->increment('clicks');
                break;
            case 'conversion':
                $variant->increment('conversions');
                if ($value > 0) {
                    $variant->increment('revenue', $value);
                }
                break;
        }

        $variant->calculateConversionRate();
    }

    /**
     * Aggregate daily results for all variants
     */
    public function aggregateDailyResults(Experiment $experiment, \DateTime $date = null): void
    {
        $date = $date ?? new \DateTime();

        foreach ($experiment->variants as $variant) {
            $events = ExperimentEvent::where('variant_id', $variant->variant_id)
                ->whereDate('occurred_at', $date)
                ->get();

            $impressions = $events->where('event_type', 'impression')->count();
            $clicks = $events->where('event_type', 'click')->count();
            $conversions = $events->where('event_type', 'conversion')->count();
            $revenue = $events->where('event_type', 'conversion')->sum('value');

            $result = ExperimentResult::updateOrCreate(
                [
                    'experiment_id' => $experiment->experiment_id,
                    'variant_id' => $variant->variant_id,
                    'date' => $date
                ],
                [
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'revenue' => $revenue,
                    'spend' => 0 // Would be calculated from actual ad spend
                ]
            );

            $result->calculateMetrics();
        }
    }

    /**
     * Calculate statistical significance between variants
     */
    public function calculateStatisticalSignificance(Experiment $experiment): array
    {
        $control = $experiment->controlVariant();

        if (!$control) {
            throw new \RuntimeException('No control variant found');
        }

        $results = [];

        foreach ($experiment->variants()->where('is_control', false)->get() as $variant) {
            $significance = $this->zTest(
                $control->conversions,
                $control->impressions,
                $variant->conversions,
                $variant->impressions,
                $experiment->confidence_level
            );

            $results[$variant->variant_id] = [
                'variant_name' => $variant->name,
                'p_value' => $significance['p_value'],
                'z_score' => $significance['z_score'],
                'is_significant' => $significance['is_significant'],
                'improvement' => $significance['improvement_percentage'],
                'confidence_interval' => $significance['confidence_interval']
            ];

            // Update variant with results
            $variant->update([
                'improvement_over_control' => $significance['improvement_percentage'],
                'confidence_interval_lower' => $significance['confidence_interval']['lower'],
                'confidence_interval_upper' => $significance['confidence_interval']['upper']
            ]);
        }

        return $results;
    }

    /**
     * Z-test for proportion difference (simplified implementation)
     */
    protected function zTest(
        int $controlConversions,
        int $controlImpressions,
        int $variantConversions,
        int $variantImpressions,
        float $confidenceLevel
    ): array {
        if ($controlImpressions === 0 || $variantImpressions === 0) {
            return [
                'p_value' => 1.0,
                'z_score' => 0.0,
                'is_significant' => false,
                'improvement_percentage' => 0.0,
                'confidence_interval' => ['lower' => 0.0, 'upper' => 0.0]
            ];
        }

        $p1 = $controlConversions / $controlImpressions;
        $p2 = $variantConversions / $variantImpressions;

        // Pooled proportion
        $p_pool = ($controlConversions + $variantConversions) / ($controlImpressions + $variantImpressions);

        // Standard error
        $se = sqrt($p_pool * (1 - $p_pool) * ((1 / $controlImpressions) + (1 / $variantImpressions)));

        // Z-score
        $z = $se > 0 ? ($p2 - $p1) / $se : 0;

        // P-value (two-tailed) - simplified approximation
        $p_value = 2 * (1 - $this->normalCDF(abs($z)));

        // Critical value based on confidence level
        $alpha = 1 - ($confidenceLevel / 100);
        $critical_z = $this->getZCritical($alpha);

        $is_significant = abs($z) > $critical_z;

        // Improvement percentage
        $improvement = $p1 > 0 ? (($p2 - $p1) / $p1) * 100 : 0;

        // Confidence interval for difference
        $se_diff = sqrt(($p1 * (1 - $p1) / $controlImpressions) + ($p2 * (1 - $p2) / $variantImpressions));
        $margin = $critical_z * $se_diff;

        return [
            'p_value' => round($p_value, 4),
            'z_score' => round($z, 4),
            'is_significant' => $is_significant,
            'improvement_percentage' => round($improvement, 2),
            'confidence_interval' => [
                'lower' => round($p2 - $margin, 4),
                'upper' => round($p2 + $margin, 4)
            ]
        ];
    }

    /**
     * Normal CDF approximation (simplified)
     */
    protected function normalCDF(float $z): float
    {
        return 0.5 * (1 + $this->erf($z / sqrt(2)));
    }

    /**
     * Error function approximation
     */
    protected function erf(float $x): float
    {
        // Abramowitz and Stegun approximation
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;

        $sign = $x < 0 ? -1 : 1;
        $x = abs($x);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return $sign * $y;
    }

    /**
     * Get Z critical value for confidence level
     */
    protected function getZCritical(float $alpha): float
    {
        // Common critical values
        $criticalValues = [
            0.10 => 1.645, // 90% confidence
            0.05 => 1.960, // 95% confidence
            0.01 => 2.576, // 99% confidence
        ];

        return $criticalValues[$alpha] ?? 1.960;
    }

    /**
     * Determine experiment winner
     */
    public function determineWinner(Experiment $experiment): ?ExperimentVariant
    {
        $significanceResults = $this->calculateStatisticalSignificance($experiment);

        $winner = null;
        $maxImprovement = 0;

        foreach ($significanceResults as $variantId => $result) {
            if ($result['is_significant'] && $result['improvement'] > $maxImprovement) {
                $maxImprovement = $result['improvement'];
                $winner = ExperimentVariant::find($variantId);
            }
        }

        return $winner;
    }

    /**
     * Get experiment performance summary
     */
    public function getPerformanceSummary(Experiment $experiment): array
    {
        $variants = $experiment->variants()->with('results')->get();

        $summary = [
            'experiment' => [
                'id' => $experiment->experiment_id,
                'name' => $experiment->name,
                'status' => $experiment->status,
                'progress' => $experiment->getProgressPercentage(),
                'remaining_days' => $experiment->getRemainingDays()
            ],
            'variants' => [],
            'winner' => null
        ];

        foreach ($variants as $variant) {
            $summary['variants'][] = [
                'id' => $variant->variant_id,
                'name' => $variant->name,
                'is_control' => $variant->is_control,
                'performance' => $variant->getPerformanceSummary(),
                'is_winning' => $variant->isWinning()
            ];
        }

        if ($experiment->status === 'completed' && $experiment->winner_variant_id) {
            $winner = $experiment->winnerVariant();
            if ($winner) {
                $summary['winner'] = [
                    'variant_id' => $winner->variant_id,
                    'name' => $winner->name,
                    'improvement' => $winner->improvement_over_control,
                    'significance' => $experiment->statistical_significance
                ];
            }
        }

        return $summary;
    }

    /**
     * Get time-series data for experiment
     */
    public function getTimeSeriesData(Experiment $experiment): array
    {
        $results = ExperimentResult::where('experiment_id', $experiment->experiment_id)
            ->with('variant')
            ->orderBy('date')
            ->get()
            ->groupBy('variant_id');

        $timeSeriesData = [];

        foreach ($results as $variantId => $variantResults) {
            $variant = ExperimentVariant::find($variantId);

            $timeSeriesData[$variantId] = [
                'variant_name' => $variant->name,
                'is_control' => $variant->is_control,
                'data' => $variantResults->map(fn($r) => [
                    'date' => $r->date->format('Y-m-d'),
                    'impressions' => $r->impressions,
                    'clicks' => $r->clicks,
                    'conversions' => $r->conversions,
                    'conversion_rate' => (float) $r->conversion_rate,
                    'spend' => (float) $r->spend,
                    'revenue' => (float) $r->revenue,
                    'roi' => (float) $r->roi
                ])
            ];
        }

        return $timeSeriesData;
    }
}
