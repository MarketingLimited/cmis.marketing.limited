<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Enterprise Analytics Dashboard Controller (Phase 9)
 *
 * Renders views for Phase 8 Alpine.js components:
 * - Real-time analytics dashboard
 * - Campaign analytics & ROI visualization
 * - KPI monitoring dashboard
 * - Notification center
 *
 * All views use Alpine.js components that connect to Phase 5-7 backend APIs
 */
class EnterpriseAnalyticsController extends Controller
{
    /**
     * Initialize controller with middleware
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('tenant'); // Ensure multi-tenancy context
    }

    /**
     * Real-time analytics dashboard
     *
     * GET /analytics/realtime
     *
     * Renders the real-time dashboard view with auto-refreshing metrics
     *
     * @param Request $request
     * @param string $org
     * @return \Illuminate\View\View
     */
    public function realtime(Request $request, string $org)
    {
        $user = $request->user();

        return view('analytics.realtime', [
            'orgId' => $org,
            'user' => $user,
            'pageTitle' => 'Real-Time Analytics Dashboard'
        ]);
    }

    /**
     * Campaign analytics & ROI dashboard
     *
     * GET /analytics/campaign/{campaign_id}
     *
     * Renders the campaign analytics view with ROI, attribution, and projection data
     *
     * @param Request $request
     * @param string $org
     * @param string $campaignId
     * @return \Illuminate\View\View
     */
    public function campaign(Request $request, string $org, string $campaignId)
    {
        $user = $request->user();

        // Verify campaign exists and belongs to org (RLS will filter automatically)
        $campaign = DB::table('cmis.campaigns')
            ->where('org_id', $org)
            ->where('campaign_id', $campaignId)
            ->first();

        if (!$campaign) {
            abort(404, 'Campaign not found');
        }

        return view('analytics.campaign', [
            'orgId' => $org,
            'campaignId' => $campaignId,
            'campaign' => $campaign,
            'user' => $user,
            'pageTitle' => "Campaign Analytics: {$campaign->name}"
        ]);
    }

    /**
     * KPI monitoring dashboard
     *
     * GET /analytics/kpis
     * GET /analytics/kpis/{entity_type}/{entity_id}
     *
     * Renders the KPI dashboard view with health scores and status indicators
     *
     * @param Request $request
     * @param string $org
     * @param string|null $entityType
     * @param string|null $entityId
     * @return \Illuminate\View\View
     */
    public function kpis(Request $request, string $org, ?string $entityType = null, ?string $entityId = null)
    {
        $user = $request->user();

        // Default to organization-wide view
        $entityType = $entityType ?? 'org';
        $entityId = $entityId ?? $org;

        $entityName = $this->resolveEntityName($entityType, $entityId, $org);

        return view('analytics.kpis', [
            'orgId' => $org,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'entityName' => $entityName,
            'user' => $user,
            'pageTitle' => "KPI Dashboard: {$entityName}"
        ]);
    }

    /**
     * Unified enterprise dashboard
     *
     * GET /analytics/enterprise
     *
     * Combines real-time metrics, KPIs, and notifications in a single view
     *
     * @param Request $request
     * @param string $org
     * @return \Illuminate\View\View
     */
    public function enterprise(Request $request, string $org)
    {
        $user = $request->user();

        // Get active campaigns for quick links
        $activeCampaigns = DB::table('cmis.campaigns')
            ->where('org_id', $org)
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get(['campaign_id', 'name', 'status', 'start_date', 'end_date']);

        return view('analytics.enterprise', [
            'orgId' => $org,
            'user' => $user,
            'activeCampaigns' => $activeCampaigns,
            'pageTitle' => 'Enterprise Analytics Hub'
        ]);
    }

    /**
     * Campaign list with performance overview
     *
     * GET /analytics/campaigns
     *
     * Lists all campaigns with quick access to analytics
     *
     * @param Request $request
     * @param string $org
     * @return \Illuminate\View\View
     */
    public function campaigns(Request $request, string $org)
    {
        $user = $request->user();

        // Get campaigns with pagination (RLS automatically filters)
        $campaigns = DB::table('cmis.campaigns')
            ->where('org_id', $org)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('analytics.campaigns', [
            'orgId' => $org,
            'campaigns' => $campaigns,
            'user' => $user,
            'pageTitle' => 'Campaign Analytics'
        ]);
    }

    /**
     * Resolve entity name for display
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $org
     * @return string
     */
    private function resolveEntityName(string $entityType, string $entityId, string $org): string
    {
        switch ($entityType) {
            case 'campaign':
                $campaign = DB::table('cmis.campaigns')
                    ->where('campaign_id', $entityId)
                    ->where('org_id', $org)
                    ->first();
                return $campaign?->name ?? 'Unknown Campaign';

            case 'org':
                $orgRecord = DB::table('cmis.orgs')
                    ->where('org_id', $entityId)
                    ->first();
                return $orgRecord?->name ?? 'Organization';

            default:
                return ucfirst($entityType);
        }
    }
}
