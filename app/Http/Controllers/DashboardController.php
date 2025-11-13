<?php

namespace App\Http\Controllers;

use App\Models\AiGeneratedCampaign;
use App\Models\AiModel;
use App\Models\AiRecommendation;
use App\Models\Campaign;
use App\Models\CreativeAsset;
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

    public function latest()
    {
        // TODO: Implement proper authorization policy
        // $this->authorize('viewAny', Campaign::class);
        $notifications = [
            [ 'message' => 'تم إنشاء حملة جديدة 🎯', 'time' => Carbon::now()->subMinutes(5)->diffForHumans() ],
            [ 'message' => 'انخفاض في أداء إحدى الحملات 📉', 'time' => Carbon::now()->subMinutes(30)->diffForHumans() ],
            [ 'message' => 'تم رفع أصل إبداعي جديد 🎨', 'time' => Carbon::now()->subHours(1)->diffForHumans() ],
            [ 'message' => 'تكامل جديد مع منصة Meta 💡', 'time' => Carbon::now()->subHours(3)->diffForHumans() ],
        ];

        return response()->json($notifications);
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