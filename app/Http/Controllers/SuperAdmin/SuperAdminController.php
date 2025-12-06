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

        if ($request->expectsJson()) {
            return $this->success([
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'api_usage' => $apiUsage,
            ]);
        }

        return view('super-admin.dashboard', compact('stats', 'recentActivity', 'apiUsage'));
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

            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'suspended_users' => User::suspended()->count(),
            'blocked_users' => User::blocked()->count(),
            'super_admins' => User::superAdmins()->count(),

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
     */
    protected function getRecentActivity(): array
    {
        $activities = collect();

        // 1. Get admin actions from super_admin_actions table
        $adminActions = DB::table('cmis.super_admin_actions')
            ->join('cmis.users', 'super_admin_actions.admin_user_id', '=', 'users.user_id')
            ->select([
                'super_admin_actions.action_id',
                'super_admin_actions.action_type',
                'super_admin_actions.target_type',
                'super_admin_actions.target_id',
                'super_admin_actions.target_name',
                'super_admin_actions.created_at',
                'users.name as admin_name',
            ])
            ->orderBy('super_admin_actions.created_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($adminActions as $action) {
            $activities->push([
                'action_id' => $action->action_id,
                'action_type' => $action->action_type,
                'admin_name' => $action->admin_name,
                'target_name' => $action->target_name,
                'target_type' => $action->target_type,
                'created_at' => $action->created_at,
                'sort_date' => $action->created_at,
            ]);
        }

        // 2. Generate activity items from org creations (last 30 days)
        $recentOrgs = Org::where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['org_id', 'name', 'status', 'created_at']);

        foreach ($recentOrgs as $org) {
            $activities->push([
                'action_id' => 'org_' . $org->org_id,
                'action_type' => 'create_organization',
                'admin_name' => 'System',
                'target_name' => $org->name,
                'target_type' => 'organization',
                'created_at' => $org->created_at->toIso8601String(),
                'sort_date' => $org->created_at,
            ]);
        }

        // 3. Generate activity items from user registrations (last 30 days)
        $recentUsers = User::where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['user_id', 'name', 'email', 'created_at']);

        foreach ($recentUsers as $user) {
            $activities->push([
                'action_id' => 'user_' . $user->user_id,
                'action_type' => 'create_user',
                'admin_name' => 'System',
                'target_name' => $user->name ?: $user->email,
                'target_type' => 'user',
                'created_at' => $user->created_at->toIso8601String(),
                'sort_date' => $user->created_at,
            ]);
        }

        // 4. Generate activity items from org suspensions/blocks
        $suspendedOrgs = Org::whereNotNull('suspended_at')
            ->orWhereNotNull('blocked_at')
            ->orderByRaw('GREATEST(COALESCE(suspended_at, \'1970-01-01\'), COALESCE(blocked_at, \'1970-01-01\')) DESC')
            ->limit(10)
            ->get(['org_id', 'name', 'status', 'suspended_at', 'blocked_at', 'suspended_by', 'blocked_by']);

        foreach ($suspendedOrgs as $org) {
            $actionType = $org->blocked_at ? 'block_organization' : 'suspend_organization';
            $actionDate = $org->blocked_at ?: $org->suspended_at;

            if ($actionDate) {
                $activities->push([
                    'action_id' => 'suspension_' . $org->org_id,
                    'action_type' => $actionType,
                    'admin_name' => 'Admin',
                    'target_name' => $org->name,
                    'target_type' => 'organization',
                    'created_at' => $actionDate instanceof \Carbon\Carbon ? $actionDate->toIso8601String() : $actionDate,
                    'sort_date' => $actionDate,
                ]);
            }
        }

        // 5. Generate activity items from user suspensions/blocks
        $suspendedUsers = User::where(function ($q) {
                $q->where('is_suspended', true)->orWhere('is_blocked', true);
            })
            ->whereNotNull('suspended_at')
            ->orWhereNotNull('blocked_at')
            ->orderByRaw('GREATEST(COALESCE(suspended_at, \'1970-01-01\'), COALESCE(blocked_at, \'1970-01-01\')) DESC')
            ->limit(10)
            ->get(['user_id', 'name', 'email', 'is_suspended', 'is_blocked', 'suspended_at', 'blocked_at']);

        foreach ($suspendedUsers as $user) {
            $actionType = $user->is_blocked ? 'block_user' : 'suspend_user';
            $actionDate = $user->blocked_at ?: $user->suspended_at;

            if ($actionDate) {
                $activities->push([
                    'action_id' => 'user_suspension_' . $user->user_id,
                    'action_type' => $actionType,
                    'admin_name' => 'Admin',
                    'target_name' => $user->name ?: $user->email,
                    'target_type' => 'user',
                    'created_at' => $actionDate instanceof \Carbon\Carbon ? $actionDate->toIso8601String() : $actionDate,
                    'sort_date' => $actionDate,
                ]);
            }
        }

        // Sort by date descending and take top 10
        $sortedActivities = $activities->sortByDesc('sort_date')->take(10)->values();

        // Format dates for display
        $formattedActivities = $sortedActivities->map(function ($activity) {
            $date = $activity['created_at'];
            if (is_string($date)) {
                try {
                    $date = \Carbon\Carbon::parse($date);
                } catch (\Exception $e) {
                    $date = now();
                }
            }
            $activity['created_at'] = $date->diffForHumans();
            unset($activity['sort_date']);
            return $activity;
        });

        return [
            'activities' => $formattedActivities->toArray(),
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
