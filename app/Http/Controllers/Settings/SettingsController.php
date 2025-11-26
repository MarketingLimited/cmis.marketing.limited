<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\ApiToken;
use App\Models\Core\Org;
use App\Models\Core\Role;
use App\Models\Setting\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display the main settings page (redirects to user settings).
     */
    public function index(Request $request, string $org)
    {
        return redirect()->route('orgs.settings.user', $org);
    }

    /**
     * Display the User Settings page.
     * Contains: Profile, Notifications, Security
     */
    public function userSettings(Request $request, string $org)
    {
        $user = Auth::user();

        // Get user settings from the settings table
        $userSettings = $this->getUserSettings($org, $user->user_id);
        $notificationSettings = $this->getNotificationSettings($org, $user->user_id);

        // Get user sessions
        $sessions = DB::table('cmis.user_sessions')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->orderBy('last_activity', 'desc')
            ->get();

        return view('settings.user', [
            'user' => $user,
            'currentOrg' => $org,
            'userSettings' => $userSettings,
            'notificationSettings' => $notificationSettings,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Display the Organization Settings page.
     * Contains: General, Team Members, API Keys, Billing
     */
    public function organizationSettings(Request $request, string $org)
    {
        $user = Auth::user();
        $organization = Org::where('org_id', $org)->first();

        // Get API tokens
        $apiTokens = ApiToken::where('org_id', $org)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get team members
        $teamMembers = $organization ? $organization->users()->get() : collect();

        // Get roles for invitation modal
        $roles = Role::where('org_id', $org)->get();

        return view('settings.organization', [
            'user' => $user,
            'organization' => $organization,
            'currentOrg' => $org,
            'apiTokens' => $apiTokens,
            'teamMembers' => $teamMembers,
            'roles' => $roles,
            // Billing placeholder data
            'currentPlan' => 'Professional',
            'renewalDate' => now()->addYear()->format('F j, Y'),
            'usage' => [
                'campaigns' => 12,
                'team_members' => $teamMembers->count(),
                'api_calls' => 45000,
            ],
            'limits' => [
                'campaigns' => 'Unlimited',
                'team_members' => 25,
                'api_calls' => 100000,
            ],
            'paymentMethod' => [
                'last4' => '4242',
                'exp_month' => '12',
                'exp_year' => '2026',
            ],
            'invoices' => [],
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request, string $org)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:cmis.users,email,' . $user->user_id . ',user_id',
            'locale' => 'nullable|string|in:ar,en',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user->update([
                'name' => $request->input('name'),
                'display_name' => $request->input('display_name'),
                'email' => $request->input('email'),
            ]);

            // Save user preferences
            $this->saveSetting($org, $user->user_id, 'user_locale', $request->input('locale', 'ar'));
            $this->saveSetting($org, $user->user_id, 'user_timezone', $request->input('timezone', 'Asia/Bahrain'));

            return back()->with('success', __('Profile updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update profile', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to update profile'));
        }
    }

    /**
     * Update organization settings.
     */
    public function updateOrganization(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'org_name' => 'required|string|max:255',
            'currency' => 'required|string|in:BHD,USD,EUR,SAR,AED',
            'default_locale' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            Org::where('org_id', $org)->update([
                'name' => $request->input('org_name'),
                'currency' => $request->input('currency'),
                'default_locale' => $request->input('default_locale'),
            ]);

            return back()->with('success', __('Organization settings updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update organization', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to update organization settings'));
        }
    }

    /**
     * Update notification preferences.
     */
    public function updateNotifications(Request $request, string $org)
    {
        $user = Auth::user();

        try {
            $notifications = $request->input('notifications', []);

            // Save notification settings
            $this->saveSetting($org, $user->user_id, 'notification_preferences', $notifications, 'json');

            return back()->with('success', __('Notification preferences updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update notifications', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to update notification preferences'));
        }
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => __('Current password is incorrect')]);
        }

        try {
            $user->update(['password' => Hash::make($request->input('password'))]);

            return back()->with('success', __('Password updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update password', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to update password'));
        }
    }

    /**
     * Revoke a session.
     */
    public function destroySession(Request $request, string $org, string $sessionId)
    {
        try {
            DB::table('cmis.user_sessions')
                ->where('session_id', $sessionId)
                ->where('user_id', Auth::id())
                ->update(['is_active' => false, 'deleted_at' => now()]);

            return back()->with('success', __('Session revoked successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to revoke session', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to revoke session'));
        }
    }

    /**
     * Create a new API token.
     */
    public function storeApiToken(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string',
            'expires_at' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $tokenData = ApiToken::generateToken();

            $apiToken = ApiToken::create([
                'token_id' => Str::uuid(),
                'org_id' => $org,
                'created_by' => Auth::id(),
                'name' => $request->input('name'),
                'token_hash' => $tokenData['hash'],
                'token_prefix' => $tokenData['prefix'],
                'scopes' => $request->input('scopes', []),
                'expires_at' => $request->input('expires_at'),
                'is_active' => true,
            ]);

            // Show the full token only once
            return back()->with('success', __('API key created successfully. Your key: ') . $tokenData['token'] . ' ' . __('(Copy it now, it won\'t be shown again)'));
        } catch (\Exception $e) {
            Log::error('Failed to create API token', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to create API key'));
        }
    }

    /**
     * Revoke an API token.
     */
    public function destroyApiToken(Request $request, string $org, string $tokenId)
    {
        try {
            ApiToken::where('token_id', $tokenId)
                ->where('org_id', $org)
                ->update(['is_active' => false]);

            return back()->with('success', __('API key revoked successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to revoke API token', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to revoke API key'));
        }
    }

    /**
     * Invite a team member.
     */
    public function inviteTeamMember(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $email = $request->input('email');
            $roleId = $request->input('role_id');

            // Check if user already exists
            $existingUser = User::where('email', $email)->first();

            // Generate invitation token
            $invitationToken = Str::random(64);

            if ($existingUser) {
                // Check if already a member
                $existingMembership = DB::table('cmis.user_orgs')
                    ->where('user_id', $existingUser->user_id)
                    ->where('org_id', $org)
                    ->whereNull('deleted_at')
                    ->first();

                if ($existingMembership) {
                    return back()->with('error', __('This user is already a member of the organization'));
                }

                // Add existing user to org
                DB::table('cmis.user_orgs')->insert([
                    'id' => Str::uuid(),
                    'user_id' => $existingUser->user_id,
                    'org_id' => $org,
                    'role_id' => $roleId,
                    'is_active' => true,
                    'invited_by' => Auth::id(),
                    'invited_at' => now(),
                    'invitation_token' => $invitationToken,
                    'invitation_expires_at' => now()->addDays(7),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Create invitation record for new user
                DB::table('cmis.user_orgs')->insert([
                    'id' => Str::uuid(),
                    'user_id' => Str::uuid(), // Placeholder, will be updated when user registers
                    'org_id' => $org,
                    'role_id' => $roleId,
                    'is_active' => false,
                    'invited_by' => Auth::id(),
                    'invited_at' => now(),
                    'invitation_token' => $invitationToken,
                    'invitation_expires_at' => now()->addDays(7),
                    'status' => 'invited',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // TODO: Send invitation email
            // Mail::to($email)->send(new TeamInvitation($org, $invitationToken));

            return back()->with('success', __('Invitation sent successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to invite team member', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to send invitation'));
        }
    }

    /**
     * Remove a team member.
     */
    public function removeTeamMember(Request $request, string $org, string $userId)
    {
        // Prevent removing yourself
        if ($userId === Auth::id()) {
            return back()->with('error', __('You cannot remove yourself from the organization'));
        }

        try {
            DB::table('cmis.user_orgs')
                ->where('user_id', $userId)
                ->where('org_id', $org)
                ->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                ]);

            return back()->with('success', __('Team member removed successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to remove team member', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to remove team member'));
        }
    }

    // ===== Helper Methods =====

    /**
     * Get user settings.
     */
    private function getUserSettings(string $orgId, string $userId): array
    {
        $settings = Setting::where('org_id', $orgId)
            ->where('key', 'LIKE', 'user_%')
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        return [
            'locale' => $settings['user_locale'] ?? 'ar',
            'timezone' => $settings['user_timezone'] ?? 'Asia/Bahrain',
        ];
    }

    /**
     * Get notification settings.
     */
    private function getNotificationSettings(string $orgId, string $userId): array
    {
        $setting = Setting::where('org_id', $orgId)
            ->where('key', 'notification_preferences')
            ->first();

        $defaults = [
            'email_campaign_alerts' => true,
            'email_performance_reports' => true,
            'email_budget_alerts' => true,
            'email_team_activity' => false,
            'app_realtime_alerts' => true,
            'app_sound' => false,
        ];

        return $setting ? array_merge($defaults, $setting->value ?? []) : $defaults;
    }

    /**
     * Save a setting.
     */
    private function saveSetting(string $orgId, string $userId, string $key, $value, string $type = 'string'): void
    {
        $settingValue = $type === 'json' ? $value : ['value' => $value];

        Setting::updateOrCreate(
            [
                'org_id' => $orgId,
                'key' => $key,
            ],
            [
                'setting_id' => Str::uuid(),
                'value' => $settingValue,
                'type' => $type,
            ]
        );
    }

    // ===== Legacy Methods (kept for backward compatibility) =====

    public function profile()
    {
        return view('settings.profile');
    }

    public function notifications()
    {
        return view('settings.notifications');
    }

    public function security()
    {
        return view('settings.security');
    }
}
