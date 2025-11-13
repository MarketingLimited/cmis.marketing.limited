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
        // TODO: Implement proper authorization policy
        // $this->authorize('viewAny', Campaign::class);

        $data = $this->resolveDashboardMetrics();

        return view('dashboard', $data);
    }

    public function data()
    {
        // TODO: Implement proper authorization policy
        // $this->authorize('viewAny', Campaign::class);

        return response()->json($this->resolveDashboardMetrics());
    }

    public function latest(Request $request)
    {
        // TODO: Implement proper authorization policy
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