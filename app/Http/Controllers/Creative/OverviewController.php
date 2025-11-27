<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;
use Illuminate\Support\Facades\Cache;

class OverviewController extends Controller
{
    public function index(string $org)
    {
        // Auth handled by middleware - RLS handles org isolation

        $stats = Cache::remember("creative.stats.{$org}", now()->addMinutes(5), function () use ($org) {
            return [
                'assets' => CreativeAsset::where('org_id', $org)->count(),
                'approved' => CreativeAsset::where('org_id', $org)->where('status', 'approved')->count(),
                'pending' => CreativeAsset::where('org_id', $org)->where('status', 'pending_review')->count(),
            ];
        });

        $recentAssets = CreativeAsset::query()
            ->where('org_id', $org)
            ->with(['org:org_id,name', 'campaign:campaign_id,name'])
            ->select('asset_id', 'org_id', 'campaign_id', 'status', 'variation_tag', 'created_at')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $searchableAssets = CreativeAsset::query()
            ->where('org_id', $org)
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
