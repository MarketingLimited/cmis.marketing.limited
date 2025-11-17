<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\PerformanceMetric;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FrontendAnalyticsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'platform' => 'nullable|string',
        ]);

        try {
            $start = Carbon::parse($request->input('start'))->startOfDay();
            $end = Carbon::parse($request->input('end'))->endOfDay();
            $orgId = $request->user()->org_id;

            $metrics = PerformanceMetric::query()
                ->where('org_id', $orgId)
                ->whereBetween('observed_at', [$start, $end])
                ->get();

            $duration = $start->diffInDays($end) + 1;
            $previousStart = $start->copy()->subDays($duration);
            $previousEnd = $start->copy()->subDay();

            $previousMetrics = PerformanceMetric::query()
                ->where('org_id', $orgId)
                ->whereBetween('observed_at', [$previousStart, $previousEnd])
                ->get();

            $totals = $this->aggregateMetrics($metrics);
            $previousTotals = $this->aggregateMetrics($previousMetrics);

            $response = [
                'kpis' => [
                    'totalSpend' => $totals['spend'],
                    'spendChange' => $this->calculateChange($totals['spend'], $previousTotals['spend']),
                    'impressions' => $totals['impressions'],
                    'impressionsChange' => $this->calculateChange($totals['impressions'], $previousTotals['impressions']),
                    'clicks' => $totals['clicks'],
                    'clicksChange' => $this->calculateChange($totals['clicks'], $previousTotals['clicks']),
                    'conversions' => $totals['conversions'],
                    'conversionsChange' => $this->calculateChange($totals['conversions'], $previousTotals['conversions']),
                    'ctr' => $totals['impressions'] > 0 ? round(($totals['clicks'] / $totals['impressions']) * 100, 2) : 0,
                    'cpc' => $totals['clicks'] > 0 ? round($totals['spend'] / $totals['clicks'], 2) : 0,
                    'roas' => $totals['spend'] > 0 ? round($totals['conversions'] * 100 / $totals['spend'], 2) : 0,
                ],
                'latestMetrics' => $metrics->take(25),
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Failed to build analytics summary', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch analytics summary',
            ], 500);
        }
    }

    public function platformPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        try {
            $start = Carbon::parse($request->input('start'))->startOfDay();
            $end = Carbon::parse($request->input('end'))->endOfDay();
            $orgId = $request->user()->org_id;

            $platforms = PerformanceMetric::query()
                ->selectRaw('provider as platform, SUM(observed) as value')
                ->where('org_id', $orgId)
                ->whereBetween('observed_at', [$start, $end])
                ->groupBy('provider')
                ->get()
                ->map(function ($row) {
                    return [
                        'name' => Str::title(str_replace('_', ' ', $row->platform ?? 'Other')),
                        'platform' => $row->platform,
                        'spend' => (float) $row->value,
                        'clicks' => null,
                        'ctr' => null,
                        'roas' => null,
                    ];
                });

            return response()->json(['platforms' => $platforms]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch platform performance', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch platform performance',
            ], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        $summary = $this->summary($request)->getData(true);
        $content = "CMIS Analytics Summary\n" . json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response()->streamDownload(
            fn () => print($content),
            'analytics-report.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportExcel(Request $request)
    {
        $summary = $this->summary($request)->getData(true);
        $csv = "Metric,Value\n";
        foreach (($summary['kpis'] ?? []) as $metric => $value) {
            $csv .= $metric . ',' . $value . "\n";
        }

        return response()->streamDownload(
            fn () => print($csv),
            'analytics-report.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    private function aggregateMetrics($metrics): array
    {
        $totals = [
            'spend' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
        ];

        foreach ($metrics as $metric) {
            $name = strtolower($metric->kpi ?? '');
            $value = (float) $metric->observed;

            if (str_contains($name, 'spend') || str_contains($name, 'cost')) {
                $totals['spend'] += $value;
            } elseif (str_contains($name, 'impression')) {
                $totals['impressions'] += $value;
            } elseif (str_contains($name, 'click')) {
                $totals['clicks'] += $value;
            } elseif (str_contains($name, 'conversion') || str_contains($name, 'lead')) {
                $totals['conversions'] += $value;
            }
        }

        return $totals;
    }

    private function calculateChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
