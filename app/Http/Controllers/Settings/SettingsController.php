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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\TeamInvitation;

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
            'email' => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
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

            return back()->with('success', __('settings.profile_updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update profile', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_update_profile'));
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

            return back()->with('success', __('settings.organization_updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update organization', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_update_organization'));
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

            return back()->with('success', __('settings.notification_preferences_updated'));
        } catch (\Exception $e) {
            Log::error('Failed to update notifications', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_update_notifications'));
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
            return back()->withErrors(['current_password' => __('settings.current_password_incorrect')]);
        }

        try {
            $user->update(['password' => Hash::make($request->input('password'))]);

            return back()->with('success', __('settings.password_updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update password', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_update_password'));
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

            return back()->with('success', __('settings.session_revoked_success'));
        } catch (\Exception $e) {
            Log::error('Failed to revoke session', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_revoke_session'));
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
            return back()->with('success', __('settings.api_key_created_success') . ' ' . $tokenData['token'] . ' ' . __('settings.copy_now_wont_show_again'));
        } catch (\Exception $e) {
            Log::error('Failed to create API token', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_create_api_key'));
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

            return back()->with('success', __('settings.api_key_revoked_success'));
        } catch (\Exception $e) {
            Log::error('Failed to revoke API token', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_revoke_api_key'));
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
                    return back()->with('error', __('settings.user_already_member'));
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

            // Send invitation email
            $organization = Org::find($org);
            $role = Role::find($roleId);
            $inviter = Auth::user();

            if ($organization && $role && $inviter) {
                try {
                    Mail::to($email)->send(new TeamInvitation(
                        email: $email,
                        organization: $organization,
                        roleName: $role->role_name ?? $role->name ?? 'Team Member',
                        inviterName: $inviter->display_name ?? $inviter->name ?? $inviter->email,
                        invitationToken: $invitationToken,
                        isNewUser: !$existingUser
                    ));
                } catch (\Exception $mailError) {
                    Log::warning('Failed to send invitation email', [
                        'email' => $email,
                        'error' => $mailError->getMessage()
                    ]);
                    // Continue even if email fails - invitation is still created
                }
            }

            return back()->with('success', __('settings.invitation_sent_success'));
        } catch (\Exception $e) {
            Log::error('Failed to invite team member', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_send_invitation'));
        }
    }

    /**
     * Remove a team member.
     */
    public function removeTeamMember(Request $request, string $org, string $userId)
    {
        // Prevent removing yourself
        if ($userId === Auth::id()) {
            return back()->with('error', __('settings.cannot_remove_yourself'));
        }

        try {
            DB::table('cmis.user_orgs')
                ->where('user_id', $userId)
                ->where('org_id', $org)
                ->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                ]);

            return back()->with('success', __('settings.team_member_removed_success'));
        } catch (\Exception $e) {
            Log::error('Failed to remove team member', ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_remove_team_member'));
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
