<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Class DashboardController
 * لوحة التحكم العامة للنظام، تعرض نظرة سريعة على جميع أقسام CMIS مع رسوم بيانية وإشعارات تفاعلية.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'orgs' => DB::table('cmis.orgs')->count(),
            'campaigns' => DB::table('cmis.campaigns')->count(),
            'offerings' => DB::table('cmis.offerings')->count(),
            'kpis' => DB::table('cmis.kpis')->count(),
            'creative_assets' => DB::table('cmis.creative_assets')->count(),
        ];

        $campaignStatus = DB::table('cmis.campaigns')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $campaignsByOrg = DB::table('cmis.campaigns')
            ->join('cmis.orgs', 'cmis.campaigns.org_id', '=', 'cmis.orgs.org_id')
            ->select('cmis.orgs.name as org_name', DB::raw('COUNT(cmis.campaigns.campaign_id) as total'))
            ->groupBy('cmis.orgs.name')
            ->get();

        return view('dashboard', [
            'stats' => $stats,
            'campaignStatus' => $campaignStatus,
            'campaignsByOrg' => $campaignsByOrg
        ]);
    }

    public function data()
    {
        // البيانات العامة
        $stats = [
            'orgs' => DB::table('cmis.orgs')->count(),
            'campaigns' => DB::table('cmis.campaigns')->count(),
            'offerings' => DB::table('cmis.offerings')->count(),
            'kpis' => DB::table('cmis.kpis')->count(),
            'creative_assets' => DB::table('cmis.creative_assets')->count(),
        ];

        // الحملات حسب الحالة والمؤسسة
        $campaignStatus = DB::table('cmis.campaigns')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $campaignsByOrg = DB::table('cmis.campaigns')
            ->join('cmis.orgs', 'cmis.campaigns.org_id', '=', 'cmis.orgs.org_id')
            ->select('cmis.orgs.name as org_name', DB::raw('COUNT(cmis.campaigns.campaign_id) as total'))
            ->groupBy('cmis.orgs.name')
            ->get();

        // مؤشرات العروض (Offerings)
        $offerings = [
            'products' => DB::table('cmis.offerings')->where('type', 'product')->count(),
            'services' => DB::table('cmis.offerings')->where('type', 'service')->count(),
            'bundles' => DB::table('cmis.offerings')->where('type', 'bundle')->count(),
        ];

        // مؤشرات التحليلات (Analytics)
        $analytics = [
            'kpis' => DB::table('cmis.kpis')->count(),
            'metrics' => DB::table('cmis.performance_metrics')->count(),
        ];

        // مؤشرات الإبداع (Creative)
        $creative = [
            'assets' => DB::table('cmis.creative_assets')->count(),
            'images' => DB::table('cmis.creative_assets')->where('type', 'image')->count(),
            'videos' => DB::table('cmis.creative_assets')->where('type', 'video')->count(),
        ];

        // مؤشرات الذكاء الاصطناعي (AI)
        $ai = [
            'ai_campaigns' => DB::table('cmis.campaigns')->where('is_ai_generated', true)->count(),
            'recommendations' => DB::table('cmis.ai_recommendations')->count() ?? 0,
            'models' => DB::table('cmis.ai_models')->count() ?? 0,
        ];

        return response()->json([
            'stats' => $stats,
            'campaignStatus' => $campaignStatus,
            'campaignsByOrg' => $campaignsByOrg,
            'offerings' => $offerings,
            'analytics' => $analytics,
            'creative' => $creative,
            'ai' => $ai,
        ]);
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
}