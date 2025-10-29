<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::query()
            ->select(['campaign_id', 'name', 'status', 'budget', 'start_date', 'end_date', 'updated_at'])
            ->orderByDesc('updated_at')
            ->with('org:org_id,name')
            ->get();

        return view('campaigns', compact('campaigns'));
    }

    public function show($campaign_id)
    {
        $campaign = Campaign::query()
            ->with([
                'org:org_id,name',
                'offerings:offering_id,name,kind',
                'performanceMetrics' => fn ($query) => $query
                    ->orderByDesc('collected_at')
                    ->limit(20)
                    ->select('dashboard_id', 'campaign_id', 'metric_name', 'metric_value', 'metric_target', 'variance', 'confidence_level', 'collected_at', 'insights'),
            ])
            ->findOrFail($campaign_id);

        $performance = $campaign->performanceMetrics->map(fn ($metric) => [
            'metric_name' => $metric->metric_name,
            'metric_value' => $metric->metric_value,
            'metric_target' => $metric->metric_target,
            'variance' => $metric->variance,
            'confidence_level' => $metric->confidence_level,
            'collected_at' => $metric->collected_at,
            'insights' => $metric->insights,
        ]);

        return view('campaigns.show', [
            'campaign' => $campaign,
            'offerings' => $campaign->offerings,
            'performance' => $performance,
        ]);
    }

    public function performanceByRange($campaign_id, $range)
    {
        $campaign = Campaign::findOrFail($campaign_id);

        $cutoff = match ($range) {
            'daily' => Carbon::now()->subDay(),
            'weekly' => Carbon::now()->subDays(7),
            'monthly' => Carbon::now()->subDays(30),
            'yearly' => Carbon::now()->subDays(365),
            default => Carbon::now()->subDays(30),
        };

        $metrics = $campaign->performanceMetrics()
            ->select('metric_name', DB::raw('AVG(metric_value) as value'))
            ->when($cutoff, fn ($query) => $query->where('collected_at', '>=', $cutoff))
            ->groupBy('metric_name')
            ->orderBy('metric_name')
            ->get()
            ->map(fn ($metric) => [
                'metric_name' => $metric->metric_name,
                'value' => (float) $metric->value,
            ]);

        return response()->json($metrics);
    }
}