<?php

namespace App\Services\Intelligence;

use App\Models\Intelligence\Anomaly;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnomalyService
{
    /**
     * Detect anomalies for an entity
     */
    public function detectAnomalies(
        string $entityType,
        string $entityId,
        array $metrics,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): Collection {
        $orgId = session('current_org_id');
        $anomalies = collect();

        $dateFrom = $dateFrom ?? now()->subDays(30)->toDateString();
        $dateTo = $dateTo ?? now()->toDateString();

        foreach ($metrics as $metric) {
            // Get historical data for the metric
            $historicalData = $this->getHistoricalMetricData(
                $entityType,
                $entityId,
                $metric,
                $dateFrom,
                $dateTo
            );

            if ($historicalData->isEmpty()) {
                continue;
            }

            // Calculate baseline statistics
            $baseline = $this->calculateBaseline($historicalData);

            // Check each data point for anomalies
            foreach ($historicalData as $dataPoint) {
                $isAnomaly = $this->isAnomaly(
                    $dataPoint['value'],
                    $baseline['mean'],
                    $baseline['std_dev']
                );

                if ($isAnomaly) {
                    $severity = $this->calculateSeverity(
                        $dataPoint['value'],
                        $baseline['mean'],
                        $baseline['std_dev']
                    );

                    $anomaly = Anomaly::create([
                        'org_id' => $orgId,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'metric_name' => $metric,
                        'detected_at' => $dataPoint['date'],
                        'expected_value' => $baseline['mean'],
                        'actual_value' => $dataPoint['value'],
                        'deviation_percentage' => $this->calculateDeviationPercentage(
                            $dataPoint['value'],
                            $baseline['mean']
                        ),
                        'severity' => $severity,
                        'status' => Anomaly::STATUS_DETECTED,
                        'detection_method' => 'statistical',
                        'confidence_score' => $this->calculateConfidenceScore(
                            $dataPoint['value'],
                            $baseline['mean'],
                            $baseline['std_dev']
                        ),
                        'metadata' => [
                            'baseline_mean' => $baseline['mean'],
                            'baseline_std_dev' => $baseline['std_dev'],
                            'z_score' => $this->calculateZScore(
                                $dataPoint['value'],
                                $baseline['mean'],
                                $baseline['std_dev']
                            ),
                        ],
                        'created_by' => auth()->id(),
                    ]);

                    $anomalies->push($anomaly);
                }
            }
        }

        return $anomalies;
    }

    /**
     * Get analytics dashboard data
     */
    public function getAnalytics(string $orgId): array
    {
        $totalAnomalies = Anomaly::where('org_id', $orgId)->count();

        $unresolvedAnomalies = Anomaly::where('org_id', $orgId)
            ->unresolved()
            ->count();

        $criticalAnomalies = Anomaly::where('org_id', $orgId)
            ->critical()
            ->unresolved()
            ->count();

        $recentAnomalies = Anomaly::where('org_id', $orgId)
            ->where('detected_at', '>=', now()->subDays(7))
            ->count();

        $avgResolutionTime = Anomaly::where('org_id', $orgId)
            ->whereNotNull('resolved_at')
            ->get()
            ->avg(function ($anomaly) {
                return $anomaly->detected_at->diffInHours($anomaly->resolved_at);
            });

        $anomaliesBySeverity = Anomaly::where('org_id', $orgId)
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->get()
            ->pluck('count', 'severity');

        $anomaliesByStatus = Anomaly::where('org_id', $orgId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $anomaliesByMetric = Anomaly::where('org_id', $orgId)
            ->select('metric_name', DB::raw('count(*) as count'))
            ->groupBy('metric_name')
            ->get()
            ->pluck('count', 'metric_name');

        return [
            'summary' => [
                'total_anomalies' => $totalAnomalies,
                'unresolved_anomalies' => $unresolvedAnomalies,
                'critical_anomalies' => $criticalAnomalies,
                'recent_anomalies' => $recentAnomalies,
                'avg_resolution_time_hours' => round($avgResolutionTime ?? 0, 2),
            ],
            'by_severity' => $anomaliesBySeverity,
            'by_status' => $anomaliesByStatus,
            'by_metric' => $anomaliesByMetric,
            'recent_critical' => $this->getRecentCriticalAnomalies($orgId),
            'trends' => $this->getAnomalyTrends($orgId),
        ];
    }

    /**
     * Get anomalies summary
     */
    public function getSummary(string $orgId, int $days = 30): array
    {
        $dateFrom = now()->subDays($days);

        $total = Anomaly::where('org_id', $orgId)
            ->where('detected_at', '>=', $dateFrom)
            ->count();

        $bySeverity = Anomaly::where('org_id', $orgId)
            ->where('detected_at', '>=', $dateFrom)
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->get()
            ->pluck('count', 'severity');

        $byStatus = Anomaly::where('org_id', $orgId)
            ->where('detected_at', '>=', $dateFrom)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $topMetrics = Anomaly::where('org_id', $orgId)
            ->where('detected_at', '>=', $dateFrom)
            ->select('metric_name', DB::raw('count(*) as count'))
            ->groupBy('metric_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'period_days' => $days,
            'total_anomalies' => $total,
            'by_severity' => $bySeverity,
            'by_status' => $byStatus,
            'top_affected_metrics' => $topMetrics,
            'false_positive_rate' => $this->calculateFalsePositiveRate($orgId, $dateFrom),
        ];
    }

    /**
     * Get historical metric data
     */
    protected function getHistoricalMetricData(
        string $entityType,
        string $entityId,
        string $metric,
        string $dateFrom,
        string $dateTo
    ): Collection {
        // This would typically query the metrics table or time-series data
        // For now, returning sample structure
        return collect([
            // ['date' => '2025-01-01', 'value' => 1000],
            // ['date' => '2025-01-02', 'value' => 1050],
            // etc.
        ]);
    }

    /**
     * Calculate baseline statistics
     */
    protected function calculateBaseline(Collection $data): array
    {
        $values = $data->pluck('value')->toArray();
        $mean = array_sum($values) / count($values);

        $variance = array_sum(array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values)) / count($values);

        $stdDev = sqrt($variance);

        return [
            'mean' => $mean,
            'std_dev' => $stdDev,
            'min' => min($values),
            'max' => max($values),
        ];
    }

    /**
     * Check if a value is an anomaly using statistical method
     */
    protected function isAnomaly(float $value, float $mean, float $stdDev, float $threshold = 3.0): bool
    {
        if ($stdDev == 0) {
            return false;
        }

        $zScore = abs(($value - $mean) / $stdDev);
        return $zScore > $threshold;
    }

    /**
     * Calculate Z-score
     */
    protected function calculateZScore(float $value, float $mean, float $stdDev): float
    {
        if ($stdDev == 0) {
            return 0;
        }

        return ($value - $mean) / $stdDev;
    }

    /**
     * Calculate anomaly severity
     */
    protected function calculateSeverity(float $value, float $mean, float $stdDev): string
    {
        if ($stdDev == 0) {
            return Anomaly::SEVERITY_LOW;
        }

        $zScore = abs(($value - $mean) / $stdDev);

        return match (true) {
            $zScore >= 5.0 => Anomaly::SEVERITY_CRITICAL,
            $zScore >= 4.0 => Anomaly::SEVERITY_HIGH,
            $zScore >= 3.0 => Anomaly::SEVERITY_MEDIUM,
            default => Anomaly::SEVERITY_LOW,
        };
    }

    /**
     * Calculate deviation percentage
     */
    protected function calculateDeviationPercentage(float $actual, float $expected): float
    {
        if ($expected == 0) {
            return 0;
        }

        return (($actual - $expected) / $expected) * 100;
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidenceScore(float $value, float $mean, float $stdDev): float
    {
        if ($stdDev == 0) {
            return 0;
        }

        $zScore = abs(($value - $mean) / $stdDev);

        // Convert Z-score to confidence (0-1 scale)
        // Higher Z-score = higher confidence it's an anomaly
        return min(1.0, $zScore / 5.0);
    }

    /**
     * Get recent critical anomalies
     */
    protected function getRecentCriticalAnomalies(string $orgId, int $limit = 10): Collection
    {
        return Anomaly::where('org_id', $orgId)
            ->critical()
            ->unresolved()
            ->with(['entity'])
            ->latest('detected_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get anomaly trends over time
     */
    protected function getAnomalyTrends(string $orgId): array
    {
        $trends = Anomaly::where('org_id', $orgId)
            ->where('detected_at', '>=', now()->subDays(90))
            ->select(
                DB::raw('DATE(detected_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) as critical_count', [Anomaly::SEVERITY_CRITICAL])
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trends->map(function ($trend) {
            return [
                'date' => $trend->date,
                'total' => $trend->count,
                'critical' => $trend->critical_count,
            ];
        })->toArray();
    }

    /**
     * Calculate false positive rate
     */
    protected function calculateFalsePositiveRate(string $orgId, $dateFrom): float
    {
        $totalAnomalies = Anomaly::where('org_id', $orgId)
            ->where('detected_at', '>=', $dateFrom)
            ->count();

        if ($totalAnomalies == 0) {
            return 0;
        }

        $falsePositives = Anomaly::where('org_id', $orgId)
            ->where('detected_at', '>=', $dateFrom)
            ->where('status', Anomaly::STATUS_FALSE_POSITIVE)
            ->count();

        return round(($falsePositives / $totalAnomalies) * 100, 2);
    }
}
