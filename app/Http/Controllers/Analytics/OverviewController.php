<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use App\Models\PerformanceMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class OverviewController extends Controller
{
    public function index()
    {
        Gate::authorize('viewDashboard', auth()->user());

        $stats = Cache::remember('analytics.stats', now()->addMinutes(5), function () {
            return [
                'kpis' => Kpi::count(),
                'metrics' => PerformanceMetric::count(),
            ];
        });

        $latestMetrics = PerformanceMetric::query()
            ->select('metric_id', 'kpi', 'observed', 'target', 'baseline', 'observed_at')
            ->orderByDesc('observed_at')
            ->limit(15)
            ->get();

        $kpis = Kpi::query()
            ->orderBy('kpi')
            ->get();

        return view('analytics.index', [
            'stats' => $stats,
            'latestMetrics' => $latestMetrics,
            'kpis' => $kpis,
        ]);
    }
}
