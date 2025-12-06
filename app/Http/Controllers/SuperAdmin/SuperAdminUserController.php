<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Super Admin User Controller
 *
 * Manages all users on the platform.
 */
class SuperAdminUserController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display a listing of all users.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->with(['orgs' => function($q) {
                $q->select('cmis.orgs.org_id', 'cmis.orgs.name', 'cmis.orgs.status');
            }])
            ->withCount('orgs');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('user_id', 'ilike', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'suspended':
                    $query->suspended();
                    break;
                case 'blocked':
                    $query->blocked();
                    break;
                case 'restricted':
                    $query->restricted();
                    break;
            }
        }

        // Super admin filter
        if ($request->filled('is_super_admin')) {
            $query->where('is_super_admin', $request->boolean('is_super_admin'));
        }

        // Org filter
        if ($request->filled('org_id')) {
            $query->whereHas('orgs', function ($q) use ($request) {
                $q->where('cmis.orgs.org_id', $request->org_id);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $users = $query->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            // Deduplicate orgs for each user (user may have multiple roles in same org)
            $users->getCollection()->transform(function ($user) {
                if ($user->orgs) {
                    $user->setRelation('orgs', $user->orgs->unique('org_id')->values());
                    $user->orgs_count = $user->orgs->count();
                }
                return $user;
            });
            return $this->paginated($users);
        }

        return view('super-admin.users.index', compact('users'));
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, string $userId)
    {
        $user = User::with([
            'orgs',
            'orgMemberships',
            'suspendedByUser',
            'blockedByUser',
        ])
            ->withCount('orgs')
            ->findOrFail($userId);

        // Deduplicate orgs (user may have multiple roles in same org)
        $user->setRelation('orgs', $user->orgs->unique('org_id')->values());

        // Get user's activity stats
        // Note: platform_api_calls tracks by org_id, not user_id
        // So we count API calls across all organizations the user belongs to
        $userOrgIds = $user->orgs->pluck('org_id')->unique()->toArray();
        $activityStats = [
            'api_calls_total' => $userOrgIds ? DB::table('cmis.platform_api_calls')
                ->whereIn('org_id', $userOrgIds)
                ->count() : 0,
            'api_calls_today' => $userOrgIds ? DB::table('cmis.platform_api_calls')
                ->whereIn('org_id', $userOrgIds)
                ->whereDate('called_at', today())
                ->count() : 0,
            'last_login' => $user->last_login_at ?? null,
            'orgs_count' => count($userOrgIds),
        ];

        if ($request->expectsJson()) {
            return $this->success([
                'user' => $user,
                'activity_stats' => $activityStats,
            ]);
        }

        return view('super-admin.users.show', compact('user', 'activityStats'));
    }

    /**
     * Suspend a user.
     */
    public function suspend(Request $request, string $userId)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $user = User::findOrFail($userId);

        // Cannot suspend yourself
        if ($user->user_id === Auth::id()) {
            return $this->error(__('super_admin.cannot_suspend_self'), 400);
        }

        // Cannot suspend another super admin (unless you are the primary)
        if ($user->is_super_admin && !$this->canManageSuperAdmin()) {
            return $this->error(__('super_admin.cannot_suspend_super_admin'), 403);
        }

        if ($user->is_blocked) {
            return $this->error(__('super_admin.user_already_blocked'), 400);
        }

        $user->suspend($request->reason, Auth::id());

        $this->logAction('user_suspended', 'user', $userId, $user->name, [
            'reason' => $request->reason,
        ]);

        if ($request->expectsJson()) {
            return $this->success($user->fresh(), __('super_admin.user_suspended'));
        }

        return redirect()->back()->with('success', __('super_admin.user_suspended'));
    }

    /**
     * Block a user.
     */
    public function block(Request $request, string $userId)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $user = User::findOrFail($userId);

        // Cannot block yourself
        if ($user->user_id === Auth::id()) {
            return $this->error(__('super_admin.cannot_block_self'), 400);
        }

        // Cannot block another super admin
        if ($user->is_super_admin && !$this->canManageSuperAdmin()) {
            return $this->error(__('super_admin.cannot_block_super_admin'), 403);
        }

        $user->block($request->reason, Auth::id());

        $this->logAction('user_blocked', 'user', $userId, $user->name, [
            'reason' => $request->reason,
        ]);

        if ($request->expectsJson()) {
            return $this->success($user->fresh(), __('super_admin.user_blocked'));
        }

        return redirect()->back()->with('success', __('super_admin.user_blocked'));
    }

    /**
     * Restore a user (unsuspend/unblock).
     */
    public function restore(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        if (!$user->isRestricted()) {
            return $this->error(__('super_admin.user_already_active'), 400);
        }

        $wasBlocked = $user->is_blocked;
        $wasSuspended = $user->is_suspended;

        if ($wasBlocked) {
            $user->unblock();
        }
        if ($wasSuspended) {
            $user->unsuspend();
        }

        $this->logAction('user_restored', 'user', $userId, $user->name, [
            'was_blocked' => $wasBlocked,
            'was_suspended' => $wasSuspended,
        ]);

        if ($request->expectsJson()) {
            return $this->success($user->fresh(), __('super_admin.user_restored'));
        }

        return redirect()->back()->with('success', __('super_admin.user_restored'));
    }

    /**
     * Perform bulk actions on users.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:suspend,block,restore,delete',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'uuid',
            'reason' => 'required_if:action,suspend,block|string|max:1000',
        ]);

        $action = $request->action;
        $userIds = $request->user_ids;
        $reason = $request->reason;
        $processedCount = 0;
        $skippedCount = 0;

        // Cannot include self in bulk actions
        if (in_array(Auth::id(), $userIds)) {
            return $this->error(__('super_admin.cannot_bulk_self'), 400);
        }

        DB::beginTransaction();
        try {
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if (!$user) continue;

                // Skip super admins in bulk operations
                if ($user->is_super_admin) {
                    $skippedCount++;
                    continue;
                }

                switch ($action) {
                    case 'suspend':
                        if (!$user->is_suspended) {
                            $user->suspend($reason, Auth::id());
                            $processedCount++;
                        }
                        break;

                    case 'block':
                        $user->block($reason, Auth::id());
                        $processedCount++;
                        break;

                    case 'restore':
                        if ($user->isRestricted()) {
                            $user->unblock();
                            $user->unsuspend();
                            $processedCount++;
                        }
                        break;

                    case 'delete':
                        $user->delete(); // Soft delete
                        $processedCount++;
                        break;
                }
            }

            DB::commit();

            $this->logAction("bulk_{$action}", 'user', null, null, [
                'user_ids' => $userIds,
                'processed_count' => $processedCount,
                'skipped_count' => $skippedCount,
                'reason' => $reason,
            ]);

            return $this->success([
                'processed_count' => $processedCount,
                'skipped_count' => $skippedCount,
            ], __('super_admin.bulk_action_completed', ['count' => $processedCount]));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk user action failed', ['error' => $e->getMessage()]);
            return $this->serverError(__('super_admin.bulk_action_failed'));
        }
    }

    /**
     * Toggle super admin status for a user.
     */
    public function toggleSuperAdmin(Request $request, string $userId)
    {
        if (!$this->canManageSuperAdmin()) {
            return $this->forbidden(__('super_admin.cannot_manage_super_admins'));
        }

        $user = User::findOrFail($userId);

        // Cannot demote yourself
        if ($user->user_id === Auth::id()) {
            return $this->error(__('super_admin.cannot_demote_self'), 400);
        }

        $user->update([
            'is_super_admin' => !$user->is_super_admin,
        ]);

        $this->logAction(
            $user->is_super_admin ? 'super_admin_granted' : 'super_admin_revoked',
            'user',
            $userId,
            $user->name,
            []
        );

        return $this->success($user->fresh(), $user->is_super_admin
            ? __('super_admin.super_admin_granted')
            : __('super_admin.super_admin_revoked')
        );
    }

    /**
     * Impersonate a user (login as them for debugging).
     */
    public function impersonate(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        // Cannot impersonate super admins
        if ($user->is_super_admin) {
            return $this->error(__('super_admin.cannot_impersonate_super_admin'), 403);
        }

        // Store original user ID in session
        Session::put('impersonating_from', Auth::id());
        Session::put('impersonating_user', $userId);

        // Login as the user
        Auth::login($user);

        $this->logAction('user_impersonated', 'user', $userId, $user->name, []);

        return redirect('/dashboard')->with('info', __('super_admin.impersonating', ['name' => $user->name]));
    }

    /**
     * Stop impersonating and return to super admin account.
     */
    public function stopImpersonating(Request $request)
    {
        $originalUserId = Session::get('impersonating_from');

        if (!$originalUserId) {
            return redirect('/dashboard');
        }

        $originalUser = User::find($originalUserId);
        if ($originalUser && $originalUser->is_super_admin) {
            Auth::login($originalUser);
        }

        Session::forget('impersonating_from');
        Session::forget('impersonating_user');

        return redirect('/super-admin/dashboard')->with('success', __('super_admin.stopped_impersonating'));
    }

    /**
     * Check if current user can manage other super admins.
     * In the future, this could check for a "primary" super admin flag.
     */
    protected function canManageSuperAdmin(): bool
    {
        // For now, any super admin can manage others
        // You might want to add a "is_primary_admin" flag later
        return Auth::user()->is_super_admin ?? false;
    }

}
