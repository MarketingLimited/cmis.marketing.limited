<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Social\ProfileManagementService;
use App\Services\Social\QueueSlotLabelService;
use App\Services\Social\BoostConfigurationService;
use App\Models\Social\ProfileGroup;
use App\Models\Platform\BoostRule;
use App\Models\Platform\AdAccount;
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
    protected QueueSlotLabelService $labelService;

    public function __construct(ProfileManagementService $service, QueueSlotLabelService $labelService)
    {
        $this->service = $service;
        $this->labelService = $labelService;
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
        $queueLabels = $this->labelService->getLabels($org);

        // Get organization for timezone inheritance display
        $organization = \App\Models\Core\Org::find($org);

        // Get ad accounts for boost settings (filtered by platform)
        $platformMapping = [
            'facebook' => 'meta',
            'instagram' => 'meta',
            'threads' => 'meta',
            'meta' => 'meta',
            'google' => 'google',
            'youtube' => 'google',
            'tiktok' => 'tiktok',
            'linkedin' => 'linkedin',
            'twitter' => 'twitter',
            'snapchat' => 'snapchat',
            'pinterest' => 'pinterest',
        ];
        $adPlatform = $platformMapping[$profile->platform] ?? null;

        $adAccountsQuery = AdAccount::where('org_id', $org)
            ->active()
            ->connected();

        if ($adPlatform) {
            $adAccountsQuery->where('platform', $adPlatform);
        }

        $adAccounts = $adAccountsQuery->get();

        if ($request->wantsJson()) {
            return $this->success([
                'profile' => $profile,
                'profile_groups' => $profileGroups,
                'queue_settings' => $queueSettings,
                'boost_rules' => $boostRules,
                'industries' => $industries,
                'queue_labels' => $queueLabels,
                'organization' => $organization,
                'ad_accounts' => $adAccounts,
            ], __('profiles.profile_retrieved'));
        }

        return view('settings.profiles.show', [
            'profile' => $profile,
            'profileGroups' => $profileGroups,
            'queueSettings' => $queueSettings,
            'boostRules' => $boostRules,
            'industries' => $industries,
            'queueLabels' => $queueLabels,
            'currentOrg' => $org,
            'organization' => $organization,
            'adAccounts' => $adAccounts,
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
            'timezone' => 'nullable|string|max:100',
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
        // Validation rules support both old format (time strings) and new format (slot objects)
        $validator = Validator::make($request->all(), [
            'queue_enabled' => 'required|boolean',
            'schedule' => 'nullable|array',
            'schedule.*' => 'nullable|array',
            // Each slot can be either a time string or an object with time, label_id, is_evergreen
            'schedule.*.*' => 'nullable',
            'schedule.*.*.time' => 'nullable|date_format:H:i',
            'schedule.*.*.label_id' => 'nullable|uuid',
            'schedule.*.*.is_evergreen' => 'nullable|boolean',
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

    /**
     * Get ad accounts compatible with a profile's platform.
     *
     * GET /orgs/{org}/settings/profiles/{integration_id}/ad-accounts
     */
    public function getAdAccounts(Request $request, string $org, string $integrationId): JsonResponse
    {
        $profile = $this->service->getProfile($org, $integrationId);

        if (!$profile) {
            return $this->notFound(__('profiles.profile_not_found'));
        }

        $platformMapping = [
            'facebook' => 'meta',
            'instagram' => 'meta',
            'threads' => 'meta',
            'meta' => 'meta',
            'google' => 'google',
            'youtube' => 'google',
            'tiktok' => 'tiktok',
            'linkedin' => 'linkedin',
            'twitter' => 'twitter',
            'snapchat' => 'snapchat',
            'pinterest' => 'pinterest',
        ];

        $adPlatform = $platformMapping[$profile->platform] ?? null;

        $query = AdAccount::where('org_id', $org)
            ->active()
            ->connected();

        if ($adPlatform) {
            $query->where('platform', $adPlatform);
        }

        $adAccounts = $query->get(['id', 'account_name', 'platform', 'currency', 'status', 'balance', 'daily_spend_limit']);

        return $this->success($adAccounts, __('profiles.ad_accounts_retrieved'));
    }

    /**
     * Get available audiences for boost targeting.
     *
     * GET /orgs/{org}/settings/profiles/{integration_id}/audiences
     */
    public function getAudiences(Request $request, string $org, string $integrationId): JsonResponse
    {
        $profile = $this->service->getProfile($org, $integrationId);

        if (!$profile) {
            return $this->notFound(__('profiles.profile_not_found'));
        }

        $adAccountId = $request->query('ad_account_id');
        $type = $request->query('type', 'all'); // custom, lookalike, saved, all

        // Get audiences from the database for this org
        $query = \App\Models\AdPlatform\AdAudience::where('org_id', $org);

        if ($adAccountId) {
            $query->where('ad_account_id', $adAccountId);
        }

        if ($type !== 'all') {
            $query->where('audience_type', $type);
        }

        $audiences = $query->get([
            'audience_id as id',
            'name',
            'audience_type as type',
            'approximate_count',
            'lookalike_audience as lookalike_ratio',
            'status',
        ]);

        return $this->success($audiences, __('profiles.audiences_retrieved'));
    }

    /**
     * Validate boost budget against ad account limits.
     *
     * POST /orgs/{org}/settings/profiles/{integration_id}/validate-budget
     */
    public function validateBudget(Request $request, string $org, string $integrationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ad_account_id' => 'required|uuid',
            'budget_amount' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), __('common.validation_error'));
        }

        $adAccount = AdAccount::where('org_id', $org)
            ->where('id', $request->input('ad_account_id'))
            ->first();

        if (!$adAccount) {
            return $this->notFound(__('profiles.ad_account_not_found'));
        }

        $validation = [
            'valid' => true,
            'warnings' => [],
            'errors' => [],
        ];

        $budgetAmount = (float) $request->input('budget_amount');
        $durationDays = (int) $request->input('duration_days');
        $dailyBudget = $budgetAmount / $durationDays;

        // Check daily spend limit
        if ($adAccount->daily_spend_limit && $dailyBudget > $adAccount->daily_spend_limit) {
            $validation['warnings'][] = __('profiles.exceeds_daily_limit', [
                'limit' => number_format($adAccount->daily_spend_limit, 2),
                'currency' => $adAccount->currency ?? 'USD',
            ]);
        }

        // Check account balance
        if ($adAccount->balance !== null && $budgetAmount > $adAccount->balance) {
            $validation['errors'][] = __('profiles.exceeds_balance', [
                'balance' => number_format($adAccount->balance, 2),
                'currency' => $adAccount->currency ?? 'USD',
            ]);
            $validation['valid'] = false;
        }

        // Check monthly budget limit if set
        if ($adAccount->monthly_budget_limit) {
            $remainingMonthly = $adAccount->monthly_budget_limit - ($adAccount->amount_spent ?? 0);
            if ($budgetAmount > $remainingMonthly) {
                $validation['warnings'][] = __('profiles.may_exceed_monthly', [
                    'remaining' => number_format($remainingMonthly, 2),
                ]);
            }
        }

        // Check daily budget limit
        if ($adAccount->daily_budget_limit && $dailyBudget > $adAccount->daily_budget_limit) {
            $validation['errors'][] = __('profiles.exceeds_spend_cap');
            $validation['valid'] = false;
        }

        return $this->success($validation, __('profiles.budget_validated'));
    }

    /**
     * Get platform-specific boost configuration.
     *
     * GET /orgs/{org}/settings/profiles/{integration_id}/boost-config
     *
     * Returns objectives, placements, bidding strategies, special features,
     * and other platform-specific options for the boost modal UI.
     */
    public function getBoostConfig(Request $request, string $org, string $integrationId): JsonResponse
    {
        $adAccountId = $request->query('ad_account_id');

        if (!$adAccountId) {
            return $this->error(__('profiles.ad_account_required'), 400);
        }

        $adAccount = AdAccount::where('org_id', $org)
            ->where('id', $adAccountId)
            ->first();

        if (!$adAccount) {
            return $this->notFound(__('profiles.ad_account_not_found'));
        }

        $configService = app(BoostConfigurationService::class);
        $locale = app()->getLocale();

        try {
            $platformConfig = $configService->getConfigForPlatform($adAccount->platform);

            // Apply locale-specific translations
            $response = [
                'platform' => $adAccount->platform,
                'platform_name' => $platformConfig['name'] ?? ucfirst($adAccount->platform),
                'objectives' => $configService->getObjectives($adAccount->platform, $locale),
                'placements' => $configService->getPlacements($adAccount->platform, $locale),
                'special_features' => $configService->getSpecialFeatures($adAccount->platform),
                'budget_multiplier' => $configService->getBudgetMultiplier($adAccount->platform),
                'min_budget' => $configService->getMinBudget($adAccount->platform),
                'min_audience_size' => $configService->getMinAudienceSize($adAccount->platform),
                'currency_symbol' => $platformConfig['currency_symbol'] ?? '$',
            ];

            // Add ad formats if available
            $adFormats = $configService->getAdFormats($adAccount->platform, $locale);
            if (!empty($adFormats)) {
                $response['ad_formats'] = $adFormats;
            }

            // Add bidding strategies if available
            $biddingStrategies = $configService->getBiddingStrategies($adAccount->platform, $locale);
            if (!empty($biddingStrategies)) {
                $response['bidding_strategies'] = $biddingStrategies;
            }

            // Add optimization goals if available
            $optimizationGoals = $configService->getOptimizationGoals($adAccount->platform, $locale);
            if (!empty($optimizationGoals)) {
                $response['optimization_goals'] = $optimizationGoals;
            }

            // Add ad types if available (Snapchat)
            $adTypes = $configService->getAdTypes($adAccount->platform, $locale);
            if (!empty($adTypes)) {
                $response['ad_types'] = $adTypes;
            }

            // Add bid types if available (TikTok)
            $bidTypes = $configService->getBidTypes($adAccount->platform, $locale);
            if (!empty($bidTypes)) {
                $response['bid_types'] = $bidTypes;
            }

            // Add B2B targeting options if available (LinkedIn)
            if ($configService->supportsB2BTargeting($adAccount->platform)) {
                $response['b2b_targeting'] = $configService->getB2BTargeting($adAccount->platform);
                $response['company_sizes'] = $configService->getCompanySizes($adAccount->platform, $locale);
                $response['seniority_levels'] = $configService->getSeniorityLevels($adAccount->platform, $locale);
            }

            return $this->success($response, __('profiles.boost_config_retrieved'));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->serverError(__('profiles.boost_config_failed'));
        }
    }

    /**
     * Get connected messaging accounts for boost destination types.
     *
     * GET /orgs/{org}/settings/profiles/{integration_id}/messaging-accounts
     *
     * Returns WhatsApp numbers, Messenger pages, and Instagram DM accounts
     * connected to the organization for use as messaging destinations in boost campaigns.
     */
    public function getMessagingAccounts(Request $request, string $org, string $integrationId): JsonResponse
    {
        $configService = app(BoostConfigurationService::class);

        try {
            $accounts = $configService->getConnectedMessagingAccounts($org, 'meta');

            return $this->success([
                'accounts' => $accounts,
                'can_connect_whatsapp' => true, // Always allow connecting new accounts
                'whatsapp_connect_url' => route('connectors.connect', ['provider' => 'whatsapp']),
            ], __('profiles.messaging_accounts_retrieved'));
        } catch (\Exception $e) {
            return $this->serverError(__('profiles.messaging_accounts_failed'));
        }
    }
}
