<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\{DB, Cache, Log, Redis};
use Carbon\Carbon;

/**
 * Real-Time Analytics Processing Service (Phase 7)
 *
 * Processes and aggregates real-time campaign and platform analytics
 */
class RealTimeAnalyticsService
{
    // Time windows for aggregation
    const WINDOW_1MIN = 60;
    const WINDOW_5MIN = 300;
    const WINDOW_15MIN = 900;
    const WINDOW_1HOUR = 3600;

    // Metric types
    const METRIC_IMPRESSIONS = 'impressions';
    const METRIC_CLICKS = 'clicks';
    const METRIC_CONVERSIONS = 'conversions';
    const METRIC_SPEND = 'spend';
    const METRIC_REVENUE = 'revenue';

    // Cache TTL
    const CACHE_TTL = 60; // 1 minute for real-time data

    /**
     * Record a real-time event
     *
     * @param string $entityType (campaign, ad_set, ad)
     * @param string $entityId
     * @param string $metric
     * @param float $value
     * @param array $metadata
     * @return bool
     */
    public function recordEvent(
        string $entityType,
        string $entityId,
        string $metric,
        float $value,
        array $metadata = []
    ): bool {
        try {
            $timestamp = Carbon::now();

            // Store in Redis for real-time processing
            $redis = Redis::connection();

            $eventKey = "rt:events:{$entityType}:{$entityId}:{$metric}";
            $event = json_encode([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric' => $metric,
                'value' => $value,
                'metadata' => $metadata,
                'timestamp' => $timestamp->timestamp
            ]);

            // Add to time-series sorted set
            $redis->zadd($eventKey, $timestamp->timestamp, $event);

            // Expire old data after 1 hour
            $redis->expire($eventKey, self::WINDOW_1HOUR);

            // Update aggregations
            $this->updateAggregations($entityType, $entityId, $metric, $value, $timestamp);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to record real-time event', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Update metric aggregations for different time windows
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $metric
     * @param float $value
     * @param Carbon $timestamp
     * @return void
     */
    protected function updateAggregations(
        string $entityType,
        string $entityId,
        string $metric,
        float $value,
        Carbon $timestamp
    ): void {
        try {
            $redis = Redis::connection();
            $windows = [
                '1m' => self::WINDOW_1MIN,
                '5m' => self::WINDOW_5MIN,
                '15m' => self::WINDOW_15MIN,
                '1h' => self::WINDOW_1HOUR
            ];

            foreach ($windows as $windowLabel => $windowSeconds) {
                // Calculate window bucket
                $bucket = floor($timestamp->timestamp / $windowSeconds) * $windowSeconds;

                $aggKey = "rt:agg:{$windowLabel}:{$entityType}:{$entityId}:{$metric}:{$bucket}";

                // Increment counter
                $redis->incrbyfloat($aggKey, $value);

                // Set expiration
                $redis->expire($aggKey, $windowSeconds * 2);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update aggregations', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get real-time metrics for an entity
     *
     * @param string $entityType
     * @param string $entityId
     * @param array $metrics
     * @param string $window (1m, 5m, 15m, 1h)
     * @return array
     */
    public function getRealtimeMetrics(
        string $entityType,
        string $entityId,
        array $metrics = [],
        string $window = '5m'
    ): array {
        try {
            $redis = Redis::connection();
            $timestamp = Carbon::now()->timestamp;

            // Map window labels to seconds
            $windowSeconds = match($window) {
                '1m' => self::WINDOW_1MIN,
                '5m' => self::WINDOW_5MIN,
                '15m' => self::WINDOW_15MIN,
                '1h' => self::WINDOW_1HOUR,
                default => self::WINDOW_5MIN
            };

            // Calculate current bucket
            $bucket = floor($timestamp / $windowSeconds) * $windowSeconds;

            // Default metrics if none specified
            if (empty($metrics)) {
                $metrics = [
                    self::METRIC_IMPRESSIONS,
                    self::METRIC_CLICKS,
                    self::METRIC_CONVERSIONS,
                    self::METRIC_SPEND
                ];
            }

            $results = [];

            foreach ($metrics as $metric) {
                $aggKey = "rt:agg:{$window}:{$entityType}:{$entityId}:{$metric}:{$bucket}";
                $value = $redis->get($aggKey);

                $results[$metric] = [
                    'value' => $value ? (float) $value : 0,
                    'window' => $window,
                    'bucket_start' => Carbon::createFromTimestamp($bucket)->toIso8601String()
                ];
            }

            // Calculate derived metrics
            if (isset($results[self::METRIC_CLICKS]) && isset($results[self::METRIC_IMPRESSIONS])) {
                $clicks = $results[self::METRIC_CLICKS]['value'];
                $impressions = $results[self::METRIC_IMPRESSIONS]['value'];
                $results['ctr'] = [
                    'value' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                    'unit' => 'percent'
                ];
            }

            if (isset($results[self::METRIC_SPEND]) && isset($results[self::METRIC_CLICKS])) {
                $spend = $results[self::METRIC_SPEND]['value'];
                $clicks = $results[self::METRIC_CLICKS]['value'];
                $results['cpc'] = [
                    'value' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                    'unit' => 'currency'
                ];
            }

            if (isset($results[self::METRIC_CONVERSIONS]) && isset($results[self::METRIC_CLICKS])) {
                $conversions = $results[self::METRIC_CONVERSIONS]['value'];
                $clicks = $results[self::METRIC_CLICKS]['value'];
                $results['conversion_rate'] = [
                    'value' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
                    'unit' => 'percent'
                ];
            }

            return [
                'success' => true,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metrics' => $results,
                'window' => $window,
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get realtime metrics', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get time-series data for a metric
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $metric
     * @param string $window
     * @param int $points Number of data points to return
     * @return array
     */
    public function getTimeSeries(
        string $entityType,
        string $entityId,
        string $metric,
        string $window = '5m',
        int $points = 12
    ): array {
        try {
            $redis = Redis::connection();
            $timestamp = Carbon::now()->timestamp;

            $windowSeconds = match($window) {
                '1m' => self::WINDOW_1MIN,
                '5m' => self::WINDOW_5MIN,
                '15m' => self::WINDOW_15MIN,
                '1h' => self::WINDOW_1HOUR,
                default => self::WINDOW_5MIN
            };

            $series = [];

            // Get data for last N buckets
            for ($i = $points - 1; $i >= 0; $i--) {
                $bucket = floor(($timestamp - ($i * $windowSeconds)) / $windowSeconds) * $windowSeconds;
                $aggKey = "rt:agg:{$window}:{$entityType}:{$entityId}:{$metric}:{$bucket}";

                $value = $redis->get($aggKey);

                $series[] = [
                    'timestamp' => Carbon::createFromTimestamp($bucket)->toIso8601String(),
                    'value' => $value ? (float) $value : 0,
                    'bucket' => $bucket
                ];
            }

            return [
                'success' => true,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric' => $metric,
                'window' => $window,
                'series' => $series,
                'points' => count($series)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get time series', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get real-time dashboard for organization
     *
     * @param string $orgId
     * @param string $window
     * @return array
     */
    public function getOrganizationDashboard(string $orgId, string $window = '5m'): array
    {
        try {
            // Get active campaigns for organization
            $campaigns = DB::table('cmis.campaigns')
                ->where('org_id', $orgId)
                ->where('status', 'active')
                ->select('campaign_id', 'name', 'budget', 'daily_budget')
                ->get();

            $dashboardData = [];
            $totals = [
                self::METRIC_IMPRESSIONS => 0,
                self::METRIC_CLICKS => 0,
                self::METRIC_CONVERSIONS => 0,
                self::METRIC_SPEND => 0
            ];

            foreach ($campaigns as $campaign) {
                $metrics = $this->getRealtimeMetrics(
                    'campaign',
                    $campaign->campaign_id,
                    [self::METRIC_IMPRESSIONS, self::METRIC_CLICKS, self::METRIC_CONVERSIONS, self::METRIC_SPEND],
                    $window
                );

                if ($metrics['success']) {
                    $dashboardData[] = [
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->name,
                        'metrics' => $metrics['metrics']
                    ];

                    // Accumulate totals
                    foreach ($totals as $metric => $value) {
                        if (isset($metrics['metrics'][$metric])) {
                            $totals[$metric] += $metrics['metrics'][$metric]['value'];
                        }
                    }
                }
            }

            // Calculate organization-level derived metrics
            $derivedMetrics = [];

            if ($totals[self::METRIC_IMPRESSIONS] > 0) {
                $derivedMetrics['ctr'] = round(($totals[self::METRIC_CLICKS] / $totals[self::METRIC_IMPRESSIONS]) * 100, 2);
            }

            if ($totals[self::METRIC_CLICKS] > 0) {
                $derivedMetrics['cpc'] = round($totals[self::METRIC_SPEND] / $totals[self::METRIC_CLICKS], 2);
                $derivedMetrics['conversion_rate'] = round(($totals[self::METRIC_CONVERSIONS] / $totals[self::METRIC_CLICKS]) * 100, 2);
            }

            if ($totals[self::METRIC_CONVERSIONS] > 0) {
                $derivedMetrics['cost_per_conversion'] = round($totals[self::METRIC_SPEND] / $totals[self::METRIC_CONVERSIONS], 2);
            }

            return [
                'success' => true,
                'org_id' => $orgId,
                'window' => $window,
                'campaigns' => $dashboardData,
                'totals' => $totals,
                'derived_metrics' => $derivedMetrics,
                'active_campaigns' => count($campaigns),
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get organization dashboard', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get top performing entities by metric
     *
     * @param string $orgId
     * @param string $entityType
     * @param string $metric
     * @param string $window
     * @param int $limit
     * @return array
     */
    public function getTopPerformers(
        string $orgId,
        string $entityType,
        string $metric,
        string $window = '5m',
        int $limit = 10
    ): array {
        try {
            // Get all entities for organization
            $entities = match($entityType) {
                'campaign' => DB::table('cmis.campaigns')
                    ->where('org_id', $orgId)
                    ->where('status', 'active')
                    ->select('campaign_id as id', 'name')
                    ->get(),
                'ad_set' => DB::table('cmis_meta.ad_sets')
                    ->join('cmis.campaigns', 'cmis_meta.ad_sets.campaign_id', '=', 'cmis.campaigns.campaign_id')
                    ->where('cmis.campaigns.org_id', $orgId)
                    ->select('cmis_meta.ad_sets.ad_set_id as id', 'cmis_meta.ad_sets.name')
                    ->get(),
                default => collect([])
            };

            $performers = [];

            foreach ($entities as $entity) {
                $metrics = $this->getRealtimeMetrics($entityType, $entity->id, [$metric], $window);

                if ($metrics['success'] && isset($metrics['metrics'][$metric])) {
                    $performers[] = [
                        'entity_id' => $entity->id,
                        'entity_name' => $entity->name,
                        'metric_value' => $metrics['metrics'][$metric]['value']
                    ];
                }
            }

            // Sort by metric value descending
            usort($performers, function($a, $b) {
                return $b['metric_value'] <=> $a['metric_value'];
            });

            // Limit results
            $performers = array_slice($performers, 0, $limit);

            return [
                'success' => true,
                'org_id' => $orgId,
                'entity_type' => $entityType,
                'metric' => $metric,
                'window' => $window,
                'top_performers' => $performers,
                'count' => count($performers),
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get top performers', [
                'org_id' => $orgId,
                'entity_type' => $entityType,
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Detect anomalies in real-time metrics
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $metric
     * @return array
     */
    public function detectAnomalies(
        string $entityType,
        string $entityId,
        string $metric
    ): array {
        try {
            // Get time series for the last hour (12 x 5-minute windows)
            $timeSeries = $this->getTimeSeries($entityType, $entityId, $metric, '5m', 12);

            if (!$timeSeries['success']) {
                return $timeSeries;
            }

            $values = array_column($timeSeries['series'], 'value');
            $count = count($values);

            if ($count < 3) {
                return [
                    'success' => true,
                    'anomalies_detected' => false,
                    'message' => 'Insufficient data for anomaly detection'
                ];
            }

            // Calculate statistics
            $mean = array_sum($values) / $count;
            $variance = 0;

            foreach ($values as $value) {
                $variance += pow($value - $mean, 2);
            }

            $variance /= $count;
            $stdDev = sqrt($variance);

            // Get current value
            $currentValue = end($values);

            // Detect anomaly using 2-sigma rule
            $threshold = 2;
            $isAnomaly = abs($currentValue - $mean) > ($threshold * $stdDev);

            $anomalyType = null;
            if ($isAnomaly) {
                $anomalyType = $currentValue > $mean ? 'spike' : 'drop';
            }

            return [
                'success' => true,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric' => $metric,
                'anomalies_detected' => $isAnomaly,
                'anomaly_type' => $anomalyType,
                'statistics' => [
                    'current_value' => $currentValue,
                    'mean' => round($mean, 2),
                    'std_dev' => round($stdDev, 2),
                    'threshold_sigma' => $threshold,
                    'deviation' => $mean > 0 ? round((($currentValue - $mean) / $mean) * 100, 2) : 0
                ],
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to detect anomalies', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear real-time data for an entity (for testing/cleanup)
     *
     * @param string $entityType
     * @param string $entityId
     * @return bool
     */
    public function clearRealtimeData(string $entityType, string $entityId): bool
    {
        try {
            $redis = Redis::connection();
            $pattern = "rt:*:{$entityType}:{$entityId}:*";

            $keys = $redis->keys($pattern);

            if (!empty($keys)) {
                $redis->del($keys);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to clear realtime data', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
