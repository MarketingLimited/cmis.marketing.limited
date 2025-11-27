<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\ProfileGroup;
use App\Models\Social\ProfileGroupMember;
use App\Models\Creative\BrandVoice;
use App\Models\Compliance\BrandSafetyPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ProfileGroupController
 *
 * Manages profile groups - organizational units for grouping social media profiles
 * by client/brand with associated brand voice, safety policies, and team members.
 *
 * Features:
 * - List, create, update, delete profile groups
 * - Manage team members and permissions
 * - Associate brand voices and safety policies
 * - Link social integrations to groups
 */
class ProfileGroupController extends Controller
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
     * List all profile groups for an organization
     *
     * GET /api/orgs/{org_id}/profile-groups
     *
     * Query params:
     * - search: string (search by name)
     * - language: string (filter by language)
     * - timezone: string (filter by timezone)
     * - per_page: int (pagination limit)
     * - include: string (comma-separated: members,profiles,brand_voice,safety_policy)
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $query = ProfileGroup::query();

            // Search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            // Language filter
            if ($request->filled('language')) {
                $query->byLanguage($request->input('language'));
            }

            // Timezone filter
            if ($request->filled('timezone')) {
                $query->byTimezone($request->input('timezone'));
            }

            // Eager loading based on include parameter
            $includes = explode(',', $request->input('include', ''));
            $with = [];

            if (in_array('members', $includes)) {
                $with[] = 'members.user';
            }
            if (in_array('profiles', $includes)) {
                $with[] = 'socialIntegrations';
            }
            if (in_array('brand_voice', $includes)) {
                $with[] = 'brandVoice';
            }
            if (in_array('safety_policy', $includes)) {
                $with[] = 'brandSafetyPolicy';
            }
            if (in_array('creator', $includes)) {
                $with[] = 'creator';
            }

            if (!empty($with)) {
                $query->with($with);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $groups = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->paginated($groups, 'Profile groups retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve profile groups: ' . $e->getMessage());
        }
    }

    /**
     * Create a new profile group
     *
     * POST /api/orgs/{org_id}/profile-groups
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function store(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_location' => 'nullable|array',
            'client_location.country' => 'required_with:client_location|string|max:100',
            'client_location.city' => 'nullable|string|max:100',
            'logo_url' => 'nullable|url',
            'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'default_link_shortener' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:10',
            'brand_voice_id' => 'nullable|uuid|exists:pgsql.cmis.brand_voices,voice_id',
            'brand_safety_policy_id' => 'nullable|uuid|exists:pgsql.cmis.brand_safety_policies,policy_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $group = ProfileGroup::create([
                'org_id' => $orgId,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'client_location' => $request->input('client_location'),
                'logo_url' => $request->input('logo_url'),
                'color' => $request->input('color', '#3B82F6'),
                'default_link_shortener' => $request->input('default_link_shortener'),
                'timezone' => $request->input('timezone', 'UTC'),
                'language' => $request->input('language', 'ar'),
                'brand_voice_id' => $request->input('brand_voice_id'),
                'brand_safety_policy_id' => $request->input('brand_safety_policy_id'),
                'created_by' => Auth::id(),
            ]);

            // Automatically add creator as owner
            ProfileGroupMember::create([
                'profile_group_id' => $group->group_id,
                'user_id' => Auth::id(),
                'role' => ProfileGroupMember::ROLE_OWNER,
                'permissions' => [
                    'can_publish' => true,
                    'can_schedule' => true,
                    'can_edit_drafts' => true,
                    'can_delete' => true,
                    'can_manage_team' => true,
                    'can_manage_brand_voice' => true,
                    'can_manage_ad_accounts' => true,
                    'requires_approval' => false,
                ],
                'assigned_by' => Auth::id(),
                'joined_at' => now(),
            ]);

            DB::commit();

            $group->load(['creator', 'members.user']);

            return $this->created($group, 'Profile group created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create profile group: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific profile group
     *
     * GET /api/orgs/{org_id}/profile-groups/{group_id}
     *
     * @param string $orgId
     * @param string $groupId
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $orgId, string $groupId, Request $request): JsonResponse
    {
        try {
            $group = ProfileGroup::with([
                'creator',
                'members.user',
                'members.assigner',
                'socialIntegrations',
                'brandVoice',
                'brandSafetyPolicy',
                'approvalWorkflows',
                'adAccounts',
                'boostRules'
            ])->findOrFail($groupId);

            return $this->success($group, 'Profile group retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Profile group not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve profile group: ' . $e->getMessage());
        }
    }

    /**
     * Update a profile group
     *
     * PUT /api/orgs/{org_id}/profile-groups/{group_id}
     *
     * @param string $orgId
     * @param string $groupId
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $orgId, string $groupId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'client_location' => 'nullable|array',
            'client_location.country' => 'required_with:client_location|string|max:100',
            'client_location.city' => 'nullable|string|max:100',
            'logo_url' => 'nullable|url',
            'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'default_link_shortener' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:10',
            'brand_voice_id' => 'nullable|uuid|exists:pgsql.cmis.brand_voices,voice_id',
            'brand_safety_policy_id' => 'nullable|uuid|exists:pgsql.cmis.brand_safety_policies,policy_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $group = ProfileGroup::findOrFail($groupId);

            $group->update($request->only([
                'name',
                'description',
                'client_location',
                'logo_url',
                'color',
                'default_link_shortener',
                'timezone',
                'language',
                'brand_voice_id',
                'brand_safety_policy_id',
            ]));

            $group->load(['creator', 'members.user', 'brandVoice', 'brandSafetyPolicy']);

            return $this->success($group, 'Profile group updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Profile group not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update profile group: ' . $e->getMessage());
        }
    }

    /**
     * Delete a profile group (soft delete)
     *
     * DELETE /api/orgs/{org_id}/profile-groups/{group_id}
     *
     * @param string $orgId
     * @param string $groupId
     * @return JsonResponse
     */
    public function destroy(string $orgId, string $groupId): JsonResponse
    {
        try {
            $group = ProfileGroup::findOrFail($groupId);

            // Check if group has any social integrations
            if ($group->socialIntegrations()->count() > 0) {
                return $this->error(
                    'Cannot delete profile group with active social integrations. Please unassign profiles first.',
                    400
                );
            }

            $group->delete();

            return $this->deleted('Profile group deleted successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Profile group not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete profile group: ' . $e->getMessage());
        }
    }

    /**
     * Get members of a profile group
     *
     * GET /api/orgs/{org_id}/profile-groups/{group_id}/members
     *
     * @param string $orgId
     * @param string $groupId
     * @return JsonResponse
     */
    public function members(string $orgId, string $groupId): JsonResponse
    {
        try {
            $group = ProfileGroup::findOrFail($groupId);

            $members = $group->members()
                ->with(['user', 'assigner'])
                ->orderBy('joined_at', 'desc')
                ->get();

            return $this->success($members, 'Profile group members retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Profile group not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve members: ' . $e->getMessage());
        }
    }

    /**
     * Add a member to the profile group
     *
     * POST /api/orgs/{org_id}/profile-groups/{group_id}/members
     *
     * @param string $orgId
     * @param string $groupId
     * @param Request $request
     * @return JsonResponse
     */
    public function addMember(string $orgId, string $groupId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid|exists:pgsql.cmis.users,user_id',
            'role' => ['required', Rule::in(ProfileGroupMember::getAvailableRoles())],
            'permissions' => 'nullable|array',
            'permissions.can_publish' => 'nullable|boolean',
            'permissions.can_schedule' => 'nullable|boolean',
            'permissions.can_edit_drafts' => 'nullable|boolean',
            'permissions.can_delete' => 'nullable|boolean',
            'permissions.can_manage_team' => 'nullable|boolean',
            'permissions.can_manage_brand_voice' => 'nullable|boolean',
            'permissions.can_manage_ad_accounts' => 'nullable|boolean',
            'permissions.requires_approval' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $group = ProfileGroup::findOrFail($groupId);

            // Check if user is already a member
            $existingMember = ProfileGroupMember::where('profile_group_id', $groupId)
                ->where('user_id', $request->input('user_id'))
                ->first();

            if ($existingMember) {
                return $this->error('User is already a member of this profile group', 400);
            }

            // Default permissions based on role
            $defaultPermissions = $this->getDefaultPermissionsForRole($request->input('role'));
            $permissions = array_merge($defaultPermissions, $request->input('permissions', []));

            $member = ProfileGroupMember::create([
                'profile_group_id' => $groupId,
                'user_id' => $request->input('user_id'),
                'role' => $request->input('role'),
                'permissions' => $permissions,
                'assigned_by' => Auth::id(),
                'joined_at' => now(),
            ]);

            $member->load(['user', 'assigner']);

            return $this->created($member, 'Member added to profile group successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Profile group not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to add member: ' . $e->getMessage());
        }
    }

    /**
     * Update a member's role or permissions
     *
     * PUT /api/orgs/{org_id}/profile-groups/{group_id}/members/{member_id}
     *
     * @param string $orgId
     * @param string $groupId
     * @param string $memberId
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMember(string $orgId, string $groupId, string $memberId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => ['sometimes', 'required', Rule::in(ProfileGroupMember::getAvailableRoles())],
            'permissions' => 'sometimes|required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $member = ProfileGroupMember::where('profile_group_id', $groupId)
                ->where('id', $memberId)
                ->firstOrFail();

            if ($request->filled('role')) {
                $member->role = $request->input('role');

                // Update default permissions for new role if permissions not explicitly set
                if (!$request->filled('permissions')) {
                    $member->permissions = $this->getDefaultPermissionsForRole($member->role);
                }
            }

            if ($request->filled('permissions')) {
                $member->permissions = array_merge($member->permissions, $request->input('permissions'));
            }

            $member->save();
            $member->load(['user', 'assigner']);

            return $this->success($member, 'Member updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Member not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update member: ' . $e->getMessage());
        }
    }

    /**
     * Remove a member from the profile group
     *
     * DELETE /api/orgs/{org_id}/profile-groups/{group_id}/members/{member_id}
     *
     * @param string $orgId
     * @param string $groupId
     * @param string $memberId
     * @return JsonResponse
     */
    public function removeMember(string $orgId, string $groupId, string $memberId): JsonResponse
    {
        try {
            $member = ProfileGroupMember::where('profile_group_id', $groupId)
                ->where('id', $memberId)
                ->firstOrFail();

            // Prevent removing the last owner
            if ($member->isOwner()) {
                $ownerCount = ProfileGroupMember::where('profile_group_id', $groupId)
                    ->where('role', ProfileGroupMember::ROLE_OWNER)
                    ->count();

                if ($ownerCount <= 1) {
                    return $this->error('Cannot remove the last owner of the profile group', 400);
                }
            }

            $member->delete();

            return $this->deleted('Member removed from profile group successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Member not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to remove member: ' . $e->getMessage());
        }
    }

    /**
     * Get default permissions for a role
     *
     * @param string $role
     * @return array
     */
    private function getDefaultPermissionsForRole(string $role): array
    {
        return match($role) {
            ProfileGroupMember::ROLE_OWNER => [
                'can_publish' => true,
                'can_schedule' => true,
                'can_edit_drafts' => true,
                'can_delete' => true,
                'can_manage_team' => true,
                'can_manage_brand_voice' => true,
                'can_manage_ad_accounts' => true,
                'requires_approval' => false,
            ],
            ProfileGroupMember::ROLE_ADMIN => [
                'can_publish' => true,
                'can_schedule' => true,
                'can_edit_drafts' => true,
                'can_delete' => true,
                'can_manage_team' => true,
                'can_manage_brand_voice' => false,
                'can_manage_ad_accounts' => false,
                'requires_approval' => false,
            ],
            ProfileGroupMember::ROLE_EDITOR => [
                'can_publish' => true,
                'can_schedule' => true,
                'can_edit_drafts' => true,
                'can_delete' => false,
                'can_manage_team' => false,
                'can_manage_brand_voice' => false,
                'can_manage_ad_accounts' => false,
                'requires_approval' => false,
            ],
            ProfileGroupMember::ROLE_CONTRIBUTOR => [
                'can_publish' => false,
                'can_schedule' => false,
                'can_edit_drafts' => true,
                'can_delete' => false,
                'can_manage_team' => false,
                'can_manage_brand_voice' => false,
                'can_manage_ad_accounts' => false,
                'requires_approval' => true,
            ],
            ProfileGroupMember::ROLE_VIEWER => [
                'can_publish' => false,
                'can_schedule' => false,
                'can_edit_drafts' => false,
                'can_delete' => false,
                'can_manage_team' => false,
                'can_manage_brand_voice' => false,
                'can_manage_ad_accounts' => false,
                'requires_approval' => true,
            ],
            default => [
                'can_publish' => false,
                'can_schedule' => false,
                'can_edit_drafts' => true,
                'can_delete' => false,
                'can_manage_team' => false,
                'can_manage_brand_voice' => false,
                'can_manage_ad_accounts' => false,
                'requires_approval' => true,
            ],
        };
    }

    /**
     * Verify org access helper
     */
    private function verifyOrgAccess($user, string $orgId): void
    {
        $hasAccess = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('org_id', $orgId)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Access denied to this organization');
        }
    }
}
