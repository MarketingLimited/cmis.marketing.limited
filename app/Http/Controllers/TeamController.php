<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Services\TeamManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * TeamController
 *
 * Handles team member management
 * Implements Sprint 5.1: Team Management
 *
 * Features:
 * - Invite and manage team members
 * - Role-based access control
 * - Account-level assignments
 * - Permission management
 */
class TeamController extends Controller
{
    use ApiResponse;

    protected TeamManagementService $teamService;

    public function __construct(TeamManagementService $teamService)
    {
        $this->middleware('auth:sanctum');
        $this->teamService = $teamService;
    }

    /**
     * Invite team member
     *
     * POST /api/orgs/{org_id}/team/invite
     *
     * Request body:
     * {
     *   "email": "user@example.com",
     *   "role": "editor",
     *   "message": "Welcome to our team!",
     *   "account_access": ["account_uuid_1", "account_uuid_2"]
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function invite(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role' => 'required|in:owner,admin,manager,editor,contributor,viewer',
            'message' => 'nullable|string|max:500',
            'account_access' => 'nullable|array',
            'account_access.*' => 'uuid'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->teamService->inviteTeamMember($orgId, [
                'email' => $request->input('email'),
                'role' => $request->input('role'),
                'message' => $request->input('message'),
                'account_access' => $request->input('account_access', []),
                'invited_by' => $request->user()->user_id ?? null
            ]);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->created($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to send invitation',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Accept invitation
     *
     * POST /api/team/invitations/{token}/accept
     *
     * @param string $token
     * @param Request $request
     * @return JsonResponse
     */
    public function acceptInvitation(string $token, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return $this->error('User must be authenticated to accept invitation', 401);
            }

            $result = $this->teamService->acceptInvitation($token, $userId);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to accept invitation',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * List team members
     *
     * GET /api/orgs/{org_id}/team/members?role=editor&search=john
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function listMembers(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'nullable|in:owner,admin,manager,editor,contributor,viewer',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:joined_at,email,role',
            'sort_order' => 'nullable|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->teamService->listTeamMembers($orgId, $request->all());

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to list team members',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Remove team member
     *
     * DELETE /api/orgs/{org_id}/team/members/{user_id}
     *
     * @param string $orgId
     * @param string $userId
     * @return JsonResponse
     */
    public function removeMember(string $orgId, string $userId): JsonResponse
    {
        try {
            $result = $this->teamService->removeTeamMember($orgId, $userId);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to remove team member',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Update member role
     *
     * PUT /api/orgs/{org_id}/team/members/{user_id}/role
     *
     * Request body:
     * {
     *   "role": "manager"
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
            'role' => 'required|in:owner,admin,manager,editor,contributor,viewer'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->teamService->updateMemberRole(
                $orgId,
                $userId,
                $request->input('role')
            );

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update role',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get role permissions
     *
     * GET /api/team/roles/{role}/permissions
     *
     * @param string $role
     * @return JsonResponse
     */
    public function getRolePermissions(string $role): JsonResponse
    {
        try {
            $result = $this->teamService->getRolePermissions($role);

            if (!$result['success']) {
            return $this->notFound($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get role permissions',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get all available roles
     *
     * GET /api/team/roles
     *
     * @return JsonResponse
     */
    public function getAllRoles(): JsonResponse
    {
        try {
            $result = $this->teamService->getAllRoles();

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get roles',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Assign member to accounts
     *
     * PUT /api/orgs/{org_id}/team/members/{user_id}/accounts
     *
     * Request body:
     * {
     *   "account_ids": ["uuid1", "uuid2", "uuid3"]
     * }
     *
     * @param string $orgId
     * @param string $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function assignToAccounts(string $orgId, string $userId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_ids' => 'required|array',
            'account_ids.*' => 'uuid'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->teamService->assignToAccounts(
                $orgId,
                $userId,
                $request->input('account_ids')
            );

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to assign accounts',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * List pending invitations
     *
     * GET /api/orgs/{org_id}/team/invitations
     *
     * @param string $orgId
     * @return JsonResponse
     */
    public function listInvitations(string $orgId): JsonResponse
    {
        try {
            $result = $this->teamService->listInvitations($orgId);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to list invitations',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Cancel invitation
     *
     * DELETE /api/orgs/{org_id}/team/invitations/{invitation_id}
     *
     * @param string $orgId
     * @param string $invitationId
     * @return JsonResponse
     */
    public function cancelInvitation(string $orgId, string $invitationId): JsonResponse
    {
        try {
            $result = $this->teamService->cancelInvitation($invitationId);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to cancel invitation',
                'error' => $e->getMessage()
            );
        }
    }
}
