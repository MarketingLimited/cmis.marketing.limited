<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Subscription\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Plan Controller
 *
 * Manages subscription plans for the platform.
 */
class SuperAdminPlanController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display a listing of all plans.
     */
    public function index(Request $request)
    {
        $query = Plan::query()
            ->withCount('subscriptions')
            ->ordered();

        if ($request->filled('active_only')) {
            $query->active();
        }

        $plans = $query->get();

        if ($request->expectsJson()) {
            return $this->success($plans);
        }

        return view('super-admin.plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create()
    {
        return view('super-admin.plans.create');
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:cmis.plans,code',
            'description' => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'max_users' => 'nullable|integer|min:1',
            'max_orgs' => 'nullable|integer|min:1',
            'max_api_calls_per_month' => 'nullable|integer|min:1',
            'max_storage_gb' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // If setting as default, unset other defaults
        if ($request->boolean('is_default')) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        $plan = Plan::create($validated);

        $this->logAction('plan_created', 'plan', $plan->plan_id, $plan->name, $validated);

        if ($request->expectsJson()) {
            return $this->created($plan, __('super_admin.plan_created'));
        }

        return redirect()->route('super-admin.plans.index')
            ->with('success', __('super_admin.plan_created'));
    }

    /**
     * Display the specified plan.
     */
    public function show(Request $request, string $planId)
    {
        $plan = Plan::withCount('subscriptions')->findOrFail($planId);

        // Get subscription stats by status
        $subscriptionStats = DB::table('cmis.subscriptions')
            ->where('plan_id', $planId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        if ($request->expectsJson()) {
            return $this->success([
                'plan' => $plan,
                'subscription_stats' => $subscriptionStats,
            ]);
        }

        return view('super-admin.plans.show', compact('plan', 'subscriptionStats'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(string $planId)
    {
        $plan = Plan::findOrFail($planId);
        return view('super-admin.plans.edit', compact('plan'));
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, string $planId)
    {
        $plan = Plan::findOrFail($planId);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:cmis.plans,code,' . $planId . ',plan_id',
            'description' => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'max_users' => 'nullable|integer|min:1',
            'max_orgs' => 'nullable|integer|min:1',
            'max_api_calls_per_month' => 'nullable|integer|min:1',
            'max_storage_gb' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // If setting as default, unset other defaults
        if ($request->boolean('is_default') && !$plan->is_default) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        $plan->update($validated);

        $this->logAction('plan_updated', 'plan', $plan->plan_id, $plan->name, $validated);

        if ($request->expectsJson()) {
            return $this->success($plan->fresh(), __('super_admin.plan_updated'));
        }

        return redirect()->route('super-admin.plans.index')
            ->with('success', __('super_admin.plan_updated'));
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(Request $request, string $planId)
    {
        $plan = Plan::withCount('subscriptions')->findOrFail($planId);

        // Cannot delete plan with active subscriptions
        if ($plan->subscriptions_count > 0) {
            return $this->error(__('super_admin.cannot_delete_plan_with_subscriptions'), 400);
        }

        // Cannot delete default plan
        if ($plan->is_default) {
            return $this->error(__('super_admin.cannot_delete_default_plan'), 400);
        }

        $this->logAction('plan_deleted', 'plan', $plan->plan_id, $plan->name, []);

        $plan->delete();

        if ($request->expectsJson()) {
            return $this->deleted(__('super_admin.plan_deleted'));
        }

        return redirect()->route('super-admin.plans.index')
            ->with('success', __('super_admin.plan_deleted'));
    }

    /**
     * Toggle plan active status.
     */
    public function toggleActive(Request $request, string $planId)
    {
        $plan = Plan::findOrFail($planId);

        // Cannot deactivate default plan
        if ($plan->is_default && $plan->is_active) {
            return $this->error(__('super_admin.cannot_deactivate_default_plan'), 400);
        }

        $plan->update(['is_active' => !$plan->is_active]);

        $this->logAction(
            $plan->is_active ? 'plan_activated' : 'plan_deactivated',
            'plan',
            $plan->plan_id,
            $plan->name,
            []
        );

        return $this->success($plan->fresh(), $plan->is_active
            ? __('super_admin.plan_activated')
            : __('super_admin.plan_deactivated')
        );
    }

    /**
     * Set a plan as default.
     */
    public function setDefault(Request $request, string $planId)
    {
        $plan = Plan::findOrFail($planId);

        if (!$plan->is_active) {
            return $this->error(__('super_admin.cannot_set_inactive_plan_default'), 400);
        }

        // Unset current default
        Plan::where('is_default', true)->update(['is_default' => false]);

        // Set new default
        $plan->update(['is_default' => true]);

        $this->logAction('plan_set_default', 'plan', $plan->plan_id, $plan->name, []);

        return $this->success($plan->fresh(), __('super_admin.plan_set_default'));
    }

}
