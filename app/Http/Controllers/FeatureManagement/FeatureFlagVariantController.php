<?php

namespace App\Http\Controllers\FeatureManagement;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\FeatureManagement\FeatureFlagVariant;
use App\Models\FeatureManagement\FeatureFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureFlagVariantController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of variants for a flag
     */
    public function index(Request $request, string $flagId)
    {
        $flag = FeatureFlag::findOrFail($flagId);

        $variants = $flag->variants()
            ->when($request->active_only, fn($q) => $q->where('is_active', true))
            ->orderBy('weight', 'desc')
            ->get();

        if ($request->expectsJson()) {
            return $this->success($variants, 'Variants retrieved successfully');
        }

        return view('feature-management.variants.index', compact('flag', 'variants'));
    }

    /**
     * Store a newly created variant
     */
    public function store(Request $request, string $flagId)
    {
        $flag = FeatureFlag::findOrFail($flagId);

        $validator = Validator::make($request->all(), FeatureFlagVariant::createRules(), FeatureFlagVariant::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $variant = FeatureFlagVariant::create(array_merge($request->all(), [
            'flag_id' => $flagId,
            'org_id' => session('current_org_id'),
        ]));

        if ($request->expectsJson()) {
            return $this->created($variant, 'Variant created successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.created_success'));
    }

    /**
     * Display the specified variant
     */
    public function show(string $flagId, string $id)
    {
        $variant = FeatureFlagVariant::where('flag_id', $flagId)
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($variant, 'Variant retrieved successfully');
        }

        return view('feature-management.variants.show', compact('variant'));
    }

    /**
     * Update the specified variant
     */
    public function update(Request $request, string $flagId, string $id)
    {
        $validator = Validator::make($request->all(), FeatureFlagVariant::updateRules(), FeatureFlagVariant::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $variant = FeatureFlagVariant::where('flag_id', $flagId)
            ->findOrFail($id);

        $variant->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($variant, 'Variant updated successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.updated_success'));
    }

    /**
     * Remove the specified variant
     */
    public function destroy(string $flagId, string $id)
    {
        $variant = FeatureFlagVariant::where('flag_id', $flagId)
            ->findOrFail($id);

        $variant->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Variant deleted successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.deleted_success'));
    }

    /**
     * Activate a variant
     */
    public function activate(string $flagId, string $id)
    {
        $variant = FeatureFlagVariant::where('flag_id', $flagId)
            ->findOrFail($id);

        $variant->activate();

        if (request()->expectsJson()) {
            return $this->success($variant, 'Variant activated successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.activated_success'));
    }

    /**
     * Deactivate a variant
     */
    public function deactivate(string $flagId, string $id)
    {
        $variant = FeatureFlagVariant::where('flag_id', $flagId)
            ->findOrFail($id);

        $variant->deactivate();

        if (request()->expectsJson()) {
            return $this->success($variant, 'Variant deactivated successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.activated_success'));
    }

    /**
     * Record conversion for a variant
     */
    public function recordConversion(Request $request, string $flagId, string $id)
    {
        $variant = FeatureFlagVariant::where('flag_id', $flagId)
            ->findOrFail($id);

        $variant->recordConversion();

        return $this->success($variant, 'Conversion recorded successfully');
    }

    /**
     * Get variant performance stats
     */
    public function stats(string $flagId, string $id)
    {
        $variant = FeatureFlagVariant::where('flag_id', $flagId)
            ->findOrFail($id);

        $stats = [
            'key' => $variant->key,
            'exposures' => $variant->exposures,
            'conversions' => $variant->conversions,
            'conversion_rate' => $variant->getConversionRate(),
            'is_control' => $variant->is_control,
            'is_active' => $variant->is_active,
            'weight' => $variant->weight,
            'performance_score' => $variant->getPerformanceScore(),
        ];

        return $this->success($stats, 'Variant stats retrieved successfully');
    }

    /**
     * Compare variants
     */
    public function compare(string $flagId)
    {
        $flag = FeatureFlag::findOrFail($flagId);

        $variants = $flag->variants()
            ->where('is_active', true)
            ->orderBy('conversions', 'desc')
            ->get();

        $comparison = $variants->map(function ($variant) {
            return [
                'variant_id' => $variant->variant_id,
                'key' => $variant->key,
                'exposures' => $variant->exposures,
                'conversions' => $variant->conversions,
                'conversion_rate' => $variant->getConversionRate(),
                'performance_score' => $variant->getPerformanceScore(),
                'is_control' => $variant->is_control,
            ];
        });

        return $this->success([
            'flag_key' => $flag->key,
            'variants' => $comparison,
            'best_performer' => $comparison->sortByDesc('conversion_rate')->first(),
        ], 'Variant comparison retrieved successfully');
    }
}
