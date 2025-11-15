<?php

namespace App\Services\Campaign;

use App\Models\Core\Org;
use App\Models\Campaign;
use App\Models\AdPlatform\AdCampaign;
use App\Models\Social\SocialPost;
use App\Events\Campaign\CampaignCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifiedCampaignService
{
    /**
     * Create integrated marketing campaign
     * Combines: Ads + Content + Scheduling in one operation
     */
    public function createIntegratedCampaign(Org $org, array $data): Campaign
    {
        DB::beginTransaction();

        try {
            // 1. Create main campaign
            $campaign = Campaign::create([
                'org_id' => $org->org_id,
                'name' => $data['name'],
                'type' => 'integrated',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'total_budget' => $data['total_budget'] ?? 0,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
            ]);

            Log::info("Created integrated campaign", ['campaign_id' => $campaign->id]);

            // 2. Create ad campaigns if specified
            if (isset($data['ads']) && is_array($data['ads'])) {
                foreach ($data['ads'] as $adConfig) {
                    $this->createAdCampaign($campaign, $adConfig);
                }
            }

            // 3. Create content posts if specified
            if (isset($data['content']['posts']) && is_array($data['content']['posts'])) {
                foreach ($data['content']['posts'] as $postData) {
                    $this->createScheduledPost($campaign, $postData);
                }
            }

            // 4. Activate campaign
            if ($data['activate'] ?? false) {
                $campaign->update(['status' => 'active']);
            }

            DB::commit();

            // Fire event
            event(new CampaignCreated($campaign->adCampaigns()->first() ?? new AdCampaign()));

            Log::info("Integrated campaign created successfully", [
                'campaign_id' => $campaign->id,
                'ad_campaigns' => $campaign->adCampaigns()->count(),
                'social_posts' => $campaign->socialPosts()->count(),
            ]);

            return $campaign;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create integrated campaign", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create ad campaign component
     */
    private function createAdCampaign(Campaign $campaign, array $config): AdCampaign
    {
        // This would integrate with platform-specific services
        // Simplified version:
        return AdCampaign::create([
            'campaign_id' => $campaign->id,
            'org_id' => $campaign->org_id,
            'name' => $config['name'] ?? $campaign->name,
            'platform' => $config['platform'] ?? 'google',
            'budget' => $config['budget'] ?? 0,
            'status' => 'active',
            'objective' => $config['objective'] ?? null,
        ]);
    }

    /**
     * Create scheduled social post
     */
    private function createScheduledPost(Campaign $campaign, array $postData): SocialPost
    {
        return SocialPost::create([
            'campaign_id' => $campaign->id,
            'org_id' => $campaign->org_id,
            'content' => $postData['content'],
            'platforms' => $postData['platforms'] ?? ['facebook'],
            'status' => 'scheduled',
            'scheduled_for' => $postData['scheduled_for'] ?? now()->addDay(),
        ]);
    }

    /**
     * Get campaign with all components
     */
    public function getCampaignWithComponents(Campaign $campaign): array
    {
        return [
            'campaign' => $campaign,
            'ad_campaigns' => $campaign->adCampaigns()->with('integration')->get(),
            'social_posts' => $campaign->socialPosts()->get(),
            'metrics' => $this->getCampaignMetrics($campaign),
        ];
    }

    /**
     * Get aggregated campaign metrics
     */
    private function getCampaignMetrics(Campaign $campaign): array
    {
        $adMetrics = $campaign->adCampaigns()
            ->with('metrics')
            ->get()
            ->flatMap(fn($ac) => $ac->metrics)
            ->reduce(function ($carry, $metric) {
                $carry['total_spend'] += $metric->spend ?? 0;
                $carry['total_impressions'] += $metric->impressions ?? 0;
                $carry['total_clicks'] += $metric->clicks ?? 0;
                $carry['total_conversions'] += $metric->conversions ?? 0;
                return $carry;
            }, [
                'total_spend' => 0,
                'total_impressions' => 0,
                'total_clicks' => 0,
                'total_conversions' => 0,
            ]);

        $adMetrics['avg_ctr'] = $adMetrics['total_impressions'] > 0
            ? ($adMetrics['total_clicks'] / $adMetrics['total_impressions']) * 100
            : 0;

        $adMetrics['roi'] = $adMetrics['total_spend'] > 0
            ? (($adMetrics['total_conversions'] * 50) / $adMetrics['total_spend']) * 100
            : 0;

        return $adMetrics;
    }
}
