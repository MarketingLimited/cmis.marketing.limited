<?php

namespace App\Services;

use App\Models\Experiment\Experiment;
use App\Models\Experiment\ExperimentVariant;
use App\Models\Experiment\ExperimentResult;
use App\Models\Experiment\ExperimentEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ABTestingService
 *
 * Handles A/B testing for ad campaigns using Eloquent models
 * Implements Sprint 4.6: A/B Testing
 *
 * Features:
 * - Create and manage A/B tests
 * - Multiple variation support
 * - Statistical significance testing (Chi-square)
 * - Automatic winner selection
 * - Test history and reporting
 * - Full RLS compliance through Eloquent models
 */
class ABTestingService
{
    /**
     * Create a new A/B test
     *
     * @param array $data
     * @return array
     */
    public function createABTest(array $data): array
    {
        try {
            DB::beginTransaction();

            // Create experiment
            $experiment = Experiment::create([
                'experiment_id' => (string) Str::uuid(),
                'org_id' => $data['org_id'] ?? auth()->user()->org_id,
                'created_by' => auth()->user()->user_id ?? $data['created_by'] ?? null,
                'name' => $data['test_name'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'experiment_type' => $data['test_type'] ?? 'creative',
                'entity_type' => $data['entity_type'] ?? 'ad',
                'entity_id' => $data['entity_id'] ?? null,
                'metric' => $data['metric_to_optimize'] ?? 'ctr',
                'metrics' => $data['metrics'] ?? null,
                'hypothesis' => $data['hypothesis'] ?? null,
                'status' => 'draft',
                'duration_days' => $data['test_duration_days'] ?? 7,
                'sample_size_per_variant' => $data['min_sample_size'] ?? 1000,
                'confidence_level' => $data['confidence_level'] ?? 95.00,
                'minimum_detectable_effect' => $data['minimum_detectable_effect'] ?? 5.00,
                'traffic_allocation' => $data['traffic_allocation'] ?? 'equal',
                'config' => $data['config'] ?? [],
            ]);

            // Create initial variations if provided
            if (!empty($data['variations'])) {
                foreach ($data['variations'] as $index => $variation) {
                    $this->addVariation(
                        $experiment->experiment_id,
                        $variation,
                        $index === 0 // First variation is control
                    );
                }
            }

            DB::commit();

            // Reload with relationships
            $experiment->load('variants');

            return [
                'success' => true,
                'message' => 'A/B test created successfully',
                'data' => [
                    'experiment_id' => $experiment->experiment_id,
                    'name' => $experiment->name,
                    'experiment_type' => $experiment->experiment_type,
                    'status' => $experiment->status,
                    'metric' => $experiment->metric,
                    'duration_days' => $experiment->duration_days,
                    'variations' => $experiment->variants->map(function ($variant) {
                        return [
                            'variant_id' => $variant->variant_id,
                            'name' => $variant->name,
                            'is_control' => $variant->is_control,
                            'traffic_percentage' => $variant->traffic_percentage,
                        ];
                    }),
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to create A/B test',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add a variation to an A/B test
     *
     * @param string $experimentId
     * @param array $variationData
     * @param bool $isControl
     * @return array
     */
    public function addVariation(string $experimentId, array $variationData, bool $isControl = false): array
    {
        try {
            $experiment = Experiment::find($experimentId);

            if (!$experiment) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($experiment->status !== 'draft') {
                return ['success' => false, 'message' => 'Cannot add variations to a test that is not in draft status'];
            }

            // Create variant
            $variant = ExperimentVariant::create([
                'variant_id' => (string) Str::uuid(),
                'experiment_id' => $experimentId,
                'name' => $variationData['variation_name'] ?? $variationData['name'],
                'description' => $variationData['description'] ?? null,
                'is_control' => $isControl,
                'traffic_percentage' => $variationData['traffic_allocation'] ?? $variationData['traffic_percentage'] ?? 50,
                'config' => $variationData['config'] ?? [],
                'status' => 'active',
            ]);

            return [
                'success' => true,
                'message' => 'Variation added successfully',
                'data' => [
                    'variant_id' => $variant->variant_id,
                    'name' => $variant->name,
                    'is_control' => $variant->is_control
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add variation',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Start an A/B test
     *
     * @param string $experimentId
     * @return array
     */
    public function startTest(string $experimentId): array
    {
        try {
            $experiment = Experiment::with('variants')->find($experimentId);

            if (!$experiment) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($experiment->status !== 'draft') {
                return ['success' => false, 'message' => 'Test can only be started from draft status'];
            }

            // Validate test has at least 2 variations
            if ($experiment->variants->count() < 2) {
                return [
                    'success' => false,
                    'message' => 'Test must have at least 2 variations to start'
                ];
            }

            // Update test status
            $startDate = now();
            $endDate = now()->addDays($experiment->duration_days);

            $experiment->update([
                'status' => 'running',
                'started_at' => $startDate,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);

            // Clear cache
            Cache::forget("ab_test:{$experimentId}");

            return [
                'success' => true,
                'message' => 'A/B test started successfully',
                'data' => [
                    'experiment_id' => $experimentId,
                    'status' => 'running',
                    'started_at' => $startDate->toDateTimeString(),
                    'end_date' => $endDate->toDateString()
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to start test',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Stop an A/B test
     *
     * @param string $experimentId
     * @param string|null $reason
     * @return array
     */
    public function stopTest(string $experimentId, ?string $reason = null): array
    {
        try {
            $experiment = Experiment::find($experimentId);

            if (!$experiment) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($experiment->status !== 'running') {
                return ['success' => false, 'message' => 'Only running tests can be stopped'];
            }

            // Update test status
            $experiment->update([
                'status' => 'paused',
                'completed_at' => now(),
            ]);

            // Clear cache
            Cache::forget("ab_test:{$experimentId}");

            return [
                'success' => true,
                'message' => 'A/B test stopped successfully',
                'data' => [
                    'experiment_id' => $experimentId,
                    'status' => 'paused',
                    'completed_at' => now()->toDateTimeString()
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to stop test',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get test results with statistical analysis
     *
     * @param string $experimentId
     * @return array
     */
    public function getTestResults(string $experimentId): array
    {
        try {
            $cacheKey = "ab_test_results:{$experimentId}";

            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($experimentId) {
                $experiment = Experiment::with(['variants', 'winnerVariant'])->find($experimentId);

                if (!$experiment) {
                    return ['success' => false, 'message' => 'A/B test not found'];
                }

                // Get variations with their performance metrics
                $variations = $this->getVariationResults($experimentId);

                // Calculate statistical significance
                $statisticalAnalysis = $this->calculateStatisticalSignificance($variations, $experiment->metric);

                // Determine if test has reached minimum sample size
                $totalSampleSize = array_sum(array_column($variations, 'impressions'));
                $hasMinimumSample = $totalSampleSize >= $experiment->sample_size_per_variant;

                // Calculate test progress
                $progress = $this->calculateTestProgress($experiment);

                // Identify winning variation if test is complete
                $winner = null;
                if ($experiment->status === 'completed' && $statisticalAnalysis['is_significant']) {
                    $winner = $this->identifyWinner($variations, $experiment->metric);
                }

                return [
                    'success' => true,
                    'data' => [
                        'test' => [
                            'experiment_id' => $experiment->experiment_id,
                            'name' => $experiment->name,
                            'experiment_type' => $experiment->experiment_type,
                            'status' => $experiment->status,
                            'metric' => $experiment->metric,
                            'started_at' => $experiment->started_at,
                            'end_date' => $experiment->end_date,
                            'completed_at' => $experiment->completed_at
                        ],
                        'progress' => $progress,
                        'variations' => $variations,
                        'statistical_analysis' => $statisticalAnalysis,
                        'has_minimum_sample' => $hasMinimumSample,
                        'winner' => $winner,
                        'recommendations' => $this->generateRecommendations($experiment, $variations, $statisticalAnalysis)
                    ]
                ];
            });

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get test results',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get variation results with metrics
     *
     * @param string $experimentId
     * @return array
     */
    protected function getVariationResults(string $experimentId): array
    {
        $variants = ExperimentVariant::where('experiment_id', $experimentId)->get();

        $results = [];

        foreach ($variants as $variant) {
            // Calculate key metrics
            $ctr = $variant->impressions > 0 ? ($variant->clicks / $variant->impressions) * 100 : 0;
            $conversionRate = $variant->clicks > 0 ? ($variant->conversions / $variant->clicks) * 100 : 0;
            $cpa = $variant->conversions > 0 ? $variant->spend / $variant->conversions : 0;
            $roas = $variant->spend > 0 ? $variant->revenue / $variant->spend : 0;
            $cpc = $variant->clicks > 0 ? $variant->spend / $variant->clicks : 0;
            $cpm = $variant->impressions > 0 ? ($variant->spend / $variant->impressions) * 1000 : 0;

            $results[] = [
                'variant_id' => $variant->variant_id,
                'name' => $variant->name,
                'is_control' => $variant->is_control,
                'traffic_percentage' => $variant->traffic_percentage,
                'metrics' => [
                    'impressions' => $variant->impressions,
                    'clicks' => $variant->clicks,
                    'conversions' => $variant->conversions,
                    'spend' => round($variant->spend, 2),
                    'revenue' => round($variant->revenue, 2),
                    'ctr' => round($ctr, 2),
                    'conversion_rate' => round($conversionRate, 2),
                    'cpa' => round($cpa, 2),
                    'roas' => round($roas, 2),
                    'cpc' => round($cpc, 2),
                    'cpm' => round($cpm, 2)
                ],
                'impressions' => $variant->impressions,
                'clicks' => $variant->clicks,
                'conversions' => $variant->conversions
            ];
        }

        return $results;
    }

    /**
     * Calculate statistical significance using Chi-square test
     *
     * @param array $variations
     * @param string $metric
     * @return array
     */
    protected function calculateStatisticalSignificance(array $variations, string $metric): array
    {
        if (count($variations) < 2) {
            return [
                'is_significant' => false,
                'confidence_level' => 0,
                'message' => 'Need at least 2 variations to calculate significance'
            ];
        }

        // Find control variation
        $control = null;
        $testVariations = [];

        foreach ($variations as $variation) {
            if ($variation['is_control']) {
                $control = $variation;
            } else {
                $testVariations[] = $variation;
            }
        }

        if (!$control) {
            $control = $variations[0];
            $testVariations = array_slice($variations, 1);
        }

        // Get metric values for comparison
        $controlValue = $control['metrics'][$metric] ?? 0;
        $controlSample = $this->getSampleSize($control, $metric);

        $results = [];

        foreach ($testVariations as $variation) {
            $variationValue = $variation['metrics'][$metric] ?? 0;
            $variationSample = $this->getSampleSize($variation, $metric);

            // Calculate improvement
            $improvement = $controlValue > 0 ? (($variationValue - $controlValue) / $controlValue) * 100 : 0;

            // Chi-square test for conversion rates
            $controlConversions = $control['conversions'];
            $controlImpressions = $control['impressions'];
            $variantConversions = $variation['conversions'];
            $variantImpressions = $variation['impressions'];

            $totalImpressions = $controlImpressions + $variantImpressions;
            $totalConversions = $controlConversions + $variantConversions;

            if ($totalImpressions > 0 && $totalConversions > 0) {
                $expectedControlConversions = $totalConversions * ($controlImpressions / $totalImpressions);
                $expectedVariantConversions = $totalConversions * ($variantImpressions / $totalImpressions);

                if ($expectedControlConversions > 0 && $expectedVariantConversions > 0) {
                    $chiSquare =
                        pow($controlConversions - $expectedControlConversions, 2) / $expectedControlConversions +
                        pow($variantConversions - $expectedVariantConversions, 2) / $expectedVariantConversions;

                    // Chi-square critical value for 95% confidence (1 degree of freedom) is 3.841
                    $isSignificant = $chiSquare > 3.841 && abs($improvement) > 5;
                    $confidence = $isSignificant ? 95 : ($chiSquare / 3.841) * 95;
                } else {
                    $chiSquare = 0;
                    $isSignificant = false;
                    $confidence = 0;
                }
            } else {
                $chiSquare = 0;
                $isSignificant = false;
                $confidence = 0;
            }

            $results[] = [
                'variant_name' => $variation['name'],
                'control_value' => round($controlValue, 2),
                'variation_value' => round($variationValue, 2),
                'improvement' => round($improvement, 2),
                'is_significant' => $isSignificant,
                'confidence' => round($confidence, 2),
                'chi_square' => round($chiSquare, 4),
                'sample_size' => $variationSample
            ];
        }

        // Overall significance (if any variation is significant)
        $overallSignificance = !empty(array_filter($results, fn($r) => $r['is_significant']));

        return [
            'is_significant' => $overallSignificance,
            'comparisons' => $results,
            'message' => $overallSignificance
                ? 'Test has reached statistical significance'
                : 'Test has not reached statistical significance yet'
        ];
    }

    /**
     * Get sample size for a variation based on metric
     *
     * @param array $variation
     * @param string $metric
     * @return int
     */
    protected function getSampleSize(array $variation, string $metric): int
    {
        switch ($metric) {
            case 'ctr':
                return $variation['impressions'];
            case 'conversion_rate':
            case 'cpc':
                return $variation['clicks'];
            case 'cpa':
            case 'roas':
                return $variation['conversions'];
            default:
                return $variation['impressions'];
        }
    }

    /**
     * Calculate test progress
     *
     * @param Experiment $experiment
     * @return array
     */
    protected function calculateTestProgress(Experiment $experiment): array
    {
        if ($experiment->status !== 'running') {
            return [
                'percent_complete' => $experiment->status === 'completed' ? 100 : 0,
                'days_elapsed' => 0,
                'days_remaining' => 0
            ];
        }

        $startDate = Carbon::parse($experiment->started_at);
        $endDate = Carbon::parse($experiment->end_date);
        $now = now();

        $totalDays = $startDate->diffInDays($endDate);
        $daysElapsed = $startDate->diffInDays($now);
        $daysRemaining = max(0, $now->diffInDays($endDate));

        $percentComplete = $totalDays > 0 ? min(100, ($daysElapsed / $totalDays) * 100) : 0;

        return [
            'percent_complete' => round($percentComplete, 1),
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysRemaining,
            'total_days' => $totalDays
        ];
    }

    /**
     * Identify winning variation
     *
     * @param array $variations
     * @param string $metric
     * @return array|null
     */
    protected function identifyWinner(array $variations, string $metric): ?array
    {
        if (empty($variations)) {
            return null;
        }

        // Sort by metric (ascending for cost metrics, descending for performance metrics)
        $costMetrics = ['cpa', 'cpc', 'cpm'];
        $ascending = in_array($metric, $costMetrics);

        usort($variations, function ($a, $b) use ($metric, $ascending) {
            $aValue = $a['metrics'][$metric] ?? 0;
            $bValue = $b['metrics'][$metric] ?? 0;
            return $ascending ? ($aValue <=> $bValue) : ($bValue <=> $aValue);
        });

        $winner = $variations[0];

        return [
            'variant_id' => $winner['variant_id'],
            'name' => $winner['name'],
            'winning_metric' => $metric,
            'winning_value' => $winner['metrics'][$metric],
            'confidence' => 'High'
        ];
    }

    /**
     * Generate recommendations based on test results
     *
     * @param Experiment $experiment
     * @param array $variations
     * @param array $statisticalAnalysis
     * @return array
     */
    protected function generateRecommendations(Experiment $experiment, array $variations, array $statisticalAnalysis): array
    {
        $recommendations = [];

        // Check if test should continue
        if ($experiment->status === 'running') {
            if ($statisticalAnalysis['is_significant']) {
                $recommendations[] = [
                    'type' => 'action',
                    'priority' => 'high',
                    'message' => 'Test has reached statistical significance. Consider stopping the test and implementing the winner.',
                    'action' => 'stop_test'
                ];
            } else {
                $recommendations[] = [
                    'type' => 'info',
                    'priority' => 'medium',
                    'message' => 'Test has not reached statistical significance yet. Continue running for more conclusive results.',
                    'action' => 'continue'
                ];
            }
        }

        // Check sample size
        $totalImpressions = array_sum(array_column($variations, 'impressions'));
        if ($totalImpressions < $experiment->sample_size_per_variant) {
            $recommendations[] = [
                'type' => 'warning',
                'priority' => 'medium',
                'message' => 'Test has not reached minimum sample size. Results may not be reliable yet.',
                'action' => 'increase_budget'
            ];
        }

        // Performance insights
        if (!empty($statisticalAnalysis['comparisons'])) {
            $bestImprovement = max(array_column($statisticalAnalysis['comparisons'], 'improvement'));
            if ($bestImprovement > 20) {
                $recommendations[] = [
                    'type' => 'insight',
                    'priority' => 'high',
                    'message' => "One variation shows {$bestImprovement}% improvement over control. Strong performance detected!",
                    'action' => 'review_winner'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Select winner and apply to campaign
     *
     * @param string $experimentId
     * @param string|null $variantId
     * @return array
     */
    public function selectWinner(string $experimentId, ?string $variantId = null): array
    {
        try {
            $experiment = Experiment::with('variants')->find($experimentId);

            if (!$experiment) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            // If no variation specified, auto-select based on metric
            if (!$variantId) {
                $results = $this->getTestResults($experimentId);
                if (!$results['success'] || !$results['data']['winner']) {
                    return ['success' => false, 'message' => 'Cannot determine winner automatically'];
                }
                $variantId = $results['data']['winner']['variant_id'];
            }

            // Get winning variation
            $winner = ExperimentVariant::find($variantId);

            if (!$winner) {
                return ['success' => false, 'message' => 'Variation not found'];
            }

            // Update test status
            $experiment->update([
                'status' => 'completed',
                'winner_variant_id' => $variantId,
                'completed_at' => now(),
            ]);

            // Clear cache
            Cache::forget("ab_test:{$experimentId}");
            Cache::forget("ab_test_results:{$experimentId}");

            return [
                'success' => true,
                'message' => 'Winner selected successfully',
                'data' => [
                    'experiment_id' => $experimentId,
                    'winner_variant_id' => $variantId,
                    'winner_name' => $winner->name,
                    'message' => 'Apply this winning variation to your active campaigns for optimal performance'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to select winner',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extend test duration
     *
     * @param string $experimentId
     * @param int $additionalDays
     * @return array
     */
    public function extendTest(string $experimentId, int $additionalDays): array
    {
        try {
            $experiment = Experiment::find($experimentId);

            if (!$experiment) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($experiment->status !== 'running') {
                return ['success' => false, 'message' => 'Only running tests can be extended'];
            }

            $newEndDate = Carbon::parse($experiment->end_date)->addDays($additionalDays);

            $experiment->update([
                'end_date' => $newEndDate->toDateString(),
                'duration_days' => $experiment->duration_days + $additionalDays,
            ]);

            Cache::forget("ab_test:{$experimentId}");

            return [
                'success' => true,
                'message' => 'Test duration extended successfully',
                'data' => [
                    'experiment_id' => $experimentId,
                    'new_end_date' => $newEndDate->toDateString(),
                    'total_duration_days' => $experiment->duration_days
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to extend test',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List all A/B tests
     *
     * @param array $filters
     * @return array
     */
    public function listTests(array $filters = []): array
    {
        try {
            $query = Experiment::query()->with('variants');

            // Apply filters
            if (!empty($filters['test_status'])) {
                $query->where('status', $filters['test_status']);
            }

            if (!empty($filters['test_type'])) {
                $query->where('experiment_type', $filters['test_type']);
            }

            if (!empty($filters['entity_type'])) {
                $query->where('entity_type', $filters['entity_type']);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $filters['per_page'] ?? 20;
            $page = $filters['page'] ?? 1;

            $experiments = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform results
            $enrichedTests = $experiments->map(function ($experiment) {
                return [
                    'experiment_id' => $experiment->experiment_id,
                    'name' => $experiment->name,
                    'experiment_type' => $experiment->experiment_type,
                    'status' => $experiment->status,
                    'entity_type' => $experiment->entity_type,
                    'metric' => $experiment->metric,
                    'variant_count' => $experiment->variants->count(),
                    'started_at' => $experiment->started_at,
                    'end_date' => $experiment->end_date,
                    'completed_at' => $experiment->completed_at,
                    'created_at' => $experiment->created_at
                ];
            });

            return [
                'success' => true,
                'data' => $enrichedTests,
                'pagination' => [
                    'total' => $experiments->total(),
                    'per_page' => $experiments->perPage(),
                    'current_page' => $experiments->currentPage(),
                    'last_page' => $experiments->lastPage()
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to list tests',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete an A/B test (only if in draft status)
     *
     * @param string $experimentId
     * @return array
     */
    public function deleteTest(string $experimentId): array
    {
        try {
            $experiment = Experiment::with('variants')->find($experimentId);

            if (!$experiment) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($experiment->status !== 'draft') {
                return ['success' => false, 'message' => 'Only draft tests can be deleted'];
            }

            DB::beginTransaction();

            // Delete variants (cascade will handle related records)
            $experiment->variants()->delete();

            // Delete experiment
            $experiment->delete();

            DB::commit();

            Cache::forget("ab_test:{$experimentId}");

            return [
                'success' => true,
                'message' => 'A/B test deleted successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to delete test',
                'error' => $e->getMessage()
            ];
        }
    }
}
