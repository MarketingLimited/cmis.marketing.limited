<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Social\ProfileManagementService;
use App\Models\Social\ProfileGroup;
use App\Models\Platform\BoostRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controller for managing social profiles in a VistaSocial-like interface.
 * Handles profile listing, individual profile management, and related settings.
 */
class ProfileManagementController extends Controller
{
    use ApiResponse;

    protected ProfileManagementService $service;

    public function __construct(ProfileManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the profile management list page.
     *
     * GET /orgs/{org}/settings/profiles
     */
    public function index(Request $request, string $org)
    {
        $filters = $request->only(['search', 'platform', 'status', 'group_id', 'sort_by', 'sort_dir']);
        $perPage = $request->input('per_page', 15);

        $profiles = $this->service->getProfiles($org, $filters, $perPage);
        $profileGroups = $this->service->getAvailableGroups($org);
        $platforms = $this->service->getAvailablePlatforms();
        $stats = $this->service->getProfileStats($org);

        if ($request->wantsJson()) {
            return $this->success([
                'profiles' => $profiles,
                'profile_groups' => $profileGroups,
                'platforms' => $platforms,
                'stats' => $stats,
            ], __('profiles.profiles_retrieved'));
        }

        return view('settings.profiles.index', [
            'profiles' => $profiles,
            'profileGroups' => $profileGroups,
            'platforms' => $platforms,
            'stats' => $stats,
            'filters' => $filters,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Display a single profile management page.
     *
     * GET /orgs/{org}/settings/profiles/{integration_id}
     */
    public function show(Request $request, string $org, string $integrationId)
    {
        $profile = $this->service->getProfile($org, $integrationId);

        if (!$profile) {
            if ($request->wantsJson()) {
                return $this->notFound(__('profiles.profile_not_found'));
            }
            abort(404, __('profiles.profile_not_found'));
        }

        $profileGroups = $this->service->getAvailableGroups($org);
        $queueSettings = $this->service->getQueueSettings($org, $integrationId);
        $boostRules = $this->service->getBoostRules($org, $integrationId);
        $industries = $this->service->getAvailableIndustries();

        if ($request->wantsJson()) {
            return $this->success([
                'profile' => $profile,
                'profile_groups' => $profileGroups,
                'queue_settings' => $queueSettings,
                'boost_rules' => $boostRules,
                'industries' => $industries,
            ], __('profiles.profile_retrieved'));
        }

        return view('settings.profiles.show', [
            'profile' => $profile,
            'profileGroups' => $profileGroups,
            'queueSettings' => $queueSettings,
            'boostRules' => $boostRules,
            'industries' => $industries,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Update profile settings.
     *
     * PATCH /orgs/{org}/settings/profiles/{integration_id}
     */
    public function update(Request $request, string $org, string $integrationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:2000',
            'website_url' => 'nullable|url|max:500',
            'profile_type' => 'nullable|in:business,personal,creator',
            'is_enabled' => 'nullable|boolean',
            'auto_boost_enabled' => 'nullable|boolean',
            'custom_fields' => 'nullable|array',
            'profile_group_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), __('common.validation_error'));
        }

        $profile = $this->service->updateProfile($org, $integrationId, $request->all());

        if (!$profile) {
            return $this->notFound(__('profiles.profile_not_found'));
        }

        return $this->success($profile, __('profiles.profile_updated'));
    }

    /**
     * Update profile avatar.
     *
     * POST /orgs/{org}/settings/profiles/{integration_id}/avatar
     */
    public function updateAvatar(Request $request, string $org, string $integrationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), __('common.validation_error'));
        }

        try {
            $file = $request->file('avatar');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "profile-avatars/{$org}/{$filename}";

            Storage::disk('public')->put($path, file_get_contents($file));
            $avatarUrl = Storage::disk('public')->url($path);

            $profile = $this->service->updateAvatar($org, $integrationId, $avatarUrl);

            if (!$profile) {
                return $this->notFound(__('profiles.profile_not_found'));
            }

            return $this->success(['avatar_url' => $avatarUrl], __('profiles.avatar_updated'));
        } catch (\Exception $e) {
            return $this->serverError(__('profiles.avatar_upload_failed'));
        }
    }

    /**
     * Assign profile to a profile group.
     *
     * POST /orgs/{org}/settings/profiles/{integration_id}/groups
     */
    public function assignGroup(Request $request, string $org, string $integrationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), __('common.validation_error'));
        }

        $profile = $this->service->assignToGroup($org, $integrationId, $request->input('group_id'));

        if (!$profile) {
            return $this->error(__('profiles.assign_group_failed'), 400);
        }

        return $this->success($profile, __('profiles.group_assigned'));
    }

    /**
     * Remove profile from its group.
     *
     * DELETE /orgs/{org}/settings/profiles/{integration_id}/groups
     */
    public function removeFromGroup(Request $request, string $org, string $integrationId): JsonResponse
    {
        $profile = $this->service->removeFromGroup($org, $integrationId);

        if (!$profile) {
            return $this->notFound(__('profiles.profile_not_found'));
        }

        return $this->success($profile, __('profiles.group_removed'));
    }

    /**
     * Toggle profile enabled status.
     *
     * POST /orgs/{org}/settings/profiles/{integration_id}/toggle
     */
    public function toggleEnabled(Request $request, string $org, string $integrationId): JsonResponse
    {
        $profile = $this->service->toggleEnabled($org, $integrationId);

        if (!$profile) {
            return $this->notFound(__('profiles.profile_not_found'));
        }

        return $this->success($profile, __('profiles.status_toggled'));
    }

    /**
     * Refresh profile connection.
     *
     * POST /orgs/{org}/settings/profiles/{integration_id}/refresh
     */
    public function refreshConnection(Request $request, string $org, string $integrationId): JsonResponse
    {
        $result = $this->service->refreshConnection($org, $integrationId);

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(null, $result['message']);
    }

    /**
     * Disconnect (remove) a profile.
     *
     * DELETE /orgs/{org}/settings/profiles/{integration_id}
     */
    public function destroy(Request $request, string $org, string $integrationId): JsonResponse
    {
        $result = $this->service->disconnectProfile($org, $integrationId);

        if (!$result) {
            return $this->notFound(__('profiles.profile_not_found'));
        }

        return $this->deleted(__('profiles.profile_removed'));
    }

    /**
     * Get queue settings for a profile.
     *
     * GET /orgs/{org}/settings/profiles/{integration_id}/queue
     */
    public function getQueueSettings(Request $request, string $org, string $integrationId): JsonResponse
    {
        $settings = $this->service->getQueueSettings($org, $integrationId);

        return $this->success($settings, __('profiles.queue_settings_retrieved'));
    }

    /**
     * Update queue settings for a profile.
     *
     * PATCH /orgs/{org}/settings/profiles/{integration_id}/queue
     */
    public function updateQueueSettings(Request $request, string $org, string $integrationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'queue_enabled' => 'required|boolean',
            'schedule' => 'nullable|array',
            'schedule.*' => 'array',
            'schedule.*.*' => 'date_format:H:i',
            'days_enabled' => 'nullable|array',
            'days_enabled.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'posting_times' => 'nullable|array',
            'posting_times.*' => 'date_format:H:i',
            'posts_per_day' => 'nullable|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), __('common.validation_error'));
        }

        $settings = $this->service->updateQueueSettings($org, $integrationId, $request->all());

        return $this->success($settings, __('profiles.queue_settings_updated'));
    }

    /**
     * Get boost rules for a profile.
     *
     * GET /orgs/{org}/settings/profiles/{integration_id}/boosts
     */
    public function getBoostRules(Request $request, string $org, string $integrationId): JsonResponse
    {
        $rules = $this->service->getBoostRules($org, $integrationId);

        return $this->success($rules, __('profiles.boost_rules_retrieved'));
    }

    /**
     * Create a boost rule for a profile.
     *
     * POST /orgs/{org}/settings/profiles/{integration_id}/boosts
     */
    public function createBoostRule(Request $request, string $org, string $integrationId): JsonResponse
    {
        $profile = $this->service->getProfile($org, $integrationId);

        if (!$profile) {
            return $this->notFound(__('profiles.profile_not_found'));
        }

        if (!$profile->profile_group_id) {
            return $this->error(__('profiles.profile_must_be_in_group'), 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|in:manual,auto_after_publish,auto_performance',
            'delay_value' => 'nullable|integer|min:0',
            'delay_unit' => 'nullable|in:hours,days',
            'ad_account_id' => 'required|uuid',
            'budget_amount' => 'required|numeric|min:0',
            'budget_currency' => 'nullable|string|max:3',
            'duration_hours' => 'required|integer|min:1',
            'targeting_options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), __('common.validation_error'));
        }

        try {
            $boostRule = BoostRule::create([
                'org_id' => $org,
                'profile_group_id' => $profile->profile_group_id,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_active' => true,
                'trigger_type' => $request->input('trigger_type'),
                'delay_after_publish' => $request->has('delay_value') ? [
                    'value' => $request->input('delay_value'),
                    'unit' => $request->input('delay_unit', 'hours'),
                ] : null,
                'ad_account_id' => $request->input('ad_account_id'),
                'budget_amount' => $request->input('budget_amount'),
                'budget_currency' => $request->input('budget_currency', 'USD'),
                'duration_hours' => $request->input('duration_hours'),
                'apply_to_social_profiles' => [$integrationId],
                'targeting_options' => $request->input('targeting_options', []),
                'created_by' => auth()->id(),
            ]);

            return $this->created($boostRule, __('profiles.boost_created'));
        } catch (\Exception $e) {
            return $this->serverError(__('profiles.boost_create_failed'));
        }
    }

    /**
     * Update a boost rule.
     *
     * PATCH /orgs/{org}/settings/profiles/{integration_id}/boosts/{boost_id}
     */
    public function updateBoostRule(Request $request, string $org, string $integrationId, string $boostId): JsonResponse
    {
        $boostRule = BoostRule::where('org_id', $org)
            ->where('boost_rule_id', $boostId)
            ->first();

        if (!$boostRule) {
            return $this->notFound(__('profiles.boost_not_found'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'trigger_type' => 'nullable|in:manual,auto_after_publish,auto_performance',
            'delay_value' => 'nullable|integer|min:0',
            'delay_unit' => 'nullable|in:hours,days',
            'ad_account_id' => 'nullable|uuid',
            'budget_amount' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|integer|min:1',
            'targeting_options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), __('common.validation_error'));
        }

        try {
            $updateData = array_filter($request->only([
                'name', 'description', 'is_active', 'trigger_type',
                'ad_account_id', 'budget_amount', 'duration_hours', 'targeting_options',
            ]), fn ($value) => $value !== null);

            if ($request->has('delay_value')) {
                $updateData['delay_after_publish'] = [
                    'value' => $request->input('delay_value'),
                    'unit' => $request->input('delay_unit', 'hours'),
                ];
            }

            $boostRule->update($updateData);

            return $this->success($boostRule->fresh(), __('profiles.boost_updated'));
        } catch (\Exception $e) {
            return $this->serverError(__('profiles.boost_update_failed'));
        }
    }

    /**
     * Delete a boost rule.
     *
     * DELETE /orgs/{org}/settings/profiles/{integration_id}/boosts/{boost_id}
     */
    public function deleteBoostRule(Request $request, string $org, string $integrationId, string $boostId): JsonResponse
    {
        $boostRule = BoostRule::where('org_id', $org)
            ->where('boost_rule_id', $boostId)
            ->first();

        if (!$boostRule) {
            return $this->notFound(__('profiles.boost_not_found'));
        }

        $boostRule->delete();

        return $this->deleted(__('profiles.boost_deleted'));
    }

    /**
     * Toggle boost rule active status.
     *
     * POST /orgs/{org}/settings/profiles/{integration_id}/boosts/{boost_id}/toggle
     */
    public function toggleBoostRule(Request $request, string $org, string $integrationId, string $boostId): JsonResponse
    {
        $boostRule = BoostRule::where('org_id', $org)
            ->where('boost_rule_id', $boostId)
            ->first();

        if (!$boostRule) {
            return $this->notFound(__('profiles.boost_not_found'));
        }

        $boostRule->update(['is_active' => !$boostRule->is_active]);

        return $this->success($boostRule->fresh(), __('profiles.boost_toggled'));
    }

    /**
     * Get profile statistics for dashboard widgets.
     *
     * GET /orgs/{org}/settings/profiles/stats
     */
    public function stats(Request $request, string $org): JsonResponse
    {
        $stats = $this->service->getProfileStats($org);

        return $this->success($stats, __('profiles.stats_retrieved'));
    }
}
