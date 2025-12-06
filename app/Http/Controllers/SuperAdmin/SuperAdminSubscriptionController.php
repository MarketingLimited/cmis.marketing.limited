<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Core\Org;
use App\Models\Subscription\Plan;
use App\Models\Subscription\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Subscription Controller
 *
 * Manages all subscriptions on the platform.
 */
class SuperAdminSubscriptionController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display a listing of all subscriptions.
     */
    public function index(Request $request)
    {
        $query = Subscription::query()
            ->with(['organization', 'plan']);

        // Search filter (org name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('organization', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'trial':
                    $query->onTrial();
                    break;
                case 'cancelled':
                    $query->cancelled();
                    break;
                case 'expired':
                    $query->expired();
                    break;
            }
        }

        // Plan filter
        if ($request->filled('plan_id')) {
            $query->forPlan($request->plan_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $subscriptions = $query->paginate($request->get('per_page', 20));

        // Get plans for filter dropdown
        $plans = Plan::active()->ordered()->get(['plan_id', 'name', 'code']);

        if ($request->expectsJson()) {
            return $this->paginated($subscriptions);
        }

        return view('super-admin.subscriptions.index', compact('subscriptions', 'plans'));
    }

    /**
     * Display the specified subscription.
     */
    public function show(Request $request, string $subscriptionId)
    {
        $subscription = Subscription::with(['organization', 'plan'])
            ->findOrFail($subscriptionId);

        if ($request->expectsJson()) {
            return $this->success($subscription);
        }

        return view('super-admin.subscriptions.show', compact('subscription'));
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Request $request, string $subscriptionId)
    {
        $request->validate([
            'plan_id' => 'required|uuid|exists:cmis.plans,plan_id',
        ]);

        $subscription = Subscription::findOrFail($subscriptionId);
        $oldPlanId = $subscription->plan_id;

        $subscription->changePlan($request->plan_id);

        $this->logAction('subscription_plan_changed', 'subscription', $subscriptionId, null, [
            'old_plan_id' => $oldPlanId,
            'new_plan_id' => $request->plan_id,
            'org_id' => $subscription->org_id,
        ]);

        return $this->success(
            $subscription->fresh()->load('plan'),
            __('super_admin.subscription_plan_changed')
        );
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Request $request, string $subscriptionId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription = Subscription::findOrFail($subscriptionId);

        if ($subscription->isCancelled()) {
            return $this->error(__('super_admin.subscription_already_cancelled'), 400);
        }

        $subscription->cancel($request->reason);

        $this->logAction('subscription_cancelled', 'subscription', $subscriptionId, null, [
            'reason' => $request->reason,
            'org_id' => $subscription->org_id,
        ]);

        return $this->success($subscription->fresh(), __('super_admin.subscription_cancelled'));
    }

    /**
     * Reactivate a cancelled subscription.
     */
    public function reactivate(Request $request, string $subscriptionId)
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        if (!$subscription->isCancelled()) {
            return $this->error(__('super_admin.subscription_not_cancelled'), 400);
        }

        $subscription->reactivate();

        $this->logAction('subscription_reactivated', 'subscription', $subscriptionId, null, [
            'org_id' => $subscription->org_id,
        ]);

        return $this->success($subscription->fresh(), __('super_admin.subscription_reactivated'));
    }

    /**
     * Extend trial period.
     */
    public function extendTrial(Request $request, string $subscriptionId)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->extendTrial($request->days);

        $this->logAction('subscription_trial_extended', 'subscription', $subscriptionId, null, [
            'days_added' => $request->days,
            'new_trial_ends_at' => $subscription->fresh()->trial_ends_at,
            'org_id' => $subscription->org_id,
        ]);

        return $this->success($subscription->fresh(), __('super_admin.trial_extended', ['days' => $request->days]));
    }

    /**
     * Create a new subscription for an organization.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'plan_id' => 'required|uuid|exists:cmis.plans,plan_id',
            'status' => 'required|in:active,trial',
            'trial_days' => 'required_if:status,trial|integer|min:1|max:365',
        ]);

        // Check if org already has an active subscription
        $existingSubscription = Subscription::where('org_id', $validated['org_id'])
            ->valid()
            ->first();

        if ($existingSubscription) {
            return $this->error(__('super_admin.org_has_active_subscription'), 400);
        }

        $subscription = Subscription::create([
            'org_id' => $validated['org_id'],
            'plan_id' => $validated['plan_id'],
            'status' => $validated['status'],
            'starts_at' => now(),
            'trial_ends_at' => $validated['status'] === 'trial'
                ? now()->addDays($validated['trial_days'])
                : null,
        ]);

        $this->logAction('subscription_created', 'subscription', $subscription->subscription_id, null, [
            'org_id' => $validated['org_id'],
            'plan_id' => $validated['plan_id'],
            'status' => $validated['status'],
        ]);

        return $this->created(
            $subscription->load(['organization', 'plan']),
            __('super_admin.subscription_created')
        );
    }

    /**
     * Get subscription statistics.
     */
    public function stats(Request $request)
    {
        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::active()->count(),
            'trial' => Subscription::onTrial()->count(),
            'cancelled' => Subscription::cancelled()->count(),
            'expired' => Subscription::expired()->count(),
        ];

        // Subscriptions by plan
        $byPlan = DB::table('cmis.subscriptions')
            ->join('cmis.plans', 'cmis.subscriptions.plan_id', '=', 'cmis.plans.plan_id')
            ->select('cmis.plans.name', 'cmis.plans.code', DB::raw('count(*) as count'))
            ->groupBy('cmis.plans.plan_id', 'cmis.plans.name', 'cmis.plans.code')
            ->orderBy('count', 'desc')
            ->get();

        // Recent activity (last 30 days)
        $recentActivity = [
            'new_subscriptions' => Subscription::where('created_at', '>=', now()->subDays(30))->count(),
            'cancellations' => Subscription::whereNotNull('cancelled_at')
                ->where('cancelled_at', '>=', now()->subDays(30))
                ->count(),
        ];

        return $this->success([
            'stats' => $stats,
            'by_plan' => $byPlan,
            'recent_activity' => $recentActivity,
        ]);
    }

}
