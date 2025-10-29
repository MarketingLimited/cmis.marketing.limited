<?php

namespace App\Http\Controllers\Offerings;

use App\Http\Controllers\Controller;
use App\Models\Offering;
use Illuminate\Support\Facades\Cache;

class OverviewController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('offerings.stats', now()->addMinutes(5), function () {
            return [
                'products' => Offering::where('kind', 'product')->count(),
                'services' => Offering::where('kind', 'service')->count(),
                'bundles' => Offering::where('kind', 'bundle')->count(),
            ];
        });

        $recentOfferings = Offering::query()
            ->with('org:org_id,name')
            ->select('offering_id', 'name', 'kind', 'org_id', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $searchableOfferings = Offering::query()
            ->select('offering_id', 'name', 'kind')
            ->orderBy('name')
            ->limit(250)
            ->get();

        return view('offerings.index', [
            'stats' => $stats,
            'recentOfferings' => $recentOfferings,
            'searchableOfferings' => $searchableOfferings,
        ]);
    }
}
