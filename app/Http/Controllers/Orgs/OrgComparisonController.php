<?php

namespace App\Http\Controllers\Orgs;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignPerformanceMetric;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

/**
 * Organization Comparison Controller
 *
 * Handles campaign comparison and export functionality
 */
class OrgComparisonController extends Controller
{
    /**
     * Compare campaigns within an organization
     */
    public function compareCampaigns(Request $request, $id): RedirectResponse
    {
        $org = Org::findOrFail($id);

        $campaignIds = collect($request->input('campaign_ids', []))
            ->filter(fn ($value) => Str::isUuid($value))
            ->values();

        if ($campaignIds->count() < 2) {
            return redirect()->back()->with('error', 'يجب اختيار حملتين على الأقل للمقارنة.');
        }

        $campaigns = Campaign::query()
            ->where('org_id', $org->org_id)
            ->whereIn('campaign_id', $campaignIds)
            ->select('campaign_id', 'name')
            ->orderBy('name')
            ->get();

        if ($campaigns->isEmpty()) {
            return redirect()->back()->with('error', 'لم يتم العثور على الحملات المحددة.');
        }

        $metrics = CampaignPerformanceMetric::query()
            ->whereIn('campaign_id', $campaigns->pluck('campaign_id'))
            ->select('campaign_id', 'metric_name', DB::raw('AVG(metric_value) as value'))
            ->groupBy('campaign_id', 'metric_name')
            ->get();

        $kpiLabels = $metrics->pluck('metric_name')->unique()->values();

        $datasets = $campaigns->map(function ($campaign) use ($metrics, $kpiLabels) {
            $values = $kpiLabels->map(fn ($name) => (float) optional(
                $metrics->first(fn ($metric) => $metric->campaign_id === $campaign->campaign_id && $metric->metric_name === $name)
            )->value)->all();

            return [
                'label' => $campaign->name,
                'data' => $values,
                'borderWidth' => 1,
                'backgroundColor' => sprintf('rgba(%d,%d,%d,0.5)', rand(0, 255), rand(0, 255), rand(0, 255)),
                'borderColor' => 'rgba(0,0,0,0.7)',
            ];
        });

        return view('orgs.campaigns_compare', [
            'org_id' => $org->org_id,
            'campaigns' => $campaigns,
            'kpiLabels' => $kpiLabels,
            'datasets' => $datasets,
        ]);
    }

    /**
     * Export comparison as PDF
     */
    public function exportComparePdf(Request $request, $id)
    {
        $org = Org::findOrFail($id);

        $campaigns = collect(json_decode($request->input('campaigns'), true));
        $kpiLabels = collect(json_decode($request->input('kpiLabels'), true));
        $datasets = collect(json_decode($request->input('datasets'), true));

        $pdf = PDF::loadView('exports.compare_pdf', compact('campaigns', 'kpiLabels', 'datasets', 'org'));
        return $pdf->download('campaign_comparison.pdf');
    }

    /**
     * Export comparison as Excel
     */
    public function exportCompareExcel(Request $request, $id)
    {
        $org = Org::findOrFail($id);

        $campaigns = collect(json_decode($request->input('campaigns'), true));
        $kpiLabels = collect(json_decode($request->input('kpiLabels'), true));
        $datasets = collect(json_decode($request->input('datasets'), true));

        return Excel::download(new class($campaigns, $kpiLabels, $datasets, $org) implements \Maatwebsite\Excel\Concerns\FromArray {
            public function __construct(private Collection $campaigns, private Collection $kpiLabels, private Collection $datasets, private Org $org)
            {
            }

            public function array(): array
            {
                $rows = [];
                $rows[] = array_merge(['KPI'], $this->datasets->pluck('label')->all());

                foreach ($this->kpiLabels as $index => $label) {
                    $row = [$label];
                    foreach ($this->datasets as $dataset) {
                        $row[] = $dataset['data'][$index] ?? 0;
                    }
                    $rows[] = $row;
                }

                return $rows;
            }
        }, 'campaign_comparison.xlsx');
    }
}
