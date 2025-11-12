<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KpiController extends Controller
{
    public function index(Request $request, string $orgId)
    {
        Gate::authorize('viewReports', auth()->user());

        try {
            $kpis = Kpi::where('org_id', $orgId)
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            return response()->json($kpis);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch KPIs'], 500);
        }
    }

    public function summary(Request $request, string $orgId)
    {
        Gate::authorize('viewPerformance', auth()->user());

        try {
            $summary = [
                'total_campaigns' => \App\Models\Campaign::where('org_id', $orgId)->count(),
                'active_campaigns' => \App\Models\Campaign::where('org_id', $orgId)->where('status', 'active')->count(),
                'total_assets' => \App\Models\CreativeAsset::where('org_id', $orgId)->count(),
                'total_channels' => \App\Models\Channel::where('org_id', $orgId)->count(),
            ];

            return response()->json($summary);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch summary'], 500);
        }
    }

    public function trends(Request $request, string $orgId)
    {
        Gate::authorize('viewInsights', auth()->user());

        try {
            // Placeholder for trends analysis
            $trends = [
                'message' => 'Trends endpoint - to be implemented',
                'org_id' => $orgId
            ];

            return response()->json($trends);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch trends'], 500);
        }
    }
}
