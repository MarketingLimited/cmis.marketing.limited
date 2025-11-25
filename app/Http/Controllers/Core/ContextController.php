<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Security\SessionContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Context Controller (Phase 2 - Option 2: Context System UI)
 *
 * Provides API endpoints for managing user organization context.
 * Enables frontend components to switch between organizations and
 * maintain context state across sessions.
 *
 * Features:
 * - Get current organization context
 * - List available organizations for user
 * - Switch organization context
 * - Context persistence across sessions
 */
class ContextController extends Controller
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
     * Get current context information
     *
     * GET /api/context
     *
     * Returns the current user's active organization context and metadata
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentContext(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->error('User not authenticated', 401);
            }

            // Get session context
            $sessionContext = SessionContext::where('session_id', session()->getId())->first();
            $activeOrgId = $sessionContext?->active_org_id ?? $user->org_id;

            // Get organization details
            $org = $user->orgs()->where('cmis.orgs.org_id', $activeOrgId)->first();

            if (!$org) {
                // Fallback to user's primary org
                $org = $user->org;
            }

            // Get user's role in this org
            $userOrg = DB::table('cmis.user_orgs')
                ->where('user_id', $user->user_id)
                ->where('org_id', $activeOrgId)
                ->first();

            return response()->json([
                'success' => true,
                'context' => [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                    'display_name' => $user->display_name ?? $user->name,
                    'active_org' => [
                        'org_id' => $org->org_id,
                        'name' => $org->name,
                        'slug' => $org->slug ?? null,
                        'default_locale' => $org->default_locale ?? 'ar-BH',
                        'currency' => $org->currency ?? 'BHD',
                    ],
                    'role_id' => $userOrg?->role_id,
                    'last_accessed' => $userOrg?->last_accessed,
                    'session_id' => session()->getId(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get current context', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve context',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of organizations available to current user
     *
     * GET /api/context/organizations
     *
     * Returns all organizations the authenticated user has access to
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableOrganizations(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->error('User not authenticated', 401);
            }

            // Get all organizations for this user with role information
            $organizations = DB::select("
                SELECT
                    o.org_id,
                    o.name,
                    o.slug,
                    o.default_locale,
                    o.currency,
                    uo.role_id,
                    r.role_name,
                    uo.joined_at,
                    uo.last_accessed,
                    uo.is_active
                FROM cmis.orgs o
                INNER JOIN cmis.user_orgs uo ON o.org_id = uo.org_id
                LEFT JOIN cmis.roles r ON uo.role_id = r.role_id
                WHERE uo.user_id = ?
                    AND uo.is_active = true
                ORDER BY uo.last_accessed DESC NULLS LAST, o.name ASC
            ", [$user->user_id]);

            // Get current active org
            $sessionContext = SessionContext::where('session_id', session()->getId())->first();
            $activeOrgId = $sessionContext?->active_org_id ?? $user->org_id;

            return response()->json([
                'success' => true,
                'organizations' => array_map(function($org) use ($activeOrgId) {
                    return [
                        'org_id' => $org->org_id,
                        'name' => $org->name,
                        'slug' => $org->slug,
                        'default_locale' => $org->default_locale,
                        'currency' => $org->currency,
                        'role_id' => $org->role_id,
                        'role_name' => $org->role_name,
                        'joined_at' => $org->joined_at,
                        'last_accessed' => $org->last_accessed,
                        'is_active' => $org->org_id === $activeOrgId,
                    ];
                }, $organizations),
                'active_org_id' => $activeOrgId,
                'total' => count($organizations),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get available organizations', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve organizations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Switch to a different organization context
     *
     * POST /api/context/switch
     *
     * Request body:
     * {
     *   "org_id": "uuid"
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function switchContext(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'org_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $newOrgId = $request->input('org_id');

            // Verify user has access to the target organization
            $hasAccess = DB::table('cmis.user_orgs')
                ->where('user_id', $user->user_id)
                ->where('org_id', $newOrgId)
                ->where('is_active', true)
                ->exists();

            if (!$hasAccess) {
                Log::warning('User attempted to switch to unauthorized org', [
                    'user_id' => $user->user_id,
                    'attempted_org_id' => $newOrgId,
                ]);

                return $this->error('You do not have access to this organization', 403);
            }

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

            // Update last accessed timestamp
            DB::table('cmis.user_orgs')
                ->where('user_id', $user->user_id)
                ->where('org_id', $newOrgId)
                ->update(['last_accessed' => now()]);

            // Update user's default org_id (optional - keeps last selected org)
            $user->update(['org_id' => $newOrgId]);

            // Get the new org details
            $newOrg = DB::table('cmis.orgs')
                ->where('org_id', $newOrgId)
                ->first();

            Log::info('User switched organization', [
                'user_id' => $user->user_id,
                'new_org_id' => $newOrgId,
                'session_id' => session()->getId(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Organization switched successfully',
                'context' => [
                    'active_org' => [
                        'org_id' => $newOrg->org_id,
                        'name' => $newOrg->name,
                        'slug' => $newOrg->slug ?? null,
                        'default_locale' => $newOrg->default_locale ?? 'ar-BH',
                        'currency' => $newOrg->currency ?? 'BHD',
                    ],
                    'switched_at' => now()->toIso8601String(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to switch organization', [
                'user_id' => Auth::id(),
                'org_id' => $request->input('org_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to switch organization. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh context (sync with database)
     *
     * POST /api/context/refresh
     *
     * Refreshes the current context state from the database
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshContext(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Re-initialize RLS context
            $activeOrgId = $user->org_id;

            DB::statement(
                "SELECT cmis.init_transaction_context(?, ?)",
                [$user->user_id, $activeOrgId]
            );

            // Get fresh context data
            return $this->getCurrentContext($request);

        } catch (\Exception $e) {
            Log::error('Failed to refresh context', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh context',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
