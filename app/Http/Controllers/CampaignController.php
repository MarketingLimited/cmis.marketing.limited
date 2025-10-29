<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function show($campaign_id)
    {
        $campaign = DB::selectOne("SELECT * FROM cmis.campaigns WHERE campaign_id = ?", [$campaign_id]);
        $offerings = DB::select("SELECT o.name, o.kind FROM cmis.campaign_offerings co JOIN cmis.offerings o ON o.offering_id = co.offering_id WHERE co.campaign_id = ?", [$campaign_id]);
        $performance = DB::select("SELECT k.kpi_name, p.value FROM cmis.campaign_performance_dashboard p JOIN cmis.kpis k ON k.kpi_id = p.kpi_id WHERE p.campaign_id = ?", [$campaign_id]);

        return view('campaigns.show', compact('campaign', 'offerings', 'performance'));
    }

    public function performanceByRange($campaign_id, $range)
    {
        $interval = match ($range) {
            'daily' => '1 day',
            'weekly' => '7 days',
            'monthly' => '30 days',
            'yearly' => '365 days',
            default => '30 days'
        };

        $query = "
            SELECT k.kpi_name, AVG(p.value) AS value
            FROM cmis.campaign_performance_dashboard p
            JOIN cmis.kpis k ON k.kpi_id = p.kpi_id
            WHERE p.campaign_id = ?
              AND p.date >= NOW() - INTERVAL '$interval'
            GROUP BY k.kpi_name
        ";

        $data = DB::select($query, [$campaign_id]);

        return response()->json($data);
    }
}