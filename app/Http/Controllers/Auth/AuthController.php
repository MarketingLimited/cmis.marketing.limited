<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * تسجيل مستخدم جديد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:cmis.users,email',
            'display_name' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'email' => $request->email,
                'display_name' => $request->display_name,
                'name' => $request->name ?? $request->display_name,
                'role' => 'editor', // الدور الافتراضي
                'status' => 'active',
                'provider' => $request->provider,
            ]);

            // إنشاء token للمصادقة
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Registration Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تسجيل الدخول
     *
     * ملاحظة: في CMIS، المصادقة تتم عبر OAuth أو خارجياً
     * هذا endpoint مبسط للتطوير
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'provider' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['User not found or inactive.'],
                ]);
            }

            // إنشاء token جديد
            $token = $user->createToken('auth_token')->plainTextToken;

            // تحديث آخر تسجيل دخول (يمكن إضافته للجدول لاحقاً)
            // $user->update(['last_login_at' => now()]);

            return response()->json([
                'message' => 'Login successful',
                'user' => $user->load('orgs:org_id,name,default_locale,currency'),
                'token' => $token,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Login Failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Login Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تسجيل الخروج
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // حذف الـ token الحالي فقط
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Logout Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف جميع tokens المستخدم
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll(Request $request)
    {
        try {
            // حذف جميع tokens المستخدم
            $request->user()->tokens()->delete();

            return response()->json([
                'message' => 'Logged out from all devices successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Logout Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * معلومات المستخدم الحالي
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user()->load([
                'orgs:org_id,name,default_locale,currency',
                'orgs.pivot' => function ($query) {
                    $query->select('user_id', 'org_id', 'role_id', 'is_active', 'joined_at');
                }
            ]);

            return response()->json([
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث ملف المستخدم الشخصي
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $user->update($request->only(['display_name', 'name']));

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * OAuth callback handler
     * يمكن استخدامه للمصادقة عبر Google، Facebook، إلخ
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function oauthCallback(Request $request, string $provider)
    {
        // سيتم تطويره لاحقاً مع OAuth providers
        return response()->json([
            'message' => 'OAuth callback handler',
            'provider' => $provider,
        ]);
    }
}
