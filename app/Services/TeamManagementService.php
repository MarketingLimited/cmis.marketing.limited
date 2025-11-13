<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * TeamManagementService
 *
 * Handles team member management and permissions
 * Implements Sprint 5.1: Team Management
 *
 * Features:
 * - Invite and manage team members
 * - Role-based access control
 * - Account-level assignments
 * - Permission management
 */
class TeamManagementService
{
    /**
     * Role definitions with permissions
     */
    protected array $rolePermissions = [
        'owner' => [
            'manage_team', 'manage_billing', 'manage_integrations',
            'create_content', 'edit_content', 'delete_content', 'publish_content',
            'view_analytics', 'manage_campaigns', 'manage_budgets',
            'approve_content', 'manage_workflows'
        ],
        'admin' => [
            'manage_team', 'manage_integrations',
            'create_content', 'edit_content', 'delete_content', 'publish_content',
            'view_analytics', 'manage_campaigns', 'manage_budgets',
            'approve_content', 'manage_workflows'
        ],
        'manager' => [
            'create_content', 'edit_content', 'delete_content', 'publish_content',
            'view_analytics', 'manage_campaigns',
            'approve_content', 'manage_workflows'
        ],
        'editor' => [
            'create_content', 'edit_content', 'publish_content',
            'view_analytics'
        ],
        'contributor' => [
            'create_content', 'edit_content',
            'view_analytics'
        ],
        'viewer' => [
            'view_analytics'
        ]
    ];

    /**
     * Invite a team member to organization
     *
     * @param string $orgId
     * @param array $invitationData
     * @return array
     */
    public function inviteTeamMember(string $orgId, array $invitationData): array
    {
        try {
            DB::beginTransaction();

            // Check if organization exists
            $org = Organization::where('org_id', $orgId)->first();
            if (!$org) {
                return ['success' => false, 'message' => 'Organization not found'];
            }

            $email = $invitationData['email'];
            $role = $invitationData['role'] ?? 'contributor';

            // Validate role
            if (!array_key_exists($role, $this->rolePermissions)) {
                return ['success' => false, 'message' => 'Invalid role specified'];
            }

            // Check if user already exists in organization
            $existingMember = DB::table('cmis.org_users')
                ->join('cmis.users', 'cmis.org_users.user_id', '=', 'cmis.users.user_id')
                ->where('cmis.org_users.org_id', $orgId)
                ->where('cmis.users.email', $email)
                ->first();

            if ($existingMember) {
                return ['success' => false, 'message' => 'User is already a member of this organization'];
            }

            // Check if invitation already exists and is pending
            $existingInvitation = DB::table('cmis.team_invitations')
                ->where('org_id', $orgId)
                ->where('email', $email)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            if ($existingInvitation) {
                return ['success' => false, 'message' => 'Invitation already sent to this email'];
            }

            // Create invitation
            $invitationId = (string) Str::uuid();
            $token = Str::random(64);

            DB::table('cmis.team_invitations')->insert([
                'invitation_id' => $invitationId,
                'org_id' => $orgId,
                'email' => $email,
                'role' => $role,
                'invited_by' => $invitationData['invited_by'] ?? null,
                'invitation_token' => hash('sha256', $token),
                'status' => 'pending',
                'message' => $invitationData['message'] ?? null,
                'account_access' => json_encode($invitationData['account_access'] ?? []),
                'expires_at' => now()->addDays(7),
                'created_at' => now()
            ]);

            // Send invitation email (placeholder - would integrate with actual mail service)
            $invitationLink = config('app.url') . "/invitations/{$token}";

            // In production, send actual email:
            // Mail::to($email)->send(new TeamInvitationMail($org, $invitationLink, $role));

            DB::commit();

            return [
                'success' => true,
                'message' => 'Invitation sent successfully',
                'data' => [
                    'invitation_id' => $invitationId,
                    'email' => $email,
                    'role' => $role,
                    'invitation_link' => $invitationLink,
                    'expires_at' => now()->addDays(7)->toDateTimeString()
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to send invitation',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Accept team invitation
     *
     * @param string $token
     * @param string $userId
     * @return array
     */
    public function acceptInvitation(string $token, string $userId): array
    {
        try {
            DB::beginTransaction();

            // Find invitation by token
            $invitation = DB::table('cmis.team_invitations')
                ->where('invitation_token', hash('sha256', $token))
                ->where('status', 'pending')
                ->first();

            if (!$invitation) {
                return ['success' => false, 'message' => 'Invalid or expired invitation'];
            }

            // Check if invitation has expired
            if (Carbon::parse($invitation->expires_at)->isPast()) {
                DB::table('cmis.team_invitations')
                    ->where('invitation_id', $invitation->invitation_id)
                    ->update(['status' => 'expired', 'updated_at' => now()]);

                return ['success' => false, 'message' => 'Invitation has expired'];
            }

            // Get user
            $user = User::where('user_id', $userId)->first();
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Verify email matches
            if ($user->email !== $invitation->email) {
                return ['success' => false, 'message' => 'Invitation email does not match user email'];
            }

            // Check if user is already a member
            $existingMember = DB::table('cmis.org_users')
                ->where('org_id', $invitation->org_id)
                ->where('user_id', $userId)
                ->first();

            if ($existingMember) {
                // Update invitation status
                DB::table('cmis.team_invitations')
                    ->where('invitation_id', $invitation->invitation_id)
                    ->update(['status' => 'accepted', 'updated_at' => now()]);

                return ['success' => false, 'message' => 'User is already a member of this organization'];
            }

            // Add user to organization
            $orgUserId = (string) Str::uuid();
            DB::table('cmis.org_users')->insert([
                'org_user_id' => $orgUserId,
                'org_id' => $invitation->org_id,
                'user_id' => $userId,
                'role' => $invitation->role,
                'joined_at' => now(),
                'created_at' => now()
            ]);

            // Assign account access if specified
            if ($invitation->account_access) {
                $accountAccess = json_decode($invitation->account_access, true);
                foreach ($accountAccess as $accountId) {
                    DB::table('cmis.team_account_access')->insert([
                        'access_id' => (string) Str::uuid(),
                        'org_user_id' => $orgUserId,
                        'social_account_id' => $accountId,
                        'created_at' => now()
                    ]);
                }
            }

            // Update invitation status
            DB::table('cmis.team_invitations')
                ->where('invitation_id', $invitation->invitation_id)
                ->update([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'accepted_by' => $userId,
                    'updated_at' => now()
                ]);

            DB::commit();

            // Clear organization cache
            Cache::forget("org_members:{$invitation->org_id}");

            return [
                'success' => true,
                'message' => 'Invitation accepted successfully',
                'data' => [
                    'org_id' => $invitation->org_id,
                    'role' => $invitation->role,
                    'permissions' => $this->rolePermissions[$invitation->role] ?? []
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to accept invitation',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove team member from organization
     *
     * @param string $orgId
     * @param string $userId
     * @return array
     */
    public function removeTeamMember(string $orgId, string $userId): array
    {
        try {
            DB::beginTransaction();

            // Find org_user record
            $orgUser = DB::table('cmis.org_users')
                ->where('org_id', $orgId)
                ->where('user_id', $userId)
                ->first();

            if (!$orgUser) {
                return ['success' => false, 'message' => 'User is not a member of this organization'];
            }

            // Prevent removing owner if it's the last owner
            if ($orgUser->role === 'owner') {
                $ownerCount = DB::table('cmis.org_users')
                    ->where('org_id', $orgId)
                    ->where('role', 'owner')
                    ->count();

                if ($ownerCount <= 1) {
                    return ['success' => false, 'message' => 'Cannot remove the last owner of the organization'];
                }
            }

            // Remove account access
            DB::table('cmis.team_account_access')
                ->where('org_user_id', $orgUser->org_user_id)
                ->delete();

            // Remove from organization
            DB::table('cmis.org_users')
                ->where('org_user_id', $orgUser->org_user_id)
                ->delete();

            DB::commit();

            // Clear cache
            Cache::forget("org_members:{$orgId}");

            return [
                'success' => true,
                'message' => 'Team member removed successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to remove team member',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update team member role
     *
     * @param string $orgId
     * @param string $userId
     * @param string $newRole
     * @return array
     */
    public function updateMemberRole(string $orgId, string $userId, string $newRole): array
    {
        try {
            // Validate role
            if (!array_key_exists($newRole, $this->rolePermissions)) {
                return ['success' => false, 'message' => 'Invalid role specified'];
            }

            DB::beginTransaction();

            // Find org_user record
            $orgUser = DB::table('cmis.org_users')
                ->where('org_id', $orgId)
                ->where('user_id', $userId)
                ->first();

            if (!$orgUser) {
                return ['success' => false, 'message' => 'User is not a member of this organization'];
            }

            // Prevent changing owner role if it's the last owner
            if ($orgUser->role === 'owner' && $newRole !== 'owner') {
                $ownerCount = DB::table('cmis.org_users')
                    ->where('org_id', $orgId)
                    ->where('role', 'owner')
                    ->count();

                if ($ownerCount <= 1) {
                    return ['success' => false, 'message' => 'Cannot change role of the last owner'];
                }
            }

            // Update role
            DB::table('cmis.org_users')
                ->where('org_user_id', $orgUser->org_user_id)
                ->update([
                    'role' => $newRole,
                    'updated_at' => now()
                ]);

            DB::commit();

            // Clear cache
            Cache::forget("org_members:{$orgId}");

            return [
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => [
                    'user_id' => $userId,
                    'role' => $newRole,
                    'permissions' => $this->rolePermissions[$newRole]
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List team members
     *
     * @param string $orgId
     * @param array $filters
     * @return array
     */
    public function listTeamMembers(string $orgId, array $filters = []): array
    {
        try {
            $cacheKey = "org_members:{$orgId}:" . md5(json_encode($filters));

            return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($orgId, $filters) {
                $query = DB::table('cmis.org_users')
                    ->join('cmis.users', 'cmis.org_users.user_id', '=', 'cmis.users.user_id')
                    ->where('cmis.org_users.org_id', $orgId)
                    ->select(
                        'cmis.org_users.org_user_id',
                        'cmis.users.user_id',
                        'cmis.users.email',
                        'cmis.users.first_name',
                        'cmis.users.last_name',
                        'cmis.org_users.role',
                        'cmis.org_users.joined_at',
                        'cmis.users.last_login_at'
                    );

                // Apply filters
                if (!empty($filters['role'])) {
                    $query->where('cmis.org_users.role', $filters['role']);
                }

                if (!empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('cmis.users.email', 'ILIKE', "%{$search}%")
                          ->orWhere('cmis.users.first_name', 'ILIKE', "%{$search}%")
                          ->orWhere('cmis.users.last_name', 'ILIKE', "%{$search}%");
                    });
                }

                // Sorting
                $sortBy = $filters['sort_by'] ?? 'joined_at';
                $sortOrder = $filters['sort_order'] ?? 'desc';
                $query->orderBy($sortBy, $sortOrder);

                $members = $query->get();

                // Enrich with account access and permissions
                $enrichedMembers = [];
                foreach ($members as $member) {
                    // Get account access
                    $accountAccess = DB::table('cmis.team_account_access')
                        ->join('cmis.social_accounts', 'cmis.team_account_access.social_account_id', '=', 'cmis.social_accounts.social_account_id')
                        ->where('cmis.team_account_access.org_user_id', $member->org_user_id)
                        ->select(
                            'cmis.social_accounts.social_account_id',
                            'cmis.social_accounts.platform',
                            'cmis.social_accounts.account_name'
                        )
                        ->get();

                    $enrichedMembers[] = [
                        'user_id' => $member->user_id,
                        'email' => $member->email,
                        'name' => trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')),
                        'role' => $member->role,
                        'permissions' => $this->rolePermissions[$member->role] ?? [],
                        'account_access' => $accountAccess->toArray(),
                        'joined_at' => $member->joined_at,
                        'last_login_at' => $member->last_login_at
                    ];
                }

                return [
                    'success' => true,
                    'data' => $enrichedMembers,
                    'total' => count($enrichedMembers)
                ];
            });

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to list team members',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get permissions for a role
     *
     * @param string $role
     * @return array
     */
    public function getRolePermissions(string $role): array
    {
        if (!array_key_exists($role, $this->rolePermissions)) {
            return ['success' => false, 'message' => 'Invalid role'];
        }

        return [
            'success' => true,
            'data' => [
                'role' => $role,
                'permissions' => $this->rolePermissions[$role],
                'description' => $this->getRoleDescription($role)
            ]
        ];
    }

    /**
     * Get all available roles
     *
     * @return array
     */
    public function getAllRoles(): array
    {
        $roles = [];
        foreach ($this->rolePermissions as $role => $permissions) {
            $roles[] = [
                'role' => $role,
                'permissions' => $permissions,
                'description' => $this->getRoleDescription($role)
            ];
        }

        return [
            'success' => true,
            'data' => $roles
        ];
    }

    /**
     * Get role description
     *
     * @param string $role
     * @return string
     */
    protected function getRoleDescription(string $role): string
    {
        $descriptions = [
            'owner' => 'Full access to all features including billing and team management',
            'admin' => 'Full access to all features except billing',
            'manager' => 'Can manage content, campaigns, and approve workflows',
            'editor' => 'Can create, edit, and publish content',
            'contributor' => 'Can create and edit content but cannot publish',
            'viewer' => 'Read-only access to analytics and reports'
        ];

        return $descriptions[$role] ?? '';
    }

    /**
     * Assign team member to social accounts
     *
     * @param string $orgId
     * @param string $userId
     * @param array $accountIds
     * @return array
     */
    public function assignToAccounts(string $orgId, string $userId, array $accountIds): array
    {
        try {
            DB::beginTransaction();

            // Find org_user record
            $orgUser = DB::table('cmis.org_users')
                ->where('org_id', $orgId)
                ->where('user_id', $userId)
                ->first();

            if (!$orgUser) {
                return ['success' => false, 'message' => 'User is not a member of this organization'];
            }

            // Remove existing account access
            DB::table('cmis.team_account_access')
                ->where('org_user_id', $orgUser->org_user_id)
                ->delete();

            // Add new account access
            foreach ($accountIds as $accountId) {
                DB::table('cmis.team_account_access')->insert([
                    'access_id' => (string) Str::uuid(),
                    'org_user_id' => $orgUser->org_user_id,
                    'social_account_id' => $accountId,
                    'created_at' => now()
                ]);
            }

            DB::commit();

            // Clear cache
            Cache::forget("org_members:{$orgId}");

            return [
                'success' => true,
                'message' => 'Account access updated successfully',
                'data' => [
                    'user_id' => $userId,
                    'account_count' => count($accountIds)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to assign accounts',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List pending invitations
     *
     * @param string $orgId
     * @return array
     */
    public function listInvitations(string $orgId): array
    {
        try {
            $invitations = DB::table('cmis.team_invitations')
                ->where('org_id', $orgId)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedInvitations = $invitations->map(function ($invitation) {
                return [
                    'invitation_id' => $invitation->invitation_id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'status' => $invitation->status,
                    'invited_by' => $invitation->invited_by,
                    'created_at' => $invitation->created_at,
                    'expires_at' => $invitation->expires_at
                ];
            });

            return [
                'success' => true,
                'data' => $formattedInvitations,
                'total' => $formattedInvitations->count()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to list invitations',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancel invitation
     *
     * @param string $invitationId
     * @return array
     */
    public function cancelInvitation(string $invitationId): array
    {
        try {
            $invitation = DB::table('cmis.team_invitations')
                ->where('invitation_id', $invitationId)
                ->first();

            if (!$invitation) {
                return ['success' => false, 'message' => 'Invitation not found'];
            }

            if ($invitation->status !== 'pending') {
                return ['success' => false, 'message' => 'Only pending invitations can be cancelled'];
            }

            DB::table('cmis.team_invitations')
                ->where('invitation_id', $invitationId)
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now()
                ]);

            return [
                'success' => true,
                'message' => 'Invitation cancelled successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to cancel invitation',
                'error' => $e->getMessage()
            ];
        }
    }
}
