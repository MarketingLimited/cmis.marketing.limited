<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function show()
    {
        return view('users.profile');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $user->update($request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
        ]));

        return redirect()->back()->with('success', __('profile.updated_success'));
    }

    public function avatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $user = Auth::user();
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json(['avatar_url' => asset('storage/' . $path)]);
    }
}
