<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;

class CreativeAssetController extends Controller
{
    public function index()
    {
        $assets = CreativeAsset::query()
            ->with(['org:org_id,name', 'campaign:campaign_id,name'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $searchable = $assets->map(fn ($asset) => [
            'name' => $asset->variation_tag ?? 'أصل بدون وسم',
            'status' => $asset->status ?? 'غير محدد',
            'created_at' => optional($asset->created_at)->format('Y-m-d'),
            'type' => data_get($asset->used_fields, 'asset_type'),
        ]);

        return view('creative-assets.index', [
            'assets' => $assets,
            'searchableAssets' => $searchable,
        ]);
    }
}