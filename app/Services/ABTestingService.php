<?php

namespace App\Services;

use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdSet;
use App\Models\AdPlatform\AdEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ABTestingService
 *
 * Handles A/B testing for ad campaigns
 * Implements Sprint 4.6: A/B Testing
 *
 * Features:
 * - Create and manage A/B tests
 * - Multiple variation support
 * - Statistical significance testing
 * - Automatic winner selection
 * - Test history and reporting
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

            $testId = (string) Str::uuid();

            // Create test record
            DB::table('cmis_ads.ab_tests')->insert([
                'ab_test_id' => $testId,
                'ad_account_id' => $data['ad_account_id'],
                'entity_type' => $data['entity_type'] ?? 'ad', // ad, ad_set, campaign
                'entity_id' => $data['entity_id'] ?? null,
                'test_name' => $data['test_name'],
                'test_type' => $data['test_type'] ?? 'creative', // creative, audience, placement, delivery_optimization
                'test_status' => 'draft',
                'hypothesis' => $data['hypothesis'] ?? null,
                'metric_to_optimize' => $data['metric_to_optimize'] ?? 'ctr', // ctr, conversion_rate, cpa, roas
                'budget_per_variation' => $data['budget_per_variation'] ?? 100,
                'test_duration_days' => $data['test_duration_days'] ?? 7,
                'min_sample_size' => $data['min_sample_size'] ?? 1000,
                'confidence_level' => $data['confidence_level'] ?? 0.95,
                'config' => json_encode($data['config'] ?? []),
                'created_at' => now()
            ]);

            // Create initial variations if provided
            if (!empty($data['variations'])) {
                foreach ($data['variations'] as $index => $variation) {
                    $this->addVariation($testId, $variation, $index === 0);
                }
            }

            DB::commit();

            $test = DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->first();

            return [
                'success' => true,
                'message' => 'A/B test created successfully',
                'data' => [
                    'ab_test_id' => $testId,
                    'test_name' => $test->test_name,
                    'test_type' => $test->test_type,
                    'test_status' => $test->test_status,
                    'metric_to_optimize' => $test->metric_to_optimize,
                    'test_duration_days' => $test->test_duration_days,
                    'variations' => $this->getTestVariations($testId)
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
     * @param string $testId
     * @param array $variationData
     * @param bool $isControl
     * @return array
     */
    public function addVariation(string $testId, array $variationData, bool $isControl = false): array
    {
        try {
            $variationId = (string) Str::uuid();

            // Get test details
            $test = DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->first();

            if (!$test) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($test->test_status !== 'draft') {
                return ['success' => false, 'message' => 'Cannot add variations to a test that is not in draft status'];
            }

            // Insert variation
            DB::table('cmis_ads.ab_test_variations')->insert([
                'variation_id' => $variationId,
                'ab_test_id' => $testId,
                'variation_name' => $variationData['variation_name'],
                'is_control' => $isControl,
                'entity_id' => $variationData['entity_id'] ?? null, // Link to actual ad/ad_set
                'variation_config' => json_encode($variationData['config'] ?? []),
                'traffic_allocation' => $variationData['traffic_allocation'] ?? 50,
                'created_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Variation added successfully',
                'data' => [
                    'variation_id' => $variationId,
                    'variation_name' => $variationData['variation_name'],
                    'is_control' => $isControl
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
     * @param string $testId
     * @return array
     */
    public function startTest(string $testId): array
    {
        try {
            $test = DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->first();

            if (!$test) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($test->test_status !== 'draft') {
                return ['success' => false, 'message' => 'Test can only be started from draft status'];
            }

            // Validate test has at least 2 variations
            $variations = DB::table('cmis_ads.ab_test_variations')
                ->where('ab_test_id', $testId)
                ->count();

            if ($variations < 2) {
                return [
                    'success' => false,
                    'message' => 'Test must have at least 2 variations to start'
                ];
            }

            // Update test status
            $startDate = now();
            $endDate = now()->addDays($test->test_duration_days);

            DB::table('cmis_ads.ab_tests')
                ->where('ab_test_id', $testId)
                ->update([
                    'test_status' => 'running',
                    'started_at' => $startDate,
                    'scheduled_end_at' => $endDate,
                    'updated_at' => now()
                ]);

            // Clear cache
            Cache::forget("ab_test:{$testId}");

            return [
                'success' => true,
                'message' => 'A/B test started successfully',
                'data' => [
                    'ab_test_id' => $testId,
                    'test_status' => 'running',
                    'started_at' => $startDate->toDateTimeString(),
                    'scheduled_end_at' => $endDate->toDateTimeString()
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
     * @param string $testId
     * @param string|null $reason
     * @return array
     */
    public function stopTest(string $testId, ?string $reason = null): array
    {
        try {
            $test = DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->first();

            if (!$test) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($test->test_status !== 'running') {
                return ['success' => false, 'message' => 'Only running tests can be stopped'];
            }

            // Update test status
            DB::table('cmis_ads.ab_tests')
                ->where('ab_test_id', $testId)
                ->update([
                    'test_status' => 'stopped',
                    'completed_at' => now(),
                    'stop_reason' => $reason,
                    'updated_at' => now()
                ]);

            // Clear cache
            Cache::forget("ab_test:{$testId}");

            return [
                'success' => true,
                'message' => 'A/B test stopped successfully',
                'data' => [
                    'ab_test_id' => $testId,
                    'test_status' => 'stopped',
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
     * @param string $testId
     * @return array
     */
    public function getTestResults(string $testId): array
    {
        try {
            $cacheKey = "ab_test_results:{$testId}";

            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($testId) {
                $test = DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->first();

                if (!$test) {
                    return ['success' => false, 'message' => 'A/B test not found'];
                }

                // Get variations with their performance metrics
                $variations = $this->getVariationResults($testId);

                // Calculate statistical significance
                $statisticalAnalysis = $this->calculateStatisticalSignificance($variations, $test->metric_to_optimize);

                // Determine if test has reached minimum sample size
                $totalSampleSize = array_sum(array_column($variations, 'impressions'));
                $hasMinimumSample = $totalSampleSize >= $test->min_sample_size;

                // Calculate test progress
                $progress = $this->calculateTestProgress($test);

                // Identify winning variation if test is complete
                $winner = null;
                if ($test->test_status === 'completed' && $statisticalAnalysis['is_significant']) {
                    $winner = $this->identifyWinner($variations, $test->metric_to_optimize);
                }

                return [
                    'success' => true,
                    'data' => [
                        'test' => [
                            'ab_test_id' => $test->ab_test_id,
                            'test_name' => $test->test_name,
                            'test_type' => $test->test_type,
                            'test_status' => $test->test_status,
                            'metric_to_optimize' => $test->metric_to_optimize,
                            'started_at' => $test->started_at,
                            'scheduled_end_at' => $test->scheduled_end_at,
                            'completed_at' => $test->completed_at
                        ],
                        'progress' => $progress,
                        'variations' => $variations,
                        'statistical_analysis' => $statisticalAnalysis,
                        'has_minimum_sample' => $hasMinimumSample,
                        'winner' => $winner,
                        'recommendations' => $this->generateRecommendations($test, $variations, $statisticalAnalysis)
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
     * @param string $testId
     * @return array
     */
    protected function getVariationResults(string $testId): array
    {
        $variations = DB::table('cmis_ads.ab_test_variations')
            ->where('ab_test_id', $testId)
            ->get();

        $results = [];

        foreach ($variations as $variation) {
            // Get metrics for the variation's entity
            $metrics = DB::table('cmis_ads.ad_metrics')
                ->where('entity_id', $variation->entity_id)
                ->selectRaw('
                    SUM(impressions) as impressions,
                    SUM(clicks) as clicks,
                    SUM(conversions) as conversions,
                    SUM(spend) as spend,
                    SUM(revenue) as revenue
                ')
                ->first();

            $impressions = $metrics->impressions ?? 0;
            $clicks = $metrics->clicks ?? 0;
            $conversions = $metrics->conversions ?? 0;
            $spend = $metrics->spend ?? 0;
            $revenue = $metrics->revenue ?? 0;

            // Calculate key metrics
            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            $conversionRate = $clicks > 0 ? ($conversions / $clicks) * 100 : 0;
            $cpa = $conversions > 0 ? $spend / $conversions : 0;
            $roas = $spend > 0 ? $revenue / $spend : 0;
            $cpc = $clicks > 0 ? $spend / $clicks : 0;
            $cpm = $impressions > 0 ? ($spend / $impressions) * 1000 : 0;

            $results[] = [
                'variation_id' => $variation->variation_id,
                'variation_name' => $variation->variation_name,
                'is_control' => $variation->is_control,
                'traffic_allocation' => $variation->traffic_allocation,
                'metrics' => [
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'spend' => round($spend, 2),
                    'revenue' => round($revenue, 2),
                    'ctr' => round($ctr, 2),
                    'conversion_rate' => round($conversionRate, 2),
                    'cpa' => round($cpa, 2),
                    'roas' => round($roas, 2),
                    'cpc' => round($cpc, 2),
                    'cpm' => round($cpm, 2)
                ],
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversions' => $conversions
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

            // Simple Chi-square approximation for significance
            // For a more accurate implementation, use a proper statistical library
            $totalSample = $controlSample + $variationSample;
            $expectedControl = $totalSample * 0.5;
            $expectedVariation = $totalSample * 0.5;

            if ($expectedControl > 0 && $expectedVariation > 0) {
                $chiSquare = pow($controlSample - $expectedControl, 2) / $expectedControl +
                             pow($variationSample - $expectedVariation, 2) / $expectedVariation;

                // Chi-square critical value for 95% confidence (1 degree of freedom) is 3.841
                $isSignificant = $chiSquare > 3.841 && abs($improvement) > 5; // Also require >5% difference
                $confidence = $isSignificant ? 0.95 : ($chiSquare / 3.841) * 0.95;
            } else {
                $isSignificant = false;
                $confidence = 0;
            }

            $results[] = [
                'variation_name' => $variation['variation_name'],
                'control_value' => round($controlValue, 2),
                'variation_value' => round($variationValue, 2),
                'improvement' => round($improvement, 2),
                'is_significant' => $isSignificant,
                'confidence' => round($confidence, 2),
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
     * @param object $test
     * @return array
     */
    protected function calculateTestProgress($test): array
    {
        if ($test->test_status !== 'running') {
            return [
                'percent_complete' => $test->test_status === 'completed' ? 100 : 0,
                'days_elapsed' => 0,
                'days_remaining' => 0
            ];
        }

        $startDate = Carbon::parse($test->started_at);
        $endDate = Carbon::parse($test->scheduled_end_at);
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
            'variation_id' => $winner['variation_id'],
            'variation_name' => $winner['variation_name'],
            'winning_metric' => $metric,
            'winning_value' => $winner['metrics'][$metric],
            'confidence' => 'High' // Simplified, would come from statistical analysis
        ];
    }

    /**
     * Generate recommendations based on test results
     *
     * @param object $test
     * @param array $variations
     * @param array $statisticalAnalysis
     * @return array
     */
    protected function generateRecommendations($test, array $variations, array $statisticalAnalysis): array
    {
        $recommendations = [];

        // Check if test should continue
        if ($test->test_status === 'running') {
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
        if ($totalImpressions < $test->min_sample_size) {
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
     * @param string $testId
     * @param string|null $variationId
     * @return array
     */
    public function selectWinner(string $testId, ?string $variationId = null): array
    {
        try {
            $test = DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->first();

            if (!$test) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            // If no variation specified, auto-select based on metric
            if (!$variationId) {
                $results = $this->getTestResults($testId);
                if (!$results['success'] || !$results['data']['winner']) {
                    return ['success' => false, 'message' => 'Cannot determine winner automatically'];
                }
                $variationId = $results['data']['winner']['variation_id'];
            }

            // Get winning variation
            $winner = DB::table('cmis_ads.ab_test_variations')
                ->where('variation_id', $variationId)
                ->first();

            if (!$winner) {
                return ['success' => false, 'message' => 'Variation not found'];
            }

            // Update test status
            DB::table('cmis_ads.ab_tests')
                ->where('ab_test_id', $testId)
                ->update([
                    'test_status' => 'completed',
                    'winner_variation_id' => $variationId,
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);

            // Clear cache
            Cache::forget("ab_test:{$testId}");
            Cache::forget("ab_test_results:{$testId}");

            return [
                'success' => true,
                'message' => 'Winner selected successfully',
                'data' => [
                    'ab_test_id' => $testId,
                    'winner_variation_id' => $variationId,
                    'winner_name' => $winner->variation_name,
                    'entity_id' => $winner->entity_id,
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
     * @param string $testId
     * @param int $additionalDays
     * @return array
     */
    public function extendTest(string $testId, int $additionalDays): array
    {
        try {
            $test = DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->first();

            if (!$test) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($test->test_status !== 'running') {
                return ['success' => false, 'message' => 'Only running tests can be extended'];
            }

            $newEndDate = Carbon::parse($test->scheduled_end_at)->addDays($additionalDays);

            DB::table('cmis_ads.ab_tests')
                ->where('ab_test_id', $testId)
                ->update([
                    'scheduled_end_at' => $newEndDate,
                    'test_duration_days' => $test->test_duration_days + $additionalDays,
                    'updated_at' => now()
                ]);

            Cache::forget("ab_test:{$testId}");

            return [
                'success' => true,
                'message' => 'Test duration extended successfully',
                'data' => [
                    'ab_test_id' => $testId,
                    'new_end_date' => $newEndDate->toDateTimeString(),
                    'total_duration_days' => $test->test_duration_days + $additionalDays
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
            $query = DB::table('cmis_ads.ab_tests');

            // Apply filters
            if (!empty($filters['ad_account_id'])) {
                $query->where('ad_account_id', $filters['ad_account_id']);
            }

            if (!empty($filters['test_status'])) {
                $query->where('test_status', $filters['test_status']);
            }

            if (!empty($filters['test_type'])) {
                $query->where('test_type', $filters['test_type']);
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
            $offset = ($page - 1) * $perPage;

            $total = $query->count();
            $tests = $query->offset($offset)->limit($perPage)->get();

            // Enrich with variation count and status
            $enrichedTests = [];
            foreach ($tests as $test) {
                $variationCount = DB::table('cmis_ads.ab_test_variations')
                    ->where('ab_test_id', $test->ab_test_id)
                    ->count();

                $enrichedTests[] = [
                    'ab_test_id' => $test->ab_test_id,
                    'test_name' => $test->test_name,
                    'test_type' => $test->test_type,
                    'test_status' => $test->test_status,
                    'entity_type' => $test->entity_type,
                    'metric_to_optimize' => $test->metric_to_optimize,
                    'variation_count' => $variationCount,
                    'started_at' => $test->started_at,
                    'scheduled_end_at' => $test->scheduled_end_at,
                    'completed_at' => $test->completed_at,
                    'created_at' => $test->created_at
                ];
            }

            return [
                'success' => true,
                'data' => $enrichedTests,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
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
     * Get test variations
     *
     * @param string $testId
     * @return array
     */
    protected function getTestVariations(string $testId): array
    {
        $variations = DB::table('cmis_ads.ab_test_variations')
            ->where('ab_test_id', $testId)
            ->get();

        return $variations->map(function ($variation) {
            return [
                'variation_id' => $variation->variation_id,
                'variation_name' => $variation->variation_name,
                'is_control' => $variation->is_control,
                'traffic_allocation' => $variation->traffic_allocation,
                'entity_id' => $variation->entity_id
            ];
        })->toArray();
    }

    /**
     * Delete an A/B test (only if in draft status)
     *
     * @param string $testId
     * @return array
     */
    public function deleteTest(string $testId): array
    {
        try {
            $test = DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->first();

            if (!$test) {
                return ['success' => false, 'message' => 'A/B test not found'];
            }

            if ($test->test_status !== 'draft') {
                return ['success' => false, 'message' => 'Only draft tests can be deleted'];
            }

            DB::beginTransaction();

            // Delete variations
            DB::table('cmis_ads.ab_test_variations')->where('ab_test_id', $testId)->delete();

            // Delete test
            DB::table('cmis_ads.ab_tests')->where('ab_test_id', $testId)->delete();

            DB::commit();

            Cache::forget("ab_test:{$testId}");

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
