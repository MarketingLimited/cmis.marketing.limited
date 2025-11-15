<?php

namespace App\Services\Dashboard;

use App\Models\Core\Org;
use App\Models\Core\Integration;
use App\Models\AdPlatform\{AdCampaign, AdMetric};
use App\Models\Social\{SocialPost, SocialAccount};
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UnifiedDashboardService
{
    /**
     * Get unified dashboard data for an organization
     */
    public function getOrgDashboard(Org $org): array
    {
        return Cache::remember(
            "dashboard:org:{$org->org_id}",
            now()->addMinutes(15),
            fn() => $this->buildDashboard($org)
        );
    }

    /**
     * Build complete dashboard data
     */
    private function buildDashboard(Org $org): array
    {
        $startDate = now()->subDays(30);

        return [
            'org_id' => $org->org_id,
            'org_name' => $org->name,
            'overview' => $this->getOverview($org, $startDate),
            'kpis' => $this->getKPIs($org),
            'active_campaigns' => $this->getActiveCampaigns($org),
            'scheduled_content' => $this->getScheduledContent($org),
            'recent_posts' => $this->getRecentPosts($org),
            'connected_accounts' => $this->getConnectedAccounts($org),
            'alerts' => $this->getAlerts($org),
            'sync_status' => $this->getSyncStatus($org),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get overview metrics
     */
    private function getOverview(Org $org, Carbon $startDate): array
    {
        // Get ad metrics
        $adMetrics = DB::table('ad_metrics as am')
            ->join('ad_campaigns as ac', 'am.campaign_id', '=', 'ac.id')
            ->where('ac.org_id', $org->org_id)
            ->where('am.created_at', '>=', $startDate)
            ->selectRaw('
                SUM(am.impressions) as total_impressions,
                SUM(am.clicks) as total_clicks,
                SUM(am.spend) as total_spend,
                SUM(am.conversions) as total_conversions,
                AVG(am.ctr) as avg_ctr,
                AVG(am.cpc) as avg_cpc
            ')
            ->first();

        // Get social metrics
        $socialMetrics = DB::table('social_posts')
            ->where('org_id', $org->org_id)
            ->where('status', 'published')
            ->where('published_at', '>=', $startDate)
            ->count();

        return [
            'period' => 'Last 30 days',
            'advertising' => [
                'total_spend' => $adMetrics->total_spend ?? 0,
                'total_impressions' => $adMetrics->total_impressions ?? 0,
                'total_clicks' => $adMetrics->total_clicks ?? 0,
                'total_conversions' => $adMetrics->total_conversions ?? 0,
                'avg_ctr' => round($adMetrics->avg_ctr ?? 0, 2),
                'avg_cpc' => round($adMetrics->avg_cpc ?? 0, 2),
                'roi' => $this->calculateROI($org, $startDate),
            ],
            'content' => [
                'posts_published' => $socialMetrics,
                'engagement_rate' => $this->calculateEngagementRate($org, $startDate),
            ],
        ];
    }

    /**
     * Get active campaigns summary
     */
    private function getActiveCampaigns(Org $org): array
    {
        $campaigns = AdCampaign::where('org_id', $org->org_id)
            ->where('status', 'active')
            ->with(['integration'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $campaigns->map(function ($campaign) {
            $metrics = $campaign->metrics()->latest()->first();

            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'platform' => $campaign->integration->provider ?? 'unknown',
                'budget' => $campaign->budget,
                'spend' => $metrics->spend ?? 0,
                'budget_used_pct' => $campaign->budget > 0
                    ? round(($metrics->spend ?? 0) / $campaign->budget * 100, 1)
                    : 0,
                'impressions' => $metrics->impressions ?? 0,
                'clicks' => $metrics->clicks ?? 0,
                'ctr' => $metrics->ctr ?? 0,
            ];
        })->toArray();
    }

    /**
     * Get scheduled content
     */
    private function getScheduledContent(Org $org): array
    {
        $posts = SocialPost::where('org_id', $org->org_id)
            ->where('status', 'scheduled')
            ->where('scheduled_for', '>=', now())
            ->orderBy('scheduled_for', 'asc')
            ->limit(10)
            ->get();

        return $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'content' => substr($post->content, 0, 100),
                'platforms' => $post->platforms ?? [],
                'scheduled_for' => $post->scheduled_for->toIso8601String(),
                'status' => $post->status,
            ];
        })->toArray();
    }

    /**
     * Get recent published posts
     */
    private function getRecentPosts(Org $org): array
    {
        $posts = SocialPost::where('org_id', $org->org_id)
            ->where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get();

        return $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'content' => substr($post->content, 0, 100),
                'published_at' => $post->published_at->toIso8601String(),
                'engagement' => [
                    'likes' => $post->likes_count ?? 0,
                    'comments' => $post->comments_count ?? 0,
                    'shares' => $post->shares_count ?? 0,
                ],
            ];
        })->toArray();
    }

    /**
     * Get connected accounts
     */
    private function getConnectedAccounts(Org $org): array
    {
        $integrations = Integration::where('org_id', $org->org_id)
            ->where('is_active', true)
            ->get();

        return [
            'total' => $integrations->count(),
            'by_platform' => $integrations->groupBy('provider')->map->count(),
            'accounts' => $integrations->map(function ($integration) {
                return [
                    'integration_id' => $integration->integration_id,
                    'provider' => $integration->provider,
                    'platform' => $integration->platform,
                    'username' => $integration->username,
                    'is_active' => $integration->is_active,
                    'last_synced' => $integration->last_synced_at?->toIso8601String(),
                    'sync_status' => $integration->sync_status,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get alerts for the organization
     */
    private function getAlerts(Org $org): array
    {
        $alerts = [];

        // Budget alerts
        $campaigns = AdCampaign::where('org_id', $org->org_id)
            ->where('status', 'active')
            ->get();

        foreach ($campaigns as $campaign) {
            $latestMetric = $campaign->metrics()->latest()->first();
            if ($latestMetric && $campaign->budget > 0) {
                $spendPct = ($latestMetric->spend / $campaign->budget) * 100;

                if ($spendPct >= 90) {
                    $alerts[] = [
                        'type' => 'budget',
                        'severity' => 'warning',
                        'message' => "Campaign '{$campaign->name}' has used {$spendPct}% of budget",
                        'campaign_id' => $campaign->id,
                    ];
                }
            }
        }

        // Token expiry alerts
        $integrations = Integration::where('org_id', $org->org_id)
            ->where('is_active', true)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<', now()->addDays(7))
            ->get();

        foreach ($integrations as $integration) {
            $alerts[] = [
                'type' => 'token_expiry',
                'severity' => 'error',
                'message' => "{$integration->provider} token will expire soon",
                'integration_id' => $integration->integration_id,
                'expires_at' => $integration->token_expires_at->toIso8601String(),
            ];
        }

        // Sync failure alerts
        $failedSyncs = Integration::where('org_id', $org->org_id)
            ->where('sync_status', 'failed')
            ->get();

        foreach ($failedSyncs as $integration) {
            $alerts[] = [
                'type' => 'sync_failed',
                'severity' => 'error',
                'message' => "{$integration->provider} sync failed",
                'integration_id' => $integration->integration_id,
                'error' => $integration->sync_errors,
            ];
        }

        return $alerts;
    }

    /**
     * Get sync status summary
     */
    private function getSyncStatus(Org $org): array
    {
        $integrations = Integration::where('org_id', $org->org_id)
            ->where('is_active', true)
            ->get();

        return [
            'total' => $integrations->count(),
            'syncing' => $integrations->where('sync_status', 'syncing')->count(),
            'success' => $integrations->where('sync_status', 'success')->count(),
            'failed' => $integrations->where('sync_status', 'failed')->count(),
            'pending' => $integrations->where('sync_status', 'pending')->count(),
            'last_sync' => $integrations->max('last_synced_at')?->toIso8601String(),
        ];
    }

    /**
     * Get KPIs
     */
    private function getKPIs(Org $org): array
    {
        // This would integrate with KPI targets if they exist
        return [
            'roi' => ['target' => 300, 'actual' => 250, 'status' => 'in_progress'],
            'conversions' => ['target' => 100, 'actual' => 85, 'status' => 'in_progress'],
            'engagement_rate' => ['target' => 5.0, 'actual' => 4.2, 'status' => 'in_progress'],
        ];
    }

    /**
     * Calculate ROI
     */
    private function calculateROI(Org $org, Carbon $startDate): float
    {
        // Simplified ROI calculation
        $spend = DB::table('ad_metrics as am')
            ->join('ad_campaigns as ac', 'am.campaign_id', '=', 'ac.id')
            ->where('ac.org_id', $org->org_id)
            ->where('am.created_at', '>=', $startDate)
            ->sum('am.spend');

        $revenue = DB::table('ad_metrics as am')
            ->join('ad_campaigns as ac', 'am.campaign_id', '=', 'ac.id')
            ->where('ac.org_id', $org->org_id)
            ->where('am.created_at', '>=', $startDate)
            ->sum(DB::raw('am.conversions * 50')); // Assuming $50 per conversion

        if ($spend <= 0) return 0;

        return round((($revenue - $spend) / $spend) * 100, 2);
    }

    /**
     * Calculate engagement rate
     */
    private function calculateEngagementRate(Org $org, Carbon $startDate): float
    {
        // Simplified engagement rate
        return 4.5; // Placeholder
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(Org $org): void
    {
        Cache::forget("dashboard:org:{$org->org_id}");
    }
}
