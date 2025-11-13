<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Core\{Org, UserOrg, Role};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * قائمة مستخدمي الشركة
     */
    public function index(Request $request, string $orgId)
    {
        $this->authorize('viewAny', User::class);

        try {
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search');

            $query = DB::table('cmis.user_orgs')
                ->join('cmis.users', 'user_orgs.user_id', '=', 'users.user_id')
                ->leftJoin('cmis.roles', 'user_orgs.role_id', '=', 'roles.role_id')
                ->where('user_orgs.org_id', $orgId)
                ->where('user_orgs.is_active', true)
                ->whereNull('user_orgs.deleted_at')
                ->whereNull('users.deleted_at')
                ->select([
                    'users.user_id',
                    'users.email',
                    'users.display_name',
                    'users.name',
                    'users.status',
                    'user_orgs.role_id',
                    'roles.role_name',
                    'roles.role_code',
                    'user_orgs.joined_at',
                    'user_orgs.last_accessed',
                ]);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('users.email', 'ilike', "%{$search}%")
                      ->orWhere('users.display_name', 'ilike', "%{$search}%")
                      ->orWhere('users.name', 'ilike', "%{$search}%");
                });
            }

            $users = $query->paginate($perPage);

            return response()->json($users);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض تفاصيل مستخدم في الشركة
     */
    public function show(Request $request, string $orgId, string $userId)
    {
        try {
            $userOrg = UserOrg::with(['user', 'role'])
                ->where('org_id', $orgId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->firstOrFail();

            $this->authorize('view', $userOrg->user);

            return response()->json([
                'user' => $userOrg->user,
                'role' => $userOrg->role,
                'membership' => [
                    'joined_at' => $userOrg->joined_at,
                    'last_accessed' => $userOrg->last_accessed,
                    'invited_by' => $userOrg->invited_by,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دعوة مستخدم للشركة
     */
    public function inviteUser(Request $request, string $orgId)
    {
        $this->authorize('invite', User::class);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role_id' => 'required|uuid|exists:cmis.roles,role_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // البحث عن المستخدم أو إنشاءه
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                $user = User::create([
                    'email' => $request->email,
                    'display_name' => explode('@', $request->email)[0],
                    'name' => explode('@', $request->email)[0],
                    'status' => 'pending',
                    'role' => 'editor',
                ]);
            }

            // التحقق من عدم وجود عضوية سابقة
            $existingMembership = UserOrg::where('user_id', $user->user_id)
                ->where('org_id', $orgId)
                ->whereNull('deleted_at')
                ->first();

            if ($existingMembership) {
                if ($existingMembership->is_active) {
                    return response()->json([
                        'error' => 'User already member',
                        'message' => 'This user is already a member of the organization'
                    ], 409);
                } else {
                    // إعادة تفعيل العضوية
                    $existingMembership->update([
                        'is_active' => true,
                        'role_id' => $request->role_id,
                    ]);
                    $userOrg = $existingMembership;
                }
            } else {
                // إنشاء عضوية جديدة
                $userOrg = UserOrg::create([
                    'user_id' => $user->user_id,
                    'org_id' => $orgId,
                    'role_id' => $request->role_id,
                    'is_active' => true,
                    'joined_at' => now(),
                    'invited_by' => $request->user()->user_id,
                ]);
            }

            DB::commit();

            // TODO: إرسال بريد دعوة

            return response()->json([
                'message' => 'User invited successfully',
                'user' => $user,
                'membership' => $userOrg
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to invite user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث دور المستخدم
     */
    public function updateRole(Request $request, string $orgId, string $userId)
    {
        $targetUser = User::findOrFail($userId);
        $this->authorize('assignRole', $targetUser);

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|uuid|exists:cmis.roles,role_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userOrg = UserOrg::where('org_id', $orgId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->firstOrFail();

            $userOrg->update(['role_id' => $request->role_id]);

            return response()->json([
                'message' => 'User role updated successfully',
                'membership' => $userOrg->fresh(['role', 'user'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User membership not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update role',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تعطيل مستخدم من الشركة
     */
    public function deactivate(Request $request, string $orgId, string $userId)
    {
        $targetUser = User::findOrFail($userId);
        $this->authorize('delete', $targetUser);

        try {
            // منع تعطيل النفس
            if ($userId === $request->user()->user_id) {
                return response()->json([
                    'error' => 'Bad Request',
                    'message' => 'You cannot deactivate yourself'
                ], 400);
            }

            $userOrg = UserOrg::where('org_id', $orgId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $userOrg->update(['is_active' => false]);

            return response()->json([
                'message' => 'User deactivated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User membership not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to deactivate user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف مستخدم من الشركة
     */
    public function remove(Request $request, string $orgId, string $userId)
    {
        $targetUser = User::findOrFail($userId);
        $this->authorize('delete', $targetUser);

        try {
            // منع حذف النفس
            if ($userId === $request->user()->user_id) {
                return response()->json([
                    'error' => 'Bad Request',
                    'message' => 'You cannot remove yourself'
                ], 400);
            }

            $userOrg = UserOrg::where('org_id', $orgId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $userOrg->delete(); // Soft delete

            return response()->json([
                'message' => 'User removed successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User membership not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove user',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
