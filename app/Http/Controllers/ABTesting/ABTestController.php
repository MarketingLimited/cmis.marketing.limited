<?php

namespace App\Http\Controllers\ABTesting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\ABTesting\ABTest;
use App\Models\ABTesting\ABTestVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ABTestController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of A/B tests
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $tests = ABTest::where('org_id', $orgId)
            ->when($request->entity_type, fn($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('test_name', 'like', "%{$request->search}%"))
            ->with(['variants', 'metrics'])
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($tests, 'A/B tests retrieved successfully');
        }

        return view('ab-testing.tests.index', compact('tests'));
    }

    /**
     * Store a newly created A/B test
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), ABTest::createRules(), ABTest::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $test = ABTest::create(array_merge($request->all(), [
            'org_id' => session('current_org_id'),
            'created_by' => auth()->id(),
        ]));

        if ($request->expectsJson()) {
            return $this->created($test, 'A/B test created successfully');
        }

        return redirect()->route('ab-testing.tests.show', $test->test_id)
            ->with('success', __('ab_testing.created_success'));
    }

    /**
     * Display the specified A/B test
     */
    public function show(string $id)
    {
        $test = ABTest::with(['variants', 'metrics'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($test, 'A/B test retrieved successfully');
        }

        return view('ab-testing.tests.show', compact('test'));
    }

    /**
     * Update the specified A/B test
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), ABTest::updateRules(), ABTest::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $test = ABTest::findOrFail($id);
        $test->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($test, 'A/B test updated successfully');
        }

        return redirect()->route('ab-testing.tests.show', $test->test_id)
            ->with('success', __('ab_testing.updated_success'));
    }

    /**
     * Remove the specified A/B test
     */
    public function destroy(string $id)
    {
        $test = ABTest::findOrFail($id);
        $test->delete();

        if (request()->expectsJson()) {
            return $this->deleted('A/B test deleted successfully');
        }

        return redirect()->route('ab-testing.tests.index')
            ->with('success', __('ab_testing.deleted_success'));
    }

    /**
     * Start an A/B test
     */
    public function start(string $id)
    {
        $test = ABTest::findOrFail($id);

        if ($test->status !== 'draft') {
            return $this->error('Only draft tests can be started', 400);
        }

        // Validate test has at least 2 variants
        if ($test->variants->count() < 2) {
            return $this->error('Test must have at least 2 variants to start', 400);
        }

        $test->update([
            'status' => 'running',
            'start_date' => now(),
        ]);

        return $this->success($test, 'A/B test started successfully');
    }

    /**
     * Pause an A/B test
     */
    public function pause(string $id)
    {
        $test = ABTest::findOrFail($id);

        if ($test->status !== 'running') {
            return $this->error('Only running tests can be paused', 400);
        }

        $test->update([
            'status' => 'paused',
        ]);

        return $this->success($test, 'A/B test paused successfully');
    }

    /**
     * Resume a paused A/B test
     */
    public function resume(string $id)
    {
        $test = ABTest::findOrFail($id);

        if ($test->status !== 'paused') {
            return $this->error('Only paused tests can be resumed', 400);
        }

        $test->update([
            'status' => 'running',
        ]);

        return $this->success($test, 'A/B test resumed successfully');
    }

    /**
     * Complete an A/B test
     */
    public function complete(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'winner_variant_id' => 'nullable|uuid|exists:cmis_ab_testing.ab_test_variants,variant_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $test = ABTest::findOrFail($id);

        if (!in_array($test->status, ['running', 'paused'])) {
            return $this->error('Only running or paused tests can be completed', 400);
        }

        $test->update([
            'status' => 'completed',
            'end_date' => now(),
            'winner_variant_id' => $request->winner_variant_id,
        ]);

        return $this->success($test, 'A/B test completed successfully');
    }

    /**
     * Get test results and statistics
     */
    public function results(string $id)
    {
        $test = ABTest::with(['variants.metrics'])->findOrFail($id);

        $results = [
            'test_id' => $test->test_id,
            'test_name' => $test->test_name,
            'status' => $test->status,
            'start_date' => $test->start_date,
            'end_date' => $test->end_date,
            'sample_size' => $test->sample_size,
            'confidence_level' => $test->confidence_level,
            'variants' => [],
            'winner' => null,
        ];

        foreach ($test->variants as $variant) {
            $variantData = [
                'variant_id' => $variant->variant_id,
                'variant_name' => $variant->variant_name,
                'traffic_split' => $variant->traffic_split,
                'is_control' => $variant->is_control,
                'impressions' => $variant->impressions ?? 0,
                'conversions' => $variant->conversions ?? 0,
                'conversion_rate' => $variant->impressions > 0
                    ? round(($variant->conversions / $variant->impressions) * 100, 2)
                    : 0,
                'metrics' => $variant->metrics->map(function ($metric) {
                    return [
                        'metric_name' => $metric->metric_name,
                        'metric_value' => $metric->metric_value,
                    ];
                }),
            ];

            $results['variants'][] = $variantData;
        }

        // Identify winner
        if ($test->winner_variant_id) {
            $results['winner'] = $test->variants->firstWhere('variant_id', $test->winner_variant_id)?->variant_name;
        }

        return $this->success($results, 'Test results retrieved successfully');
    }

    /**
     * Calculate statistical significance
     */
    public function calculateSignificance(string $id)
    {
        $test = ABTest::with('variants')->findOrFail($id);

        $control = $test->variants->firstWhere('is_control', true);
        $variants = $test->variants->where('is_control', false);

        if (!$control) {
            return $this->error('No control variant found', 400);
        }

        $results = [];

        foreach ($variants as $variant) {
            // Calculate Z-score for conversion rate difference
            $p1 = $control->impressions > 0 ? $control->conversions / $control->impressions : 0;
            $p2 = $variant->impressions > 0 ? $variant->conversions / $variant->impressions : 0;

            $n1 = $control->impressions;
            $n2 = $variant->impressions;

            if ($n1 > 0 && $n2 > 0) {
                $pooledP = ($control->conversions + $variant->conversions) / ($n1 + $n2);
                $se = sqrt($pooledP * (1 - $pooledP) * (1/$n1 + 1/$n2));

                $zScore = $se > 0 ? ($p2 - $p1) / $se : 0;

                // Calculate p-value (simplified - using standard normal distribution)
                $pValue = 2 * (1 - $this->normalCDF(abs($zScore)));

                $isSignificant = $pValue < (1 - $test->confidence_level / 100);

                $results[] = [
                    'variant_id' => $variant->variant_id,
                    'variant_name' => $variant->variant_name,
                    'control_rate' => round($p1 * 100, 2),
                    'variant_rate' => round($p2 * 100, 2),
                    'improvement' => round((($p2 - $p1) / max($p1, 0.0001)) * 100, 2),
                    'z_score' => round($zScore, 4),
                    'p_value' => round($pValue, 4),
                    'is_significant' => $isSignificant,
                ];
            }
        }

        return $this->success($results, 'Statistical significance calculated successfully');
    }

    /**
     * Get A/B testing analytics
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $tests = ABTest::where('org_id', $orgId)->get();

        $totalTests = $tests->count();
        $runningTests = $tests->where('status', 'running')->count();
        $completedTests = $tests->where('status', 'completed')->count();

        $analytics = [
            'summary' => [
                'total_tests' => $totalTests,
                'running_tests' => $runningTests,
                'completed_tests' => $completedTests,
                'draft_tests' => $tests->where('status', 'draft')->count(),
            ],
            'by_entity_type' => $tests->groupBy('entity_type')->map->count(),
            'by_status' => $tests->groupBy('status')->map->count(),
        ];

        if ($request->expectsJson()) {
            return $this->success($analytics, 'A/B testing analytics retrieved successfully');
        }

        return view('ab-testing.tests.analytics', compact('analytics'));
    }

    /**
     * Bulk update tests
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_ids' => 'required|array',
            'test_ids.*' => 'uuid',
            'status' => 'nullable|in:draft,running,paused,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $updated = ABTest::where('org_id', $orgId)
            ->whereIn('test_id', $request->test_ids)
            ->update(array_filter([
                'status' => $request->status,
            ]));

        return $this->success([
            'updated_count' => $updated,
        ], 'Tests updated successfully');
    }

    /**
     * Approximate cumulative distribution function for standard normal
     */
    protected function normalCDF(float $z): float
    {
        return 0.5 * (1 + erf($z / sqrt(2)));
    }
}
