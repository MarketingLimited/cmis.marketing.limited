<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Set database context for RLS using proper PostgreSQL function
        // This ensures Row Level Security works correctly
        try {
            \DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
                $user->id,
                $activeOrgId
            ]);

            \Log::debug('ResolveActiveOrg: RLS context set successfully', [
                'user_id' => $user->id,
                'org_id' => $activeOrgId
            ]);
        } catch (\Exception $e) {
            \Log::error('ResolveActiveOrg: Failed to set RLS context', [
                'user_id' => $user->id,
                'org_id' => $activeOrgId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to initialize context',
                'message' => 'An error occurred while setting up your organization context.'
            ], 500);
        }

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * Clean up the database context after the request is complete.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            \DB::statement('SELECT cmis.clear_transaction_context()');
            \Log::debug('ResolveActiveOrg: Context cleared in terminate()');
        } catch (\Exception $e) {
            \Log::debug('ResolveActiveOrg: Could not clear context in terminate()', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
