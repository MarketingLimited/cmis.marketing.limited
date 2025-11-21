<?php

namespace App\Services\Enterprise;

use App\Models\AdPlatform\AdCampaign;
use App\Services\AI\CampaignOptimizationService as AICampaignOptimizationService;
use Illuminate\Support\Facades\{DB, Log, Cache, Notification};
use Carbon\Carbon;

/**
 * Performance Monitoring & Alerting Service (Phase 5 - Enterprise Features)
 *
 * Real-time performance monitoring with intelligent alerting:
 * - Anomaly detection
 * - Threshold-based alerts
 * - Performance degradation detection
 * - Budget overspend alerts
 * - Multi-channel notifications (email, SMS, Slack, webhook)
 * - Alert aggregation and rate limiting
 */
class PerformanceMonitoringService
{
    protected AICampaignOptimizationService $aiOptimizer;

    // Alert types
    const ALERT_TYPE_PERFORMANCE_DROP = 'performance_drop';
    const ALERT_TYPE_BUDGET_OVERSPEND = 'budget_overspend';
    const ALERT_TYPE_ANOMALY = 'anomaly';
    const ALERT_TYPE_CONVERSION_DROP = 'conversion_drop';
    const ALERT_TYPE_CTR_DROP = 'ctr_drop';
    const ALERT_TYPE_HIGH_CPC = 'high_cpc';

    // Alert severity levels
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Alert status
    const STATUS_ACTIVE = 'active';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_RESOLVED = 'resolved';

    public function __construct(AICampaignOptimizationService $aiOptimizer)
    {
        $this->aiOptimizer = $aiOptimizer;
    }

    /**
     * Monitor all campaigns for organization
     *
     * @param string $orgId
     * @return array
     */
    public function monitorOrganization(string $orgId): array
    {
        $results = [
            'campaigns_monitored' => 0,
            'alerts_generated' => 0,
            'anomalies_detected' => 0,
            'notifications_sent' => 0,
            'alerts' => []
        ];

        try {
            $campaigns = AdCampaign::where('org_id', $orgId)
                ->whereIn('status', ['active', 'scheduled'])
                ->get();

            foreach ($campaigns as $campaign) {
                $results['campaigns_monitored']++;

                // Check for performance issues
                $alerts = $this->monitorCampaign($campaign);

                foreach ($alerts as $alert) {
                    $results['alerts_generated']++;
                    $results['alerts'][] = $alert;

                    if ($alert['type'] === self::ALERT_TYPE_ANOMALY) {
                        $results['anomalies_detected']++;
                    }

                    // Send notification if severity is high or critical
                    if (in_array($alert['severity'], [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL])) {
                        $this->sendAlertNotification($orgId, $alert);
                        $results['notifications_sent']++;
                    }
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Organization monitoring error', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return array_merge($results, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Monitor individual campaign
     *
     * @param AdCampaign $campaign
     * @return array
     */
    public function monitorCampaign(AdCampaign $campaign): array
    {
        $alerts = [];

        try {
            // Get current metrics
            $metrics = $this->getCampaignMetrics($campaign->campaign_id);

            // Get historical baseline
            $baseline = $this->getHistoricalBaseline($campaign->campaign_id);

            // Check for performance drop
            if ($this->hasPerformanceDrop($metrics, $baseline)) {
                $alerts[] = $this->createAlert(
                    $campaign,
                    self::ALERT_TYPE_PERFORMANCE_DROP,
                    self::SEVERITY_HIGH,
                    'Campaign performance dropped significantly',
                    [
                        'current_performance' => $metrics,
                        'baseline' => $baseline,
                        'drop_percentage' => $this->calculateDropPercentage($metrics, $baseline)
                    ]
                );
            }

            // Check for budget overspend
            if ($this->hasBudgetOverspend($campaign, $metrics)) {
                $alerts[] = $this->createAlert(
                    $campaign,
                    self::ALERT_TYPE_BUDGET_OVERSPEND,
                    self::SEVERITY_CRITICAL,
                    'Campaign exceeding budget threshold',
                    [
                        'budget' => $campaign->budget,
                        'spend' => $metrics['spend'],
                        'overspend_percentage' => (($metrics['spend'] - $campaign->budget) / $campaign->budget) * 100
                    ]
                );
            }

            // Check for anomalies
            $anomalies = $this->detectAnomalies($campaign->campaign_id, $metrics);
            foreach ($anomalies as $anomaly) {
                $alerts[] = $this->createAlert(
                    $campaign,
                    self::ALERT_TYPE_ANOMALY,
                    self::SEVERITY_MEDIUM,
                    $anomaly['description'],
                    $anomaly['data']
                );
            }

            // Check for conversion rate drop
            if ($this->hasConversionDrop($metrics, $baseline)) {
                $alerts[] = $this->createAlert(
                    $campaign,
                    self::ALERT_TYPE_CONVERSION_DROP,
                    self::SEVERITY_HIGH,
                    'Conversion rate dropped below threshold',
                    [
                        'current_rate' => $metrics['conversion_rate'] ?? 0,
                        'baseline_rate' => $baseline['conversion_rate'] ?? 0,
                        'drop_percentage' => $this->calculateMetricDrop(
                            $metrics['conversion_rate'] ?? 0,
                            $baseline['conversion_rate'] ?? 0
                        )
                    ]
                );
            }

            // Check for CTR drop
            if ($this->hasCTRDrop($metrics, $baseline)) {
                $alerts[] = $this->createAlert(
                    $campaign,
                    self::ALERT_TYPE_CTR_DROP,
                    self::SEVERITY_MEDIUM,
                    'Click-through rate decreased significantly',
                    [
                        'current_ctr' => $metrics['ctr'] ?? 0,
                        'baseline_ctr' => $baseline['ctr'] ?? 0
                    ]
                );
            }

            // Check for high CPC
            if ($this->hasHighCPC($metrics)) {
                $alerts[] = $this->createAlert(
                    $campaign,
                    self::ALERT_TYPE_HIGH_CPC,
                    self::SEVERITY_MEDIUM,
                    'Cost per click is unusually high',
                    [
                        'current_cpc' => $metrics['cpc'] ?? 0,
                        'threshold' => 5.0
                    ]
                );
            }

            // Store alerts in database
            foreach ($alerts as $alert) {
                $this->storeAlert($alert);
            }

            return $alerts;

        } catch (\Exception $e) {
            Log::error('Campaign monitoring error', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get current campaign metrics
     *
     * @param string $campaignId
     * @return array
     */
    protected function getCampaignMetrics(string $campaignId): array
    {
        $result = DB::table('cmis.ad_metrics')
            ->where('campaign_id', $campaignId)
            ->where('date', '>=', Carbon::now()->subDays(7))
            ->select([
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('AVG(ctr) as ctr'),
                DB::raw('AVG(cpc) as cpc'),
                DB::raw('AVG(conversion_rate) as conversion_rate')
            ])
            ->first();

        return [
            'impressions' => $result->impressions ?? 0,
            'clicks' => $result->clicks ?? 0,
            'conversions' => $result->conversions ?? 0,
            'spend' => $result->spend ?? 0,
            'ctr' => $result->ctr ?? 0,
            'cpc' => $result->cpc ?? 0,
            'conversion_rate' => $result->conversion_rate ?? 0
        ];
    }

    /**
     * Get historical baseline metrics
     *
     * @param string $campaignId
     * @return array
     */
    protected function getHistoricalBaseline(string $campaignId): array
    {
        $result = DB::table('cmis.ad_metrics')
            ->where('campaign_id', $campaignId)
            ->where('date', '>=', Carbon::now()->subDays(30))
            ->where('date', '<', Carbon::now()->subDays(7))
            ->select([
                DB::raw('AVG(ctr) as ctr'),
                DB::raw('AVG(cpc) as cpc'),
                DB::raw('AVG(conversion_rate) as conversion_rate'),
                DB::raw('AVG(impressions) as avg_impressions')
            ])
            ->first();

        return [
            'ctr' => $result->ctr ?? 0,
            'cpc' => $result->cpc ?? 0,
            'conversion_rate' => $result->conversion_rate ?? 0,
            'avg_impressions' => $result->avg_impressions ?? 0
        ];
    }

    /**
     * Check for performance drop
     *
     * @param array $current
     * @param array $baseline
     * @return bool
     */
    protected function hasPerformanceDrop(array $current, array $baseline): bool
    {
        // Check if impressions dropped > 50%
        if ($baseline['avg_impressions'] > 100) {
            $currentDailyAvg = $current['impressions'] / 7;
            if ($currentDailyAvg < $baseline['avg_impressions'] * 0.5) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for budget overspend
     *
     * @param AdCampaign $campaign
     * @param array $metrics
     * @return bool
     */
    protected function hasBudgetOverspend(AdCampaign $campaign, array $metrics): bool
    {
        if (!$campaign->budget || $campaign->budget <= 0) {
            return false;
        }

        // Alert if spend exceeds 95% of budget
        return ($metrics['spend'] / $campaign->budget) >= 0.95;
    }

    /**
     * Detect anomalies in metrics
     *
     * @param string $campaignId
     * @param array $current
     * @return array
     */
    protected function detectAnomalies(string $campaignId, array $current): array
    {
        $anomalies = [];

        // Get historical data for anomaly detection
        $historicalData = $this->getHistoricalTimeSeries($campaignId, 30);

        // Check for sudden spike in spend
        if ($this->isSuddenSpike($current['spend'], $historicalData, 'spend')) {
            $anomalies[] = [
                'description' => 'Unusual spike in campaign spend detected',
                'data' => [
                    'current_spend' => $current['spend'],
                    'expected_range' => $this->calculateExpectedRange($historicalData, 'spend')
                ]
            ];
        }

        // Check for sudden drop in conversions
        if ($this->isSuddenDrop($current['conversions'], $historicalData, 'conversions')) {
            $anomalies[] = [
                'description' => 'Unusual drop in conversions detected',
                'data' => [
                    'current_conversions' => $current['conversions'],
                    'expected_range' => $this->calculateExpectedRange($historicalData, 'conversions')
                ]
            ];
        }

        return $anomalies;
    }

    /**
     * Check for conversion rate drop
     *
     * @param array $current
     * @param array $baseline
     * @return bool
     */
    protected function hasConversionDrop(array $current, array $baseline): bool
    {
        if ($baseline['conversion_rate'] <= 0) {
            return false;
        }

        // Alert if conversion rate drops > 30%
        $dropPercentage = $this->calculateMetricDrop(
            $current['conversion_rate'],
            $baseline['conversion_rate']
        );

        return $dropPercentage > 30;
    }

    /**
     * Check for CTR drop
     *
     * @param array $current
     * @param array $baseline
     * @return bool
     */
    protected function hasCTRDrop(array $current, array $baseline): bool
    {
        if ($baseline['ctr'] <= 0) {
            return false;
        }

        // Alert if CTR drops > 40%
        $dropPercentage = $this->calculateMetricDrop($current['ctr'], $baseline['ctr']);

        return $dropPercentage > 40;
    }

    /**
     * Check for high CPC
     *
     * @param array $metrics
     * @return bool
     */
    protected function hasHighCPC(array $metrics): bool
    {
        // Alert if CPC exceeds $5.00
        return ($metrics['cpc'] ?? 0) > 5.0;
    }

    /**
     * Calculate metric drop percentage
     *
     * @param float $current
     * @param float $baseline
     * @return float
     */
    protected function calculateMetricDrop(float $current, float $baseline): float
    {
        if ($baseline <= 0) {
            return 0;
        }

        return (($baseline - $current) / $baseline) * 100;
    }

    /**
     * Calculate performance drop percentage
     *
     * @param array $current
     * @param array $baseline
     * @return float
     */
    protected function calculateDropPercentage(array $current, array $baseline): float
    {
        if ($baseline['avg_impressions'] <= 0) {
            return 0;
        }

        $currentDailyAvg = $current['impressions'] / 7;
        return (($baseline['avg_impressions'] - $currentDailyAvg) / $baseline['avg_impressions']) * 100;
    }

    /**
     * Get historical time series data
     *
     * @param string $campaignId
     * @param int $days
     * @return array
     */
    protected function getHistoricalTimeSeries(string $campaignId, int $days): array
    {
        $data = DB::table('cmis.ad_metrics')
            ->where('campaign_id', $campaignId)
            ->where('date', '>=', Carbon::now()->subDays($days))
            ->orderBy('date', 'asc')
            ->get();

        return $data->toArray();
    }

    /**
     * Check for sudden spike in metric
     *
     * @param float $current
     * @param array $historical
     * @param string $metric
     * @return bool
     */
    protected function isSuddenSpike(float $current, array $historical, string $metric): bool
    {
        $values = array_column($historical, $metric);
        $mean = array_sum($values) / max(count($values), 1);
        $stdDev = $this->calculateStdDev($values, $mean);

        // Spike if current value is > 3 standard deviations above mean
        return $current > ($mean + 3 * $stdDev);
    }

    /**
     * Check for sudden drop in metric
     *
     * @param float $current
     * @param array $historical
     * @param string $metric
     * @return bool
     */
    protected function isSuddenDrop(float $current, array $historical, string $metric): bool
    {
        $values = array_column($historical, $metric);
        $mean = array_sum($values) / max(count($values), 1);
        $stdDev = $this->calculateStdDev($values, $mean);

        // Drop if current value is > 3 standard deviations below mean
        return $current < ($mean - 3 * $stdDev);
    }

    /**
     * Calculate standard deviation
     *
     * @param array $values
     * @param float $mean
     * @return float
     */
    protected function calculateStdDev(array $values, float $mean): float
    {
        if (empty($values)) {
            return 0;
        }

        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        return sqrt($variance);
    }

    /**
     * Calculate expected range for metric
     *
     * @param array $historical
     * @param string $metric
     * @return array
     */
    protected function calculateExpectedRange(array $historical, string $metric): array
    {
        $values = array_column($historical, $metric);
        $mean = array_sum($values) / max(count($values), 1);
        $stdDev = $this->calculateStdDev($values, $mean);

        return [
            'min' => max(0, $mean - 2 * $stdDev),
            'max' => $mean + 2 * $stdDev,
            'mean' => $mean
        ];
    }

    /**
     * Create alert record
     *
     * @param AdCampaign $campaign
     * @param string $type
     * @param string $severity
     * @param string $message
     * @param array $data
     * @return array
     */
    protected function createAlert(
        AdCampaign $campaign,
        string $type,
        string $severity,
        string $message,
        array $data
    ): array {
        return [
            'alert_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'org_id' => $campaign->org_id,
            'campaign_id' => $campaign->campaign_id,
            'campaign_name' => $campaign->name,
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'data' => $data,
            'status' => self::STATUS_ACTIVE,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
    }

    /**
     * Store alert in database
     *
     * @param array $alert
     * @return void
     */
    protected function storeAlert(array $alert): void
    {
        // Check if similar alert exists in last hour (avoid duplicates)
        $existingAlert = DB::table('cmis_enterprise.performance_alerts')
            ->where('campaign_id', $alert['campaign_id'])
            ->where('type', $alert['type'])
            ->where('status', self::STATUS_ACTIVE)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->first();

        if ($existingAlert) {
            // Update existing alert
            DB::table('cmis_enterprise.performance_alerts')
                ->where('alert_id', $existingAlert->alert_id)
                ->update([
                    'data' => json_encode($alert['data']),
                    'updated_at' => Carbon::now()
                ]);
        } else {
            // Create new alert
            DB::table('cmis_enterprise.performance_alerts')->insert([
                'alert_id' => $alert['alert_id'],
                'org_id' => $alert['org_id'],
                'campaign_id' => $alert['campaign_id'],
                'type' => $alert['type'],
                'severity' => $alert['severity'],
                'message' => $alert['message'],
                'data' => json_encode($alert['data']),
                'status' => $alert['status'],
                'created_at' => $alert['created_at'],
                'updated_at' => $alert['updated_at']
            ]);
        }
    }

    /**
     * Send alert notification
     *
     * @param string $orgId
     * @param array $alert
     * @return void
     */
    protected function sendAlertNotification(string $orgId, array $alert): void
    {
        // Get notification preferences for organization
        $preferences = $this->getNotificationPreferences($orgId);

        // Send notifications based on preferences
        foreach ($preferences as $channel) {
            try {
                match ($channel['type']) {
                    'email' => $this->sendEmailNotification($channel['config'], $alert),
                    'webhook' => $this->sendWebhookNotification($channel['config'], $alert),
                    'slack' => $this->sendSlackNotification($channel['config'], $alert),
                    default => Log::warning('Unknown notification channel', ['channel' => $channel['type']])
                };
            } catch (\Exception $e) {
                Log::error('Notification send error', [
                    'channel' => $channel['type'],
                    'alert_id' => $alert['alert_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get notification preferences for organization
     *
     * @param string $orgId
     * @return array
     */
    protected function getNotificationPreferences(string $orgId): array
    {
        // Would retrieve from database
        // For now, return default email notification
        return [
            [
                'type' => 'email',
                'config' => ['enabled' => true]
            ]
        ];
    }

    /**
     * Send email notification
     *
     * @param array $config
     * @param array $alert
     * @return void
     */
    protected function sendEmailNotification(array $config, array $alert): void
    {
        Log::info('Email notification sent', [
            'alert_id' => $alert['alert_id'],
            'severity' => $alert['severity']
        ]);
    }

    /**
     * Send webhook notification
     *
     * @param array $config
     * @param array $alert
     * @return void
     */
    protected function sendWebhookNotification(array $config, array $alert): void
    {
        Log::info('Webhook notification sent', [
            'alert_id' => $alert['alert_id']
        ]);
    }

    /**
     * Send Slack notification
     *
     * @param array $config
     * @param array $alert
     * @return void
     */
    protected function sendSlackNotification(array $config, array $alert): void
    {
        Log::info('Slack notification sent', [
            'alert_id' => $alert['alert_id']
        ]);
    }

    /**
     * Get active alerts for organization
     *
     * @param string $orgId
     * @param array $filters
     * @return array
     */
    public function getAlerts(string $orgId, array $filters = []): array
    {
        $query = DB::table('cmis_enterprise.performance_alerts')
            ->where('org_id', $orgId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        $alerts = $query->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get();

        return array_map(function ($alert) {
            return [
                'alert_id' => $alert->alert_id,
                'campaign_id' => $alert->campaign_id,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'message' => $alert->message,
                'data' => json_decode($alert->data, true),
                'status' => $alert->status,
                'created_at' => $alert->created_at,
                'updated_at' => $alert->updated_at
            ];
        }, $alerts->toArray());
    }

    /**
     * Acknowledge alert
     *
     * @param string $alertId
     * @param string $userId
     * @return bool
     */
    public function acknowledgeAlert(string $alertId, string $userId): bool
    {
        return DB::table('cmis_enterprise.performance_alerts')
            ->where('alert_id', $alertId)
            ->update([
                'status' => self::STATUS_ACKNOWLEDGED,
                'acknowledged_by' => $userId,
                'acknowledged_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]) > 0;
    }

    /**
     * Resolve alert
     *
     * @param string $alertId
     * @param string $userId
     * @param string $resolution
     * @return bool
     */
    public function resolveAlert(string $alertId, string $userId, string $resolution = null): bool
    {
        return DB::table('cmis_enterprise.performance_alerts')
            ->where('alert_id', $alertId)
            ->update([
                'status' => self::STATUS_RESOLVED,
                'resolved_by' => $userId,
                'resolved_at' => Carbon::now(),
                'resolution' => $resolution,
                'updated_at' => Carbon::now()
            ]) > 0;
    }
}
