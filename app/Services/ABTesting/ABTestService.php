<?php

namespace App\Services\ABTesting;

use App\Models\ABTesting\ABTest;
use App\Models\ABTesting\ABTestVariant;
use App\Models\ABTesting\ABTestMetric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ABTestService
{
    /**
     * Create A/B test with variants
     */
    public function createTestWithVariants(array $testData, array $variants): ABTest
    {
        DB::beginTransaction();
        try {
            $test = ABTest::create(array_merge($testData, [
                'org_id' => session('current_org_id'),
                'created_by' => auth()->id(),
            ]));

            // Create variants
            foreach ($variants as $variantData) {
                ABTestVariant::create(array_merge($variantData, [
                    'test_id' => $test->test_id,
                    'org_id' => session('current_org_id'),
                ]));
            }

            DB::commit();
            return $test->fresh(['variants']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Select variant for user (consistent hashing)
     */
    public function selectVariant(ABTest $test, string $userId): ?ABTestVariant
    {
        if ($test->status !== 'running') {
            return null;
        }

        $variants = $test->variants;

        if ($variants->isEmpty()) {
            return null;
        }

        // Use consistent hashing to ensure same user always gets same variant
        $hash = crc32($test->test_id . ':' . $userId);
        $normalizedHash = ($hash & 0xFFFFFFFF) / 0xFFFFFFFF * 100;

        $cumulativeSplit = 0;

        foreach ($variants as $variant) {
            $cumulativeSplit += $variant->traffic_split;

            if ($normalizedHash <= $cumulativeSplit) {
                return $variant;
            }
        }

        // Fallback to first variant
        return $variants->first();
    }

    /**
     * Calculate statistical significance between variants
     */
    public function calculateSignificance(ABTestVariant $control, ABTestVariant $variant, float $confidenceLevel = 95): array
    {
        $p1 = $control->impressions > 0 ? $control->conversions / $control->impressions : 0;
        $p2 = $variant->impressions > 0 ? $variant->conversions / $variant->impressions : 0;

        $n1 = $control->impressions;
        $n2 = $variant->impressions;

        if ($n1 === 0 || $n2 === 0) {
            return [
                'is_significant' => false,
                'confidence' => 0,
                'z_score' => 0,
                'p_value' => 1,
                'improvement' => 0,
            ];
        }

        // Calculate pooled proportion
        $pooledP = ($control->conversions + $variant->conversions) / ($n1 + $n2);

        // Calculate standard error
        $se = sqrt($pooledP * (1 - $pooledP) * (1/$n1 + 1/$n2));

        // Calculate Z-score
        $zScore = $se > 0 ? ($p2 - $p1) / $se : 0;

        // Calculate p-value (two-tailed test)
        $pValue = 2 * (1 - $this->normalCDF(abs($zScore)));

        // Determine significance
        $alpha = (100 - $confidenceLevel) / 100;
        $isSignificant = $pValue < $alpha;

        // Calculate improvement percentage
        $improvement = $p1 > 0 ? (($p2 - $p1) / $p1) * 100 : 0;

        return [
            'is_significant' => $isSignificant,
            'confidence' => round((1 - $pValue) * 100, 2),
            'z_score' => round($zScore, 4),
            'p_value' => round($pValue, 4),
            'improvement' => round($improvement, 2),
            'control_rate' => round($p1 * 100, 2),
            'variant_rate' => round($p2 * 100, 2),
        ];
    }

    /**
     * Calculate required sample size for test
     */
    public function calculateSampleSize(
        float $baselineRate,
        float $minimumDetectableEffect,
        float $confidenceLevel = 95,
        float $power = 80
    ): int {
        // Convert percentages to proportions
        $p1 = $baselineRate / 100;
        $p2 = $p1 * (1 + $minimumDetectableEffect / 100);

        // Z-scores for confidence level and power
        $zAlpha = $this->normalInverse(1 - (100 - $confidenceLevel) / 200);
        $zBeta = $this->normalInverse($power / 100);

        // Calculate sample size per variant
        $numerator = pow($zAlpha + $zBeta, 2) * ($p1 * (1 - $p1) + $p2 * (1 - $p2));
        $denominator = pow($p2 - $p1, 2);

        $sampleSize = $denominator > 0 ? ceil($numerator / $denominator) : 1000;

        return max($sampleSize, 100); // Minimum 100 samples
    }

    /**
     * Determine test winner based on statistical significance
     */
    public function determineWinner(ABTest $test): ?array
    {
        $control = $test->variants->firstWhere('is_control', true);
        $variants = $test->variants->where('is_control', false);

        if (!$control) {
            return null;
        }

        $winner = null;
        $maxImprovement = 0;

        foreach ($variants as $variant) {
            $significance = $this->calculateSignificance($control, $variant, $test->confidence_level);

            if ($significance['is_significant'] && $significance['improvement'] > $maxImprovement) {
                $maxImprovement = $significance['improvement'];
                $winner = [
                    'variant_id' => $variant->variant_id,
                    'variant_name' => $variant->variant_name,
                    'improvement' => $significance['improvement'],
                    'confidence' => $significance['confidence'],
                ];
            }
        }

        // If no variant significantly beats control, control wins
        if (!$winner) {
            $winner = [
                'variant_id' => $control->variant_id,
                'variant_name' => $control->variant_name,
                'improvement' => 0,
                'confidence' => 100,
            ];
        }

        return $winner;
    }

    /**
     * Get test progress
     */
    public function getTestProgress(ABTest $test): array
    {
        $totalImpressions = $test->variants->sum('impressions');
        $targetSampleSize = $test->sample_size ?? 1000;

        $progress = $targetSampleSize > 0 ? min(100, ($totalImpressions / $targetSampleSize) * 100) : 0;

        $daysRunning = $test->start_date ? now()->diffInDays($test->start_date) : 0;
        $daysRemaining = null;

        if ($test->end_date && $test->start_date) {
            $totalDays = $test->start_date->diffInDays($test->end_date);
            $daysRemaining = max(0, now()->diffInDays($test->end_date, false));
        }

        return [
            'total_impressions' => $totalImpressions,
            'target_sample_size' => $targetSampleSize,
            'progress_percentage' => round($progress, 2),
            'days_running' => $daysRunning,
            'days_remaining' => $daysRemaining,
            'is_ready_for_completion' => $progress >= 95,
        ];
    }

    /**
     * Generate test recommendations
     */
    public function generateRecommendations(ABTest $test): array
    {
        $recommendations = [];
        $progress = $this->getTestProgress($test);

        // Sample size recommendations
        if ($progress['progress_percentage'] < 50) {
            $recommendations[] = [
                'type' => 'sample_size',
                'priority' => 'medium',
                'message' => 'Test has not reached 50% of target sample size. Continue running test.',
            ];
        }

        // Duration recommendations
        if ($progress['days_running'] < 7) {
            $recommendations[] = [
                'type' => 'duration',
                'priority' => 'high',
                'message' => 'Test has been running for less than 7 days. Run for at least 1 week to account for weekly patterns.',
            ];
        }

        // Traffic split recommendations
        $totalSplit = $test->variants->sum('traffic_split');
        if ($totalSplit < 100) {
            $recommendations[] = [
                'type' => 'traffic_split',
                'priority' => 'high',
                'message' => "Traffic split totals {$totalSplit}%. Allocate remaining traffic to variants.",
            ];
        }

        // Variant performance
        $control = $test->variants->firstWhere('is_control', true);
        if ($control) {
            foreach ($test->variants->where('is_control', false) as $variant) {
                $significance = $this->calculateSignificance($control, $variant, $test->confidence_level);

                if ($significance['is_significant'] && $progress['progress_percentage'] >= 95) {
                    $recommendations[] = [
                        'type' => 'winner_found',
                        'priority' => 'high',
                        'message' => "Variant '{$variant->variant_name}' shows significant improvement. Consider completing test.",
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Export test results
     */
    public function exportResults(ABTest $test): array
    {
        $control = $test->variants->firstWhere('is_control', true);

        $variantsData = [];

        foreach ($test->variants as $variant) {
            $significance = $control && !$variant->is_control
                ? $this->calculateSignificance($control, $variant, $test->confidence_level)
                : null;

            $variantsData[] = [
                'variant_name' => $variant->variant_name,
                'is_control' => $variant->is_control,
                'traffic_split' => $variant->traffic_split,
                'impressions' => $variant->impressions,
                'conversions' => $variant->conversions,
                'conversion_rate' => $variant->impressions > 0
                    ? round(($variant->conversions / $variant->impressions) * 100, 2)
                    : 0,
                'total_revenue' => $variant->total_revenue,
                'significance' => $significance,
            ];
        }

        return [
            'test' => [
                'test_name' => $test->test_name,
                'entity_type' => $test->entity_type,
                'status' => $test->status,
                'start_date' => $test->start_date?->toDateString(),
                'end_date' => $test->end_date?->toDateString(),
                'confidence_level' => $test->confidence_level,
            ],
            'progress' => $this->getTestProgress($test),
            'variants' => $variantsData,
            'winner' => $this->determineWinner($test),
            'recommendations' => $this->generateRecommendations($test),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Normal cumulative distribution function
     */
    protected function normalCDF(float $z): float
    {
        return 0.5 * (1 + erf($z / sqrt(2)));
    }

    /**
     * Inverse normal distribution (approximation)
     */
    protected function normalInverse(float $p): float
    {
        // Rational approximation for inverse normal CDF
        if ($p <= 0) return -INF;
        if ($p >= 1) return INF;

        $c0 = 2.515517;
        $c1 = 0.802853;
        $c2 = 0.010328;
        $d1 = 1.432788;
        $d2 = 0.189269;
        $d3 = 0.001308;

        if ($p < 0.5) {
            $t = sqrt(-2 * log($p));
            return -($t - (($c2 * $t + $c1) * $t + $c0) / ((($d3 * $t + $d2) * $t + $d1) * $t + 1));
        } else {
            $t = sqrt(-2 * log(1 - $p));
            return $t - (($c2 * $t + $c1) * $t + $c0) / ((($d3 * $t + $d2) * $t + $d1) * $t + 1);
        }
    }
}
