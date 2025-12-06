<?php

namespace App\Listeners\Analytics;

use App\Events\Campaign\CampaignMetricsUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, DB, Log};

/**
 * Updates performance metrics when campaign metrics change
 */
class UpdatePerformanceMetrics implements ShouldQueue
{
    /**
     * Handle campaign metrics updated event
     */
    public function handle(CampaignMetricsUpdated $event): void
    {
        $campaign = $event->campaign;

        Log::debug('UpdatePerformanceMetrics::handle - Updating performance metrics', [
            'campaign_id' => $campaign->campaign_id ?? $campaign->id,
            'platform' => $campaign->platform ?? 'N/A',
        ]);

        // Clear relevant caches
        if (isset($campaign->org_id)) {
            Cache::forget("dashboard:org:{$campaign->org_id}");
            Cache::forget("analytics:org:{$campaign->org_id}");
            Cache::forget("campaigns:org:{$campaign->org_id}");
        }

        // Calculate and store ROI
        $this->updateROI($campaign);

        // Update performance trends
        $this->updatePerformanceTrends($campaign);

        // Update aggregated metrics
        $this->updateAggregatedMetrics($campaign);

        // Check for optimization recommendations
        $this->checkOptimizationTriggers($campaign);
    }

    /**
     * Calculate and update ROI for campaign
     */
    protected function updateROI($campaign): void
    {
        if (!isset($campaign->org_id)) {
            return;
        }

        $spend = $campaign->spend ?? $campaign->total_spend ?? 0;
        $revenue = $campaign->revenue ?? $campaign->total_revenue ?? 0;

        if ($spend > 0) {
            $roi = (($revenue - $spend) / $spend) * 100;

            DB::table('cmis_ads.ad_campaigns')
                ->where('campaign_id', $campaign->campaign_id ?? $campaign->id)
                ->update([
                    'roi' => round($roi, 2),
                    'updated_at' => now(),
                ]);

            Log::info('Campaign ROI updated', [
                'campaign_id' => $campaign->campaign_id ?? $campaign->id,
                'roi' => round($roi, 2),
            ]);
        }
    }

    /**
     * Update performance trends data
     */
    protected function updatePerformanceTrends($campaign): void
    {
        $campaignId = $campaign->campaign_id ?? $campaign->id;

        // Store daily performance snapshot
        DB::table('cmis.unified_metrics')->updateOrInsert(
            [
                'org_id' => $campaign->org_id,
                'platform' => $campaign->platform ?? 'unknown',
                'entity_type' => 'campaign',
                'entity_id' => $campaignId,
                'date' => now()->toDateString(),
            ],
            [
                'impressions' => $campaign->impressions ?? 0,
                'clicks' => $campaign->clicks ?? 0,
                'spend' => $campaign->spend ?? 0,
                'conversions' => $campaign->conversions ?? 0,
                'revenue' => $campaign->revenue ?? 0,
                'raw_metrics' => json_encode([
                    'ctr' => $campaign->ctr ?? 0,
                    'cpc' => $campaign->cpc ?? 0,
                    'cpm' => $campaign->cpm ?? 0,
                    'cvr' => $campaign->cvr ?? 0,
                    'roas' => $campaign->roas ?? 0,
                ]),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Update aggregated organization metrics
     */
    protected function updateAggregatedMetrics($campaign): void
    {
        if (!isset($campaign->org_id)) {
            return;
        }

        // Recalculate organization totals
        $totals = DB::table('cmis_ads.ad_campaigns')
            ->where('org_id', $campaign->org_id)
            ->where('status', 'active')
            ->selectRaw('
                SUM(COALESCE(spend, 0)) as total_spend,
                SUM(COALESCE(impressions, 0)) as total_impressions,
                SUM(COALESCE(clicks, 0)) as total_clicks,
                SUM(COALESCE(conversions, 0)) as total_conversions,
                SUM(COALESCE(revenue, 0)) as total_revenue
            ')
            ->first();

        if ($totals) {
            DB::table('cmis.organizations')
                ->where('org_id', $campaign->org_id)
                ->update([
                    'settings' => DB::raw("jsonb_set(
                        COALESCE(settings, '{}'),
                        '{aggregated_metrics}',
                        '" . json_encode([
                            'total_spend' => $totals->total_spend ?? 0,
                            'total_impressions' => $totals->total_impressions ?? 0,
                            'total_clicks' => $totals->total_clicks ?? 0,
                            'total_conversions' => $totals->total_conversions ?? 0,
                            'total_revenue' => $totals->total_revenue ?? 0,
                            'updated_at' => now()->toIso8601String(),
                        ]) . "'
                    )"),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Check if optimization recommendations should be triggered
     */
    protected function checkOptimizationTriggers($campaign): void
    {
        $campaignId = $campaign->campaign_id ?? $campaign->id;

        // Check for poor CTR (< 1%)
        $ctr = $campaign->ctr ?? 0;
        if ($ctr > 0 && $ctr < 1.0 && ($campaign->impressions ?? 0) > 1000) {
            $this->createOptimizationRecommendation($campaign, 'low_ctr', [
                'current_ctr' => $ctr,
                'recommendation' => 'Consider improving ad creative or targeting',
            ]);
        }

        // Check for high CPC
        $cpc = $campaign->cpc ?? 0;
        $industryAvgCpc = 2.50; // This could be dynamic based on industry
        if ($cpc > $industryAvgCpc * 2) {
            $this->createOptimizationRecommendation($campaign, 'high_cpc', [
                'current_cpc' => $cpc,
                'industry_avg' => $industryAvgCpc,
                'recommendation' => 'Consider adjusting bids or improving quality score',
            ]);
        }

        // Check for low conversion rate
        $cvr = $campaign->cvr ?? 0;
        if ($cvr > 0 && $cvr < 1.0 && ($campaign->clicks ?? 0) > 100) {
            $this->createOptimizationRecommendation($campaign, 'low_cvr', [
                'current_cvr' => $cvr,
                'recommendation' => 'Review landing page experience and targeting',
            ]);
        }
    }

    /**
     * Create optimization recommendation record
     */
    protected function createOptimizationRecommendation($campaign, string $type, array $data): void
    {
        $campaignId = $campaign->campaign_id ?? $campaign->id;

        // Check if similar recommendation exists in last 24 hours
        $exists = DB::table('cmis.optimization_recommendations')
            ->where('campaign_id', $campaignId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if (!$exists) {
            DB::table('cmis.optimization_recommendations')->insert([
                'org_id' => $campaign->org_id,
                'campaign_id' => $campaignId,
                'type' => $type,
                'data' => json_encode($data),
                'status' => 'pending',
                'created_at' => now(),
            ]);

            Log::info('Optimization recommendation created', [
                'campaign_id' => $campaignId,
                'type' => $type,
            ]);
        }
    }
}
