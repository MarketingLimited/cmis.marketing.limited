<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\BoostRule;
use App\Models\Social\ProfileGroup;
use App\Models\Platform\AdAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BoostRuleSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of boost rules.
     */
    public function index(Request $request, string $org)
    {
        $boostRules = BoostRule::where('org_id', $org)
            ->with(['profileGroup', 'adAccount', 'creator'])
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return $this->success($boostRules, 'Boost rules retrieved successfully');
        }

        return view('settings.boost-rules.index', [
            'boostRules' => $boostRules,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for creating a new boost rule.
     */
    public function create(Request $request, string $org)
    {
        $profileGroups = ProfileGroup::where('org_id', $org)->get();
        $adAccounts = AdAccount::where('org_id', $org)->get();

        return view('settings.boost-rules.create', [
            'currentOrg' => $org,
            'profileGroups' => $profileGroups,
            'adAccounts' => $adAccounts,
            'triggerTypes' => $this->getTriggerTypes(),
            'budgetTypes' => $this->getBudgetTypes(),
        ]);
    }

    /**
     * Store a newly created boost rule.
     */
    public function store(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'ad_account_id' => 'required|uuid|exists:pgsql.cmis.ad_accounts,account_id',
            'trigger_type' => 'required|string|max:50',
            'trigger_threshold' => 'required|numeric|min:0',
            'trigger_metric' => 'required|string|max:50',
            'trigger_time_window_hours' => 'required|integer|min:1|max:168',
            'budget_type' => 'required|string|in:fixed,percentage,dynamic',
            'budget_amount' => 'required|numeric|min:1',
            'budget_currency' => 'required|string|max:3',
            'duration_hours' => 'required|integer|min:1|max:168',
            'targeting_options' => 'nullable|array',
            'max_boosts_per_day' => 'nullable|integer|min:1',
            'max_budget_per_day' => 'nullable|numeric|min:0',
            'platforms' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $boostRule = BoostRule::create([
                'org_id' => $org,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'profile_group_id' => $request->input('profile_group_id'),
                'ad_account_id' => $request->input('ad_account_id'),
                'trigger_type' => $request->input('trigger_type'),
                'trigger_threshold' => $request->input('trigger_threshold'),
                'trigger_metric' => $request->input('trigger_metric'),
                'trigger_time_window_hours' => $request->input('trigger_time_window_hours'),
                'budget_type' => $request->input('budget_type'),
                'budget_amount' => $request->input('budget_amount'),
                'budget_currency' => $request->input('budget_currency'),
                'duration_hours' => $request->input('duration_hours'),
                'targeting_options' => $request->input('targeting_options', []),
                'max_boosts_per_day' => $request->input('max_boosts_per_day'),
                'max_budget_per_day' => $request->input('max_budget_per_day'),
                'platforms' => $request->input('platforms', []),
                'is_active' => $request->input('is_active', true),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return $this->created($boostRule, 'Boost rule created successfully');
            }

            return redirect()->route('orgs.settings.boost-rules.show', ['org' => $org, 'rule' => $boostRule->boost_rule_id])
                ->with('success', 'Boost rule created successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to create boost rule: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to create boost rule'])->withInput();
        }
    }

    /**
     * Display the specified boost rule.
     */
    public function show(Request $request, string $org, string $rule)
    {
        $boostRule = BoostRule::where('org_id', $org)
            ->where('boost_rule_id', $rule)
            ->with(['profileGroup', 'adAccount', 'creator'])
            ->firstOrFail();

        if ($request->wantsJson()) {
            return $this->success($boostRule, 'Boost rule retrieved successfully');
        }

        return view('settings.boost-rules.show', [
            'rule' => $boostRule,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for editing the specified boost rule.
     */
    public function edit(Request $request, string $org, string $rule)
    {
        $boostRule = BoostRule::where('org_id', $org)
            ->where('boost_rule_id', $rule)
            ->firstOrFail();

        $profileGroups = ProfileGroup::where('org_id', $org)->get();
        $adAccounts = AdAccount::where('org_id', $org)->get();

        return view('settings.boost-rules.edit', [
            'rule' => $boostRule,
            'currentOrg' => $org,
            'profileGroups' => $profileGroups,
            'adAccounts' => $adAccounts,
            'triggerTypes' => $this->getTriggerTypes(),
            'budgetTypes' => $this->getBudgetTypes(),
        ]);
    }

    /**
     * Update the specified boost rule.
     */
    public function update(Request $request, string $org, string $rule)
    {
        $boostRule = BoostRule::where('org_id', $org)
            ->where('boost_rule_id', $rule)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'ad_account_id' => 'required|uuid|exists:pgsql.cmis.ad_accounts,account_id',
            'trigger_type' => 'required|string|max:50',
            'trigger_threshold' => 'required|numeric|min:0',
            'trigger_metric' => 'required|string|max:50',
            'trigger_time_window_hours' => 'required|integer|min:1|max:168',
            'budget_type' => 'required|string|in:fixed,percentage,dynamic',
            'budget_amount' => 'required|numeric|min:1',
            'budget_currency' => 'required|string|max:3',
            'duration_hours' => 'required|integer|min:1|max:168',
            'targeting_options' => 'nullable|array',
            'max_boosts_per_day' => 'nullable|integer|min:1',
            'max_budget_per_day' => 'nullable|numeric|min:0',
            'platforms' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $boostRule->update($request->only([
                'name',
                'description',
                'profile_group_id',
                'ad_account_id',
                'trigger_type',
                'trigger_threshold',
                'trigger_metric',
                'trigger_time_window_hours',
                'budget_type',
                'budget_amount',
                'budget_currency',
                'duration_hours',
                'targeting_options',
                'max_boosts_per_day',
                'max_budget_per_day',
                'platforms',
                'is_active',
            ]));

            if ($request->wantsJson()) {
                return $this->success($boostRule, 'Boost rule updated successfully');
            }

            return redirect()->route('orgs.settings.boost-rules.show', ['org' => $org, 'rule' => $rule])
                ->with('success', 'Boost rule updated successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update boost rule: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update boost rule'])->withInput();
        }
    }

    /**
     * Remove the specified boost rule.
     */
    public function destroy(Request $request, string $org, string $rule)
    {
        $boostRule = BoostRule::where('org_id', $org)
            ->where('boost_rule_id', $rule)
            ->firstOrFail();

        try {
            $boostRule->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Boost rule deleted successfully');
            }

            return redirect()->route('orgs.settings.boost-rules.index', ['org' => $org])
                ->with('success', 'Boost rule deleted successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to delete boost rule: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete boost rule']);
        }
    }

    /**
     * Get trigger type options.
     */
    private function getTriggerTypes(): array
    {
        return [
            'engagement_rate' => 'Engagement Rate Threshold',
            'likes' => 'Number of Likes',
            'comments' => 'Number of Comments',
            'shares' => 'Number of Shares',
            'reach' => 'Reach Threshold',
            'impressions' => 'Impressions Threshold',
            'video_views' => 'Video Views',
            'link_clicks' => 'Link Clicks',
        ];
    }

    /**
     * Get budget type options.
     */
    private function getBudgetTypes(): array
    {
        return [
            'fixed' => 'Fixed Amount',
            'percentage' => 'Percentage of Monthly Budget',
            'dynamic' => 'Dynamic (Based on Performance)',
        ];
    }
}
