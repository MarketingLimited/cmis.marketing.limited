<?php

namespace App\Http\Controllers\FeatureManagement;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\FeatureManagement\FeatureFlag;
use App\Services\FeatureManagement\FeatureFlagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureFlagController extends Controller
{
    use ApiResponse;

    protected FeatureFlagService $flagService;

    public function __construct(FeatureFlagService $flagService)
    {
        $this->flagService = $flagService;
    }

    /**
     * Display a listing of feature flags
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $flags = FeatureFlag::where('org_id', $orgId)
            ->when($request->type, fn($q) => $q->byType($request->type))
            ->when($request->active_only, fn($q) => $q->active())
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->with(['variants', 'overrides'])
            ->latest('updated_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($flags, 'Feature flags retrieved successfully');
        }

        return view('feature-management.flags.index', compact('flags'));
    }

    /**
     * Show the form for creating a new feature flag
     */
    public function create()
    {
        return view('feature-management.flags.create');
    }

    /**
     * Store a newly created feature flag
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), FeatureFlag::createRules(), FeatureFlag::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $flag = $this->flagService->createFlag($request->all());

        if ($request->expectsJson()) {
            return $this->created($flag, 'Feature flag created successfully');
        }

        return redirect()->route('feature-flags.show', $flag->flag_id)
            ->with('success', 'Feature flag created successfully');
    }

    /**
     * Display the specified feature flag
     */
    public function show(string $id)
    {
        $flag = FeatureFlag::with(['variants', 'overrides', 'creator'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($flag, 'Feature flag retrieved successfully');
        }

        return view('feature-management.flags.show', compact('flag'));
    }

    /**
     * Show the form for editing the specified feature flag
     */
    public function edit(string $id)
    {
        $flag = FeatureFlag::findOrFail($id);

        return view('feature-management.flags.edit', compact('flag'));
    }

    /**
     * Update the specified feature flag
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), FeatureFlag::updateRules(), FeatureFlag::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $flag = FeatureFlag::findOrFail($id);
        $flag->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($flag, 'Feature flag updated successfully');
        }

        return redirect()->route('feature-flags.show', $flag->flag_id)
            ->with('success', 'Feature flag updated successfully');
    }

    /**
     * Remove the specified feature flag
     */
    public function destroy(string $id)
    {
        $flag = FeatureFlag::findOrFail($id);
        $flag->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Feature flag deleted successfully');
        }

        return redirect()->route('feature-flags.index')
            ->with('success', 'Feature flag deleted successfully');
    }

    /**
     * Evaluate a feature flag
     */
    public function evaluate(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|uuid',
            'org_id' => 'nullable|uuid',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $flag = FeatureFlag::findOrFail($id);

        $result = $flag->evaluate(
            $request->user_id ?? auth()->id(),
            $request->org_id ?? session('current_org_id'),
            $request->context ?? []
        );

        return $this->success([
            'flag_key' => $flag->key,
            'enabled' => $result,
            'type' => $flag->type,
        ], 'Feature flag evaluated successfully');
    }

    /**
     * Get variant for a feature flag
     */
    public function getVariant(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $flag = FeatureFlag::findOrFail($id);
        $variant = $this->flagService->getVariant($flag, $request->identifier);

        return $this->success([
            'flag_key' => $flag->key,
            'variant' => $variant,
        ], 'Variant retrieved successfully');
    }

    /**
     * Enable a feature flag
     */
    public function enable(string $id)
    {
        $flag = FeatureFlag::findOrFail($id);
        $flag->enable();

        if (request()->expectsJson()) {
            return $this->success($flag, 'Feature flag enabled successfully');
        }

        return redirect()->route('feature-flags.show', $flag->flag_id)
            ->with('success', 'Feature flag enabled successfully');
    }

    /**
     * Disable a feature flag
     */
    public function disable(string $id)
    {
        $flag = FeatureFlag::findOrFail($id);
        $flag->disable();

        if (request()->expectsJson()) {
            return $this->success($flag, 'Feature flag disabled successfully');
        }

        return redirect()->route('feature-flags.show', $flag->flag_id)
            ->with('success', 'Feature flag disabled successfully');
    }

    /**
     * Archive a feature flag
     */
    public function archive(string $id)
    {
        $flag = FeatureFlag::findOrFail($id);
        $flag->archive();

        if (request()->expectsJson()) {
            return $this->success($flag, 'Feature flag archived successfully');
        }

        return redirect()->route('feature-flags.index')
            ->with('success', 'Feature flag archived successfully');
    }

    /**
     * Update rollout percentage
     */
    public function updateRollout(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'percentage' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $flag = FeatureFlag::findOrFail($id);
        $flag->setRolloutPercentage($request->percentage);

        if ($request->expectsJson()) {
            return $this->success($flag, 'Rollout percentage updated successfully');
        }

        return redirect()->route('feature-flags.show', $flag->flag_id)
            ->with('success', 'Rollout percentage updated successfully');
    }

    /**
     * Add user to whitelist
     */
    public function addToWhitelist(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $flag = FeatureFlag::findOrFail($id);
        $flag->addToWhitelist($request->user_ids);

        return $this->success($flag, 'Users added to whitelist successfully');
    }

    /**
     * Add user to blacklist
     */
    public function addToBlacklist(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $flag = FeatureFlag::findOrFail($id);
        $flag->addToBlacklist($request->user_ids);

        return $this->success($flag, 'Users added to blacklist successfully');
    }

    /**
     * Get feature flag analytics
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $analytics = $this->flagService->getAnalytics($orgId);

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Analytics retrieved successfully');
        }

        return view('feature-management.flags.analytics', compact('analytics'));
    }

    /**
     * Get flag evaluation stats
     */
    public function stats(string $id)
    {
        $flag = FeatureFlag::findOrFail($id);

        $stats = [
            'total_evaluations' => $flag->evaluation_count,
            'last_evaluated' => $flag->last_evaluated_at,
            'is_active' => $flag->isActive(),
            'rollout_percentage' => $flag->rollout_percentage,
            'whitelist_count' => count($flag->whitelist_user_ids ?? []),
            'blacklist_count' => count($flag->blacklist_user_ids ?? []),
            'override_count' => $flag->overrides()->count(),
            'variant_count' => $flag->variants()->count(),
        ];

        return $this->success($stats, 'Feature flag stats retrieved successfully');
    }

    /**
     * Bulk enable flags
     */
    public function bulkEnable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'flag_ids' => 'required|array',
            'flag_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $updated = $this->flagService->bulkEnable($request->flag_ids);

        return $this->success([
            'updated_count' => $updated,
        ], 'Feature flags enabled successfully');
    }

    /**
     * Bulk disable flags
     */
    public function bulkDisable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'flag_ids' => 'required|array',
            'flag_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $updated = $this->flagService->bulkDisable($request->flag_ids);

        return $this->success([
            'updated_count' => $updated,
        ], 'Feature flags disabled successfully');
    }
}
