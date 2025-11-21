<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Security\SessionContext;
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
    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
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
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        // Get all organizations the user belongs to
        $organizations = $user->orgs()
            ->select('cmis.orgs.org_id', 'cmis.orgs.name', 'cmis.orgs.slug')
            ->orderBy('cmis.orgs.name')
            ->get();

        // Get current active org from session context
        $sessionContext = SessionContext::where('session_id', session()->getId())->first();
        $activeOrgId = $sessionContext?->active_org_id ?? $user->org_id;

        return response()->json([
            'organizations' => $organizations,
            'active_org_id' => $activeOrgId,
        ]);
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

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You do not have access to this organization',
            ], 403);
        }

        try {
            // Get or create session context
            $sessionContext = SessionContext::getOrCreate(
                session()->getId(),
                $newOrgId,
                'web'
            );

            // Switch to new org
            $sessionContext->switchOrg($newOrgId);

            // Set PostgreSQL session variable for RLS
            DB::statement(
                "SELECT cmis.set_org_context(?)",
                [$newOrgId]
            );

            // Update user's default org_id (optional - keeps last selected org)
            $user->update(['org_id' => $newOrgId]);

            Log::info('User switched organization', [
                'user_id' => $user->user_id,
                'old_org_id' => $sessionContext->getOriginal('active_org_id'),
                'new_org_id' => $newOrgId,
            ]);

            // Get the new org details
            $newOrg = $user->orgs()->where('cmis.orgs.org_id', $newOrgId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Organization switched successfully',
                'active_org' => [
                    'org_id' => $newOrg->org_id,
                    'name' => $newOrg->name,
                    'slug' => $newOrg->slug,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to switch organization', [
                'user_id' => $user->user_id,
                'org_id' => $newOrgId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Server Error',
                'message' => 'Failed to switch organization. Please try again.',
            ], 500);
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
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $sessionContext = SessionContext::where('session_id', session()->getId())->first();
        $activeOrgId = $sessionContext?->active_org_id ?? $user->org_id;

        $activeOrg = $user->orgs()->where('cmis.orgs.org_id', $activeOrgId)->first();

        if (!$activeOrg) {
            // Fallback to user's primary org
            $activeOrg = $user->org;
        }

        return response()->json([
            'active_org' => [
                'org_id' => $activeOrg->org_id,
                'name' => $activeOrg->name,
                'slug' => $activeOrg->slug ?? null,
            ],
        ]);
    }
}
