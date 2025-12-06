<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Core\PlanApp;
use App\Models\Marketplace\MarketplaceApp;
use App\Models\Subscription\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin App Controller
 *
 * Manages marketplace apps and their plan access assignments.
 */
class SuperAdminAppController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display a listing of all apps with plan matrix.
     */
    public function index(Request $request)
    {
        $apps = MarketplaceApp::query()
            ->with(['plans'])
            ->active()
            ->ordered()
            ->get();

        $plans = Plan::active()->ordered()->get();

        // Build plan-app matrix
        $planAppMatrix = [];
        foreach ($plans as $plan) {
            $planAppMatrix[$plan->plan_id] = $plan->apps()
                ->wherePivot('is_enabled', true)
                ->pluck('cmis.marketplace_apps.app_id')
                ->toArray();
        }

        // Get category breakdown
        $categories = $apps->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category,
                'count' => $items->count(),
                'apps' => $items,
            ];
        })->values();

        // Get stats
        $stats = [
            'total_apps' => $apps->count(),
            'core_apps' => $apps->where('is_core', true)->count(),
            'premium_apps' => $apps->where('is_premium', true)->count(),
            'categories' => $categories->count(),
        ];

        if ($request->expectsJson()) {
            return $this->success([
                'apps' => $apps,
                'plans' => $plans,
                'plan_app_matrix' => $planAppMatrix,
                'categories' => $categories,
                'stats' => $stats,
            ]);
        }

        return view('super-admin.apps.index', compact('apps', 'plans', 'planAppMatrix', 'categories', 'stats'));
    }

    /**
     * Display the specified app with plan assignments.
     */
    public function show(Request $request, string $appId)
    {
        $app = MarketplaceApp::with(['plans', 'organizationApps'])
            ->findOrFail($appId);

        $plans = Plan::active()->ordered()->get();

        // Get plan assignments with details
        $planAssignments = $app->plans->mapWithKeys(function ($plan) {
            return [$plan->plan_id => [
                'is_enabled' => $plan->pivot->is_enabled,
                'usage_limit' => $plan->pivot->usage_limit,
                'settings_override' => $plan->pivot->settings_override,
            ]];
        })->toArray();

        // Get usage stats by plan
        $usageByPlan = DB::table('cmis.organization_apps')
            ->join('cmis.subscriptions', 'organization_apps.org_id', '=', 'subscriptions.org_id')
            ->where('organization_apps.app_id', $appId)
            ->where('organization_apps.is_enabled', true)
            ->select('subscriptions.plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('subscriptions.plan_id')
            ->pluck('count', 'plan_id')
            ->toArray();

        // Get total organizations using this app
        $totalOrgsUsingApp = $app->organizationApps()->where('is_enabled', true)->count();

        if ($request->expectsJson()) {
            return $this->success([
                'app' => $app,
                'plans' => $plans,
                'plan_assignments' => $planAssignments,
                'usage_by_plan' => $usageByPlan,
                'total_orgs_using' => $totalOrgsUsingApp,
            ]);
        }

        return view('super-admin.apps.show', compact(
            'app',
            'plans',
            'planAssignments',
            'usageByPlan',
            'totalOrgsUsingApp'
        ));
    }

    /**
     * Update plan assignments for an app.
     */
    public function updatePlanApps(Request $request, string $appId)
    {
        $request->validate([
            'plan_assignments' => 'required|array',
            'plan_assignments.*.plan_id' => 'required|uuid|exists:cmis.plans,plan_id',
            'plan_assignments.*.is_enabled' => 'required|boolean',
            'plan_assignments.*.usage_limit' => 'nullable|integer|min:0',
            'plan_assignments.*.settings_override' => 'nullable|array',
        ]);

        $app = MarketplaceApp::findOrFail($appId);

        DB::beginTransaction();
        try {
            foreach ($request->plan_assignments as $assignment) {
                PlanApp::updateOrCreate(
                    [
                        'plan_id' => $assignment['plan_id'],
                        'app_id' => $appId,
                    ],
                    [
                        'is_enabled' => $assignment['is_enabled'],
                        'usage_limit' => $assignment['usage_limit'] ?? null,
                        'settings_override' => $assignment['settings_override'] ?? [],
                    ]
                );
            }

            DB::commit();

            $this->logAction('app_plan_assignments_updated', 'app', $appId, $app->name_key, [
                'assignments_count' => count($request->plan_assignments),
            ]);

            return $this->success(
                $app->fresh()->load('plans'),
                __('super_admin.app_plan_assignments_updated')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update app plan assignments', [
                'app_id' => $appId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('super_admin.app_plan_update_failed'));
        }
    }

    /**
     * Quick toggle app access for a specific plan.
     */
    public function toggleAppForPlan(Request $request, string $appId, string $planId)
    {
        $app = MarketplaceApp::findOrFail($appId);
        $plan = Plan::findOrFail($planId);

        $planApp = PlanApp::where('plan_id', $planId)
            ->where('app_id', $appId)
            ->first();

        if ($planApp) {
            $planApp->update(['is_enabled' => !$planApp->is_enabled]);
            $isEnabled = $planApp->is_enabled;
        } else {
            PlanApp::create([
                'plan_id' => $planId,
                'app_id' => $appId,
                'is_enabled' => true,
            ]);
            $isEnabled = true;
        }

        $this->logAction(
            $isEnabled ? 'app_enabled_for_plan' : 'app_disabled_for_plan',
            'app',
            $appId,
            $app->name_key,
            ['plan_id' => $planId, 'plan_name' => $plan->name]
        );

        return $this->success([
            'app_id' => $appId,
            'plan_id' => $planId,
            'is_enabled' => $isEnabled,
        ], $isEnabled
            ? __('super_admin.app_enabled_for_plan', ['plan' => $plan->name])
            : __('super_admin.app_disabled_for_plan', ['plan' => $plan->name])
        );
    }

    /**
     * Bulk assign apps to plans.
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'app_ids' => 'required|array|min:1',
            'app_ids.*' => 'uuid|exists:cmis.marketplace_apps,app_id',
            'plan_ids' => 'required|array|min:1',
            'plan_ids.*' => 'uuid|exists:cmis.plans,plan_id',
            'action' => 'required|in:enable,disable',
        ]);

        $appIds = $request->app_ids;
        $planIds = $request->plan_ids;
        $action = $request->action;
        $isEnabled = $action === 'enable';

        DB::beginTransaction();
        try {
            $processedCount = 0;

            foreach ($appIds as $appId) {
                foreach ($planIds as $planId) {
                    PlanApp::updateOrCreate(
                        ['plan_id' => $planId, 'app_id' => $appId],
                        ['is_enabled' => $isEnabled]
                    );
                    $processedCount++;
                }
            }

            DB::commit();

            $this->logAction('bulk_app_plan_assignment', 'app', null, null, [
                'app_ids' => $appIds,
                'plan_ids' => $planIds,
                'action' => $action,
                'processed_count' => $processedCount,
            ]);

            return $this->success([
                'processed_count' => $processedCount,
            ], __('super_admin.bulk_assignment_completed', ['count' => $processedCount]));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk app assignment failed', ['error' => $e->getMessage()]);
            return $this->serverError(__('super_admin.bulk_assignment_failed'));
        }
    }

    /**
     * Get plan-app matrix for quick overview.
     */
    public function matrix(Request $request)
    {
        $apps = MarketplaceApp::active()
            ->ordered()
            ->get(['app_id', 'slug', 'name_key', 'category', 'is_core', 'is_premium']);

        $plans = Plan::active()
            ->ordered()
            ->get(['plan_id', 'name', 'code']);

        // Build matrix
        $matrix = [];
        foreach ($apps as $app) {
            $appRow = [
                'app_id' => $app->app_id,
                'slug' => $app->slug,
                'name' => __($app->name_key),
                'category' => $app->category,
                'is_core' => $app->is_core,
                'is_premium' => $app->is_premium,
                'plans' => [],
            ];

            foreach ($plans as $plan) {
                $planApp = PlanApp::where('app_id', $app->app_id)
                    ->where('plan_id', $plan->plan_id)
                    ->first();

                $appRow['plans'][$plan->code] = [
                    'plan_id' => $plan->plan_id,
                    'is_enabled' => $planApp?->is_enabled ?? false,
                    'usage_limit' => $planApp?->usage_limit,
                ];
            }

            $matrix[] = $appRow;
        }

        if ($request->expectsJson()) {
            return $this->success([
                'matrix' => $matrix,
                'plans' => $plans,
                'apps_count' => count($apps),
            ]);
        }

        return view('super-admin.apps.matrix', compact('matrix', 'plans'));
    }
}
