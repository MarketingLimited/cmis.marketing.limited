<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Org;
use App\Models\Subscription\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Organization Controller
 *
 * Manages all organizations on the platform.
 */
class SuperAdminOrgController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of all organizations.
     */
    public function index(Request $request)
    {
        $query = Org::query()
            ->withCount(['users' => function($q) {
                // Count distinct users (user may have multiple roles in org)
                $q->select(DB::raw('count(distinct cmis.user_orgs.user_id)'));
            }])
            ->with(['subscription', 'subscription.plan']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('org_id', 'ilike', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Plan filter
        if ($request->filled('plan')) {
            $query->whereHas('subscription', function ($q) use ($request) {
                $q->where('plan_id', $request->plan);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $organizations = $query->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($organizations);
        }

        return view('super-admin.organizations.index', compact('organizations'));
    }

    /**
     * Display the specified organization.
     */
    public function show(Request $request, string $orgId)
    {
        $org = Org::with([
            'users',
            'subscription',
            'subscription.plan',
            'suspendedByUser',
            'blockedByUser',
        ])
            ->withCount([
                'users' => function($q) {
                    // Count distinct users (user may have multiple roles in org)
                    $q->select(DB::raw('count(distinct cmis.user_orgs.user_id)'));
                },
                'campaigns',
                'integrations'
            ])
            ->findOrFail($orgId);

        // Deduplicate users (user may have multiple roles in same org)
        $org->setRelation('users', $org->users->unique('user_id')->values());

        // Get API usage stats for this org
        $apiStats = [
            'total_calls' => DB::table('cmis.platform_api_calls')
                ->where('org_id', $orgId)
                ->count(),
            'calls_today' => DB::table('cmis.platform_api_calls')
                ->where('org_id', $orgId)
                ->whereDate('called_at', today())
                ->count(),
            'error_rate' => \App\Models\Platform\PlatformApiCall::getErrorRate($orgId),
        ];

        if ($request->expectsJson()) {
            return $this->success([
                'organization' => $org,
                'api_stats' => $apiStats,
            ]);
        }

        return view('super-admin.organizations.show', compact('org', 'apiStats'));
    }

    /**
     * Suspend an organization.
     */
    public function suspend(Request $request, string $orgId)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $org = Org::findOrFail($orgId);

        if ($org->isBlocked()) {
            return $this->error(__('super_admin.org_already_blocked'), 400);
        }

        $org->suspend($request->reason, Auth::id());

        $this->logAction('org_suspended', 'organization', $orgId, $org->name, [
            'reason' => $request->reason,
        ]);

        if ($request->expectsJson()) {
            return $this->success($org->fresh(), __('super_admin.org_suspended'));
        }

        return redirect()->back()->with('success', __('super_admin.org_suspended'));
    }

    /**
     * Block an organization.
     */
    public function block(Request $request, string $orgId)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $org = Org::findOrFail($orgId);

        $org->block($request->reason, Auth::id());

        $this->logAction('org_blocked', 'organization', $orgId, $org->name, [
            'reason' => $request->reason,
        ]);

        if ($request->expectsJson()) {
            return $this->success($org->fresh(), __('super_admin.org_blocked'));
        }

        return redirect()->back()->with('success', __('super_admin.org_blocked'));
    }

    /**
     * Restore an organization (unsuspend/unblock).
     */
    public function restore(Request $request, string $orgId)
    {
        $org = Org::findOrFail($orgId);

        if ($org->isActive()) {
            return $this->error(__('super_admin.org_already_active'), 400);
        }

        $previousStatus = $org->status;
        $org->activate();

        $this->logAction('org_restored', 'organization', $orgId, $org->name, [
            'previous_status' => $previousStatus,
        ]);

        if ($request->expectsJson()) {
            return $this->success($org->fresh(), __('super_admin.org_restored'));
        }

        return redirect()->back()->with('success', __('super_admin.org_restored'));
    }

    /**
     * Perform bulk actions on organizations.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:suspend,block,restore,delete',
            'org_ids' => 'required|array|min:1',
            'org_ids.*' => 'uuid',
            'reason' => 'required_if:action,suspend,block|string|max:1000',
        ]);

        $action = $request->action;
        $orgIds = $request->org_ids;
        $reason = $request->reason;
        $processedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($orgIds as $orgId) {
                $org = Org::find($orgId);
                if (!$org) continue;

                switch ($action) {
                    case 'suspend':
                        if (!$org->isSuspended()) {
                            $org->suspend($reason, Auth::id());
                            $processedCount++;
                        }
                        break;

                    case 'block':
                        $org->block($reason, Auth::id());
                        $processedCount++;
                        break;

                    case 'restore':
                        if ($org->isRestricted()) {
                            $org->activate();
                            $processedCount++;
                        }
                        break;

                    case 'delete':
                        $org->delete(); // Soft delete
                        $processedCount++;
                        break;
                }
            }

            DB::commit();

            $this->logAction("bulk_{$action}", 'organization', null, null, [
                'org_ids' => $orgIds,
                'processed_count' => $processedCount,
                'reason' => $reason,
            ]);

            return $this->success([
                'processed_count' => $processedCount,
            ], __('super_admin.bulk_action_completed', ['count' => $processedCount]));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk org action failed', ['error' => $e->getMessage()]);
            return $this->serverError(__('super_admin.bulk_action_failed'));
        }
    }

    /**
     * Change organization subscription plan.
     */
    public function changePlan(Request $request, string $orgId)
    {
        $request->validate([
            'plan_id' => 'required|uuid|exists:cmis.plans,plan_id',
        ]);

        $org = Org::findOrFail($orgId);
        $subscription = $org->subscription;

        if (!$subscription) {
            // Create new subscription
            Subscription::create([
                'org_id' => $orgId,
                'plan_id' => $request->plan_id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
            ]);
        } else {
            $subscription->changePlan($request->plan_id);
        }

        $this->logAction('plan_changed', 'organization', $orgId, $org->name, [
            'new_plan_id' => $request->plan_id,
        ]);

        return $this->success($org->fresh()->load('subscription.plan'), __('super_admin.plan_changed'));
    }

    /**
     * Log super admin action.
     */
    protected function logAction(string $actionType, string $targetType, ?string $targetId, ?string $targetName, array $details = []): void
    {
        try {
            DB::table('cmis.super_admin_actions')->insert([
                'action_id' => \Illuminate\Support\Str::uuid(),
                'admin_user_id' => Auth::id(),
                'action_type' => $actionType,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'target_name' => $targetName,
                'details' => json_encode($details),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log super admin action', ['error' => $e->getMessage()]);
        }
    }
}
