<?php

namespace App\Services\Influencer;

use App\Models\Influencer\InfluencerCampaign;
use App\Models\Influencer\Influencer;
use App\Models\Influencer\InfluencerContent;
use App\Models\Influencer\InfluencerPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InfluencerCampaignService
{
    /**
     * Create campaign with influencer assignments
     */
    public function createCampaign(array $data, array $influencerIds): InfluencerCampaign
    {
        DB::beginTransaction();
        try {
            $campaign = InfluencerCampaign::create(array_merge($data, [
                'org_id' => session('current_org_id'),
            ]));

            // Assign influencers to campaign
            foreach ($influencerIds as $influencerId) {
                $this->assignInfluencer($campaign, $influencerId);
            }

            DB::commit();
            return $campaign->fresh(['influencer']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign influencer to campaign
     */
    public function assignInfluencer(InfluencerCampaign $campaign, string $influencerId): void
    {
        $influencer = Influencer::findOrFail($influencerId);

        // Update campaign with influencer if not set
        if (!$campaign->influencer_id) {
            $campaign->update(['influencer_id' => $influencerId]);
        }

        // Create initial payment record if budget is set
        if ($campaign->budget > 0) {
            InfluencerPayment::create([
                'org_id' => $campaign->org_id,
                'campaign_id' => $campaign->campaign_id,
                'influencer_id' => $influencerId,
                'amount' => $campaign->budget,
                'currency' => $campaign->currency ?? 'USD',
                'status' => 'pending',
                'due_date' => $campaign->end_date,
            ]);
        }
    }

    /**
     * Calculate campaign ROI
     */
    public function calculateROI(InfluencerCampaign $campaign): float
    {
        $totalSpend = $campaign->budget ?? 0;
        $totalRevenue = $campaign->total_conversions * ($campaign->conversion_value ?? 0);

        if ($totalSpend <= 0) {
            return 0;
        }

        return round((($totalRevenue - $totalSpend) / $totalSpend) * 100, 2);
    }

    /**
     * Calculate engagement rate
     */
    public function calculateEngagementRate(InfluencerCampaign $campaign): float
    {
        $totalReach = $campaign->total_reach ?? 0;
        $totalEngagement = $campaign->total_engagement ?? 0;

        if ($totalReach <= 0) {
            return 0;
        }

        return round(($totalEngagement / $totalReach) * 100, 2);
    }

    /**
     * Calculate cost per engagement
     */
    public function calculateCostPerEngagement(InfluencerCampaign $campaign): float
    {
        $totalSpend = $campaign->budget ?? 0;
        $totalEngagement = $campaign->total_engagement ?? 0;

        if ($totalEngagement <= 0) {
            return 0;
        }

        return round($totalSpend / $totalEngagement, 2);
    }

    /**
     * Get campaign performance breakdown
     */
    public function getPerformanceBreakdown(InfluencerCampaign $campaign): array
    {
        $content = InfluencerContent::where('campaign_id', $campaign->campaign_id)
            ->whereNotNull('published_at')
            ->get();

        $breakdown = [
            'total_content' => $content->count(),
            'total_reach' => $content->sum('total_reach'),
            'total_impressions' => $content->sum('total_impressions'),
            'total_engagement' => $content->sum('total_engagement'),
            'total_clicks' => $content->sum('total_clicks'),
            'total_conversions' => $content->sum('total_conversions'),
            'by_content_type' => [],
            'by_platform' => [],
        ];

        // Breakdown by content type
        $byType = $content->groupBy('content_type');
        foreach ($byType as $type => $items) {
            $breakdown['by_content_type'][$type] = [
                'count' => $items->count(),
                'reach' => $items->sum('total_reach'),
                'engagement' => $items->sum('total_engagement'),
            ];
        }

        // Breakdown by platform
        $byPlatform = $content->groupBy('platform');
        foreach ($byPlatform as $platform => $items) {
            $breakdown['by_platform'][$platform] = [
                'count' => $items->count(),
                'reach' => $items->sum('total_reach'),
                'engagement' => $items->sum('total_engagement'),
            ];
        }

        return $breakdown;
    }

    /**
     * Track campaign progress
     */
    public function trackProgress(InfluencerCampaign $campaign): array
    {
        $startDate = $campaign->start_date;
        $endDate = $campaign->end_date;
        $now = now();

        if (!$startDate || !$endDate) {
            return [
                'status' => 'not_scheduled',
                'progress_percentage' => 0,
                'days_remaining' => null,
                'completion_status' => 'pending',
            ];
        }

        $totalDays = $startDate->diffInDays($endDate);
        $daysElapsed = $startDate->diffInDays(min($now, $endDate));
        $progressPercentage = $totalDays > 0 ? round(($daysElapsed / $totalDays) * 100, 2) : 0;

        $daysRemaining = max(0, $now->diffInDays($endDate, false));

        // Determine completion status
        $completionStatus = 'in_progress';
        if ($now < $startDate) {
            $completionStatus = 'not_started';
        } elseif ($now > $endDate) {
            $completionStatus = 'ended';
        }

        return [
            'status' => $campaign->status,
            'progress_percentage' => $progressPercentage,
            'days_remaining' => $daysRemaining,
            'completion_status' => $completionStatus,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    /**
     * Get content approval statistics
     */
    public function getContentApprovalStats(InfluencerCampaign $campaign): array
    {
        $content = InfluencerContent::where('campaign_id', $campaign->campaign_id)->get();

        $total = $content->count();
        $approved = $content->where('approval_status', 'approved')->count();
        $pending = $content->where('approval_status', 'pending')->count();
        $rejected = $content->where('approval_status', 'rejected')->count();
        $revisionRequested = $content->where('approval_status', 'revision_requested')->count();

        return [
            'total_content' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'revision_requested' => $revisionRequested,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get payment status for campaign
     */
    public function getPaymentStatus(InfluencerCampaign $campaign): array
    {
        $payments = InfluencerPayment::where('campaign_id', $campaign->campaign_id)->get();

        $total = $payments->count();
        $completed = $payments->where('status', 'completed')->count();
        $pending = $payments->where('status', 'pending')->count();
        $processing = $payments->where('status', 'processing')->count();
        $failed = $payments->where('status', 'failed')->count();

        $totalAmount = $payments->sum('amount');
        $paidAmount = $payments->where('status', 'completed')->sum('amount');
        $pendingAmount = $payments->where('status', 'pending')->sum('amount');

        return [
            'total_payments' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'processing' => $processing,
            'failed' => $failed,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'payment_completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Analyze campaign effectiveness
     */
    public function analyzeCampaignEffectiveness(InfluencerCampaign $campaign): array
    {
        $performance = $this->getPerformanceBreakdown($campaign);
        $budget = $campaign->budget ?? 0;

        $reach = $performance['total_reach'];
        $engagement = $performance['total_engagement'];
        $conversions = $performance['total_conversions'];

        $costPerReach = $reach > 0 ? round($budget / $reach, 4) : 0;
        $costPerEngagement = $engagement > 0 ? round($budget / $engagement, 2) : 0;
        $costPerConversion = $conversions > 0 ? round($budget / $conversions, 2) : 0;
        $engagementRate = $reach > 0 ? round(($engagement / $reach) * 100, 2) : 0;
        $conversionRate = $engagement > 0 ? round(($conversions / $engagement) * 100, 2) : 0;

        // Calculate effectiveness score (0-100)
        $effectivenessScore = $this->calculateEffectivenessScore($campaign);

        return [
            'budget' => $budget,
            'reach' => $reach,
            'engagement' => $engagement,
            'conversions' => $conversions,
            'cost_per_reach' => $costPerReach,
            'cost_per_engagement' => $costPerEngagement,
            'cost_per_conversion' => $costPerConversion,
            'engagement_rate' => $engagementRate,
            'conversion_rate' => $conversionRate,
            'roi' => $this->calculateROI($campaign),
            'effectiveness_score' => $effectivenessScore,
        ];
    }

    /**
     * Calculate effectiveness score
     */
    protected function calculateEffectivenessScore(InfluencerCampaign $campaign): float
    {
        $weights = [
            'roi' => 0.30,
            'engagement_rate' => 0.25,
            'conversion_rate' => 0.25,
            'content_approval' => 0.10,
            'on_time_completion' => 0.10,
        ];

        // ROI score (normalize to 0-100)
        $roi = $this->calculateROI($campaign);
        $roiScore = max(0, min(100, 50 + $roi));

        // Engagement rate score
        $engagementRate = $this->calculateEngagementRate($campaign);
        $engagementScore = min(100, $engagementRate * 10);

        // Conversion rate score (assuming 2% is excellent)
        $performance = $this->getPerformanceBreakdown($campaign);
        $conversionRate = $performance['total_engagement'] > 0
            ? ($performance['total_conversions'] / $performance['total_engagement']) * 100
            : 0;
        $conversionScore = min(100, $conversionRate * 50);

        // Content approval score
        $approvalStats = $this->getContentApprovalStats($campaign);
        $approvalScore = $approvalStats['approval_rate'];

        // On-time completion score
        $progress = $this->trackProgress($campaign);
        $onTimeScore = 100; // Default to perfect if campaign is on track
        if ($progress['completion_status'] === 'ended' && $campaign->status !== 'completed') {
            $onTimeScore = 0; // Penalize if campaign ended but not completed
        }

        $totalScore = (
            ($roiScore * $weights['roi']) +
            ($engagementScore * $weights['engagement_rate']) +
            ($conversionScore * $weights['conversion_rate']) +
            ($approvalScore * $weights['content_approval']) +
            ($onTimeScore * $weights['on_time_completion'])
        );

        return round($totalScore, 2);
    }

    /**
     * Generate campaign recommendations
     */
    public function generateRecommendations(InfluencerCampaign $campaign): array
    {
        $effectiveness = $this->analyzeCampaignEffectiveness($campaign);
        $approvalStats = $this->getContentApprovalStats($campaign);
        $recommendations = [];

        // ROI recommendations
        if ($effectiveness['roi'] < 0) {
            $recommendations[] = [
                'type' => 'roi',
                'priority' => 'high',
                'message' => 'Campaign ROI is negative. Consider adjusting targeting or content strategy.',
            ];
        }

        // Engagement recommendations
        if ($effectiveness['engagement_rate'] < 2) {
            $recommendations[] = [
                'type' => 'engagement',
                'priority' => 'medium',
                'message' => 'Engagement rate is below industry average. Review content quality and posting times.',
            ];
        }

        // Content approval recommendations
        if ($approvalStats['approval_rate'] < 70) {
            $recommendations[] = [
                'type' => 'content_approval',
                'priority' => 'medium',
                'message' => 'Content approval rate is low. Improve influencer briefing and guidelines.',
            ];
        }

        // Budget utilization
        $paymentStatus = $this->getPaymentStatus($campaign);
        if ($paymentStatus['pending_amount'] > $paymentStatus['total_amount'] * 0.5) {
            $recommendations[] = [
                'type' => 'payment',
                'priority' => 'low',
                'message' => 'More than 50% of payments are pending. Ensure timely payment processing.',
            ];
        }

        return $recommendations;
    }

    /**
     * Compare campaign performance
     */
    public function compareCampaigns(array $campaignIds): array
    {
        $campaigns = InfluencerCampaign::whereIn('campaign_id', $campaignIds)->get();

        $comparison = [];

        foreach ($campaigns as $campaign) {
            $effectiveness = $this->analyzeCampaignEffectiveness($campaign);

            $comparison[] = [
                'campaign_id' => $campaign->campaign_id,
                'name' => $campaign->name,
                'type' => $campaign->campaign_type,
                'status' => $campaign->status,
                'budget' => $campaign->budget,
                'reach' => $effectiveness['reach'],
                'engagement' => $effectiveness['engagement'],
                'conversions' => $effectiveness['conversions'],
                'roi' => $effectiveness['roi'],
                'engagement_rate' => $effectiveness['engagement_rate'],
                'effectiveness_score' => $effectiveness['effectiveness_score'],
            ];
        }

        return $comparison;
    }

    /**
     * Export campaign report
     */
    public function exportCampaignReport(InfluencerCampaign $campaign): array
    {
        return [
            'campaign' => [
                'name' => $campaign->name,
                'type' => $campaign->campaign_type,
                'status' => $campaign->status,
                'budget' => $campaign->budget,
                'start_date' => $campaign->start_date?->toDateString(),
                'end_date' => $campaign->end_date?->toDateString(),
            ],
            'influencer' => [
                'name' => $campaign->influencer->name,
                'platform' => $campaign->influencer->platform,
                'followers' => $campaign->influencer->followers_count,
            ],
            'progress' => $this->trackProgress($campaign),
            'performance' => $this->getPerformanceBreakdown($campaign),
            'effectiveness' => $this->analyzeCampaignEffectiveness($campaign),
            'content_approval' => $this->getContentApprovalStats($campaign),
            'payment_status' => $this->getPaymentStatus($campaign),
            'recommendations' => $this->generateRecommendations($campaign),
            'exported_at' => now()->toIso8601String(),
        ];
    }
}
