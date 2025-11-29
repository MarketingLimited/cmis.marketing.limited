<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use App\Models\PerformanceMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class OverviewController extends Controller
{
    public function index(string $org)
    {
        Gate::authorize('viewDashboard', auth()->user());

        $stats = Cache::remember("analytics.stats.{$org}", now()->addMinutes(5), function () use ($org) {
            return [
                'kpis' => Kpi::where('org_id', $org)->count(),
                'metrics' => PerformanceMetric::where('org_id', $org)->count(),
            ];
        });

        $latestMetrics = PerformanceMetric::query()
            ->where('org_id', $org)
            ->select('metric_id', 'kpi', 'observed', 'target', 'baseline', 'observed_at')
            ->orderByDesc('observed_at')
            ->limit(15)
            ->get();

        $kpis = Kpi::query()
            ->where('org_id', $org)
            ->orderBy('kpi')
            ->get();

        return view('analytics.index', [
            'stats' => $stats,
            'latestMetrics' => $latestMetrics,
            'kpis' => $kpis,
        ]);
    }

    /**
     * Display the analytics reports page.
     * HI-009: Added for proper /analytics/reports route handling
     */
    public function reports(string $org)
    {
        Gate::authorize('viewDashboard', auth()->user());

        return view('analytics.reports', [
            'currentOrg' => $org,
        ]);
    }

    /**
     * Display the analytics metrics page.
     */
    public function metrics(string $org)
    {
        Gate::authorize('viewDashboard', auth()->user());

        return view('analytics.metrics', [
            'currentOrg' => $org,
        ]);
    }
}
