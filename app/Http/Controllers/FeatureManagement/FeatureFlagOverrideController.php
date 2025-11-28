<?php

namespace App\Http\Controllers\FeatureManagement;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\FeatureManagement\FeatureFlagOverride;
use App\Models\FeatureManagement\FeatureFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureFlagOverrideController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of overrides for a flag
     */
    public function index(Request $request, string $flagId)
    {
        $flag = FeatureFlag::findOrFail($flagId);

        $overrides = $flag->overrides()
            ->when($request->type, fn($q) => $q->where('override_type', $request->type))
            ->when($request->active_only, fn($q) => $q->active())
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($overrides, 'Overrides retrieved successfully');
        }

        return view('feature-management.overrides.index', compact('flag', 'overrides'));
    }

    /**
     * Store a newly created override
     */
    public function store(Request $request, string $flagId)
    {
        $flag = FeatureFlag::findOrFail($flagId);

        $validator = Validator::make($request->all(), FeatureFlagOverride::createRules(), FeatureFlagOverride::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $override = FeatureFlagOverride::create(array_merge($request->all(), [
            'flag_id' => $flagId,
            'org_id' => session('current_org_id'),
        ]));

        if ($request->expectsJson()) {
            return $this->created($override, 'Override created successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.created_success'));
    }

    /**
     * Display the specified override
     */
    public function show(string $flagId, string $id)
    {
        $override = FeatureFlagOverride::where('flag_id', $flagId)
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($override, 'Override retrieved successfully');
        }

        return view('feature-management.overrides.show', compact('override'));
    }

    /**
     * Update the specified override
     */
    public function update(Request $request, string $flagId, string $id)
    {
        $validator = Validator::make($request->all(), FeatureFlagOverride::updateRules(), FeatureFlagOverride::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $override = FeatureFlagOverride::where('flag_id', $flagId)
            ->findOrFail($id);

        $override->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($override, 'Override updated successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.updated_success'));
    }

    /**
     * Remove the specified override
     */
    public function destroy(string $flagId, string $id)
    {
        $override = FeatureFlagOverride::where('flag_id', $flagId)
            ->findOrFail($id);

        $override->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Override deleted successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.deleted_success'));
    }

    /**
     * Activate an override
     */
    public function activate(string $flagId, string $id)
    {
        $override = FeatureFlagOverride::where('flag_id', $flagId)
            ->findOrFail($id);

        $override->activate();

        if (request()->expectsJson()) {
            return $this->success($override, 'Override activated successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.activated_success'));
    }

    /**
     * Deactivate an override
     */
    public function deactivate(string $flagId, string $id)
    {
        $override = FeatureFlagOverride::where('flag_id', $flagId)
            ->findOrFail($id);

        $override->deactivate();

        if (request()->expectsJson()) {
            return $this->success($override, 'Override deactivated successfully');
        }

        return redirect()->route('feature-flags.show', $flagId)
            ->with('success', __('features.activated_success'));
    }

    /**
     * Create override for a user
     */
    public function createForUser(Request $request, string $flagId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid|exists:cmis.users,user_id',
            'value' => 'required|boolean',
            'reason' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $override = FeatureFlagOverride::createForUser(
            $flagId,
            session('current_org_id'),
            $request->user_id,
            $request->value,
            $request->reason,
            $request->expires_at ? new \DateTime($request->expires_at) : null
        );

        return $this->created($override, 'User override created successfully');
    }

    /**
     * Create override for an organization
     */
    public function createForOrganization(Request $request, string $flagId)
    {
        $validator = Validator::make($request->all(), [
            'target_org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'value' => 'required|boolean',
            'reason' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $override = FeatureFlagOverride::createForOrganization(
            $flagId,
            session('current_org_id'),
            $request->target_org_id,
            $request->value,
            $request->reason,
            $request->expires_at ? new \DateTime($request->expires_at) : null
        );

        return $this->created($override, 'Organization override created successfully');
    }

    /**
     * Create override for a role
     */
    public function createForRole(Request $request, string $flagId)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|uuid|exists:cmis.roles,role_id',
            'value' => 'required|boolean',
            'reason' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $override = FeatureFlagOverride::createForRole(
            $flagId,
            session('current_org_id'),
            $request->role_id,
            $request->value,
            $request->reason,
            $request->expires_at ? new \DateTime($request->expires_at) : null
        );

        return $this->created($override, 'Role override created successfully');
    }

    /**
     * Get active overrides for a user
     */
    public function getUserOverrides(Request $request, string $userId)
    {
        $overrides = FeatureFlagOverride::where('override_type', FeatureFlagOverride::TYPE_USER)
            ->where('override_id_value', $userId)
            ->active()
            ->with('flag')
            ->get();

        return $this->success($overrides, 'User overrides retrieved successfully');
    }

    /**
     * Bulk activate overrides
     */
    public function bulkActivate(Request $request, string $flagId)
    {
        $validator = Validator::make($request->all(), [
            'override_ids' => 'required|array',
            'override_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $updated = FeatureFlagOverride::where('flag_id', $flagId)
            ->whereIn('override_id', $request->override_ids)
            ->update(['is_active' => true]);

        return $this->success([
            'updated_count' => $updated,
        ], 'Overrides activated successfully');
    }

    /**
     * Bulk deactivate overrides
     */
    public function bulkDeactivate(Request $request, string $flagId)
    {
        $validator = Validator::make($request->all(), [
            'override_ids' => 'required|array',
            'override_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $updated = FeatureFlagOverride::where('flag_id', $flagId)
            ->whereIn('override_id', $request->override_ids)
            ->update(['is_active' => false]);

        return $this->success([
            'updated_count' => $updated,
        ], 'Overrides deactivated successfully');
    }

    /**
     * Clean up expired overrides
     */
    public function cleanupExpired(string $flagId)
    {
        $deleted = FeatureFlagOverride::where('flag_id', $flagId)
            ->where('expires_at', '<', now())
            ->delete();

        return $this->success([
            'deleted_count' => $deleted,
        ], 'Expired overrides cleaned up successfully');
    }
}
