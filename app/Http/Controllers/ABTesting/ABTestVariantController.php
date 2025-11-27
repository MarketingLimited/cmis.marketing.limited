<?php

namespace App\Http\Controllers\ABTesting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\ABTesting\ABTest;
use App\Models\ABTesting\ABTestVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ABTestVariantController extends Controller
{
    use ApiResponse;

    /**
     * Display variants for a test
     */
    public function index(Request $request, string $testId)
    {
        $test = ABTest::findOrFail($testId);

        $variants = ABTestVariant::where('test_id', $testId)
            ->with('metrics')
            ->orderBy('is_control', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($request->expectsJson()) {
            return $this->success($variants, 'Variants retrieved successfully');
        }

        return view('ab-testing.variants.index', compact('test', 'variants'));
    }

    /**
     * Store a newly created variant
     */
    public function store(Request $request, string $testId)
    {
        $test = ABTest::findOrFail($testId);

        $validator = Validator::make($request->all(), ABTestVariant::createRules(), ABTestVariant::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validate traffic split doesn't exceed 100%
        $currentSplit = ABTestVariant::where('test_id', $testId)->sum('traffic_split');
        if ($currentSplit + $request->traffic_split > 100) {
            return $this->error('Total traffic split cannot exceed 100%', 400);
        }

        $variant = ABTestVariant::create(array_merge($request->all(), [
            'test_id' => $testId,
            'org_id' => session('current_org_id'),
        ]));

        if ($request->expectsJson()) {
            return $this->created($variant, 'Variant created successfully');
        }

        return redirect()->route('ab-testing.tests.show', $testId)
            ->with('success', 'Variant created successfully');
    }

    /**
     * Display the specified variant
     */
    public function show(string $testId, string $id)
    {
        $variant = ABTestVariant::where('test_id', $testId)
            ->with('metrics')
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($variant, 'Variant retrieved successfully');
        }

        return view('ab-testing.variants.show', compact('variant'));
    }

    /**
     * Update the specified variant
     */
    public function update(Request $request, string $testId, string $id)
    {
        $validator = Validator::make($request->all(), ABTestVariant::updateRules(), ABTestVariant::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $variant = ABTestVariant::where('test_id', $testId)->findOrFail($id);

        // Validate traffic split if updating
        if ($request->has('traffic_split')) {
            $currentSplit = ABTestVariant::where('test_id', $testId)
                ->where('variant_id', '!=', $id)
                ->sum('traffic_split');

            if ($currentSplit + $request->traffic_split > 100) {
                return $this->error('Total traffic split cannot exceed 100%', 400);
            }
        }

        $variant->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($variant, 'Variant updated successfully');
        }

        return redirect()->route('ab-testing.tests.show', $testId)
            ->with('success', 'Variant updated successfully');
    }

    /**
     * Remove the specified variant
     */
    public function destroy(string $testId, string $id)
    {
        $variant = ABTestVariant::where('test_id', $testId)->findOrFail($id);

        // Cannot delete control variant
        if ($variant->is_control) {
            return $this->error('Cannot delete the control variant', 400);
        }

        $variant->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Variant deleted successfully');
        }

        return redirect()->route('ab-testing.tests.show', $testId)
            ->with('success', 'Variant deleted successfully');
    }

    /**
     * Record impression for variant
     */
    public function recordImpression(Request $request, string $testId, string $id)
    {
        $variant = ABTestVariant::where('test_id', $testId)->findOrFail($id);

        $variant->increment('impressions');

        return $this->success(['impressions' => $variant->fresh()->impressions], 'Impression recorded successfully');
    }

    /**
     * Record conversion for variant
     */
    public function recordConversion(Request $request, string $testId, string $id)
    {
        $validator = Validator::make($request->all(), [
            'conversion_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $variant = ABTestVariant::where('test_id', $testId)->findOrFail($id);

        $variant->increment('conversions');

        if ($request->has('conversion_value')) {
            $variant->increment('total_revenue', $request->conversion_value);
        }

        return $this->success([
            'conversions' => $variant->fresh()->conversions,
            'total_revenue' => $variant->fresh()->total_revenue,
        ], 'Conversion recorded successfully');
    }

    /**
     * Get variant performance statistics
     */
    public function statistics(string $testId, string $id)
    {
        $variant = ABTestVariant::where('test_id', $testId)
            ->with('metrics')
            ->findOrFail($id);

        $impressions = $variant->impressions ?? 0;
        $conversions = $variant->conversions ?? 0;
        $revenue = $variant->total_revenue ?? 0;

        $stats = [
            'variant_id' => $variant->variant_id,
            'variant_name' => $variant->variant_name,
            'is_control' => $variant->is_control,
            'impressions' => $impressions,
            'conversions' => $conversions,
            'conversion_rate' => $impressions > 0 ? round(($conversions / $impressions) * 100, 2) : 0,
            'total_revenue' => $revenue,
            'revenue_per_impression' => $impressions > 0 ? round($revenue / $impressions, 2) : 0,
            'revenue_per_conversion' => $conversions > 0 ? round($revenue / $conversions, 2) : 0,
            'traffic_split' => $variant->traffic_split,
        ];

        return $this->success($stats, 'Variant statistics retrieved successfully');
    }

    /**
     * Compare variants
     */
    public function compare(string $testId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'variant_ids' => 'required|array|min:2',
            'variant_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $variants = ABTestVariant::where('test_id', $testId)
            ->whereIn('variant_id', $request->variant_ids)
            ->get();

        if ($variants->count() < 2) {
            return $this->error('At least 2 variants are required for comparison', 400);
        }

        $comparison = [];

        foreach ($variants as $variant) {
            $impressions = $variant->impressions ?? 0;
            $conversions = $variant->conversions ?? 0;
            $revenue = $variant->total_revenue ?? 0;

            $comparison[] = [
                'variant_id' => $variant->variant_id,
                'variant_name' => $variant->variant_name,
                'is_control' => $variant->is_control,
                'impressions' => $impressions,
                'conversions' => $conversions,
                'conversion_rate' => $impressions > 0 ? round(($conversions / $impressions) * 100, 2) : 0,
                'total_revenue' => $revenue,
                'avg_revenue_per_conversion' => $conversions > 0 ? round($revenue / $conversions, 2) : 0,
            ];
        }

        return $this->success($comparison, 'Variant comparison retrieved successfully');
    }

    /**
     * Set variant as control
     */
    public function setAsControl(string $testId, string $id)
    {
        $test = ABTest::findOrFail($testId);

        if ($test->status !== 'draft') {
            return $this->error('Can only change control variant for draft tests', 400);
        }

        // Remove control flag from all variants
        ABTestVariant::where('test_id', $testId)->update(['is_control' => false]);

        // Set new control
        $variant = ABTestVariant::where('test_id', $testId)->findOrFail($id);
        $variant->update(['is_control' => true]);

        return $this->success($variant, 'Variant set as control successfully');
    }

    /**
     * Auto-balance traffic split
     */
    public function autoBalanceTraffic(string $testId)
    {
        $variants = ABTestVariant::where('test_id', $testId)->get();

        if ($variants->isEmpty()) {
            return $this->error('No variants found', 404);
        }

        $count = $variants->count();
        $splitPerVariant = floor(100 / $count);
        $remainder = 100 - ($splitPerVariant * $count);

        foreach ($variants as $index => $variant) {
            $split = $splitPerVariant;

            // Add remainder to first variant
            if ($index === 0) {
                $split += $remainder;
            }

            $variant->update(['traffic_split' => $split]);
        }

        return $this->success(
            ABTestVariant::where('test_id', $testId)->get(),
            'Traffic split auto-balanced successfully'
        );
    }
}
