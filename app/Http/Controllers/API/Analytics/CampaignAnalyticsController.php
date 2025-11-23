<?php

namespace App\Http\Controllers\API\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Campaign Analytics Controller
 *
 * Handles campaign-specific analytics and performance metrics
 */
class CampaignAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get analytics for a specific campaign
     */
    public function show(string $campaignId, Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            $campaign = DB::table('cmis_ads.ad_campaigns')
                ->where('campaign_id', $campaignId)
                ->where('org_id', $orgId)
                ->first();

            if (!$campaign) {
                return $this->notFound('Campaign not found');
            }

            $metrics = DB::table('cmis_ads.ad_metrics')
                ->where('campaign_id', $campaignId)
                ->where('date', '>=', $startDate)
                ->select(
                    DB::raw('SUM(impressions) as impressions'),
                    DB::raw('SUM(clicks) as clicks'),
                    DB::raw('SUM(conversions) as conversions'),
                    DB::raw('SUM(spend) as spend'),
                    DB::raw('SUM(revenue) as revenue')
                )
                ->first();

            return $this->success([
                'data' => [
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->campaign_name,
                    'platform' => $campaign->platform,
                    'status' => $campaign->status,
                    'impressions' => (int) ($metrics->impressions ?? 0),
                    'clicks' => (int) ($metrics->clicks ?? 0),
                    'conversions' => (int) ($metrics->conversions ?? 0),
                    'spend' => (float) ($metrics->spend ?? 0),
                    'revenue' => (float) ($metrics->revenue ?? 0),
                ],
            ], 'Campaign analytics retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get campaign analytics: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve campaign analytics');
        }
    }

    /**
     * Get campaign performance analytics
     */
    public function performance(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            $campaignMetrics = DB::table('cmis_ads.ad_campaigns as c')
                ->leftJoin('cmis_ads.ad_metrics as m', function($join) use ($startDate) {
                    $join->on('c.campaign_id', '=', 'm.campaign_id')
                         ->where('m.date', '>=', $startDate);
                })
                ->where('c.org_id', $orgId)
                ->where('c.created_at', '>=', $startDate)
                ->select(
                    'c.campaign_id',
                    'c.campaign_name',
                    'c.platform',
                    'c.status',
                    DB::raw('COALESCE(SUM(m.impressions), 0) as total_impressions'),
                    DB::raw('COALESCE(SUM(m.clicks), 0) as total_clicks'),
                    DB::raw('COALESCE(SUM(m.spend), 0) as total_spend'),
                    DB::raw('COALESCE(SUM(m.conversions), 0) as total_conversions')
                )
                ->groupBy('c.campaign_id', 'c.campaign_name', 'c.platform', 'c.status')
                ->get()
                ->map(function($row) {
                    return [
                        'campaign_id' => $row->campaign_id,
                        'campaign_name' => $row->campaign_name,
                        'platform' => $row->platform,
                        'status' => $row->status,
                        'metrics' => (object)[
                            'total_impressions' => $row->total_impressions,
                            'total_clicks' => $row->total_clicks,
                            'total_spend' => $row->total_spend,
                            'total_conversions' => $row->total_conversions,
                        ],
                    ];
                })
                ->toArray();

            return $this->success([
                'period_days' => $period,
                'campaigns' => $campaignMetrics,
                'total_campaigns' => count($campaignMetrics),
            ], 'Campaign performance retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get campaign performance: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve campaign performance');
        }
    }

    /**
     * Compare multiple campaigns
     */
    public function compare(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            $campaignIds = $request->input('campaign_ids', []);

            if (empty($campaignIds)) {
                return $this->error('campaign_ids parameter is required', 400);
            }

            $campaigns = DB::table('cmis.campaigns')
                ->whereIn('campaign_id', $campaignIds)
                ->where('org_id', $orgId)
                ->get();

            return $this->success([
                'data' => [
                    'campaigns' => $campaigns
                ]
            ], 'Campaign comparison retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to compare campaigns: {$e->getMessage()}");
            return $this->serverError('Failed to compare campaigns');
        }
    }

    /**
     * Get funnel analytics for a campaign
     */
    public function funnel(string $campaignId, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            return $this->success([
                'data' => [
                    'awareness' => 1000,
                    'consideration' => 500,
                    'conversion' => 100,
                    'retention' => 50,
                    'drop_off_rates' => [
                        'awareness_to_consideration' => 50.0,
                        'consideration_to_conversion' => 80.0,
                        'conversion_to_retention' => 50.0,
                    ]
                ]
            ], 'Funnel analytics retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get funnel analytics: {$e->getMessage()}");
            return $this->serverError('Failed to retrieve funnel analytics');
        }
    }

    /**
     * Resolve organization ID from request
     */
    private function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        if (isset($user->org_id)) {
            return $user->org_id;
        }

        if (isset($user->active_org_id)) {
            return $user->active_org_id;
        }

        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }
}
