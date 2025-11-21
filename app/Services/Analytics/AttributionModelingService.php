<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

/**
 * Attribution Modeling Service (Phase 7)
 *
 * Multi-touch attribution modeling for conversion tracking
 */
class AttributionModelingService
{
    // Attribution models
    const MODEL_LAST_CLICK = 'last_click';
    const MODEL_FIRST_CLICK = 'first_click';
    const MODEL_LINEAR = 'linear';
    const MODEL_TIME_DECAY = 'time_decay';
    const MODEL_POSITION_BASED = 'position_based';
    const MODEL_DATA_DRIVEN = 'data_driven';

    // Touchpoint types
    const TOUCHPOINT_IMPRESSION = 'impression';
    const TOUCHPOINT_CLICK = 'click';
    const TOUCHPOINT_ENGAGEMENT = 'engagement';
    const TOUCHPOINT_VIEW = 'view';

    /**
     * Attribute conversions using specified model
     *
     * @param string $campaignId
     * @param string $model
     * @param array $dateRange
     * @return array
     */
    public function attributeConversions(
        string $campaignId,
        string $model = self::MODEL_LAST_CLICK,
        array $dateRange = []
    ): array {
        try {
            $startDate = $dateRange['start'] ?? Carbon::now()->subDays(30);
            $endDate = $dateRange['end'] ?? Carbon::now();

            // Get conversion paths
            $conversionPaths = $this->getConversionPaths($campaignId, $startDate, $endDate);

            if (empty($conversionPaths)) {
                return [
                    'success' => false,
                    'error' => 'No conversion paths found'
                ];
            }

            // Apply attribution model
            $attributedConversions = match($model) {
                self::MODEL_LAST_CLICK => $this->applyLastClickAttribution($conversionPaths),
                self::MODEL_FIRST_CLICK => $this->applyFirstClickAttribution($conversionPaths),
                self::MODEL_LINEAR => $this->applyLinearAttribution($conversionPaths),
                self::MODEL_TIME_DECAY => $this->applyTimeDecayAttribution($conversionPaths),
                self::MODEL_POSITION_BASED => $this->applyPositionBasedAttribution($conversionPaths),
                self::MODEL_DATA_DRIVEN => $this->applyDataDrivenAttribution($conversionPaths),
                default => $this->applyLastClickAttribution($conversionPaths)
            };

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'attribution_model' => $model,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'attributed_conversions' => $attributedConversions,
                'total_conversions' => count($conversionPaths),
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to attribute conversions', [
                'campaign_id' => $campaignId,
                'model' => $model,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get conversion paths with touchpoints
     *
     * @param string $campaignId
     * @param $startDate
     * @param $endDate
     * @return array
     */
    protected function getConversionPaths(string $campaignId, $startDate, $endDate): array
    {
        // Simulate conversion path data (in production, this would come from tracking data)
        $conversions = DB::table('cmis_analytics.campaign_performance')
            ->where('campaign_id', $campaignId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('conversions', '>', 0)
            ->select('date', 'conversions', 'spend', 'revenue')
            ->get();

        $paths = [];

        foreach ($conversions as $conversion) {
            // In production, fetch actual touchpoint sequence from tracking data
            // For now, simulate a multi-touch path
            $touchpoints = $this->simulateTouchpoints($campaignId, $conversion->date);

            for ($i = 0; $i < $conversion->conversions; $i++) {
                $paths[] = [
                    'conversion_id' => uniqid('conv_'),
                    'conversion_date' => $conversion->date,
                    'conversion_value' => $conversion->revenue / $conversion->conversions,
                    'touchpoints' => $touchpoints
                ];
            }
        }

        return $paths;
    }

    /**
     * Simulate touchpoints for demonstration
     *
     * @param string $campaignId
     * @param $conversionDate
     * @return array
     */
    protected function simulateTouchpoints(string $campaignId, $conversionDate): array
    {
        $numTouchpoints = rand(2, 5);
        $touchpoints = [];
        $conversionTimestamp = Carbon::parse($conversionDate)->timestamp;

        for ($i = $numTouchpoints - 1; $i >= 0; $i--) {
            $daysBack = $i * rand(1, 3);
            $timestamp = $conversionTimestamp - ($daysBack * 86400);

            $touchpoints[] = [
                'touchpoint_id' => uniqid('touch_'),
                'campaign_id' => $campaignId,
                'type' => $this->randomTouchpointType(),
                'timestamp' => $timestamp,
                'date' => Carbon::createFromTimestamp($timestamp)->toDateString(),
                'channel' => $this->randomChannel(),
                'ad_id' => uniqid('ad_')
            ];
        }

        // Sort by timestamp (oldest first)
        usort($touchpoints, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return $touchpoints;
    }

    /**
     * Get random touchpoint type
     *
     * @return string
     */
    protected function randomTouchpointType(): string
    {
        $types = [self::TOUCHPOINT_IMPRESSION, self::TOUCHPOINT_CLICK, self::TOUCHPOINT_ENGAGEMENT, self::TOUCHPOINT_VIEW];
        return $types[array_rand($types)];
    }

    /**
     * Get random channel
     *
     * @return string
     */
    protected function randomChannel(): string
    {
        $channels = ['social', 'search', 'display', 'email', 'video'];
        return $channels[array_rand($channels)];
    }

    /**
     * Apply last-click attribution
     *
     * @param array $conversionPaths
     * @return array
     */
    protected function applyLastClickAttribution(array $conversionPaths): array
    {
        $attribution = [];

        foreach ($conversionPaths as $path) {
            // Last touchpoint gets 100% credit
            $lastTouchpoint = end($path['touchpoints']);

            $key = $lastTouchpoint['campaign_id'] . ':' . $lastTouchpoint['channel'];

            if (!isset($attribution[$key])) {
                $attribution[$key] = [
                    'campaign_id' => $lastTouchpoint['campaign_id'],
                    'channel' => $lastTouchpoint['channel'],
                    'conversions' => 0,
                    'conversion_value' => 0
                ];
            }

            $attribution[$key]['conversions'] += 1;
            $attribution[$key]['conversion_value'] += $path['conversion_value'];
        }

        return array_values($attribution);
    }

    /**
     * Apply first-click attribution
     *
     * @param array $conversionPaths
     * @return array
     */
    protected function applyFirstClickAttribution(array $conversionPaths): array
    {
        $attribution = [];

        foreach ($conversionPaths as $path) {
            // First touchpoint gets 100% credit
            $firstTouchpoint = $path['touchpoints'][0];

            $key = $firstTouchpoint['campaign_id'] . ':' . $firstTouchpoint['channel'];

            if (!isset($attribution[$key])) {
                $attribution[$key] = [
                    'campaign_id' => $firstTouchpoint['campaign_id'],
                    'channel' => $firstTouchpoint['channel'],
                    'conversions' => 0,
                    'conversion_value' => 0
                ];
            }

            $attribution[$key]['conversions'] += 1;
            $attribution[$key]['conversion_value'] += $path['conversion_value'];
        }

        return array_values($attribution);
    }

    /**
     * Apply linear attribution
     *
     * @param array $conversionPaths
     * @return array
     */
    protected function applyLinearAttribution(array $conversionPaths): array
    {
        $attribution = [];

        foreach ($conversionPaths as $path) {
            $touchpointCount = count($path['touchpoints']);
            $creditPerTouchpoint = 1 / $touchpointCount;
            $valuePerTouchpoint = $path['conversion_value'] / $touchpointCount;

            foreach ($path['touchpoints'] as $touchpoint) {
                $key = $touchpoint['campaign_id'] . ':' . $touchpoint['channel'];

                if (!isset($attribution[$key])) {
                    $attribution[$key] = [
                        'campaign_id' => $touchpoint['campaign_id'],
                        'channel' => $touchpoint['channel'],
                        'conversions' => 0,
                        'conversion_value' => 0
                    ];
                }

                $attribution[$key]['conversions'] += $creditPerTouchpoint;
                $attribution[$key]['conversion_value'] += $valuePerTouchpoint;
            }
        }

        // Round conversions
        foreach ($attribution as &$item) {
            $item['conversions'] = round($item['conversions'], 2);
            $item['conversion_value'] = round($item['conversion_value'], 2);
        }

        return array_values($attribution);
    }

    /**
     * Apply time-decay attribution
     *
     * @param array $conversionPaths
     * @return array
     */
    protected function applyTimeDecayAttribution(array $conversionPaths): array
    {
        $attribution = [];
        $halfLife = 7 * 86400; // 7 days in seconds

        foreach ($conversionPaths as $path) {
            $conversionTimestamp = Carbon::parse($path['conversion_date'])->timestamp;
            $totalWeight = 0;
            $weights = [];

            // Calculate weights for each touchpoint
            foreach ($path['touchpoints'] as $touchpoint) {
                $daysDiff = ($conversionTimestamp - $touchpoint['timestamp']) / 86400;
                $weight = pow(2, -$daysDiff / 7); // Exponential decay with 7-day half-life
                $weights[] = $weight;
                $totalWeight += $weight;
            }

            // Distribute credit based on weights
            foreach ($path['touchpoints'] as $index => $touchpoint) {
                $credit = $totalWeight > 0 ? $weights[$index] / $totalWeight : 0;
                $key = $touchpoint['campaign_id'] . ':' . $touchpoint['channel'];

                if (!isset($attribution[$key])) {
                    $attribution[$key] = [
                        'campaign_id' => $touchpoint['campaign_id'],
                        'channel' => $touchpoint['channel'],
                        'conversions' => 0,
                        'conversion_value' => 0
                    ];
                }

                $attribution[$key]['conversions'] += $credit;
                $attribution[$key]['conversion_value'] += ($credit * $path['conversion_value']);
            }
        }

        // Round values
        foreach ($attribution as &$item) {
            $item['conversions'] = round($item['conversions'], 2);
            $item['conversion_value'] = round($item['conversion_value'], 2);
        }

        return array_values($attribution);
    }

    /**
     * Apply position-based attribution (40% first, 40% last, 20% middle)
     *
     * @param array $conversionPaths
     * @return array
     */
    protected function applyPositionBasedAttribution(array $conversionPaths): array
    {
        $attribution = [];

        foreach ($conversionPaths as $path) {
            $touchpointCount = count($path['touchpoints']);

            if ($touchpointCount === 1) {
                // Single touchpoint gets 100%
                $credit = [1.0];
            } elseif ($touchpointCount === 2) {
                // First and last get 50% each
                $credit = [0.5, 0.5];
            } else {
                // First 40%, Last 40%, Middle 20% split
                $middleCredit = 0.2 / ($touchpointCount - 2);
                $credit = array_fill(0, $touchpointCount, $middleCredit);
                $credit[0] = 0.4; // First
                $credit[$touchpointCount - 1] = 0.4; // Last
            }

            foreach ($path['touchpoints'] as $index => $touchpoint) {
                $key = $touchpoint['campaign_id'] . ':' . $touchpoint['channel'];

                if (!isset($attribution[$key])) {
                    $attribution[$key] = [
                        'campaign_id' => $touchpoint['campaign_id'],
                        'channel' => $touchpoint['channel'],
                        'conversions' => 0,
                        'conversion_value' => 0
                    ];
                }

                $attribution[$key]['conversions'] += $credit[$index];
                $attribution[$key]['conversion_value'] += ($credit[$index] * $path['conversion_value']);
            }
        }

        // Round values
        foreach ($attribution as &$item) {
            $item['conversions'] = round($item['conversions'], 2);
            $item['conversion_value'] = round($item['conversion_value'], 2);
        }

        return array_values($attribution);
    }

    /**
     * Apply data-driven attribution (algorithmic)
     *
     * @param array $conversionPaths
     * @return array
     */
    protected function applyDataDrivenAttribution(array $conversionPaths): array
    {
        // Simplified data-driven model based on frequency and recency
        $touchpointContributions = [];

        // Analyze touchpoint effectiveness
        foreach ($conversionPaths as $path) {
            foreach ($path['touchpoints'] as $index => $touchpoint) {
                $key = $touchpoint['campaign_id'] . ':' . $touchpoint['channel'];

                if (!isset($touchpointContributions[$key])) {
                    $touchpointContributions[$key] = [
                        'appearances' => 0,
                        'positions' => [],
                        'conversion_value_sum' => 0
                    ];
                }

                $touchpointContributions[$key]['appearances']++;
                $touchpointContributions[$key]['positions'][] = $index;
                $touchpointContributions[$key]['conversion_value_sum'] += $path['conversion_value'];
            }
        }

        // Calculate contribution scores
        $totalScore = 0;
        $scores = [];

        foreach ($touchpointContributions as $key => $data) {
            // Score based on frequency and average position
            $avgPosition = array_sum($data['positions']) / count($data['positions']);
            $positionScore = 1 / ($avgPosition + 1); // Earlier positions get higher scores
            $frequencyScore = log($data['appearances'] + 1);

            $score = $positionScore * $frequencyScore;
            $scores[$key] = $score;
            $totalScore += $score;
        }

        // Distribute conversions based on scores
        $attribution = [];

        foreach ($conversionPaths as $path) {
            $pathTouchpoints = [];
            $pathTotalScore = 0;

            foreach ($path['touchpoints'] as $touchpoint) {
                $key = $touchpoint['campaign_id'] . ':' . $touchpoint['channel'];
                $score = $scores[$key] ?? 0;
                $pathTouchpoints[$key] = $score;
                $pathTotalScore += $score;
            }

            foreach ($pathTouchpoints as $key => $score) {
                $credit = $pathTotalScore > 0 ? $score / $pathTotalScore : 0;

                if (!isset($attribution[$key])) {
                    list($campaignId, $channel) = explode(':', $key);
                    $attribution[$key] = [
                        'campaign_id' => $campaignId,
                        'channel' => $channel,
                        'conversions' => 0,
                        'conversion_value' => 0
                    ];
                }

                $attribution[$key]['conversions'] += $credit;
                $attribution[$key]['conversion_value'] += ($credit * $path['conversion_value']);
            }
        }

        // Round values
        foreach ($attribution as &$item) {
            $item['conversions'] = round($item['conversions'], 2);
            $item['conversion_value'] = round($item['conversion_value'], 2);
        }

        return array_values($attribution);
    }

    /**
     * Compare attribution models
     *
     * @param string $campaignId
     * @param array $dateRange
     * @return array
     */
    public function compareAttributionModels(string $campaignId, array $dateRange = []): array
    {
        try {
            $models = [
                self::MODEL_LAST_CLICK,
                self::MODEL_FIRST_CLICK,
                self::MODEL_LINEAR,
                self::MODEL_TIME_DECAY,
                self::MODEL_POSITION_BASED,
                self::MODEL_DATA_DRIVEN
            ];

            $comparison = [];

            foreach ($models as $model) {
                $result = $this->attributeConversions($campaignId, $model, $dateRange);

                if ($result['success']) {
                    $comparison[$model] = [
                        'attributed_conversions' => $result['attributed_conversions'],
                        'total_conversions' => $result['total_conversions']
                    ];
                }
            }

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'comparison' => $comparison,
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to compare attribution models', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get attribution insights
     *
     * @param string $campaignId
     * @param string $model
     * @param array $dateRange
     * @return array
     */
    public function getAttributionInsights(
        string $campaignId,
        string $model = self::MODEL_LINEAR,
        array $dateRange = []
    ): array {
        try {
            $attribution = $this->attributeConversions($campaignId, $model, $dateRange);

            if (!$attribution['success']) {
                return $attribution;
            }

            // Analyze channel performance
            $channels = $attribution['attributed_conversions'];

            // Sort by conversion value
            usort($channels, function($a, $b) {
                return $b['conversion_value'] <=> $a['conversion_value'];
            });

            // Calculate channel insights
            $totalValue = array_sum(array_column($channels, 'conversion_value'));

            $insights = array_map(function($channel) use ($totalValue) {
                $contribution = $totalValue > 0
                    ? round(($channel['conversion_value'] / $totalValue) * 100, 2)
                    : 0;

                return [
                    'channel' => $channel['channel'],
                    'conversions' => $channel['conversions'],
                    'conversion_value' => $channel['conversion_value'],
                    'contribution_percentage' => $contribution,
                    'value_per_conversion' => $channel['conversions'] > 0
                        ? round($channel['conversion_value'] / $channel['conversions'], 2)
                        : 0
                ];
            }, $channels);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'attribution_model' => $model,
                'insights' => $insights,
                'top_channel' => $insights[0] ?? null,
                'total_value' => round($totalValue, 2),
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get attribution insights', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
