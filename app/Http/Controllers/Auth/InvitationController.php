<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Core\UserOrg;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InvitationController extends Controller
{
    /**
     * Show the invitation acceptance page
     */
    public function show(string $token)
    {
        $invitation = UserOrg::where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->where('invitation_expires_at', '>', now())
            ->with(['user', 'org', 'role'])
            ->first();

        if (!$invitation) {
            return view('auth.invitation-invalid', [
                'message' => 'This invitation link is invalid or has expired.'
            ]);
        }

        return view('auth.invitation-accept', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    /**
     * Accept the invitation
     */
    public function accept(Request $request, string $token)
    {
        $invitation = UserOrg::where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->where('invitation_expires_at', '>', now())
            ->with(['user', 'org'])
            ->first();

        if (!$invitation) {
            return redirect()->route('login')->with('error', 'This invitation link is invalid or has expired.');
        }

        $user = $invitation->user;

        // If user doesn't have a password yet (new user), require them to set one
        if (!$user->password) {
            $validated = $request->validate([
                'password' => 'required|string|min:8|confirmed',
                'name' => 'required|string|max:255',
            ]);

            $user->update([
                'password' => Hash::make($validated['password']),
                'name' => $validated['name'],
                'display_name' => $validated['name'],
                'status' => 'active',
            ]);
        }

        // Mark invitation as accepted
        $invitation->update([
            'invitation_accepted_at' => now(),
            'invitation_token' => null, // Clear token after use
            'is_active' => true,
        ]);

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard.index')->with('success', "Welcome to {$invitation->org->name}! You've successfully joined the organization.");
    }

    /**
     * Decline the invitation
     */
    public function decline(string $token)
    {
        $invitation = UserOrg::where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->first();

        if ($invitation) {
            // Mark as inactive and clear token
            $invitation->update([
                'is_active' => false,
                'invitation_token' => null,
            ]);
        }

        return redirect()->route('login')->with('info', 'You have declined the invitation.');
    }
}
