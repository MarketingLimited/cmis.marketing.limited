<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\ProfileGroup;
use App\Models\Social\ProfileGroupMember;
use App\Models\Core\User;
use App\Models\Creative\BrandVoice;
use App\Models\Compliance\BrandSafetyPolicy;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfileGroupSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of profile groups.
     */
    public function index(Request $request, string $org)
    {
        $profileGroups = ProfileGroup::where('org_id', $org)
            ->with(['brandVoice', 'brandSafetyPolicy', 'creator'])
            ->withCount(['members', 'socialIntegrations'])
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return $this->success($profileGroups, 'Profile groups retrieved successfully');
        }

        return view('settings.profile-groups.index', [
            'profileGroups' => $profileGroups,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for creating a new profile group.
     */
    public function create(Request $request, string $org)
    {
        $brandVoices = BrandVoice::where('org_id', $org)->get();
        $brandSafetyPolicies = BrandSafetyPolicy::where('org_id', $org)->get();
        $availableProfiles = Integration::where('org_id', $org)
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereNull('profile_group_id')
            ->orderBy('platform')
            ->orderBy('account_name')
            ->get();

        return view('settings.profile-groups.create', [
            'currentOrg' => $org,
            'brandVoices' => $brandVoices,
            'brandSafetyPolicies' => $brandSafetyPolicies,
            'availableProfiles' => $availableProfiles,
            'timezones' => $this->getTimezones(),
            'languages' => $this->getLanguages(),
        ]);
    }

    /**
     * Store a newly created profile group.
     */
    public function store(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'client_location' => 'nullable|array',
            'client_location.country' => 'nullable|string|max:100',
            'client_location.city' => 'nullable|string|max:100',
            'logo_url' => 'nullable|url|max:500',
            'color' => 'nullable|string|max:7',
            'default_link_shortener' => 'nullable|string|max:50',
            'timezone' => 'required|string|max:50',
            'language' => 'required|string|max:10',
            'brand_voice_id' => 'nullable|uuid|exists:pgsql.cmis.brand_voices,voice_id',
            'brand_safety_policy_id' => 'nullable|uuid|exists:pgsql.cmis.brand_safety_policies,policy_id',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $profileGroup = ProfileGroup::create([
                'org_id' => $org,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'client_location' => $request->input('client_location'),
                'logo_url' => $request->input('logo_url'),
                'color' => $request->input('color', '#3B82F6'),
                'default_link_shortener' => $request->input('default_link_shortener'),
                'timezone' => $request->input('timezone'),
                'language' => $request->input('language'),
                'brand_voice_id' => $request->input('brand_voice_id'),
                'brand_safety_policy_id' => $request->input('brand_safety_policy_id'),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return $this->created($profileGroup, 'Profile group created successfully');
            }

            return redirect()->route('orgs.settings.profile-groups.show', ['org' => $org, 'group' => $profileGroup->group_id])
                ->with('success', 'Profile group created successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to create profile group: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to create profile group'])->withInput();
        }
    }

    /**
     * Display the specified profile group.
     */
    public function show(Request $request, string $org, string $group)
    {
        $profileGroup = ProfileGroup::where('org_id', $org)
            ->where('group_id', $group)
            ->with([
                'brandVoice',
                'brandSafetyPolicy',
                'creator',
                'members.user',
                'socialIntegrations',
                'approvalWorkflows',
                'adAccounts',
                'boostRules',
            ])
            ->firstOrFail();

        if ($request->wantsJson()) {
            return $this->success($profileGroup, 'Profile group retrieved successfully');
        }

        return view('settings.profile-groups.show', [
            'profileGroup' => $profileGroup,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for editing the specified profile group.
     */
    public function edit(Request $request, string $org, string $group)
    {
        $profileGroup = ProfileGroup::where('org_id', $org)
            ->where('group_id', $group)
            ->firstOrFail();

        $brandVoices = BrandVoice::where('org_id', $org)->get();
        $brandSafetyPolicies = BrandSafetyPolicy::where('org_id', $org)->get();

        return view('settings.profile-groups.edit', [
            'profileGroup' => $profileGroup,
            'currentOrg' => $org,
            'brandVoices' => $brandVoices,
            'brandSafetyPolicies' => $brandSafetyPolicies,
            'timezones' => $this->getTimezones(),
            'languages' => $this->getLanguages(),
        ]);
    }

    /**
     * Update the specified profile group.
     */
    public function update(Request $request, string $org, string $group)
    {
        $profileGroup = ProfileGroup::where('org_id', $org)
            ->where('group_id', $group)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'client_location' => 'nullable|array',
            'logo_url' => 'nullable|url|max:500',
            'color' => 'nullable|string|max:7',
            'default_link_shortener' => 'nullable|string|max:50',
            'timezone' => 'required|string|max:50',
            'language' => 'required|string|max:10',
            'brand_voice_id' => 'nullable|uuid|exists:pgsql.cmis.brand_voices,voice_id',
            'brand_safety_policy_id' => 'nullable|uuid|exists:pgsql.cmis.brand_safety_policies,policy_id',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $profileGroup->update($request->only([
                'name',
                'description',
                'client_location',
                'logo_url',
                'color',
                'default_link_shortener',
                'timezone',
                'language',
                'brand_voice_id',
                'brand_safety_policy_id',
            ]));

            if ($request->wantsJson()) {
                return $this->success($profileGroup, 'Profile group updated successfully');
            }

            return redirect()->route('orgs.settings.profile-groups.show', ['org' => $org, 'group' => $group])
                ->with('success', 'Profile group updated successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update profile group: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update profile group'])->withInput();
        }
    }

    /**
     * Remove the specified profile group.
     */
    public function destroy(Request $request, string $org, string $group)
    {
        $profileGroup = ProfileGroup::where('org_id', $org)
            ->where('group_id', $group)
            ->firstOrFail();

        try {
            $profileGroup->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Profile group deleted successfully');
            }

            return redirect()->route('orgs.settings.profile-groups.index', ['org' => $org])
                ->with('success', 'Profile group deleted successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to delete profile group: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete profile group']);
        }
    }

    /**
     * Display group members.
     */
    public function members(Request $request, string $org, string $group)
    {
        $profileGroup = ProfileGroup::where('org_id', $org)
            ->where('group_id', $group)
            ->with(['members.user'])
            ->firstOrFail();

        $availableUsers = User::whereHas('orgMemberships', function ($query) use ($org) {
            $query->where('org_id', $org);
        })->whereNotIn('user_id', $profileGroup->members->pluck('user_id'))->get();

        if ($request->wantsJson()) {
            return $this->success($profileGroup->members, 'Members retrieved successfully');
        }

        return view('settings.profile-groups.members', [
            'profileGroup' => $profileGroup,
            'members' => $profileGroup->members,
            'availableUsers' => $availableUsers,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Add a member to the profile group.
     */
    public function addMember(Request $request, string $org, string $group)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid|exists:pgsql.cmis.users,user_id',
            'role' => 'required|string|in:admin,editor,viewer',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator);
        }

        try {
            $member = ProfileGroupMember::create([
                'profile_group_id' => $group,
                'user_id' => $request->input('user_id'),
                'role' => $request->input('role'),
            ]);

            if ($request->wantsJson()) {
                return $this->created($member, 'Member added successfully');
            }

            return back()->with('success', 'Member added successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to add member: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to add member']);
        }
    }

    /**
     * Update a member's role.
     */
    public function updateMember(Request $request, string $org, string $group, string $member)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:admin,editor,viewer',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator);
        }

        try {
            $memberRecord = ProfileGroupMember::where('profile_group_id', $group)
                ->where('member_id', $member)
                ->firstOrFail();

            $memberRecord->update(['role' => $request->input('role')]);

            if ($request->wantsJson()) {
                return $this->success($memberRecord, 'Member role updated successfully');
            }

            return back()->with('success', 'Member role updated successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update member: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update member']);
        }
    }

    /**
     * Remove a member from the profile group.
     */
    public function removeMember(Request $request, string $org, string $group, string $member)
    {
        try {
            $memberRecord = ProfileGroupMember::where('profile_group_id', $group)
                ->where('member_id', $member)
                ->firstOrFail();

            $memberRecord->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Member removed successfully');
            }

            return back()->with('success', 'Member removed successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to remove member: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to remove member']);
        }
    }

    /**
     * Display social profiles in the group.
     */
    public function profiles(Request $request, string $org, string $group)
    {
        $profileGroup = ProfileGroup::where('org_id', $org)
            ->where('group_id', $group)
            ->with(['socialIntegrations'])
            ->firstOrFail();

        $availableProfiles = Integration::where('org_id', $org)
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereNull('profile_group_id')
            ->orderBy('platform')
            ->orderBy('account_name')
            ->get();

        if ($request->wantsJson()) {
            return $this->success($profileGroup->socialIntegrations, 'Profiles retrieved successfully');
        }

        return view('settings.profile-groups.profiles', [
            'profileGroup' => $profileGroup,
            'profiles' => $profileGroup->socialIntegrations,
            'availableProfiles' => $availableProfiles,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Attach a social profile to the group.
     */
    public function attachProfile(Request $request, string $org, string $group)
    {
        $validator = Validator::make($request->all(), [
            'integration_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator);
        }

        try {
            $integration = Integration::where('org_id', $org)
                ->where('integration_id', $request->input('integration_id'))
                ->firstOrFail();

            $integration->update(['profile_group_id' => $group]);

            if ($request->wantsJson()) {
                return $this->success($integration, 'Profile attached successfully');
            }

            return back()->with('success', 'Profile attached successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to attach profile: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to attach profile']);
        }
    }

    /**
     * Detach a social profile from the group.
     */
    public function detachProfile(Request $request, string $org, string $group, string $profile)
    {
        try {
            $integration = Integration::where('org_id', $org)
                ->where('integration_id', $profile)
                ->where('profile_group_id', $group)
                ->firstOrFail();

            $integration->update(['profile_group_id' => null]);

            if ($request->wantsJson()) {
                return $this->deleted('Profile detached successfully');
            }

            return back()->with('success', 'Profile detached successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to detach profile: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to detach profile']);
        }
    }

    /**
     * Get available timezones.
     */
    private function getTimezones(): array
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Europe/Berlin' => 'Berlin',
            'Asia/Tokyo' => 'Tokyo',
            'Asia/Shanghai' => 'Shanghai',
            'Asia/Dubai' => 'Dubai',
            'Australia/Sydney' => 'Sydney',
        ];
    }

    /**
     * Get available languages.
     */
    private function getLanguages(): array
    {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ar' => 'Arabic',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
        ];
    }
}
