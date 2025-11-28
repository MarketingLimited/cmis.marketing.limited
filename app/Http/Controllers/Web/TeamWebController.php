<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use App\Models\Core\UserOrg;
use App\Models\Core\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Team Web Controller (Frontend UI)
 *
 * Handles team member management web pages
 */
class TeamWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display team members page
     */
    public function index(string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        // Verify access
        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403, 'You do not have access to this organization');
        }

        // Set RLS context
        try {
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $org
            ]);
        } catch (\Exception $e) {
            \Log::warning("Could not set RLS context: " . $e->getMessage());
        }

        // Get team members (distinct by user_id to avoid duplicates)
        $memberIds = DB::table('cmis.user_orgs')
            ->selectRaw('DISTINCT ON (user_id) id')
            ->where('org_id', $org)
            ->whereNull('deleted_at')
            ->orderByRaw('user_id, joined_at DESC NULLS LAST')
            ->pluck('id');

        $members = UserOrg::where('org_id', $org)
            ->with(['user', 'role'])
            ->whereIn('id', $memberIds)
            ->orderByDesc('joined_at')
            ->paginate(20);

        // Get available roles
        $roles = Role::all();

        // Get pending invitations
        $pendingInvitations = DB::table('cmis.team_invitations')
            ->where('org_id', $org)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Stats
        $stats = [
            'total_members' => UserOrg::where('org_id', $org)->count(),
            'active_members' => UserOrg::where('org_id', $org)->where('is_active', true)->count(),
            'pending_invitations' => DB::table('cmis.team_invitations')
                ->where('org_id', $org)
                ->where('status', 'pending')
                ->count(),
        ];

        return view('orgs.team', compact('orgModel', 'members', 'roles', 'pendingInvitations', 'stats'));
    }

    /**
     * Send invitation
     */
    public function invite(Request $request, string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role_id' => 'required|exists:cmis.roles,role_id',
            'message' => 'nullable|string|max:500',
        ]);

        // Set context
        try {
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$user->user_id, $org]);
        } catch (\Exception $e) {}

        // Check existing
        $existingInvitation = DB::table('cmis.team_invitations')
            ->where('org_id', $org)
            ->where('invited_email', $validated['email'])
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            return back()->with('error', __('web.an_invitation_has_already_been_sent_to_this_email'));
        }

        // Create invitation
        $invitationId = Str::uuid()->toString();

        DB::table('cmis.team_invitations')->insert([
            'invitation_id' => $invitationId,
            'org_id' => $org,
            'invited_email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'invited_by' => $user->user_id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'sent_at' => now(),
        ]);

        $invitationUrl = route('invitations.show', ['token' => $invitationId]);

        \Log::info("Invitation created for {$validated['email']} to join {$orgModel->name}", [
            'invitation_id' => $invitationId,
            'url' => $invitationUrl
        ]);

        return back()->with('success', __('common.invitation_sent', ['email' => $validated['email']]));
    }
}
