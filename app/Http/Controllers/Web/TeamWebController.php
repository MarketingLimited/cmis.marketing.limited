<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Core\Organization;
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
        $orgModel = Organization::findOrFail($org);

        // Verify access
        if (!$user->orgs()->where('org_id', $org)->exists()) {
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

        // Get team members
        $members = UserOrg::where('org_id', $org)
            ->with(['user', 'role'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get available roles
        $roles = Role::all();

        // Get pending invitations
        $pendingInvitations = DB::table('cmis.invitations')
            ->where('org_id', $org)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Stats
        $stats = [
            'total_members' => UserOrg::where('org_id', $org)->count(),
            'active_members' => UserOrg::where('org_id', $org)->where('status', 'active')->count(),
            'pending_invitations' => DB::table('cmis.invitations')
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
        $orgModel = Organization::findOrFail($org);

        if (!$user->orgs()->where('org_id', $org)->exists()) {
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
        $existingInvitation = DB::table('cmis.invitations')
            ->where('org_id', $org)
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            return back()->with('error', 'An invitation has already been sent to this email.');
        }

        // Create invitation
        $token = Str::random(64);

        DB::table('cmis.invitations')->insert([
            'org_id' => $org,
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'invited_by' => $user->user_id,
            'token' => $token,
            'status' => 'pending',
            'message' => $validated['message'] ?? null,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invitationUrl = route('invitations.show', ['token' => $token]);

        \Log::info("Invitation created for {$validated['email']} to join {$orgModel->name}", [
            'url' => $invitationUrl
        ]);

        return back()->with('success', "Invitation sent to {$validated['email']}!");
    }
}
