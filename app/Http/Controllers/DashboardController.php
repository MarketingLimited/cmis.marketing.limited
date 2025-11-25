<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\AiGeneratedCampaign;
use App\Models\AiModel;
use App\Models\AiRecommendation;
use App\Models\Campaign;
use App\Models\CreativeAsset;
use App\Models\Notification;
use App\Models\Offering;
use App\Models\Core\Org;
use App\Models\PerformanceMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class DashboardController
 * لوحة التحكم العامة للنظام، تعرض نظرة سريعة على جميع أقسام CMIS مع رسوم بيانية وإشعارات تفاعلية.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function index(string $org)
    {
        // Anyone authenticated can view dashboard
        // Stub implementation - Proper authorization policy not yet implemented
        // $this->authorize('viewAny', Campaign::class);

        $data = $this->resolveDashboardMetrics($org);
        $data['currentOrg'] = Org::where('org_id', $org)->first();

        return view('dashboard', $data);
    }

    public function data(string $org)
    {
        // Stub implementation - Proper authorization policy not yet implemented
        // $this->authorize('viewAny', Campaign::class);

        return $this->success($this->resolveDashboardMetrics($org), 'Dashboard metrics retrieved successfully');
    }

    public function latest(Request $request, string $org)
    {
        // Stub implementation - Proper authorization policy not yet implemented
        // $this->authorize('viewAny', Campaign::class);

        try {
            $user = $request->user();

            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            // Get latest notifications for the user (last 20)
            $notifications = Notification::forUser($user->user_id)
                ->recent(20)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->notification_id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'data' => $notification->data,
                        'read' => $notification->read,
                        'time' => $notification->time,
                        'created_at' => $notification->created_at->toISOString(),
                    ];
                });

            return $this->success(['notifications' => $notifications], 'Notifications retrieved successfully');
        } catch (\Exception $e) {
            // Fallback to sample data if database is not ready
            \Log::error('Failed to load notifications: ' . $e->getMessage());

            $notifications = [
                [
                    'id' => 1,
                    'type' => 'campaign',
                    'message' => 'تم إطلاق حملة "عروض الصيف" بنجاح',
                    'time' => 'منذ 5 دقائق',
                    'read' => false
                ],
                [
                    'id' => 2,
                    'type' => 'analytics',
                    'message' => 'تحديث في أداء الحملات - زيادة 15% في التحويلات',
                    'time' => 'منذ ساعة',
                    'read' => false
                ],
                [
                    'id' => 3,
                    'type' => 'integration',
                    'message' => 'تم ربط حساب Meta Ads بنجاح',
                    'time' => 'منذ 3 ساعات',
                    'read' => true
                ],
                [
                    'id' => 4,
                    'type' => 'user',
                    'message' => 'تمت إضافة عضو جديد إلى الفريق',
                    'time' => 'منذ يوم',
                    'read' => true
                ],
            ];

            return $this->success(['notifications' => $notifications], 'Fallback notifications retrieved');
        }
    }

    public function markAsRead(Request $request, string $org, $notificationId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            $notification = Notification::where('notification_id', $notificationId)
                ->where('user_id', $user->user_id)
                ->where('org_id', $org)
                ->first();

            if (!$notification) {
                return $this->error('Notification not found', 404);
            }

            $notification->markAsRead();

            return $this->success(null, 'Notification marked as read');
        } catch (\Exception $e) {
            \Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return $this->error('Failed to mark as read', 500);
        }
    }

    /**
     * Get dashboard overview data
     */
    public function overview(Request $request)
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $data = [
            'campaigns' => $this->getCampaignsData($orgId),
            'analytics' => $this->getAnalyticsData($orgId),
            'recent_activity' => $this->getRecentActivity($orgId),
        ];

        return $this->success($data, 'Overview retrieved successfully');
    }

    /**
     * Get dashboard statistics (filtered by org_id)
     */
    public function stats(Request $request, string $org)
    {
        $stats = [
            'total_campaigns' => Campaign::where('org_id', $org)->count(),
            'active_campaigns' => Campaign::where('org_id', $org)->where('status', 'active')->count(),
            'total_content' => DB::table('cmis.content_items')->where('org_id', $org)->count(),
            'total_assets' => CreativeAsset::where('org_id', $org)->count(),
        ];

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get recent activity
     */
    public function recentActivity(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $activities = $this->getRecentActivity($orgId);

        return $this->success($activities, 'Recent activity retrieved successfully');
    }

    /**
     * Get campaigns summary (filtered by org_id)
     */
    public function campaignsSummary(Request $request, string $org)
    {
        $summary = [
            'total' => Campaign::where('org_id', $org)->count(),
            'active' => Campaign::where('org_id', $org)->where('status', 'active')->count(),
            'completed' => Campaign::where('org_id', $org)->where('status', 'completed')->count(),
            'draft' => Campaign::where('org_id', $org)->where('status', 'draft')->count(),
        ];

        return $this->success($summary, 'Campaigns summary retrieved successfully');
    }

    /**
     * Get analytics overview
     */
    public function analyticsOverview(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $overview = [
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'ctr' => 0,
        ];

        return $this->success($overview, 'Analytics overview retrieved successfully');
    }

    /**
     * Get upcoming social media posts (filtered by org_id)
     */
    public function upcomingPosts(Request $request, string $org)
    {
        $posts = DB::table('cmis.scheduled_social_posts')
            ->where('org_id', $org)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        return $this->success($posts, 'Upcoming posts retrieved successfully');
    }

    /**
     * Get campaigns performance chart data
     */
    public function campaignsPerformance(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $performance = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Active Campaigns',
                    'data' => [5, 8, 12, 10, 15, 18],
                ],
            ],
        ];

        return $this->success($performance, 'Campaigns performance retrieved successfully');
    }

    /**
     * Get engagement chart data
     */
    public function engagement(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $engagement = [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'datasets' => [
                [
                    'label' => 'Engagement',
                    'data' => [120, 150, 180, 170, 200, 190, 160],
                ],
            ],
        ];

        return $this->success($engagement, 'Engagement data retrieved successfully');
    }

    /**
     * Get top performing campaigns (filtered by org_id)
     */
    public function topCampaigns(Request $request, string $org)
    {
        $campaigns = Campaign::where('org_id', $org)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['campaign_id', 'name', 'status', 'budget', 'start_date', 'end_date']);

        return $this->success($campaigns, 'Top campaigns retrieved successfully');
    }

    /**
     * Get budget summary (filtered by org_id)
     */
    public function budgetSummary(Request $request, string $org)
    {
        $totalBudget = Campaign::where('org_id', $org)->sum('budget') ?? 0;

        $summary = [
            'total_budget' => $totalBudget,
            'spent' => 0,
            'remaining' => $totalBudget,
            'allocated' => $totalBudget,
        ];

        return $this->success($summary, 'Budget summary retrieved successfully');
    }

    /**
     * Resolve organization ID from request
     */
    private function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        // Try to get from route parameter first
        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        // Fall back to user's active org
        // First try active_org_id property, then query the pivot table
        if ($user->active_org_id) {
            return $user->active_org_id;
        }

        // Query the user_orgs pivot table for an active org
        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }

    /**
     * Get campaigns data (filtered by org_id)
     */
    private function getCampaignsData(string $orgId): array
    {
        return [
            'total' => Campaign::where('org_id', $orgId)->count(),
            'active' => Campaign::where('org_id', $orgId)->where('status', 'active')->count(),
            'recent' => Campaign::where('org_id', $orgId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['campaign_id', 'name', 'status', 'created_at']),
        ];
    }

    /**
     * Get analytics data for organization
     */
    private function getAnalyticsData(string $orgId): array
    {
        return [
            'total_impressions' => 0,
            'total_clicks' => 0,
            'avg_ctr' => 0,
        ];
    }

    /**
     * Get recent activity for organization
     */
    private function getRecentActivity(string $orgId): array
    {
        // This would typically query an activity log table
        return [];
    }

    protected function resolveDashboardMetrics(string $orgId): array
    {
        return Cache::remember("dashboard.metrics.{$orgId}", now()->addMinutes(5), function () use ($orgId) {
            // Safely count records with error handling - filtered by org_id
            $stats = [
                'orgs' => 1, // Current org
                'campaigns' => $this->safeCount(fn() => Campaign::where('org_id', $orgId)->count()),
                'offerings' => 0, // Table doesn't exist yet
                'kpis' => $this->safeCount(fn() => DB::table('cmis.kpis')->where('org_id', $orgId)->count()),
                'creative_assets' => $this->safeCount(fn() => CreativeAsset::where('org_id', $orgId)->count()),
            ];

            $campaignStatus = $this->safeTry(function() use ($orgId) {
                return Campaign::where('org_id', $orgId)
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');
            }, collect());

            $campaignsByOrg = $this->safeTry(function() use ($orgId) {
                return Campaign::where('cmis.campaigns.org_id', $orgId)
                    ->join('cmis.orgs as o', 'cmis.campaigns.org_id', '=', 'o.org_id')
                    ->select('o.name as org_name', DB::raw('COUNT(cmis.campaigns.campaign_id) as total'))
                    ->groupBy('o.name')
                    ->orderBy('o.name')
                    ->get();
            }, collect());

            $offerings = [
                'products' => 0,
                'services' => 0,
                'bundles' => 0,
            ];

            $analytics = [
                'kpis' => $this->safeCount(fn() => DB::table('cmis.kpis')->where('org_id', $orgId)->count()),
                'metrics' => 0, // PerformanceMetric table may not exist
            ];

            $creative = [
                'assets' => $this->safeCount(fn() => CreativeAsset::where('org_id', $orgId)->count()),
                'images' => 0,
                'videos' => 0,
            ];

            $ai = [
                'ai_campaigns' => 0,
                'recommendations' => 0,
                'models' => 0,
            ];

            return compact('stats', 'campaignStatus', 'campaignsByOrg', 'offerings', 'analytics', 'creative', 'ai');
        });
    }

    /**
     * Safely execute a count query with error handling
     */
    private function safeCount(callable $callback): int
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Safely execute a query with error handling
     */
    private function safeTry(callable $callback, $default)
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            return $default;
        }
    }
}