<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;
use Illuminate\Support\Facades\Cache;

class OverviewController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('creative.stats', now()->addMinutes(5), function () {
            return [
                'assets' => CreativeAsset::count(),
                'approved' => CreativeAsset::where('status', 'approved')->count(),
                'pending' => CreativeAsset::where('status', 'pending_review')->count(),
            ];
        });

        $recentAssets = CreativeAsset::query()
            ->with(['org:org_id,name', 'campaign:campaign_id,name'])
            ->select('asset_id', 'org_id', 'campaign_id', 'status', 'variation_tag', 'created_at')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $searchableAssets = CreativeAsset::query()
            ->select('asset_id', 'variation_tag', 'status')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return view('creative.index', [
            'stats' => $stats,
            'recentAssets' => $recentAssets,
            'searchableAssets' => $searchableAssets,
        ]);
    }
}
