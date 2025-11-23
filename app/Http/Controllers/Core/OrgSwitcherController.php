<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
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
    use ApiResponse;

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
    public function getUserOrganizations(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return $this->unauthorized('You must be authenticated to access this resource');
        }

        // Get all organizations the user belongs to
        $organizations = $user->orgs()
            ->select('cmis.orgs.org_id', 'cmis.orgs.name', 'cmis.orgs.slug')
            ->orderBy('cmis.orgs.name')
            ->get();

        // Get current active org from session context
        $sessionContext = SessionContext::where('session_id', session()->getId())->first();
        $activeOrgId = $sessionContext?->active_org_id ?? $user->org_id;

        return $this->success([
            'organizations' => $organizations,
            'active_org_id' => $activeOrgId,
        ], 'Organizations retrieved successfully');
    }

    /**
     * Switch to a different organization
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function switchOrganization(Request $request): JsonResponse
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

            // Sync to Redis for cross-interface org context (Issue #63)
            \Cache::put('user:' . $user->user_id . ':active_org', $newOrgId, now()->addDays(7));

            Log::info('User switched organization', [
                'user_id' => $user->user_id,
                'old_org_id' => $sessionContext->getOriginal('active_org_id'),
                'new_org_id' => $newOrgId,
            ]);

            // Get the new org details
            $newOrg = $user->orgs()->where('cmis.orgs.org_id', $newOrgId)->first();

            return $this->success([
                'active_org' => [
                    'org_id' => $newOrg->org_id,
                    'name' => $newOrg->name,
                    'slug' => $newOrg->slug,
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
    public function getActiveOrganization(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return $this->unauthorized('You must be authenticated to access this resource');
        }

        // Check Redis cache first for cross-interface consistency (Issue #63)
        $cachedOrgId = \Cache::get('user:' . $user->user_id . ':active_org');

        $sessionContext = SessionContext::where('session_id', session()->getId())->first();
        $activeOrgId = $cachedOrgId ?? $sessionContext?->active_org_id ?? $user->org_id;

        $activeOrg = $user->orgs()->where('cmis.orgs.org_id', $activeOrgId)->first();

        if (!$activeOrg) {
            // Fallback to user's primary org
            $activeOrg = $user->org;
        }

        return $this->success([
            'active_org' => [
                'org_id' => $activeOrg->org_id,
                'name' => $activeOrg->name,
                'slug' => $activeOrg->slug ?? null,
            ],
        ], 'Active organization retrieved successfully');
    }
}
