<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Org;
use App\Models\Platform\PlatformApiCall;
use App\Models\Subscription\Plan;
use App\Models\Subscription\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Super Admin Dashboard Controller
 *
 * Provides the main dashboard for super admins with platform-wide statistics.
 */
class SuperAdminController extends Controller
{
    use ApiResponse;

    /**
     * Display the super admin dashboard.
     */
    public function dashboard(Request $request)
    {
        $stats = $this->getDashboardStats();
        $recentActivity = $this->getRecentActivity();
        $apiUsage = $this->getApiUsageSummary();
        $subscriptionsByPlan = $this->getSubscriptionsByPlan();

        if ($request->expectsJson()) {
            return $this->success([
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'api_usage' => $apiUsage,
                'subscriptions_by_plan' => $subscriptionsByPlan,
            ]);
        }

        return view('super-admin.dashboard', compact('stats', 'recentActivity', 'apiUsage', 'subscriptionsByPlan'));
    }

    /**
     * Get dashboard statistics.
     */
    protected function getDashboardStats(): array
    {
        return [
            'total_organizations' => Org::count(),
            'active_organizations' => Org::active()->count(),
            'suspended_organizations' => Org::suspended()->count(),
            'blocked_organizations' => Org::blocked()->count(),
            'new_organizations_today' => Org::whereDate('created_at', now()->toDateString())->count(),

            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'suspended_users' => User::suspended()->count(),
            'blocked_users' => User::blocked()->count(),
            'super_admins' => User::superAdmins()->count(),
            'new_users_today' => User::whereDate('created_at', now()->toDateString())->count(),

            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::active()->count(),
            'trial_subscriptions' => Subscription::onTrial()->count(),
            'cancelled_subscriptions' => Subscription::cancelled()->count(),

            'total_plans' => Plan::count(),
            'active_plans' => Plan::active()->count(),

            'api_calls_today' => PlatformApiCall::today()->count(),
            'api_calls_this_hour' => PlatformApiCall::thisHour()->count(),
            'api_error_rate' => PlatformApiCall::getErrorRate(null, null, 24),
        ];
    }

    /**
     * Get recent activity for the dashboard.
     * Only shows REAL admin actions from super_admin_actions table - no mock/synthetic data.
     */
    protected function getRecentActivity(): array
    {
        // Get REAL admin actions from super_admin_actions table only
        $adminActions = DB::table('cmis.super_admin_actions')
            ->leftJoin('cmis.users', 'super_admin_actions.admin_user_id', '=', 'users.user_id')
            ->select([
                'super_admin_actions.action_id',
                'super_admin_actions.action_type',
                'super_admin_actions.target_type',
                'super_admin_actions.target_id',
                'super_admin_actions.target_name',
                'super_admin_actions.details',
                'super_admin_actions.ip_address',
                'super_admin_actions.created_at',
                'users.name as admin_name',
            ])
            ->orderBy('super_admin_actions.created_at', 'desc')
            ->limit(10)
            ->get();

        // Format the activities
        $activities = $adminActions->map(function ($action) {
            $createdAt = $action->created_at;
            try {
                $createdAt = \Carbon\Carbon::parse($action->created_at)->diffForHumans();
            } catch (\Exception $e) {
                // Keep original if parsing fails
            }

            return [
                'action_id' => $action->action_id,
                'action_type' => $action->action_type,
                'admin_name' => $action->admin_name ?: 'System',
                'target_name' => $action->target_name,
                'target_type' => $action->target_type,
                'ip_address' => $action->ip_address,
                'created_at' => $createdAt,
            ];
        })->toArray();

        // Get recent organizations for the "Recent Organizations" section
        $recentOrgs = Org::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['org_id', 'name', 'status', 'created_at']);

        return [
            'activities' => $activities,
            'recent_organizations' => $recentOrgs,
        ];
    }

    /**
     * Get API usage summary for the dashboard.
     */
    protected function getApiUsageSummary(): array
    {
        return [
            'by_platform' => PlatformApiCall::getRequestCountByPlatform(null, now()->subHours(24), now()),
            'hourly_stats' => PlatformApiCall::getHourlyStats(null, null, 24),
            'average_response_time' => PlatformApiCall::getAverageResponseTime(),
            'error_rate_24h' => PlatformApiCall::getErrorRate(null, null, 24),
        ];
    }

    /**
     * Get subscriptions grouped by plan with percentages.
     */
    protected function getSubscriptionsByPlan(): array
    {
        $totalActive = Subscription::active()->count();

        $subscriptionsByPlan = DB::table('cmis.subscriptions')
            ->join('cmis.plans', 'subscriptions.plan_id', '=', 'plans.plan_id')
            ->select(
                'plans.plan_id',
                'plans.name',
                'plans.code',
                DB::raw('COUNT(*) as count')
            )
            ->whereNull('subscriptions.cancelled_at')
            ->groupBy('plans.plan_id', 'plans.name', 'plans.code')
            ->orderByRaw("CASE
                WHEN plans.code = 'free' THEN 1
                WHEN plans.code = 'starter' THEN 2
                WHEN plans.code = 'professional' THEN 3
                WHEN plans.code = 'enterprise' THEN 4
                ELSE 5
            END")
            ->get();

        return $subscriptionsByPlan->map(function ($plan) use ($totalActive) {
            return [
                'plan_id' => $plan->plan_id,
                'name' => $plan->name,
                'code' => $plan->code,
                'count' => $plan->count,
                'percentage' => $totalActive > 0 ? round(($plan->count / $totalActive) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Get quick stats for AJAX refresh.
     */
    public function quickStats()
    {
        return $this->success([
            'organizations' => Org::count(),
            'users' => User::count(),
            'api_calls_today' => PlatformApiCall::today()->count(),
            'active_subscriptions' => Subscription::active()->count(),
        ]);
    }
}
