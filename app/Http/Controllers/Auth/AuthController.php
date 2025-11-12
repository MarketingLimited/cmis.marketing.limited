<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Core\Org;
use App\Models\Core\UserOrg;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:cmis.users,email',
            'password' => 'required|string|min:8|confirmed',
            'org_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'display_name' => $request->name,
                'email' => $request->email,
                'role' => 'admin',
                'status' => 'active',
            ]);

            // If org_name is provided, create a new organization
            if ($request->org_name) {
                $org = Org::create([
                    'name' => $request->org_name,
                    'default_locale' => $request->input('locale', 'ar-BH'),
                    'currency' => $request->input('currency', 'BHD'),
                ]);

                // Create default admin role for the org
                $adminRole = $org->roles()->create([
                    'role_name' => 'Admin',
                    'role_code' => 'admin',
                    'description' => 'Organization Administrator',
                    'is_system' => true,
                    'is_active' => true,
                    'created_by' => $user->user_id,
                ]);

                // Associate user with org
                UserOrg::create([
                    'user_id' => $user->user_id,
                    'org_id' => $org->org_id,
                    'role_id' => $adminRole->role_id,
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }

            DB::commit();

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Login user and create token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active. Please contact support.'
            ], 403);
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load user organizations
        $user->load('orgs');

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Get authenticated user information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('orgs');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * Update user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'display_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:cmis.users,email,' . $user->user_id . ',user_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update($request->only(['name', 'display_name', 'email']));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Logout user (revoke current token).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices successfully'
        ]);
    }

    /**
     * Handle OAuth callback.
     *
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     */
    public function oauthCallback(Request $request, string $provider): JsonResponse
    {
        // TODO: Implement OAuth integration with providers like Google, Facebook, etc.
        // This would require Socialite package

        return response()->json([
            'success' => false,
            'message' => 'OAuth authentication is not yet implemented'
        ], 501);
    }
}
