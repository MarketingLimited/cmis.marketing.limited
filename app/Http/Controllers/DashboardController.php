<?php

namespace App\Http\Controllers;

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
    public function index()
    {
        // Anyone authenticated can view dashboard
        // Stub implementation - Proper authorization policy not yet implemented
        // $this->authorize('viewAny', Campaign::class);

        $data = $this->resolveDashboardMetrics();

        return view('dashboard', $data);
    }

    public function data()
    {
        // Stub implementation - Proper authorization policy not yet implemented
        // $this->authorize('viewAny', Campaign::class);

        return response()->json($this->resolveDashboardMetrics());
    }

    public function latest(Request $request)
    {
        // Stub implementation - Proper authorization policy not yet implemented
        // $this->authorize('viewAny', Campaign::class);

        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['notifications' => []], 401);
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

            return response()->json(['notifications' => $notifications]);
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

            return response()->json(['notifications' => $notifications]);
        }
    }

    public function markAsRead(Request $request, $notificationId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $notification = Notification::where('notification_id', $notificationId)
                ->where('user_id', $user->user_id)
                ->first();

            if (!$notification) {
                return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
            }

            $notification->markAsRead();

            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            \Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to mark as read'], 500);
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
            return response()->json(['error' => 'No active organization found'], 404);
        }

        $data = [
            'campaigns' => $this->getCampaignsData($orgId),
            'analytics' => $this->getAnalyticsData($orgId),
            'recent_activity' => $this->getRecentActivity($orgId),
        ];

        return response()->json(['data' => $data]);
    }

    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
        }

        $stats = [
            'total_campaigns' => Campaign::where('org_id', $orgId)->count(),
            'active_campaigns' => Campaign::where('org_id', $orgId)->where('status', 'active')->count(),
            'total_content' => DB::table('cmis.content_items')->where('org_id', $orgId)->count(),
            'total_assets' => CreativeAsset::where('org_id', $orgId)->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Get recent activity
     */
    public function recentActivity(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
        }

        $activities = $this->getRecentActivity($orgId);

        return response()->json(['data' => $activities]);
    }

    /**
     * Get campaigns summary
     */
    public function campaignsSummary(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
        }

        $summary = [
            'total' => Campaign::where('org_id', $orgId)->count(),
            'active' => Campaign::where('org_id', $orgId)->where('status', 'active')->count(),
            'completed' => Campaign::where('org_id', $orgId)->where('status', 'completed')->count(),
            'draft' => Campaign::where('org_id', $orgId)->where('status', 'draft')->count(),
        ];

        return response()->json(['data' => $summary]);
    }

    /**
     * Get analytics overview
     */
    public function analyticsOverview(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
        }

        $overview = [
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'ctr' => 0,
        ];

        return response()->json(['data' => $overview]);
    }

    /**
     * Get upcoming social media posts
     */
    public function upcomingPosts(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
        }

        $posts = DB::table('cmis.scheduled_social_posts')
            ->where('org_id', $orgId)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        return response()->json(['data' => $posts]);
    }

    /**
     * Get campaigns performance chart data
     */
    public function campaignsPerformance(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
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

        return response()->json(['data' => $performance]);
    }

    /**
     * Get engagement chart data
     */
    public function engagement(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
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

        return response()->json(['data' => $engagement]);
    }

    /**
     * Get top performing campaigns
     */
    public function topCampaigns(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
        }

        $campaigns = Campaign::where('org_id', $orgId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['campaign_id', 'name', 'status', 'budget', 'start_date', 'end_date']);

        return response()->json(['data' => $campaigns]);
    }

    /**
     * Get budget summary
     */
    public function budgetSummary(Request $request)
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return response()->json(['error' => 'No active organization found'], 404);
        }

        $totalBudget = Campaign::where('org_id', $orgId)
            ->sum('budget') ?? 0;

        $summary = [
            'total_budget' => $totalBudget,
            'spent' => 0,
            'remaining' => $totalBudget,
            'allocated' => $totalBudget,
        ];

        return response()->json(['data' => $summary]);
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
     * Get campaigns data for organization
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

    protected function resolveDashboardMetrics(): array
    {
        return Cache::remember('dashboard.metrics', now()->addMinutes(5), function () {
            // Safely count records with error handling
            $stats = [
                'orgs' => $this->safeCount(fn() => Org::count()),
                'campaigns' => $this->safeCount(fn() => Campaign::count()),
                'offerings' => 0, // Table doesn't exist yet
                'kpis' => $this->safeCount(fn() => DB::table('cmis.kpis')->count()),
                'creative_assets' => $this->safeCount(fn() => CreativeAsset::count()),
            ];

            $campaignStatus = $this->safeTry(function() {
                return Campaign::query()
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');
            }, collect());

            $campaignsByOrg = $this->safeTry(function() {
                return Campaign::query()
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
                'kpis' => $this->safeCount(fn() => DB::table('cmis.kpis')->count()),
                'metrics' => 0, // PerformanceMetric table may not exist
            ];

            $creative = [
                'assets' => $this->safeCount(fn() => CreativeAsset::count()),
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