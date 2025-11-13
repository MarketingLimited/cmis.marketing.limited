<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Core\{Org, UserOrg, Role};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrgController extends Controller
{
    /**
     * قائمة شركات المستخدم الحالي
     */
    public function listUserOrgs(Request $request)
    {
        try {
            $orgs = $request->user()
                ->orgs()
                ->with(['roles' => function($query) {
                    $query->where('is_system', true)->where('is_active', true);
                }])
                ->get()
                ->map(function($org) {
                    return [
                        'org_id' => $org->org_id,
                        'name' => $org->name,
                        'default_locale' => $org->default_locale,
                        'currency' => $org->currency,
                        'role_id' => $org->pivot->role_id,
                        'joined_at' => $org->pivot->joined_at,
                        'last_accessed' => $org->pivot->last_accessed,
                    ];
                });

            return response()->json([
                'orgs' => $orgs,
                'total' => $orgs->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch organizations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء شركة جديدة
     */
    public function store(Request $request)
    {
        $this->authorize('create', Org::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'default_locale' => 'nullable|string|max:10',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $org = Org::create([
                'name' => $request->name,
                'default_locale' => $request->default_locale ?? 'ar-BH',
                'currency' => $request->currency ?? 'BHD',
                'provider' => $request->provider,
            ]);

            // الحصول على دور Owner
            $ownerRole = Role::where('role_code', 'owner')
                ->where('is_system', true)
                ->whereNull('org_id')
                ->first();

            if (!$ownerRole) {
                $ownerRole = Role::create([
                    'role_name' => 'Owner',
                    'role_code' => 'owner',
                    'description' => 'Organization owner with full permissions',
                    'is_system' => true,
                    'is_active' => true,
                ]);
            }

            // ربط المستخدم كمالك
            UserOrg::create([
                'user_id' => $request->user()->user_id,
                'org_id' => $org->org_id,
                'role_id' => $ownerRole->role_id,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Organization created successfully',
                'org' => $org->fresh(['users', 'roles']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create organization',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض تفاصيل الشركة
     */
    public function show(Request $request, string $orgId)
    {
        $org = Org::findOrFail($orgId);
        $this->authorize('view', $org);

        try {
            $org = Org::with([
                'users' => function($query) {
                    $query->select('users.user_id', 'email', 'display_name', 'name', 'status')
                          ->where('is_active', true);
                },
                'roles' => function($query) {
                    $query->where('is_active', true);
                },
                'integrations' => function($query) {
                    $query->where('status', 'active');
                }
            ])->findOrFail($orgId);

            return response()->json(['org' => $org]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Organization not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch organization',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث الشركة
     */
    public function update(Request $request, string $orgId)
    {
        $org = Org::findOrFail($orgId);
        $this->authorize('update', $org);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'default_locale' => 'sometimes|string|max:10',
            'currency' => 'sometimes|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $org->update($request->only(['name', 'default_locale', 'currency']));

            return response()->json([
                'message' => 'Organization updated successfully',
                'org' => $org->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Organization not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update organization',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف الشركة
     */
    public function destroy(Request $request, string $orgId)
    {
        try {
            $org = Org::findOrFail($orgId);
            $this->authorize('delete', $org);

            $org->delete();

            return response()->json(['message' => 'Organization deleted successfully']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Organization not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete organization',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات الشركة
     */
    public function statistics(Request $request, string $orgId)
    {
        try {
            $org = Org::findOrFail($orgId);
            $this->authorize('view', $org);

            $stats = [
                'users_count' => $org->users()->count(),
                'campaigns_count' => $org->campaigns()->count(),
                'active_campaigns' => $org->campaigns()->where('status', 'active')->count(),
                'creative_assets_count' => $org->creativeAssets()->count(),
                'integrations_count' => $org->integrations()->where('status', 'active')->count(),
                'created_at' => $org->created_at,
            ];

            return response()->json([
                'org_id' => $orgId,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
