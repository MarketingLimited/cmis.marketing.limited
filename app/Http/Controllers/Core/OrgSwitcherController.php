<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller for organization switching functionality
 *
 * Allows users who belong to multiple organizations to switch between them.
 * The active organization is stored in session_context table and used by RLS middleware.
 */
class OrgSwitcherController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     * Support both web session and API token authentication
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum,web');
    }

    /**
     * Get list of organizations the current user belongs to
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserOrganizations(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->unauthorized('You must be authenticated to access this resource');
        }

        // Get all organizations the user belongs to
        // Note: Disable global scopes to avoid issues with pivot table soft deletes
        $organizations = $user->orgs()
            ->withoutGlobalScopes()
            ->select('cmis.orgs.org_id', 'cmis.orgs.name', 'cmis.orgs.default_locale', 'cmis.orgs.currency')
            ->orderBy('cmis.orgs.name')
            ->get();

        // Get current org from user (frontend determines current org from URL)
        $currentOrgId = $user->current_org_id ?? $user->org_id;

        return $this->success([
            'organizations' => $organizations,
            'current_org_id' => $currentOrgId,
        ], 'Organizations retrieved successfully');
    }

    /**
     * Switch to a different organization
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function switchOrganization(Request $request)
    {
        $request->validate([
            'org_id' => 'required|uuid',
        ]);

        $user = Auth::user();
        $newOrgId = $request->input('org_id');

        // Verify user belongs to the target organization
        if (!$user->belongsToOrg($newOrgId)) {
            Log::warning('User attempted to switch to unauthorized org', [
                'user_id' => $user->user_id,
                'attempted_org_id' => $newOrgId,
            ]);

            return $this->forbidden(
                'You do not have access to this organization',
                'ORG_ACCESS_DENIED'
            );
        }

        try {
            $oldOrgId = $user->org_id;

            // Set PostgreSQL session variable for RLS
            DB::statement(
                "SELECT cmis.set_org_context(?)",
                [$newOrgId]
            );

            // Update user's current org
            $user->update(['org_id' => $newOrgId]);

            // Sync to Redis for cross-interface org context (Issue #63)
            \Cache::put('user:' . $user->user_id . ':active_org', $newOrgId, now()->addDays(7));

            Log::info('User switched organization', [
                'user_id' => $user->user_id,
                'old_org_id' => $oldOrgId,
                'new_org_id' => $newOrgId,
            ]);

            // Get the new org details
            $newOrg = $user->orgs()
                ->withoutGlobalScopes()
                ->where('cmis.orgs.org_id', $newOrgId)
                ->first();

            return $this->success([
                'active_org' => [
                    'org_id' => $newOrg->org_id,
                    'name' => $newOrg->name,
                ],
            ], 'Organization switched successfully');

        } catch (\Exception $e) {
            Log::error('Failed to switch organization', [
                'user_id' => $user->user_id,
                'org_id' => $newOrgId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to switch organization. Please try again.');
        }
    }

    /**
     * Get the currently active organization
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveOrganization()
    {
        $user = Auth::user();

        if (!$user) {
            return $this->unauthorized('You must be authenticated to access this resource');
        }

        // Check Redis cache first for cross-interface consistency (Issue #63)
        $cachedOrgId = \Cache::get('user:' . $user->user_id . ':active_org');

        // Use cached org or user's current/default org
        $activeOrgId = $cachedOrgId ?? $user->current_org_id ?? $user->org_id;

        $activeOrg = $user->orgs()
            ->withoutGlobalScopes()
            ->where('cmis.orgs.org_id', $activeOrgId)
            ->first();

        if (!$activeOrg) {
            // Fallback to user's primary org
            $activeOrg = $user->org;
        }

        return $this->success([
            'active_org' => [
                'org_id' => $activeOrg->org_id,
                'name' => $activeOrg->name,
            ],
        ], 'Active organization retrieved successfully');
    }
}
