<?php

namespace App\Repositories\Analytics;

use App\Models\Campaign\Campaign;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Analytics Functions
 * Encapsulates PostgreSQL functions related to analytics and reporting
 */
class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    /**
     * Get migration reports
     * Corresponds to: cmis_analytics.report_migrations()
     *
     * @return Collection Collection of migration execution logs
     */
    public function reportMigrations(): Collection
    {
        $results = DB::select('SELECT * FROM cmis_analytics.report_migrations()');

        return collect($results);
    }

    /**
     * Run AI query on analytics data
     * Corresponds to: cmis_analytics.run_ai_query(p_org_id, p_prompt)
     *
     * @param string $orgId Organization UUID
     * @param string $prompt Query prompt text
     * @return bool Success status
     */
    public function runAiQuery(string $orgId, string $prompt): bool
    {
        return DB::statement(
            'SELECT cmis_analytics.run_ai_query(?, ?)',
            [$orgId, $prompt]
        );
    }

    /**
     * Snapshot performance metrics (last 30 days by default)
     * Corresponds to: cmis_analytics.snapshot_performance()
     *
     * @return Collection Collection of performance metrics with trends
     */
    public function snapshotPerformance(): Collection
    {
        $results = DB::select('SELECT * FROM cmis_analytics.snapshot_performance()');

        return collect($results);
    }

    /**
     * Snapshot performance metrics for specific number of days
     * Corresponds to: cmis_analytics.snapshot_performance(snapshot_days)
     *
     * @param int $snapshotDays Number of days to snapshot (default: 30)
     * @return Collection Collection of performance metrics with trends
     */
    public function snapshotPerformanceForDays(int $snapshotDays = 30): Collection
    {
        $results = DB::select(
            'SELECT * FROM cmis_analytics.snapshot_performance(?)',
            [$snapshotDays]
        );

        return collect($results);
    }

    /**
     * Get organization overview analytics
     *
     * @param string $orgId Organization UUID
     * @param array $params Optional parameters (date_from, date_to, etc.)
     * @return Collection
     */
    public function getOrgOverview(string $orgId, array $params = []): Collection
    {
        $dateFrom = $params['date_from'] ?? now()->subDays(30)->toDateString();
        $dateTo = $params['date_to'] ?? now()->toDateString();

        // Get campaign counts
        $campaignStats = DB::table('cmis.campaigns')
            ->where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*) as total_campaigns,
                COUNT(CASE WHEN status = \'active\' THEN 1 END) as active_campaigns,
                COUNT(CASE WHEN status = \'paused\' THEN 1 END) as paused_campaigns,
                COUNT(CASE WHEN status = \'completed\' THEN 1 END) as completed_campaigns,
                SUM(COALESCE(budget, 0)) as total_budget
            ')
            ->first();

        // Get performance metrics
        $performanceStats = DB::table('cmis.performance_metrics as pm')
            ->join('cmis.campaigns as c', 'pm.campaign_id', '=', 'c.campaign_id')
            ->where('c.org_id', $orgId)
            ->whereBetween('pm.collected_at', [$dateFrom, $dateTo])
            ->whereNull('c.deleted_at')
            ->selectRaw('
                SUM(CASE WHEN pm.kpi = \'impressions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as total_impressions,
                SUM(CASE WHEN pm.kpi = \'clicks\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as total_clicks,
                SUM(CASE WHEN pm.kpi = \'conversions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as total_conversions,
                SUM(CASE WHEN pm.kpi = \'spend\' THEN CAST(pm.observed AS NUMERIC) ELSE 0 END) as total_spend,
                AVG(CASE WHEN pm.kpi = \'ctr\' THEN CAST(pm.observed AS NUMERIC) ELSE NULL END) as avg_ctr,
                AVG(CASE WHEN pm.kpi = \'cpc\' THEN CAST(pm.observed AS NUMERIC) ELSE NULL END) as avg_cpc,
                AVG(CASE WHEN pm.kpi = \'roi\' THEN CAST(pm.observed AS NUMERIC) ELSE NULL END) as avg_roi
            ')
            ->first();

        // Get social posts count
        $socialPosts = DB::table('cmis.scheduled_social_posts')
            ->where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*) as total_posts,
                COUNT(CASE WHEN status = \'published\' THEN 1 END) as published_posts,
                COUNT(CASE WHEN status = \'scheduled\' THEN 1 END) as scheduled_posts
            ')
            ->first();

        return collect([
            'campaigns' => [
                'total' => $campaignStats->total_campaigns ?? 0,
                'active' => $campaignStats->active_campaigns ?? 0,
                'paused' => $campaignStats->paused_campaigns ?? 0,
                'completed' => $campaignStats->completed_campaigns ?? 0,
            ],
            'performance' => [
                'impressions' => (int) ($performanceStats->total_impressions ?? 0),
                'clicks' => (int) ($performanceStats->total_clicks ?? 0),
                'conversions' => (int) ($performanceStats->total_conversions ?? 0),
                'spend' => (float) ($performanceStats->total_spend ?? 0),
                'ctr' => round((float) ($performanceStats->avg_ctr ?? 0), 2),
                'cpc' => round((float) ($performanceStats->avg_cpc ?? 0), 2),
                'roi' => round((float) ($performanceStats->avg_roi ?? 0), 2),
            ],
            'budget' => [
                'total_allocated' => (float) ($campaignStats->total_budget ?? 0),
                'total_spent' => (float) ($performanceStats->total_spend ?? 0),
                'remaining' => (float) (($campaignStats->total_budget ?? 0) - ($performanceStats->total_spend ?? 0)),
            ],
            'social' => [
                'total_posts' => $socialPosts->total_posts ?? 0,
                'published' => $socialPosts->published_posts ?? 0,
                'scheduled' => $socialPosts->scheduled_posts ?? 0,
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Get real-time analytics data
     *
     * @param string $orgId Organization UUID
     * @return Collection
     */
    public function getRealTimeAnalytics(string $orgId): Collection
    {
        // Get metrics from last hour
        $oneHourAgo = now()->subHour();

        $realtimeMetrics = DB::table('cmis.performance_metrics as pm')
            ->join('cmis.campaigns as c', 'pm.campaign_id', '=', 'c.campaign_id')
            ->where('c.org_id', $orgId)
            ->where('pm.collected_at', '>=', $oneHourAgo)
            ->whereNull('c.deleted_at')
            ->selectRaw('
                SUM(CASE WHEN pm.kpi = \'impressions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as current_impressions,
                SUM(CASE WHEN pm.kpi = \'clicks\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as current_clicks,
                SUM(CASE WHEN pm.kpi = \'conversions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as current_conversions,
                SUM(CASE WHEN pm.kpi = \'spend\' THEN CAST(pm.observed AS NUMERIC) ELSE 0 END) as current_spend
            ')
            ->first();

        // Get active campaigns count
        $activeCampaigns = DB::table('cmis.campaigns')
            ->where('org_id', $orgId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        return collect([
            'timestamp' => now()->toIso8601String(),
            'period' => 'Last 1 hour',
            'active_campaigns' => $activeCampaigns,
            'metrics' => [
                'impressions' => (int) ($realtimeMetrics->current_impressions ?? 0),
                'clicks' => (int) ($realtimeMetrics->current_clicks ?? 0),
                'conversions' => (int) ($realtimeMetrics->current_conversions ?? 0),
                'spend' => (float) ($realtimeMetrics->current_spend ?? 0),
            ],
        ]);
    }

    /**
     * Get platform-specific analytics
     *
     * @param string $orgId Organization UUID
     * @param string $platform Platform name (meta, google, tiktok, etc.)
     * @param array $params Optional parameters
     * @return Collection
     */
    public function getPlatformAnalytics(string $orgId, string $platform, array $params = []): Collection
    {
        $dateFrom = $params['date_from'] ?? now()->subDays(30)->toDateString();
        $dateTo = $params['date_to'] ?? now()->toDateString();

        // Get campaigns for this platform
        $campaignStats = DB::table('cmis.campaigns')
            ->where('org_id', $orgId)
            ->where('platform', 'ILIKE', "%{$platform}%")
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*) as total_campaigns,
                COUNT(CASE WHEN status = \'active\' THEN 1 END) as active_campaigns,
                SUM(COALESCE(budget, 0)) as total_budget
            ')
            ->first();

        // Get performance metrics for platform campaigns
        $performanceStats = DB::table('cmis.performance_metrics as pm')
            ->join('cmis.campaigns as c', 'pm.campaign_id', '=', 'c.campaign_id')
            ->where('c.org_id', $orgId)
            ->where('c.platform', 'ILIKE', "%{$platform}%")
            ->whereBetween('pm.collected_at', [$dateFrom, $dateTo])
            ->whereNull('c.deleted_at')
            ->selectRaw('
                SUM(CASE WHEN pm.kpi = \'impressions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as impressions,
                SUM(CASE WHEN pm.kpi = \'clicks\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as clicks,
                SUM(CASE WHEN pm.kpi = \'conversions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as conversions,
                SUM(CASE WHEN pm.kpi = \'spend\' THEN CAST(pm.observed AS NUMERIC) ELSE 0 END) as spend,
                AVG(CASE WHEN pm.kpi = \'ctr\' THEN CAST(pm.observed AS NUMERIC) ELSE NULL END) as ctr,
                AVG(CASE WHEN pm.kpi = \'cpc\' THEN CAST(pm.observed AS NUMERIC) ELSE NULL END) as cpc
            ')
            ->first();

        return collect([
            'platform' => $platform,
            'campaigns' => [
                'total' => $campaignStats->total_campaigns ?? 0,
                'active' => $campaignStats->active_campaigns ?? 0,
            ],
            'performance' => [
                'impressions' => (int) ($performanceStats->impressions ?? 0),
                'clicks' => (int) ($performanceStats->clicks ?? 0),
                'conversions' => (int) ($performanceStats->conversions ?? 0),
                'spend' => (float) ($performanceStats->spend ?? 0),
                'ctr' => round((float) ($performanceStats->ctr ?? 0), 2),
                'cpc' => round((float) ($performanceStats->cpc ?? 0), 2),
            ],
            'budget' => [
                'allocated' => (float) ($campaignStats->total_budget ?? 0),
                'spent' => (float) ($performanceStats->spend ?? 0),
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Get engagement metrics
     *
     * @param string $orgId Organization UUID
     * @param array $params Optional parameters
     * @return Collection
     */
    public function getEngagementMetrics(string $orgId, array $params = []): Collection
    {
        $dateFrom = $params['date_from'] ?? now()->subDays(30)->toDateString();
        $dateTo = $params['date_to'] ?? now()->toDateString();

        // Get social engagement metrics
        $engagementStats = DB::table('cmis.performance_metrics as pm')
            ->join('cmis.campaigns as c', 'pm.campaign_id', '=', 'c.campaign_id')
            ->where('c.org_id', $orgId)
            ->whereBetween('pm.collected_at', [$dateFrom, $dateTo])
            ->whereNull('c.deleted_at')
            ->selectRaw('
                SUM(CASE WHEN pm.kpi = \'likes\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as total_likes,
                SUM(CASE WHEN pm.kpi = \'comments\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as total_comments,
                SUM(CASE WHEN pm.kpi = \'shares\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as total_shares,
                SUM(CASE WHEN pm.kpi = \'impressions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as total_impressions,
                SUM(CASE WHEN pm.kpi = \'engagement\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as total_engagement
            ')
            ->first();

        $impressions = max((int) ($engagementStats->total_impressions ?? 0), 1);
        $engagement = (int) ($engagementStats->total_engagement ?? 0)
                    + (int) ($engagementStats->total_likes ?? 0)
                    + (int) ($engagementStats->total_comments ?? 0)
                    + (int) ($engagementStats->total_shares ?? 0);

        return collect([
            'likes' => (int) ($engagementStats->total_likes ?? 0),
            'comments' => (int) ($engagementStats->total_comments ?? 0),
            'shares' => (int) ($engagementStats->total_shares ?? 0),
            'total_engagement' => $engagement,
            'impressions' => (int) ($engagementStats->total_impressions ?? 0),
            'engagement_rate' => round(($engagement / $impressions) * 100, 2),
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Get conversion funnel data
     *
     * @param string $orgId Organization UUID
     * @param string $campaignId Campaign UUID
     * @return Collection
     */
    public function getConversionFunnel(string $orgId, string $campaignId): Collection
    {
        // Verify campaign belongs to org
        $campaign = DB::table('cmis.campaigns')
            ->where('campaign_id', $campaignId)
            ->where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->first();

        if (!$campaign) {
            return collect([
                'error' => 'Campaign not found',
            ]);
        }

        // Get funnel metrics
        $funnelMetrics = DB::table('cmis.performance_metrics')
            ->where('campaign_id', $campaignId)
            ->selectRaw('
                SUM(CASE WHEN kpi = \'impressions\' THEN CAST(observed AS BIGINT) ELSE 0 END) as impressions,
                SUM(CASE WHEN kpi = \'clicks\' THEN CAST(observed AS BIGINT) ELSE 0 END) as clicks,
                SUM(CASE WHEN kpi = \'leads\' THEN CAST(observed AS BIGINT) ELSE 0 END) as leads,
                SUM(CASE WHEN kpi = \'conversions\' THEN CAST(observed AS BIGINT) ELSE 0 END) as conversions
            ')
            ->first();

        $impressions = max((int) ($funnelMetrics->impressions ?? 0), 1);
        $clicks = (int) ($funnelMetrics->clicks ?? 0);
        $leads = (int) ($funnelMetrics->leads ?? 0);
        $conversions = (int) ($funnelMetrics->conversions ?? 0);

        return collect([
            'campaign_id' => $campaignId,
            'campaign_name' => $campaign->name,
            'funnel' => [
                'impressions' => $impressions,
                'clicks' => $clicks,
                'leads' => $leads,
                'conversions' => $conversions,
            ],
            'rates' => [
                'click_rate' => round(($clicks / $impressions) * 100, 2),
                'lead_rate' => $clicks > 0 ? round(($leads / $clicks) * 100, 2) : 0,
                'conversion_rate' => $leads > 0 ? round(($conversions / $leads) * 100, 2) : 0,
                'overall_conversion_rate' => round(($conversions / $impressions) * 100, 2),
            ],
            'drop_off' => [
                'impression_to_click' => $impressions - $clicks,
                'click_to_lead' => $clicks - $leads,
                'lead_to_conversion' => $leads - $conversions,
            ],
        ]);
    }

    /**
     * Get channel attribution data
     *
     * @param string $orgId Organization UUID
     * @param array $params Optional parameters
     * @return Collection
     */
    public function getChannelAttribution(string $orgId, array $params = []): Collection
    {
        $dateFrom = $params['date_from'] ?? now()->subDays(30)->toDateString();
        $dateTo = $params['date_to'] ?? now()->toDateString();

        // Get attribution by platform/channel
        $attributionData = DB::table('cmis.campaigns as c')
            ->join('cmis.performance_metrics as pm', 'c.campaign_id', '=', 'pm.campaign_id')
            ->where('c.org_id', $orgId)
            ->whereBetween('pm.collected_at', [$dateFrom, $dateTo])
            ->whereNull('c.deleted_at')
            ->groupBy('c.platform')
            ->selectRaw('
                c.platform,
                COUNT(DISTINCT c.campaign_id) as campaigns,
                SUM(CASE WHEN pm.kpi = \'conversions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as conversions,
                SUM(CASE WHEN pm.kpi = \'spend\' THEN CAST(pm.observed AS NUMERIC) ELSE 0 END) as spend,
                SUM(CASE WHEN pm.kpi = \'revenue\' THEN CAST(pm.observed AS NUMERIC) ELSE 0 END) as revenue
            ')
            ->get();

        $totalConversions = $attributionData->sum('conversions');
        $totalSpend = $attributionData->sum('spend');

        $channels = $attributionData->map(function ($channel) use ($totalConversions, $totalSpend) {
            $attribution = $totalConversions > 0 ? round(($channel->conversions / $totalConversions) * 100, 2) : 0;
            $spendShare = $totalSpend > 0 ? round(($channel->spend / $totalSpend) * 100, 2) : 0;

            return [
                'channel' => $channel->platform,
                'campaigns' => $channel->campaigns,
                'conversions' => (int) $channel->conversions,
                'spend' => (float) $channel->spend,
                'revenue' => (float) $channel->revenue,
                'attribution_percentage' => $attribution,
                'spend_share' => $spendShare,
                'roi' => $channel->spend > 0 ? round((($channel->revenue - $channel->spend) / $channel->spend) * 100, 2) : 0,
            ];
        });

        return collect([
            'channels' => $channels,
            'summary' => [
                'total_conversions' => (int) $totalConversions,
                'total_spend' => (float) $totalSpend,
                'total_revenue' => (float) $attributionData->sum('revenue'),
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Get campaign analytics
     *
     * @param string $orgId Organization UUID
     * @param string $campaignId Campaign UUID
     * @param array $params Optional parameters
     * @return Collection
     */
    public function getCampaignAnalytics(string $orgId, string $campaignId, array $params = []): Collection
    {
        $dateFrom = $params['date_from'] ?? now()->subDays(30)->toDateString();
        $dateTo = $params['date_to'] ?? now()->toDateString();

        // Get campaign details
        $campaign = DB::table('cmis.campaigns')
            ->where('campaign_id', $campaignId)
            ->where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->first();

        if (!$campaign) {
            return collect(['error' => 'Campaign not found']);
        }

        // Get performance metrics
        $metrics = DB::table('cmis.performance_metrics')
            ->where('campaign_id', $campaignId)
            ->whereBetween('collected_at', [$dateFrom, $dateTo])
            ->selectRaw('
                SUM(CASE WHEN kpi = \'impressions\' THEN CAST(observed AS BIGINT) ELSE 0 END) as impressions,
                SUM(CASE WHEN kpi = \'clicks\' THEN CAST(observed AS BIGINT) ELSE 0 END) as clicks,
                SUM(CASE WHEN kpi = \'conversions\' THEN CAST(observed AS BIGINT) ELSE 0 END) as conversions,
                SUM(CASE WHEN kpi = \'spend\' THEN CAST(observed AS NUMERIC) ELSE 0 END) as spend,
                AVG(CASE WHEN kpi = \'ctr\' THEN CAST(observed AS NUMERIC) ELSE NULL END) as ctr,
                AVG(CASE WHEN kpi = \'cpc\' THEN CAST(observed AS NUMERIC) ELSE NULL END) as cpc,
                AVG(CASE WHEN kpi = \'cpa\' THEN CAST(observed AS NUMERIC) ELSE NULL END) as cpa,
                AVG(CASE WHEN kpi = \'roi\' THEN CAST(observed AS NUMERIC) ELSE NULL END) as roi
            ')
            ->first();

        return collect([
            'campaign' => [
                'id' => $campaign->campaign_id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'platform' => $campaign->platform,
                'budget' => (float) ($campaign->budget ?? 0),
            ],
            'performance' => [
                'impressions' => (int) ($metrics->impressions ?? 0),
                'clicks' => (int) ($metrics->clicks ?? 0),
                'conversions' => (int) ($metrics->conversions ?? 0),
                'spend' => (float) ($metrics->spend ?? 0),
                'ctr' => round((float) ($metrics->ctr ?? 0), 2),
                'cpc' => round((float) ($metrics->cpc ?? 0), 2),
                'cpa' => round((float) ($metrics->cpa ?? 0), 2),
                'roi' => round((float) ($metrics->roi ?? 0), 2),
            ],
            'budget' => [
                'allocated' => (float) ($campaign->budget ?? 0),
                'spent' => (float) ($metrics->spend ?? 0),
                'remaining' => (float) (($campaign->budget ?? 0) - ($metrics->spend ?? 0)),
                'utilization' => $campaign->budget > 0 ? round((($metrics->spend ?? 0) / $campaign->budget) * 100, 2) : 0,
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Get audience demographics
     *
     * @param string $orgId Organization UUID
     * @param array $params Optional parameters
     * @return Collection
     */
    public function getAudienceDemographics(string $orgId, array $params = []): Collection
    {
        // Note: Demographics data would typically come from platform-specific tables
        // For now, return structure with sample distribution

        return collect([
            'age_groups' => [
                ['range' => '18-24', 'percentage' => 0, 'count' => 0],
                ['range' => '25-34', 'percentage' => 0, 'count' => 0],
                ['range' => '35-44', 'percentage' => 0, 'count' => 0],
                ['range' => '45-54', 'percentage' => 0, 'count' => 0],
                ['range' => '55+', 'percentage' => 0, 'count' => 0],
            ],
            'gender' => [
                ['type' => 'male', 'percentage' => 0, 'count' => 0],
                ['type' => 'female', 'percentage' => 0, 'count' => 0],
                ['type' => 'other', 'percentage' => 0, 'count' => 0],
            ],
            'locations' => [],
            'note' => 'Demographics data requires platform-specific audience insights. Implement platform connector sync for detailed demographics.',
        ]);
    }

    /**
     * Compare campaigns
     *
     * @param string $orgId Organization UUID
     * @param array $campaignIds Array of campaign UUIDs
     * @param array $params Optional parameters
     * @return Collection
     */
    public function compareCampaigns(string $orgId, array $campaignIds, array $params = []): Collection
    {
        $dateFrom = $params['date_from'] ?? now()->subDays(30)->toDateString();
        $dateTo = $params['date_to'] ?? now()->toDateString();

        if (empty($campaignIds)) {
            return collect(['error' => 'No campaign IDs provided']);
        }

        // Get campaigns and their metrics
        $campaignsData = DB::table('cmis.campaigns as c')
            ->leftJoin('cmis.performance_metrics as pm', function ($join) use ($dateFrom, $dateTo) {
                $join->on('c.campaign_id', '=', 'pm.campaign_id')
                     ->whereBetween('pm.collected_at', [$dateFrom, $dateTo]);
            })
            ->where('c.org_id', $orgId)
            ->whereIn('c.campaign_id', $campaignIds)
            ->whereNull('c.deleted_at')
            ->groupBy('c.campaign_id', 'c.name', 'c.status', 'c.platform', 'c.budget')
            ->selectRaw('
                c.campaign_id,
                c.name,
                c.status,
                c.platform,
                c.budget,
                SUM(CASE WHEN pm.kpi = \'impressions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as impressions,
                SUM(CASE WHEN pm.kpi = \'clicks\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as clicks,
                SUM(CASE WHEN pm.kpi = \'conversions\' THEN CAST(pm.observed AS BIGINT) ELSE 0 END) as conversions,
                SUM(CASE WHEN pm.kpi = \'spend\' THEN CAST(pm.observed AS NUMERIC) ELSE 0 END) as spend
            ')
            ->get();

        $campaigns = $campaignsData->map(function ($campaign) {
            $impressions = max((int) $campaign->impressions, 1);
            $clicks = (int) $campaign->clicks;
            $conversions = (int) $campaign->conversions;
            $spend = (float) $campaign->spend;

            return [
                'campaign_id' => $campaign->campaign_id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'platform' => $campaign->platform,
                'metrics' => [
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'spend' => $spend,
                    'ctr' => round(($clicks / $impressions) * 100, 2),
                    'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                    'cpa' => $conversions > 0 ? round($spend / $conversions, 2) : 0,
                ],
                'budget' => [
                    'allocated' => (float) ($campaign->budget ?? 0),
                    'spent' => $spend,
                    'remaining' => (float) (($campaign->budget ?? 0) - $spend),
                ],
            ];
        });

        return collect([
            'campaigns' => $campaigns,
            'summary' => [
                'total_campaigns' => $campaigns->count(),
                'total_impressions' => $campaigns->sum('metrics.impressions'),
                'total_clicks' => $campaigns->sum('metrics.clicks'),
                'total_conversions' => $campaigns->sum('metrics.conversions'),
                'total_spend' => $campaigns->sum('metrics.spend'),
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Calculate ROI for campaign
     *
     * @param string $campaignId Campaign UUID
     * @param float $revenue Revenue generated
     * @return array
     */
    public function calculateROI(string $campaignId, float $revenue): array
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->first();

        if (!$campaign || !$campaign->budget) {
            return [
                'roi_percentage' => 0,
                'revenue' => $revenue,
                'cost' => 0,
                'profit' => $revenue,
            ];
        }

        $cost = $campaign->budget;
        $profit = $revenue - $cost;
        $roiPercentage = ($cost > 0) ? ($profit / $cost) * 100 : 0;

        return [
            'roi_percentage' => round($roiPercentage, 2),
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
        ];
    }
}
