<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to automatically resolve the user's active organization
 * for convenience API routes that don't explicitly specify org_id
 *
 * This allows routes like:
 *   GET /api/integrations/activity
 * instead of requiring:
 *   GET /api/orgs/{org_id}/integrations/activity
 *
 * The middleware will:
 * 1. Get the authenticated user
 * 2. Find their active organization (from user.active_org_id or first org)
 * 3. Inject org_id into the request
 * 4. Set database context if needed
 */
class ResolveActiveOrg
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource'
            ], 401);
        }

        // Try to get active org from user's preference
        $activeOrgId = $user->active_org_id;

        // If no active org set, try to get the first organization the user belongs to
        if (!$activeOrgId) {
            $firstOrg = $user->orgs()->first();
            if ($firstOrg) {
                $activeOrgId = $firstOrg->id;

                // Optionally update user's active org
                $user->active_org_id = $activeOrgId;
                $user->save();
            }
        }

        // If still no org found, return error
        if (!$activeOrgId) {
            return response()->json([
                'error' => 'No active organization',
                'message' => 'Please select or join an organization first',
                'action_required' => 'select_organization'
            ], 400);
        }

        // Inject org_id into request for controllers to use
        $request->merge(['org_id' => $activeOrgId]);
        $request->attributes->set('org_id', $activeOrgId);
        $request->attributes->set('resolved_org_id', true); // Flag to indicate auto-resolved

        // Set database context for RLS (if needed by your app)
        // This ensures Row Level Security works correctly
        if (function_exists('init_transaction_context')) {
            init_transaction_context($activeOrgId);
        } else {
            // Alternative: Call DB raw query
            try {
                \DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$activeOrgId]);
            } catch (\Exception $e) {
                // Log but don't fail - RLS might not be enabled
                \Log::debug("Could not set RLS context: " . $e->getMessage());
            }
        }

        return $next($request);
    }
}
