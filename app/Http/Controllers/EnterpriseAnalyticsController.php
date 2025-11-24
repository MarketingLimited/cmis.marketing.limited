<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
        // Multi-tenancy is handled via RLS (Row-Level Security) and OrgScope
    }

    /**
     * Real-time analytics dashboard
     *
     * GET /analytics/realtime
     *
     * Renders the real-time dashboard view with auto-refreshing metrics
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function realtime(Request $request): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        return view('analytics.realtime', [
            'orgId' => $orgId,
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
     * @param string $campaignId
     * @return \Illuminate\View\View
     */
    public function campaign(Request $request, string $campaignId): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        // Verify campaign exists and belongs to org (RLS will filter automatically)
        $campaign = DB::table('cmis.campaigns')
            ->where('campaign_id', $campaignId)
            ->first();

        if (!$campaign) {
            abort(404, 'Campaign not found');
        }

        return view('analytics.campaign', [
            'orgId' => $orgId,
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
     * @param string|null $entityType
     * @param string|null $entityId
     * @return \Illuminate\View\View
     */
    public function kpis(Request $request, ?string $entityType = null, ?string $entityId = null): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        // Default to organization-wide view
        $entityType = $entityType ?? 'org';
        $entityId = $entityId ?? $orgId;

        $entityName = $this->resolveEntityName($entityType, $entityId);

        return view('analytics.kpis', [
            'orgId' => $orgId,
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
     * @return \Illuminate\View\View
     */
    public function enterprise(Request $request): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        // Get active campaigns for quick links
        $activeCampaigns = DB::table('cmis.campaigns')
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get(['campaign_id', 'name', 'status', 'start_date', 'end_date']);

        return view('analytics.enterprise', [
            'orgId' => $orgId,
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
     * @return \Illuminate\View\View
     */
    public function campaigns(Request $request): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        // Get campaigns with pagination (RLS automatically filters)
        $campaigns = DB::table('cmis.campaigns')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('analytics.campaigns', [
            'orgId' => $orgId,
            'campaigns' => $campaigns,
            'user' => $user,
            'pageTitle' => 'Campaign Analytics'
        ]);
    }

    /**
     * Resolve organization ID from request
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        // Try to get from route parameter first
        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        // Fall back to user's active org
        if ($user->active_org_id) {
            return $user->active_org_id;
        }

        // Query the user_orgs pivot table for an active org
        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }

    /**
     * AI-powered insights dashboard
     *
     * GET /analytics/insights
     *
     * Displays AI-generated insights and recommendations
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function insights(Request $request): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        return view('analytics.insights', [
            'orgId' => $orgId,
            'user' => $user,
            'pageTitle' => 'AI Insights'
        ]);
    }

    /**
     * Reports dashboard
     *
     * GET /analytics/reports
     *
     * Displays custom reports and report builder
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function reports(Request $request): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        return view('analytics.reports', [
            'orgId' => $orgId,
            'user' => $user,
            'pageTitle' => 'Reports'
        ]);
    }

    /**
     * Metrics dashboard
     *
     * GET /analytics/metrics
     *
     * Displays detailed metrics and performance indicators
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function metrics(Request $request): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        return view('analytics.metrics', [
            'orgId' => $orgId,
            'user' => $user,
            'pageTitle' => 'Metrics'
        ]);
    }

    /**
     * Export dashboard
     *
     * GET /analytics/export
     *
     * Export data and reports
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function export(Request $request): View
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            abort(404, 'No active organization found');
        }

        return view('analytics.export', [
            'orgId' => $orgId,
            'user' => $user,
            'pageTitle' => 'Export Data'
        ]);
    }

    /**
     * Resolve entity name for display
     *
     * @param string $entityType
     * @param string $entityId
     * @return string
     */
    private function resolveEntityName(string $entityType, string $entityId): string
    {
        switch ($entityType) {
            case 'campaign':
                $campaign = DB::table('cmis.campaigns')
                    ->where('campaign_id', $entityId)
                    ->first();
                return $campaign?->name ?? 'Unknown Campaign';

            case 'org':
                $org = DB::table('cmis.orgs')
                    ->where('org_id', $entityId)
                    ->first();
                return $org?->name ?? 'Organization';

            default:
                return ucfirst($entityType);
        }
    }
}
