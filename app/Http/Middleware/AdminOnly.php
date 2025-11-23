<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to ensure only admin users can access certain routes
 */
class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'You must be logged in to access this resource',
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = Auth::user();
        $orgId = session('current_org_id');

        // Require organization context for admin checks
        if (!$orgId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'No organization context available',
                ], 403);
            }

            abort(403, 'No organization context available.');
        }

        // Check if user has admin or owner role in current organization
        $isAdmin = $user->hasRoleInOrg($orgId, 'admin') || $user->hasRoleInOrg($orgId, 'owner');

        if (!$isAdmin) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Admin privileges required for this resource',
                ], 403);
            }

            abort(403, 'Access denied. Admin privileges required.');
        }

        // Set admin context for database operations (RLS bypass for admin operations)
        try {
            \DB::statement("SET LOCAL app.is_admin = true");
        } catch (\Exception $e) {
            // If setting context fails, log but continue
            \Log::warning('Failed to set admin context', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }
}
