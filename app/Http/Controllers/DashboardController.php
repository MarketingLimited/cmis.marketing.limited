<?php

namespace App\Http\Controllers;

use App\Models\AiGeneratedCampaign;
use App\Models\AiModel;
use App\Models\AiRecommendation;
use App\Models\Campaign;
use App\Models\CreativeAsset;
use App\Models\Offering;
use App\Models\Org;
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
        $data = $this->resolveDashboardMetrics();

        return view('dashboard', $data);
    }

    public function data()
    {
        return response()->json($this->resolveDashboardMetrics());
    }

    public function latest()
    {
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
            $stats = [
                'orgs' => Org::count(),
                'campaigns' => Campaign::count(),
                'offerings' => Offering::count(),
                'kpis' => DB::table('cmis.kpis')->count(),
                'creative_assets' => CreativeAsset::count(),
            ];

            $campaignStatus = Campaign::query()
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');

            $campaignsByOrg = Campaign::query()
                ->join('cmis.orgs as o', 'cmis.campaigns.org_id', '=', 'o.org_id')
                ->select('o.name as org_name', DB::raw('COUNT(cmis.campaigns.campaign_id) as total'))
                ->groupBy('o.name')
                ->orderBy('o.name')
                ->get();

            $offerings = [
                'products' => Offering::where('kind', 'product')->count(),
                'services' => Offering::where('kind', 'service')->count(),
                'bundles' => Offering::where('kind', 'bundle')->count(),
            ];

            $analytics = [
                'kpis' => DB::table('cmis.kpis')->count(),
                'metrics' => PerformanceMetric::count(),
            ];

            $creative = [
                'assets' => CreativeAsset::count(),
                'images' => CreativeAsset::whereNotNull('used_fields')
                    ->whereJsonContains('used_fields->asset_type', 'image')
                    ->count(),
                'videos' => CreativeAsset::whereNotNull('used_fields')
                    ->whereJsonContains('used_fields->asset_type', 'video')
                    ->count(),
            ];

            $ai = [
                'ai_campaigns' => AiGeneratedCampaign::count(),
                'recommendations' => AiRecommendation::count(),
                'models' => AiModel::count(),
            ];

            return compact('stats', 'campaignStatus', 'campaignsByOrg', 'offerings', 'analytics', 'creative', 'ai');
        });
    }
}