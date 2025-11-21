<?php

namespace App\Services\Optimization;

use App\Models\Campaign\Campaign;
use App\Models\Optimization\AudienceOverlap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AudienceAnalyzer
{
    /**
     * Detect audience overlaps across campaigns.
     */
    public function detectOverlaps(string $orgId, ?array $campaignIds = null): array
    {
        // Get campaigns to analyze
        $query = Campaign::where('org_id', $orgId)->where('status', 'active');

        if ($campaignIds) {
            $query->whereIn('campaign_id', $campaignIds);
        }

        $campaigns = $query->get();

        if ($campaigns->count() < 2) {
            return [];
        }

        $overlaps = [];

        // Compare each pair of campaigns
        for ($i = 0; $i < $campaigns->count(); $i++) {
            for ($j = $i + 1; $j < $campaigns->count(); $j++) {
                $campaignA = $campaigns[$i];
                $campaignB = $campaigns[$j];

                $overlap = $this->analyzeOverlap($orgId, $campaignA, $campaignB);

                if ($overlap && $overlap->overlap_percentage >= 10) { // Only save significant overlaps
                    $overlaps[] = $overlap;
                }
            }
        }

        return $overlaps;
    }

    /**
     * Analyze overlap between two campaigns.
     */
    protected function analyzeOverlap(string $orgId, Campaign $campaignA, Campaign $campaignB): ?AudienceOverlap
    {
        try {
            // Fetch audience data for both campaigns
            $audienceA = $this->getAudienceTargeting($campaignA);
            $audienceB = $this->getAudienceTargeting($campaignB);

            if (!$audienceA || !$audienceB) {
                return null;
            }

            // Calculate overlap using set intersection
            $overlapData = $this->calculateOverlap($audienceA, $audienceB);

            if ($overlapData['overlap_percentage'] < 1) {
                return null; // Skip minimal overlaps
            }

            // Estimate impact
            $impact = $this->estimateImpact(
                $campaignA,
                $campaignB,
                $overlapData['overlap_percentage'],
                $overlapData['overlap_size']
            );

            // Generate recommendations
            $recommendations = $this->generateRecommendations(
                $campaignA,
                $campaignB,
                $overlapData,
                $impact
            );

            // Create or update overlap record
            return AudienceOverlap::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'campaign_a_id' => $campaignA->campaign_id,
                    'campaign_b_id' => $campaignB->campaign_id,
                ],
                [
                    'entity_a_type' => 'campaign',
                    'entity_a_id' => $campaignA->campaign_id,
                    'entity_b_type' => 'campaign',
                    'entity_b_id' => $campaignB->campaign_id,
                    'overlap_percentage' => $overlapData['overlap_percentage'],
                    'overlap_size' => $overlapData['overlap_size'],
                    'audience_a_size' => $overlapData['audience_a_size'],
                    'audience_b_size' => $overlapData['audience_b_size'],
                    'severity' => $this->determineSeverity($overlapData['overlap_percentage']),
                    'impact_score' => $impact['score'],
                    'wasted_spend_estimate' => $impact['wasted_spend'],
                    'frequency_inflation' => $impact['frequency_inflation'],
                    'recommendations' => $recommendations,
                    'status' => 'active',
                    'detected_at' => now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error('Audience overlap analysis failed', [
                'campaign_a' => $campaignA->campaign_id,
                'campaign_b' => $campaignB->campaign_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get audience targeting configuration for a campaign.
     */
    protected function getAudienceTargeting(Campaign $campaign): ?array
    {
        // Fetch targeting criteria from campaign
        $targeting = $campaign->targeting_config ?? [];

        if (empty($targeting)) {
            return null;
        }

        // Build audience profile
        $audience = [
            'campaign_id' => $campaign->campaign_id,
            'demographics' => $targeting['demographics'] ?? [],
            'interests' => $targeting['interests'] ?? [],
            'behaviors' => $targeting['behaviors'] ?? [],
            'custom_audiences' => $targeting['custom_audiences'] ?? [],
            'lookalike_audiences' => $targeting['lookalike_audiences'] ?? [],
            'geo_locations' => $targeting['geo_locations'] ?? [],
            'age_range' => $targeting['age_range'] ?? [],
            'gender' => $targeting['gender'] ?? [],
            'estimated_size' => $this->estimateAudienceSize($targeting),
        ];

        return $audience;
    }

    /**
     * Calculate overlap between two audiences using set intersection.
     */
    protected function calculateOverlap(array $audienceA, array $audienceB): array
    {
        $sizeA = $audienceA['estimated_size'];
        $sizeB = $audienceB['estimated_size'];

        // Calculate overlap across different targeting dimensions
        $overlapScores = [];

        // Demographics overlap
        $overlapScores['demographics'] = $this->calculateSetOverlap(
            $audienceA['demographics'],
            $audienceB['demographics']
        );

        // Interests overlap
        $overlapScores['interests'] = $this->calculateSetOverlap(
            $audienceA['interests'],
            $audienceB['interests']
        );

        // Behaviors overlap
        $overlapScores['behaviors'] = $this->calculateSetOverlap(
            $audienceA['behaviors'],
            $audienceB['behaviors']
        );

        // Geo overlap
        $overlapScores['geo'] = $this->calculateSetOverlap(
            $audienceA['geo_locations'],
            $audienceB['geo_locations']
        );

        // Custom/Lookalike audiences
        $overlapScores['custom_audiences'] = $this->calculateSetOverlap(
            array_merge($audienceA['custom_audiences'], $audienceA['lookalike_audiences']),
            array_merge($audienceB['custom_audiences'], $audienceB['lookalike_audiences'])
        );

        // Weighted average overlap (interests and behaviors are more important)
        $weights = [
            'demographics' => 0.15,
            'interests' => 0.30,
            'behaviors' => 0.25,
            'geo' => 0.10,
            'custom_audiences' => 0.20,
        ];

        $totalOverlap = 0;
        $totalWeight = 0;

        foreach ($weights as $dimension => $weight) {
            if (isset($overlapScores[$dimension])) {
                $totalOverlap += $overlapScores[$dimension] * $weight;
                $totalWeight += $weight;
            }
        }

        $overlapPercentage = $totalWeight > 0 ? ($totalOverlap / $totalWeight) * 100 : 0;

        // Estimate overlap size using Jaccard index approximation
        $union = $sizeA + $sizeB;
        $jaccardIndex = $overlapPercentage / 100;
        $overlapSize = (int) (($jaccardIndex * $union) / (1 + $jaccardIndex));

        return [
            'overlap_percentage' => round($overlapPercentage, 2),
            'overlap_size' => $overlapSize,
            'audience_a_size' => $sizeA,
            'audience_b_size' => $sizeB,
            'dimension_scores' => $overlapScores,
        ];
    }

    /**
     * Calculate set overlap using Jaccard similarity.
     */
    protected function calculateSetOverlap(array $setA, array $setB): float
    {
        if (empty($setA) && empty($setB)) {
            return 0.0;
        }

        if (empty($setA) || empty($setB)) {
            return 0.0;
        }

        $intersection = count(array_intersect($setA, $setB));
        $union = count(array_unique(array_merge($setA, $setB)));

        return $union > 0 ? ($intersection / $union) : 0.0;
    }

    /**
     * Estimate audience size from targeting configuration.
     */
    protected function estimateAudienceSize(array $targeting): int
    {
        // Simplified estimation logic
        // In production, this should call platform APIs for accurate estimates
        $baseSize = 1000000; // 1M baseline

        // Narrow down based on targeting criteria
        if (!empty($targeting['geo_locations'])) {
            $baseSize *= 0.3; // Geographic targeting reduces 70%
        }

        if (!empty($targeting['age_range'])) {
            $baseSize *= 0.5; // Age targeting reduces 50%
        }

        if (!empty($targeting['interests'])) {
            $baseSize *= 0.4; // Interest targeting reduces 60%
        }

        if (!empty($targeting['custom_audiences'])) {
            return count($targeting['custom_audiences']) * 10000; // Custom audiences have known size
        }

        return (int) $baseSize;
    }

    /**
     * Estimate impact of audience overlap.
     */
    protected function estimateImpact(Campaign $campaignA, Campaign $campaignB, float $overlapPercentage, int $overlapSize): array
    {
        $budgetA = $campaignA->daily_budget ?? 0;
        $budgetB = $campaignB->daily_budget ?? 0;
        $totalBudget = $budgetA + $budgetB;

        // Estimate wasted spend due to overlap
        $overlapRatio = $overlapPercentage / 100;
        $wastedSpend = $totalBudget * $overlapRatio * 0.3; // 30% of overlap budget is wasted

        // Estimate frequency inflation
        $frequencyInflation = 1 + ($overlapRatio * 1.5); // Overlapping users see ads 1.5x more

        // Calculate impact score (0-1)
        $impactScore = min(1.0, ($overlapRatio * 0.6) + ($wastedSpend / max($totalBudget, 1)) * 0.4);

        return [
            'score' => round($impactScore, 4),
            'wasted_spend' => round($wastedSpend, 2),
            'frequency_inflation' => round($frequencyInflation, 2),
        ];
    }

    /**
     * Determine severity level based on overlap percentage.
     */
    protected function determineSeverity(float $overlapPercentage): string
    {
        if ($overlapPercentage >= 75) {
            return 'critical';
        } elseif ($overlapPercentage >= 50) {
            return 'high';
        } elseif ($overlapPercentage >= 25) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Generate recommendations to resolve overlap.
     */
    protected function generateRecommendations(Campaign $campaignA, Campaign $campaignB, array $overlapData, array $impact): array
    {
        $recommendations = [];
        $overlapPercentage = $overlapData['overlap_percentage'];

        if ($overlapPercentage >= 75) {
            $recommendations[] = [
                'action' => 'consolidate_campaigns',
                'description' => 'Consider consolidating these campaigns due to extreme overlap (>75%).',
                'priority' => 'critical',
                'estimated_savings' => $impact['wasted_spend'],
            ];
        } elseif ($overlapPercentage >= 50) {
            $recommendations[] = [
                'action' => 'exclude_audiences',
                'description' => 'Add audience exclusions to prevent overlap between campaigns.',
                'priority' => 'high',
                'estimated_savings' => $impact['wasted_spend'] * 0.7,
            ];
        } elseif ($overlapPercentage >= 25) {
            $recommendations[] = [
                'action' => 'refine_targeting',
                'description' => 'Refine targeting criteria to reduce audience overlap.',
                'priority' => 'medium',
                'estimated_savings' => $impact['wasted_spend'] * 0.5,
            ];
        } else {
            $recommendations[] = [
                'action' => 'monitor',
                'description' => 'Overlap is minimal but monitor for changes over time.',
                'priority' => 'low',
                'estimated_savings' => 0,
            ];
        }

        // Add frequency cap recommendation if inflation is high
        if ($impact['frequency_inflation'] > 2.0) {
            $recommendations[] = [
                'action' => 'set_frequency_cap',
                'description' => sprintf(
                    'Set frequency cap to prevent overexposure (current inflation: %.1fx).',
                    $impact['frequency_inflation']
                ),
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }
}
