<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class OrgController extends Controller
{
    public function index()
    {
        $orgs = DB::table('cmis.orgs')
            ->select('org_id', 'name', 'default_locale', 'currency', 'created_at')
            ->orderBy('name')
            ->get();

        return view('orgs.index', compact('orgs'));
    }

    public function show($id)
    {
        $org = $this->resolveOrg($id);

        return view('orgs.show', compact('org'));
    }

    public function campaigns($id)
    {
        $org = $this->resolveOrg($id);

        $campaigns = DB::table('cmis.campaigns')
            ->where('org_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return view('orgs.campaigns', [
            'id' => $id,
            'org' => $org,
            'campaigns' => $campaigns,
        ]);
    }

    public function services($id)
    {
        $org = $this->resolveOrg($id);

        $services = DB::table('cmis.offerings')
            ->select('name', DB::raw("type as kind"))
            ->where('org_id', $id)
            ->where('type', 'service')
            ->get();

        return view('orgs.services', [
            'org' => $org,
            'services' => $services,
        ]);
    }

    public function products($id)
    {
        $org = $this->resolveOrg($id);

        $products = DB::table('cmis.offerings')
            ->select('name', DB::raw("type as kind"))
            ->where('org_id', $id)
            ->where('type', 'product')
            ->get();

        return view('orgs.products', [
            'org' => $org,
            'products' => $products,
        ]);
    }

    // ... الدوال السابقة تبقى كما هي ...

    public function compareCampaigns(Request $request, $id)
    {
        $campaignIds = $request->input('campaign_ids', []);
        if (count($campaignIds) < 2) {
            return redirect()->back()->with('error', 'يجب اختيار حملتين على الأقل للمقارنة.');
        }

        $campaigns = DB::select('SELECT campaign_id, name FROM cmis.campaigns WHERE campaign_id IN (' . implode(',', array_fill(0, count($campaignIds), '?')) . ')', $campaignIds);

        $data = DB::select('SELECT p.campaign_id, k.kpi_name, AVG(p.value) AS value
                            FROM cmis.campaign_performance_dashboard p
                            JOIN cmis.kpis k ON k.kpi_id = p.kpi_id
                            WHERE p.campaign_id IN (' . implode(',', array_fill(0, count($campaignIds), '?')) . ')
                            GROUP BY p.campaign_id, k.kpi_name', $campaignIds);

        $kpiLabels = collect($data)->pluck('kpi_name')->unique()->values();
        $datasets = [];

        foreach ($campaigns as $c) {
            $values = [];
            foreach ($kpiLabels as $kpi) {
                $metric = collect($data)->first(fn($d) => $d->campaign_id == $c->campaign_id && $d->kpi_name == $kpi);
                $values[] = $metric->value ?? 0;
            }
            $datasets[] = [
                'label' => $c->name,
                'data' => $values,
                'borderWidth' => 1,
                'backgroundColor' => sprintf('rgba(%d,%d,%d,0.5)', rand(0,255), rand(0,255), rand(0,255)),
                'borderColor' => 'rgba(0,0,0,0.7)'
            ];
        }

        return view('orgs.campaigns_compare', [
            'org_id' => $id,
            'campaigns' => $campaigns,
            'kpiLabels' => $kpiLabels,
            'datasets' => $datasets
        ]);
    }

    public function exportComparePdf(Request $request, $id)
    {
        $campaigns = json_decode($request->input('campaigns'));
        $kpiLabels = json_decode($request->input('kpiLabels'));
        $datasets = json_decode($request->input('datasets'));

        $pdf = PDF::loadView('exports.compare_pdf', compact('campaigns', 'kpiLabels', 'datasets'));
        return $pdf->download('campaign_comparison.pdf');
    }

    public function exportCompareExcel(Request $request, $id)
    {
        $campaigns = json_decode($request->input('campaigns'));
        $kpiLabels = json_decode($request->input('kpiLabels'));
        $datasets = json_decode($request->input('datasets'));

        return Excel::download(new class($campaigns, $kpiLabels, $datasets) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $campaigns, $kpiLabels, $datasets;
            public function __construct($c, $k, $d){ $this->campaigns=$c; $this->kpiLabels=$k; $this->datasets=$d; }
            public function array(): array {
                $rows = [];
                $rows[] = array_merge(['KPI'], array_map(fn($c)=>$c->label, $this->datasets));
                foreach($this->kpiLabels as $i => $kpi){
                    $row = [$kpi];
                    foreach($this->datasets as $d){ $row[] = $d->data[$i] ?? 0; }
                    $rows[] = $row;
                }
                return $rows;
            }
        }, 'campaign_comparison.xlsx');
    }

    protected function resolveOrg($id)
    {
        $org = DB::table('cmis.orgs')->where('org_id', $id)->first();

        abort_if(!$org, 404, 'المؤسسة غير موجودة');

        return $org;
    }
}