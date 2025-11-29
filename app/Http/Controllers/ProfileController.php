<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\Concerns\ApiResponse;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Show the profile page
     */
    public function show()
    {
        return view('users.profile');
    }

    /**
     * Update user profile information
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $user->update($validated);

        return $this->success($user, __('users.profile_updated'));
    }

    /**
     * Update user language preference
     */
    public function updateLanguage(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|in:ar,en',
        ]);

        $user = Auth::user();
        $user->update(['locale' => $validated['locale']]);

        // Set cookie for immediate language change
        Cookie::queue('app_locale', $validated['locale'], 60 * 24 * 365); // 1 year

        return $this->success($user, __('users.language_updated'));
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        $user = Auth::user();

        // Delete old avatar if it exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        // Update user avatar path
        $user->update(['avatar' => $path]);

        return $this->success([
            'avatar_url' => asset('storage/' . $path),
            'avatar_path' => $path,
        ], __('users.avatar_updated'));
    }
}
