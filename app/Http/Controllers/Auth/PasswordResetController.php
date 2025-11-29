<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if email exists - always show success message
            return back()->with('status', __('auth.reset_link_sent'));
        }

        // Generate token
        $token = Str::random(64);

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Insert new token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // Send email with reset link
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);

        try {
            Mail::send('emails.password-reset', [
                'resetUrl' => $resetUrl,
                'user' => $user,
            ], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject(__('auth.password_reset_subject'));
            });
        } catch (\Exception $e) {
            // Log the error but don't reveal it to user
            \Log::error('Password reset email failed: ' . $e->getMessage());
        }

        return back()->with('status', __('auth.reset_link_sent'));
    }

    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Find the token
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenData) {
            return back()->withErrors(['email' => __('auth.invalid_reset_token')]);
        }

        // Check if token is valid
        if (!Hash::check($request->token, $tokenData->token)) {
            return back()->withErrors(['email' => __('auth.invalid_reset_token')]);
        }

        // Check if token is expired (60 minutes)
        if (Carbon::parse($tokenData->created_at)->addMinutes(60)->isPast()) {
            return back()->withErrors(['email' => __('auth.reset_token_expired')]);
        }

        // Update password
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => __('auth.user_not_found')]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', __('auth.password_reset_success'));
    }
}
