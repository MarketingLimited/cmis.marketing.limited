<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Core\UserInvitation;
use App\Models\Security\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * User Management Controller (Phase 2 - Option 4: User Management UI)
 *
 * Provides comprehensive user management capabilities for organizations
 * including user listing, invitations, role management, and activity tracking.
 *
 * Features:
 * - List organization users with filters
 * - Invite users via email
 * - Manage user roles and permissions
 * - Activate/deactivate users
 * - View user activity logs
 * - Update user profiles
 */
class UserManagementController extends Controller
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
     * List users in an organization
     *
     * GET /api/orgs/{org_id}/users
     *
     * Query params:
     * - search: string (search by name/email)
     * - role_id: uuid (filter by role)
     * - status: active|inactive (filter by status)
     * - per_page: int (pagination limit)
     * - page: int (page number)
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'role_id' => 'nullable|uuid',
            'status' => 'nullable|in:active,inactive',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $user = Auth::user();

            // Verify user has access to this org
            $this->verifyOrgAccess($user, $orgId);

            // Build query
            $query = DB::table('cmis.users as u')
                ->join('cmis.user_orgs as uo', 'u.user_id', '=', 'uo.user_id')
                ->leftJoin('cmis.roles as r', 'uo.role_id', '=', 'r.role_id')
                ->where('uo.org_id', $orgId)
                ->select([
                    'u.user_id',
                    'u.email',
                    'u.name',
                    'u.display_name',
                    'u.avatar_url',
                    'u.email_verified_at',
                    'u.created_at',
                    'uo.role_id',
                    'r.role_name',
                    'uo.is_active',
                    'uo.joined_at',
                    'uo.last_accessed',
                ]);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('u.email', 'ILIKE', "%{$search}%")
                      ->orWhere('u.name', 'ILIKE', "%{$search}%")
                      ->orWhere('u.display_name', 'ILIKE', "%{$search}%");
                });
            }

            if ($request->has('role_id')) {
                $query->where('uo.role_id', $request->input('role_id'));
            }

            if ($request->has('status')) {
                $isActive = $request->input('status') === 'active';
                $query->where('uo.is_active', $isActive);
            }

            // Pagination
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $offset = ($page - 1) * $perPage;

            $total = $query->count();
            $users = $query->orderBy('uo.joined_at', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            return response()->json([
                'success' => true,
                'users' => $users,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list users', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single user details
     *
     * GET /api/orgs/{org_id}/users/{user_id}
     *
     * @param string $orgId
     * @param string $userId
     * @return JsonResponse
     */
    public function show(string $orgId, string $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            $this->verifyOrgAccess($currentUser, $orgId);

            $user = DB::table('cmis.users as u')
                ->join('cmis.user_orgs as uo', 'u.user_id', '=', 'uo.user_id')
                ->leftJoin('cmis.roles as r', 'uo.role_id', '=', 'r.role_id')
                ->where('uo.org_id', $orgId)
                ->where('u.user_id', $userId)
                ->select([
                    'u.user_id',
                    'u.email',
                    'u.name',
                    'u.display_name',
                    'u.avatar_url',
                    'u.email_verified_at',
                    'u.created_at',
                    'uo.role_id',
                    'r.role_name',
                    'r.permissions',
                    'uo.is_active',
                    'uo.joined_at',
                    'uo.last_accessed',
                ])
                ->first();

            if (!$user) {
                return $this->error('User not found', 404);
            }

            return response()->json([
                'success' => true,
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user details', [
                'org_id' => $orgId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invite a new user to the organization
     *
     * POST /api/orgs/{org_id}/users/invite
     *
     * Request body:
     * {
     *   "email": "user@example.com",
     *   "role_id": "uuid",
     *   "message": "Optional welcome message"
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function inviteUser(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'role_id' => 'required|uuid|exists:cmis.roles,role_id',
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $currentUser = Auth::user();
            $this->verifyOrgAccess($currentUser, $orgId);

            $email = $request->input('email');

            // Check if user already exists in this org
            $existingUser = DB::table('cmis.users as u')
                ->join('cmis.user_orgs as uo', 'u.user_id', '=', 'uo.user_id')
                ->where('u.email', $email)
                ->where('uo.org_id', $orgId)
                ->exists();

            if ($existingUser) {
                return $this->error('User already exists in this organization', 400);
            }

            // Check for pending invitation
            $pendingInvitation = DB::table('cmis.user_invitations')
                ->where('email', $email)
                ->where('org_id', $orgId)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->exists();

            if ($pendingInvitation) {
                return $this->error('An invitation is already pending for this email', 400);
            }

            // Create invitation
            $invitationToken = Str::random(64);
            $invitationId = Str::uuid()->toString();

            DB::table('cmis.user_invitations')->insert([
                'invitation_id' => $invitationId,
                'org_id' => $orgId,
                'email' => $email,
                'role_id' => $request->input('role_id'),
                'invited_by' => $currentUser->user_id,
                'invitation_token' => hash('sha256', $invitationToken),
                'custom_message' => $request->input('message'),
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get org details
            $org = DB::table('cmis.orgs')->where('org_id', $orgId)->first();

            // Send invitation email (you'll need to create the mailable)
            // Mail::to($email)->send(new UserInvitation($invitationToken, $org, $currentUser));

            // Log the invitation
            $this->logActivity($currentUser->user_id, $orgId, 'user_invited', [
                'invited_email' => $email,
                'role_id' => $request->input('role_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully',
                'invitation' => [
                    'invitation_id' => $invitationId,
                    'email' => $email,
                    'expires_at' => now()->addDays(7)->toIso8601String(),
                    // Don't expose the token in response for security
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to invite user', [
                'org_id' => $orgId,
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user role
     *
     * PUT /api/orgs/{org_id}/users/{user_id}/role
     *
     * Request body:
     * {
     *   "role_id": "uuid"
     * }
     *
     * @param string $orgId
     * @param string $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function updateRole(string $orgId, string $userId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|uuid|exists:cmis.roles,role_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $currentUser = Auth::user();
            $this->verifyOrgAccess($currentUser, $orgId);

            // Update role
            $updated = DB::table('cmis.user_orgs')
                ->where('org_id', $orgId)
                ->where('user_id', $userId)
                ->update([
                    'role_id' => $request->input('role_id'),
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return $this->error('User not found in organization', 404);
            }

            // Log the change
            $this->logActivity($currentUser->user_id, $orgId, 'user_role_updated', [
                'target_user_id' => $userId,
                'new_role_id' => $request->input('role_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update user role', [
                'org_id' => $orgId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate or deactivate a user
     *
     * PUT /api/orgs/{org_id}/users/{user_id}/status
     *
     * Request body:
     * {
     *   "is_active": true|false
     * }
     *
     * @param string $orgId
     * @param string $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(string $orgId, string $userId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $currentUser = Auth::user();
            $this->verifyOrgAccess($currentUser, $orgId);

            // Prevent self-deactivation
            if ($userId === $currentUser->user_id && !$request->input('is_active')) {
                return $this->error('You cannot deactivate yourself', 400);
            }

            // Update status
            $updated = DB::table('cmis.user_orgs')
                ->where('org_id', $orgId)
                ->where('user_id', $userId)
                ->update([
                    'is_active' => $request->input('is_active'),
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return $this->error('User not found in organization', 404);
            }

            // Log the change
            $action = $request->input('is_active') ? 'user_activated' : 'user_deactivated';
            $this->logActivity($currentUser->user_id, $orgId, $action, [
                'target_user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update user status', [
                'org_id' => $orgId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove user from organization
     *
     * DELETE /api/orgs/{org_id}/users/{user_id}
     *
     * @param string $orgId
     * @param string $userId
     * @return JsonResponse
     */
    public function removeUser(string $orgId, string $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            $this->verifyOrgAccess($currentUser, $orgId);

            // Prevent self-removal
            if ($userId === $currentUser->user_id) {
                return $this->error('You cannot remove yourself from the organization', 400);
            }

            // Soft delete by setting is_active to false and marking deleted_at
            $updated = DB::table('cmis.user_orgs')
                ->where('org_id', $orgId)
                ->where('user_id', $userId)
                ->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return $this->error('User not found in organization', 404);
            }

            // Log the removal
            $this->logActivity($currentUser->user_id, $orgId, 'user_removed', [
                'removed_user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User removed from organization successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove user', [
                'org_id' => $orgId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user activity log
     *
     * GET /api/orgs/{org_id}/users/{user_id}/activity
     *
     * Query params:
     * - start_date: date
     * - end_date: date
     * - action_type: string
     * - limit: int
     *
     * @param string $orgId
     * @param string $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function getActivity(string $orgId, string $userId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'action_type' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $currentUser = Auth::user();
            $this->verifyOrgAccess($currentUser, $orgId);

            $query = DB::table('cmis.audit_logs')
                ->where('org_id', $orgId)
                ->where('user_id', $userId);

            if ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->input('start_date'));
            }

            if ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
            }

            if ($request->has('action_type')) {
                $query->where('action_type', $request->input('action_type'));
            }

            $limit = $request->input('limit', 100);
            $activities = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'activities' => $activities,
                'total' => count($activities),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user activity', [
                'org_id' => $orgId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending invitations
     *
     * GET /api/orgs/{org_id}/users/invitations
     *
     * @param string $orgId
     * @return JsonResponse
     */
    public function getInvitations(string $orgId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            $this->verifyOrgAccess($currentUser, $orgId);

            $invitations = DB::table('cmis.user_invitations as i')
                ->leftJoin('cmis.users as u', 'i.invited_by', '=', 'u.user_id')
                ->leftJoin('cmis.roles as r', 'i.role_id', '=', 'r.role_id')
                ->where('i.org_id', $orgId)
                ->where('i.status', 'pending')
                ->where('i.expires_at', '>', now())
                ->select([
                    'i.invitation_id',
                    'i.email',
                    'i.role_id',
                    'r.role_name',
                    'i.invited_by',
                    'u.name as invited_by_name',
                    'i.created_at',
                    'i.expires_at',
                ])
                ->orderBy('i.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'invitations' => $invitations,
                'total' => count($invitations),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get invitations', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invitations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an invitation
     *
     * DELETE /api/orgs/{org_id}/users/invitations/{invitation_id}
     *
     * @param string $orgId
     * @param string $invitationId
     * @return JsonResponse
     */
    public function cancelInvitation(string $orgId, string $invitationId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            $this->verifyOrgAccess($currentUser, $orgId);

            $updated = DB::table('cmis.user_invitations')
                ->where('invitation_id', $invitationId)
                ->where('org_id', $orgId)
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return $this->error('Invitation not found', 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invitation cancelled successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel invitation', [
                'org_id' => $orgId,
                'invitation_id' => $invitationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify user has access to organization
     *
     * @param User $user
     * @param string $orgId
     * @throws \Exception
     */
    private function verifyOrgAccess($user, string $orgId): void
    {
        $hasAccess = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('org_id', $orgId)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccess) {
            throw new \Exception('You do not have access to this organization');
        }
    }

    /**
     * Log user management activity
     *
     * @param string $userId
     * @param string $orgId
     * @param string $actionType
     * @param array $metadata
     */
    private function logActivity(string $userId, string $orgId, string $actionType, array $metadata = []): void
    {
        try {
            DB::table('cmis.audit_logs')->insert([
                'log_id' => Str::uuid()->toString(),
                'org_id' => $orgId,
                'user_id' => $userId,
                'action_type' => $actionType,
                'entity_type' => 'user',
                'metadata' => json_encode($metadata),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log activity', [
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
