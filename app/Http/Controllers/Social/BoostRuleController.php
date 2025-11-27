<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\BoostRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * BoostRuleController
 *
 * Manages automated boost rules for promoting social media posts.
 * Includes manual, time-based, and performance-based triggers.
 */
class BoostRuleController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all boost rules for a profile group
     *
     * GET /api/orgs/{org_id}/profile-groups/{group_id}/boost-rules
     */
    public function index(string $orgId, string $groupId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'trigger_type' => ['nullable', Rule::in(BoostRule::getAvailableTriggerTypes())],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $query = BoostRule::where('profile_group_id', $groupId);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('trigger_type')) {
                $query->byTriggerType($request->input('trigger_type'));
            }

            $query->with(['creator', 'profileGroup', 'adAccount']);

            $perPage = $request->input('per_page', 15);
            $rules = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->paginated($rules, 'Boost rules retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve boost rules: ' . $e->getMessage());
        }
    }

    /**
     * Create a new boost rule
     *
     * POST /api/orgs/{org_id}/profile-groups/{group_id}/boost-rules
     */
    public function store(string $orgId, string $groupId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'trigger_type' => ['required', Rule::in(BoostRule::getAvailableTriggerTypes())],
            'delay_after_publish' => 'nullable|array',
            'delay_after_publish.value' => 'required_with:delay_after_publish|integer|min:1',
            'delay_after_publish.unit' => 'required_with:delay_after_publish|string|in:minutes,hours,days',
            'performance_threshold' => 'nullable|array',
            'performance_threshold.metric' => 'required_with:performance_threshold|string|in:engagement_rate,likes,comments,shares,impressions,reach',
            'performance_threshold.operator' => 'required_with:performance_threshold|string|in:>,>=,<,<=,==',
            'performance_threshold.value' => 'required_with:performance_threshold|numeric|min:0',
            'performance_threshold.time_window_hours' => 'nullable|integer|min:1|max:168',
            'apply_to_social_profiles' => 'nullable|array',
            'apply_to_social_profiles.*' => 'uuid',
            'ad_account_id' => 'required|uuid|exists:pgsql.cmis.ad_accounts,id',
            'boost_config' => 'required|array',
            'boost_config.budget_amount' => 'required|numeric|min:1',
            'boost_config.budget_type' => 'required|string|in:daily,lifetime',
            'boost_config.duration_days' => 'required|integer|min:1|max:30',
            'boost_config.objective' => 'required|string|in:reach,engagement,traffic,conversions',
            'boost_config.audience' => 'nullable|array',
            'boost_config.audience.type' => 'nullable|string|in:auto,saved,lookalike,custom',
            'boost_config.audience.id' => 'nullable|string',
            'boost_config.audience.locations' => 'nullable|array',
            'boost_config.audience.age_min' => 'nullable|integer|min:13|max:65',
            'boost_config.audience.age_max' => 'nullable|integer|min:13|max:65',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Validate trigger-specific requirements
        $triggerType = $request->input('trigger_type');
        if ($triggerType === BoostRule::TRIGGER_AUTO_AFTER_PUBLISH && !$request->filled('delay_after_publish')) {
            return $this->validationError([
                'delay_after_publish' => ['Delay settings are required for auto_after_publish trigger type']
            ]);
        }
        if ($triggerType === BoostRule::TRIGGER_AUTO_PERFORMANCE && !$request->filled('performance_threshold')) {
            return $this->validationError([
                'performance_threshold' => ['Performance threshold is required for auto_performance trigger type']
            ]);
        }

        try {
            $rule = BoostRule::create([
                'org_id' => $orgId,
                'profile_group_id' => $groupId,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true),
                'trigger_type' => $triggerType,
                'delay_after_publish' => $request->input('delay_after_publish'),
                'performance_threshold' => $request->input('performance_threshold'),
                'apply_to_social_profiles' => $request->input('apply_to_social_profiles', []),
                'ad_account_id' => $request->input('ad_account_id'),
                'boost_config' => $request->input('boost_config'),
                'created_by' => Auth::id(),
            ]);

            $rule->load(['creator', 'profileGroup', 'adAccount']);

            return $this->created($rule, 'Boost rule created successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create boost rule: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific boost rule
     *
     * GET /api/orgs/{org_id}/profile-groups/{group_id}/boost-rules/{rule_id}
     */
    public function show(string $orgId, string $groupId, string $ruleId): JsonResponse
    {
        try {
            $rule = BoostRule::with(['creator', 'profileGroup', 'adAccount'])
                ->where('profile_group_id', $groupId)
                ->findOrFail($ruleId);

            return $this->success($rule, 'Boost rule retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Boost rule not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve boost rule: ' . $e->getMessage());
        }
    }

    /**
     * Update a boost rule
     *
     * PUT /api/orgs/{org_id}/profile-groups/{group_id}/boost-rules/{rule_id}
     */
    public function update(string $orgId, string $groupId, string $ruleId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'trigger_type' => ['sometimes', 'required', Rule::in(BoostRule::getAvailableTriggerTypes())],
            'delay_after_publish' => 'nullable|array',
            'delay_after_publish.value' => 'required_with:delay_after_publish|integer|min:1',
            'delay_after_publish.unit' => 'required_with:delay_after_publish|string|in:minutes,hours,days',
            'performance_threshold' => 'nullable|array',
            'performance_threshold.metric' => 'required_with:performance_threshold|string',
            'performance_threshold.operator' => 'required_with:performance_threshold|string|in:>,>=,<,<=,==',
            'performance_threshold.value' => 'required_with:performance_threshold|numeric|min:0',
            'performance_threshold.time_window_hours' => 'nullable|integer|min:1|max:168',
            'apply_to_social_profiles' => 'nullable|array',
            'ad_account_id' => 'sometimes|required|uuid|exists:pgsql.cmis.ad_accounts,id',
            'boost_config' => 'sometimes|required|array',
            'boost_config.budget_amount' => 'required_with:boost_config|numeric|min:1',
            'boost_config.budget_type' => 'required_with:boost_config|string|in:daily,lifetime',
            'boost_config.duration_days' => 'required_with:boost_config|integer|min:1|max:30',
            'boost_config.objective' => 'required_with:boost_config|string|in:reach,engagement,traffic,conversions',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $rule = BoostRule::where('profile_group_id', $groupId)
                ->findOrFail($ruleId);

            $rule->update($request->only([
                'name', 'description', 'is_active', 'trigger_type',
                'delay_after_publish', 'performance_threshold',
                'apply_to_social_profiles', 'ad_account_id', 'boost_config',
            ]));

            $rule->load(['creator', 'profileGroup', 'adAccount']);

            return $this->success($rule, 'Boost rule updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Boost rule not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update boost rule: ' . $e->getMessage());
        }
    }

    /**
     * Delete a boost rule (soft delete)
     *
     * DELETE /api/orgs/{org_id}/profile-groups/{group_id}/boost-rules/{rule_id}
     */
    public function destroy(string $orgId, string $groupId, string $ruleId): JsonResponse
    {
        try {
            $rule = BoostRule::where('profile_group_id', $groupId)
                ->findOrFail($ruleId);

            $rule->delete();

            return $this->deleted('Boost rule deleted successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Boost rule not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete boost rule: ' . $e->getMessage());
        }
    }

    /**
     * Toggle boost rule active status
     *
     * POST /api/orgs/{org_id}/profile-groups/{group_id}/boost-rules/{rule_id}/toggle
     */
    public function toggle(string $orgId, string $groupId, string $ruleId): JsonResponse
    {
        try {
            $rule = BoostRule::where('profile_group_id', $groupId)
                ->findOrFail($ruleId);

            $rule->is_active = !$rule->is_active;
            $rule->save();

            $status = $rule->is_active ? 'activated' : 'deactivated';

            return $this->success($rule, "Boost rule {$status} successfully");

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Boost rule not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to toggle boost rule: ' . $e->getMessage());
        }
    }
}
