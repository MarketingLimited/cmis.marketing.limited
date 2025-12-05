<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure only super admin users can access certain routes.
 *
 * Super admins are platform-level administrators who can manage:
 * - All organizations
 * - All users across the platform
 * - Subscription plans
 * - Platform-wide settings
 * - API analytics and monitoring
 */
class SuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                    'message' => __('auth.unauthenticated'),
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user is a super admin
        if (!$user->is_super_admin) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => __('super_admin.access_denied'),
                ], 403);
            }

            abort(403, __('super_admin.access_denied'));
        }

        // Check if user is suspended or blocked
        if ($user->is_suspended || $user->is_blocked) {
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Account Restricted',
                    'message' => $user->is_blocked
                        ? __('super_admin.account_blocked')
                        : __('super_admin.account_suspended'),
                ], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => $user->is_blocked
                    ? __('super_admin.account_blocked')
                    : __('super_admin.account_suspended')
                ]);
        }

        // Set super admin context for database operations
        try {
            \DB::statement("SET LOCAL app.is_super_admin = true");
        } catch (\Exception $e) {
            \Log::warning('Failed to set super admin context', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }
}
