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
        // Get recent org registrations
        $recentOrgs = Org::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['org_id', 'name', 'status', 'created_at']);

        // Get recent user registrations
        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['user_id', 'name', 'email', 'is_super_admin', 'created_at']);

        // Get recently suspended/blocked items
        $recentSuspensions = Org::where('status', '!=', 'active')
            ->whereNotNull('suspended_at')
            ->orWhereNotNull('blocked_at')
            ->orderByRaw('COALESCE(suspended_at, blocked_at) DESC')
            ->limit(5)
            ->get(['org_id', 'name', 'status', 'suspended_at', 'blocked_at']);

        return [
            'recent_organizations' => $recentOrgs,
            'recent_users' => $recentUsers,
            'recent_suspensions' => $recentSuspensions,
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
