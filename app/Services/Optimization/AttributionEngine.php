<?php

namespace App\Services\Optimization;

use App\Models\Optimization\AttributionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttributionEngine
{
    /**
     * Calculate attribution for a conversion across all touchpoints.
     */
    public function attributeConversion(
        string $orgId,
        string $conversionId,
        array $touchpoints,
        float $conversionValue,
        string $modelType = 'data_driven',
        int $lookbackDays = 30
    ): AttributionModel {
        // Sort touchpoints by timestamp
        usort($touchpoints, fn($a, $b) => strtotime($a['timestamp']) <=> strtotime($b['timestamp']));

        // Filter touchpoints within lookback window
        $cutoffDate = now()->subDays($lookbackDays);
        $touchpoints = array_filter($touchpoints, function($tp) use ($cutoffDate) {
            return strtotime($tp['timestamp']) >= $cutoffDate->timestamp;
        });

        if (empty($touchpoints)) {
            throw new \Exception('No touchpoints found within lookback window');
        }

        // Calculate attribution weights based on model type
        $attributionWeights = $this->calculateAttributionWeights($touchpoints, $modelType);

        // Distribute conversion value across touchpoints
        $attributedRevenue = [];
        foreach ($attributionWeights as $campaignId => $weight) {
            $attributedRevenue[$campaignId] = $conversionValue * $weight;
        }

        // Extract first and last touch campaigns
        $firstTouch = reset($touchpoints);
        $lastTouch = end($touchpoints);

        // Create attribution record
        return AttributionModel::create([
            'org_id' => $orgId,
            'conversion_id' => $conversionId,
            'conversion_value' => $conversionValue,
            'conversion_date' => now(),
            'model_type' => $modelType,
            'touchpoints' => $touchpoints,
            'touchpoint_count' => count($touchpoints),
            'attribution_weights' => $attributionWeights,
            'attributed_revenue' => $conversionValue,
            'first_touch_campaign_id' => $firstTouch['campaign_id'] ?? null,
            'last_touch_campaign_id' => $lastTouch['campaign_id'] ?? null,
            'lookback_window_days' => $lookbackDays,
            'confidence_score' => $this->calculateConfidence($touchpoints, $modelType),
        ]);
    }

    /**
     * Calculate attribution weights based on model type.
     */
    protected function calculateAttributionWeights(array $touchpoints, string $modelType): array
    {
        return match($modelType) {
            'first_touch' => $this->firstTouchAttribution($touchpoints),
            'last_touch' => $this->lastTouchAttribution($touchpoints),
            'linear' => $this->linearAttribution($touchpoints),
            'time_decay' => $this->timeDecayAttribution($touchpoints),
            'position_based' => $this->positionBasedAttribution($touchpoints),
            'data_driven' => $this->dataDrivenAttribution($touchpoints),
            default => $this->linearAttribution($touchpoints),
        };
    }

    /**
     * First Touch Attribution - 100% credit to first touchpoint.
     */
    protected function firstTouchAttribution(array $touchpoints): array
    {
        $first = reset($touchpoints);
        $campaignId = $first['campaign_id'] ?? 'unknown';

        return [$campaignId => 1.0];
    }

    /**
     * Last Touch Attribution - 100% credit to last touchpoint.
     */
    protected function lastTouchAttribution(array $touchpoints): array
    {
        $last = end($touchpoints);
        $campaignId = $last['campaign_id'] ?? 'unknown';

        return [$campaignId => 1.0];
    }

    /**
     * Linear Attribution - Equal credit to all touchpoints.
     */
    protected function linearAttribution(array $touchpoints): array
    {
        $weights = [];
        $count = count($touchpoints);
        $equalWeight = 1.0 / $count;

        foreach ($touchpoints as $touchpoint) {
            $campaignId = $touchpoint['campaign_id'] ?? 'unknown';
            $weights[$campaignId] = ($weights[$campaignId] ?? 0) + $equalWeight;
        }

        return $weights;
    }

    /**
     * Time Decay Attribution - More credit to recent touchpoints.
     */
    protected function timeDecayAttribution(array $touchpoints, float $halfLife = 7.0): array
    {
        $weights = [];
        $totalWeight = 0;

        $conversionTime = strtotime($touchpoints[count($touchpoints) - 1]['timestamp']);

        foreach ($touchpoints as $touchpoint) {
            $touchTime = strtotime($touchpoint['timestamp']);
            $daysSince = ($conversionTime - $touchTime) / 86400;

            // Exponential decay: weight = 2^(-days / half_life)
            $weight = pow(2, -$daysSince / $halfLife);

            $campaignId = $touchpoint['campaign_id'] ?? 'unknown';
            $weights[$campaignId] = ($weights[$campaignId] ?? 0) + $weight;
            $totalWeight += $weight;
        }

        // Normalize weights to sum to 1.0
        foreach ($weights as $campaignId => $weight) {
            $weights[$campaignId] = $weight / $totalWeight;
        }

        return $weights;
    }

    /**
     * Position-Based (U-Shaped) Attribution - 40% first, 40% last, 20% middle.
     */
    protected function positionBasedAttribution(array $touchpoints): array
    {
        $weights = [];
        $count = count($touchpoints);

        if ($count === 1) {
            $campaignId = $touchpoints[0]['campaign_id'] ?? 'unknown';
            return [$campaignId => 1.0];
        }

        if ($count === 2) {
            $first = $touchpoints[0]['campaign_id'] ?? 'unknown';
            $last = $touchpoints[1]['campaign_id'] ?? 'unknown';
            return [
                $first => 0.5,
                $last => 0.5,
            ];
        }

        // First touch: 40%
        $firstId = $touchpoints[0]['campaign_id'] ?? 'unknown';
        $weights[$firstId] = ($weights[$firstId] ?? 0) + 0.4;

        // Last touch: 40%
        $lastId = $touchpoints[$count - 1]['campaign_id'] ?? 'unknown';
        $weights[$lastId] = ($weights[$lastId] ?? 0) + 0.4;

        // Middle touches: 20% distributed equally
        $middleWeight = 0.2 / ($count - 2);
        for ($i = 1; $i < $count - 1; $i++) {
            $campaignId = $touchpoints[$i]['campaign_id'] ?? 'unknown';
            $weights[$campaignId] = ($weights[$campaignId] ?? 0) + $middleWeight;
        }

        return $weights;
    }

    /**
     * Data-Driven Attribution using Shapley Value approximation.
     */
    protected function dataDrivenAttribution(array $touchpoints): array
    {
        // Simplified Shapley Value calculation
        // In production, this should use historical conversion data

        $weights = [];
        $count = count($touchpoints);

        // Extract unique campaigns
        $campaigns = array_unique(array_column($touchpoints, 'campaign_id'));

        // Calculate marginal contribution for each campaign
        foreach ($campaigns as $campaign) {
            $marginalContribution = $this->calculateMarginalContribution($campaign, $touchpoints);
            $weights[$campaign] = $marginalContribution;
        }

        // Normalize weights
        $totalWeight = array_sum($weights);
        if ($totalWeight > 0) {
            foreach ($weights as $campaignId => $weight) {
                $weights[$campaignId] = $weight / $totalWeight;
            }
        }

        return $weights;
    }

    /**
     * Calculate marginal contribution of a campaign using Shapley Value.
     */
    protected function calculateMarginalContribution(string $targetCampaign, array $touchpoints): float
    {
        $contribution = 0;
        $touchpointIndices = [];

        // Find all indices where target campaign appears
        foreach ($touchpoints as $index => $touchpoint) {
            if (($touchpoint['campaign_id'] ?? '') === $targetCampaign) {
                $touchpointIndices[] = $index;
            }
        }

        if (empty($touchpointIndices)) {
            return 0;
        }

        // Simplified contribution: weighted by position and interaction type
        foreach ($touchpointIndices as $index) {
            $touchpoint = $touchpoints[$index];
            $position = $index / (count($touchpoints) - 1 ?: 1); // 0 to 1

            // Base contribution
            $baseContribution = 1.0 / count($touchpointIndices);

            // Position factor (first and last are more valuable)
            $positionFactor = 1.0;
            if ($position === 0) {
                $positionFactor = 1.5; // First touch bonus
            } elseif ($position === 1.0) {
                $positionFactor = 1.8; // Last touch bonus
            }

            // Interaction type factor
            $interactionFactor = match($touchpoint['interaction_type'] ?? 'view') {
                'click' => 1.5,
                'view' => 1.0,
                'engagement' => 1.3,
                default => 1.0,
            };

            $contribution += $baseContribution * $positionFactor * $interactionFactor;
        }

        return $contribution;
    }

    /**
     * Calculate confidence score for attribution.
     */
    protected function calculateConfidence(array $touchpoints, string $modelType): float
    {
        $touchpointCount = count($touchpoints);

        // More touchpoints = higher confidence (up to a point)
        $touchpointFactor = min($touchpointCount / 5, 1.0);

        // Data-driven models require more data for confidence
        $modelFactor = match($modelType) {
            'data_driven' => 0.7,
            'position_based' => 0.85,
            'time_decay' => 0.85,
            'linear' => 0.9,
            'first_touch', 'last_touch' => 0.95,
            default => 0.8,
        };

        // Check for data quality
        $hasTimestamps = !empty(array_filter($touchpoints, fn($tp) => isset($tp['timestamp'])));
        $hasInteractionTypes = !empty(array_filter($touchpoints, fn($tp) => isset($tp['interaction_type'])));

        $dataQualityFactor = ($hasTimestamps ? 0.5 : 0) + ($hasInteractionTypes ? 0.5 : 0);

        $confidence = $touchpointFactor * $modelFactor * (0.5 + 0.5 * $dataQualityFactor);

        return round($confidence, 4);
    }

    /**
     * Generate attribution report for an organization.
     */
    public function generateAttributionReport(string $orgId, int $days = 30): array
    {
        $attributions = AttributionModel::where('org_id', $orgId)
            ->where('conversion_date', '>=', now()->subDays($days))
            ->get();

        $report = [
            'total_conversions' => $attributions->count(),
            'total_revenue' => $attributions->sum('conversion_value'),
            'by_model_type' => [],
            'by_campaign' => [],
            'top_contributing_campaigns' => [],
        ];

        // Group by model type
        foreach ($attributions->groupBy('model_type') as $modelType => $group) {
            $report['by_model_type'][$modelType] = [
                'count' => $group->count(),
                'revenue' => $group->sum('conversion_value'),
                'avg_touchpoints' => round($group->avg('touchpoint_count'), 2),
            ];
        }

        // Aggregate by campaign
        $campaignRevenue = [];
        foreach ($attributions as $attribution) {
            foreach ($attribution->attribution_weights as $campaignId => $weight) {
                if (!isset($campaignRevenue[$campaignId])) {
                    $campaignRevenue[$campaignId] = 0;
                }
                $campaignRevenue[$campaignId] += $attribution->conversion_value * $weight;
            }
        }

        arsort($campaignRevenue);
        $report['by_campaign'] = $campaignRevenue;
        $report['top_contributing_campaigns'] = array_slice($campaignRevenue, 0, 10, true);

        return $report;
    }
}
