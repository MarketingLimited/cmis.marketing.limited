<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\{DB, Cache, Log};
use Carbon\Carbon;

/**
 * Custom Metrics & KPI Tracking Service (Phase 7)
 *
 * Allows organizations to define and track custom metrics and KPIs
 */
class CustomMetricsService
{
    // Metric calculation types
    const CALC_SUM = 'sum';
    const CALC_AVG = 'average';
    const CALC_COUNT = 'count';
    const CALC_RATIO = 'ratio';
    const CALC_FORMULA = 'formula';

    // KPI status
    const STATUS_ON_TRACK = 'on_track';
    const STATUS_AT_RISK = 'at_risk';
    const STATUS_OFF_TRACK = 'off_track';
    const STATUS_EXCEEDED = 'exceeded';

    /**
     * Create a custom metric definition
     *
     * @param array $definition
     * @return array
     */
    public function createMetric(array $definition): array
    {
        try {
            $metricId = \Ramsey\Uuid\Uuid::uuid4()->toString();

            DB::table('cmis_analytics.custom_metrics')->insert([
                'metric_id' => $metricId,
                'org_id' => $definition['org_id'],
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
                'calculation_type' => $definition['calculation_type'],
                'source_metrics' => json_encode($definition['source_metrics'] ?? []),
                'formula' => $definition['formula'] ?? null,
                'unit' => $definition['unit'] ?? null,
                'decimal_places' => $definition['decimal_places'] ?? 2,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return [
                'success' => true,
                'metric_id' => $metricId,
                'message' => 'Custom metric created successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create custom metric', [
                'definition' => $definition,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate custom metric value
     *
     * @param string $metricId
     * @param string $entityType
     * @param string $entityId
     * @param array $dateRange
     * @return array
     */
    public function calculateMetric(
        string $metricId,
        string $entityType,
        string $entityId,
        array $dateRange = []
    ): array {
        try {
            // Get metric definition
            $metric = DB::table('cmis_analytics.custom_metrics')
                ->where('metric_id', $metricId)
                ->where('is_active', true)
                ->first();

            if (!$metric) {
                return [
                    'success' => false,
                    'error' => 'Metric not found or inactive'
                ];
            }

            $sourceMetrics = json_decode($metric->source_metrics, true);

            // Get date range
            $startDate = $dateRange['start'] ?? Carbon::now()->subDays(30);
            $endDate = $dateRange['end'] ?? Carbon::now();

            // Fetch source data
            $sourceData = $this->fetchSourceData(
                $entityType,
                $entityId,
                $sourceMetrics,
                $startDate,
                $endDate
            );

            // Perform calculation based on type
            $value = match($metric->calculation_type) {
                self::CALC_SUM => $this->calculateSum($sourceData),
                self::CALC_AVG => $this->calculateAverage($sourceData),
                self::CALC_COUNT => $this->calculateCount($sourceData),
                self::CALC_RATIO => $this->calculateRatio($sourceData, $sourceMetrics),
                self::CALC_FORMULA => $this->calculateFormula($sourceData, $metric->formula),
                default => 0
            };

            // Round to specified decimal places
            $value = round($value, $metric->decimal_places);

            return [
                'success' => true,
                'metric_id' => $metricId,
                'metric_name' => $metric->name,
                'value' => $value,
                'unit' => $metric->unit,
                'calculation_type' => $metric->calculation_type,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate custom metric', [
                'metric_id' => $metricId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fetch source data for calculation
     *
     * @param string $entityType
     * @param string $entityId
     * @param array $sourceMetrics
     * @param $startDate
     * @param $endDate
     * @return array
     */
    protected function fetchSourceData(
        string $entityType,
        string $entityId,
        array $sourceMetrics,
        $startDate,
        $endDate
    ): array {
        $data = [];

        foreach ($sourceMetrics as $metric) {
            $query = DB::table('cmis_analytics.campaign_performance')
                ->where('campaign_id', $entityId)
                ->whereBetween('date', [$startDate, $endDate]);

            $data[$metric] = $query->sum($metric);
        }

        return $data;
    }

    /**
     * Calculate sum
     *
     * @param array $data
     * @return float
     */
    protected function calculateSum(array $data): float
    {
        return array_sum($data);
    }

    /**
     * Calculate average
     *
     * @param array $data
     * @return float
     */
    protected function calculateAverage(array $data): float
    {
        $count = count($data);
        return $count > 0 ? array_sum($data) / $count : 0;
    }

    /**
     * Calculate count
     *
     * @param array $data
     * @return float
     */
    protected function calculateCount(array $data): float
    {
        return count($data);
    }

    /**
     * Calculate ratio
     *
     * @param array $data
     * @param array $sourceMetrics
     * @return float
     */
    protected function calculateRatio(array $data, array $sourceMetrics): float
    {
        if (count($sourceMetrics) < 2) {
            return 0;
        }

        $numerator = $data[$sourceMetrics[0]] ?? 0;
        $denominator = $data[$sourceMetrics[1]] ?? 0;

        return $denominator > 0 ? ($numerator / $denominator) * 100 : 0;
    }

    /**
     * Calculate using formula
     *
     * @param array $data
     * @param string|null $formula
     * @return float
     */
    protected function calculateFormula(array $data, ?string $formula): float
    {
        if (!$formula) {
            return 0;
        }

        try {
            // Replace variable names with values
            $expression = $formula;
            foreach ($data as $key => $value) {
                $expression = str_replace('${' . $key . '}', $value, $expression);
            }

            // Evaluate safe mathematical expression
            $result = $this->evaluateMathExpression($expression);

            return is_numeric($result) ? (float) $result : 0;

        } catch (\Exception $e) {
            Log::error('Formula evaluation failed', [
                'formula' => $formula,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Safely evaluate mathematical expression
     *
     * @param string $expression
     * @return float
     */
    protected function evaluateMathExpression(string $expression): float
    {
        // Remove any non-mathematical characters for safety
        $expression = preg_replace('/[^0-9+\-*\/\(\)\.\s]/', '', $expression);

        // Use eval with extreme caution - only for mathematical expressions
        try {
            $result = @eval("return $expression;");
            return is_numeric($result) ? (float) $result : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Create a KPI definition
     *
     * @param array $definition
     * @return array
     */
    public function createKPI(array $definition): array
    {
        try {
            $kpiId = \Ramsey\Uuid\Uuid::uuid4()->toString();

            DB::table('cmis_analytics.kpis')->insert([
                'kpi_id' => $kpiId,
                'org_id' => $definition['org_id'],
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
                'metric_id' => $definition['metric_id'] ?? null,
                'target_value' => $definition['target_value'],
                'warning_threshold' => $definition['warning_threshold'] ?? ($definition['target_value'] * 0.8),
                'critical_threshold' => $definition['critical_threshold'] ?? ($definition['target_value'] * 0.6),
                'period' => $definition['period'] ?? 'monthly',
                'is_higher_better' => $definition['is_higher_better'] ?? true,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return [
                'success' => true,
                'kpi_id' => $kpiId,
                'message' => 'KPI created successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create KPI', [
                'definition' => $definition,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Evaluate KPI status
     *
     * @param string $kpiId
     * @param string $entityType
     * @param string $entityId
     * @return array
     */
    public function evaluateKPI(
        string $kpiId,
        string $entityType,
        string $entityId
    ): array {
        try {
            // Get KPI definition
            $kpi = DB::table('cmis_analytics.kpis')
                ->where('kpi_id', $kpiId)
                ->where('is_active', true)
                ->first();

            if (!$kpi) {
                return [
                    'success' => false,
                    'error' => 'KPI not found or inactive'
                ];
            }

            // Calculate current metric value
            if ($kpi->metric_id) {
                $metricResult = $this->calculateMetric(
                    $kpi->metric_id,
                    $entityType,
                    $entityId,
                    $this->getPeriodDateRange($kpi->period)
                );

                if (!$metricResult['success']) {
                    return $metricResult;
                }

                $currentValue = $metricResult['value'];
            } else {
                // Use direct value if no metric defined
                $currentValue = 0;
            }

            // Determine status
            $status = $this->determineKPIStatus(
                $currentValue,
                $kpi->target_value,
                $kpi->warning_threshold,
                $kpi->critical_threshold,
                $kpi->is_higher_better
            );

            // Calculate progress percentage
            $progress = $kpi->target_value > 0
                ? round(($currentValue / $kpi->target_value) * 100, 2)
                : 0;

            // Calculate gap
            $gap = $kpi->target_value - $currentValue;
            $gapPercentage = $kpi->target_value > 0
                ? round(($gap / $kpi->target_value) * 100, 2)
                : 0;

            return [
                'success' => true,
                'kpi_id' => $kpiId,
                'kpi_name' => $kpi->name,
                'status' => $status,
                'current_value' => $currentValue,
                'target_value' => $kpi->target_value,
                'progress_percentage' => $progress,
                'gap' => $gap,
                'gap_percentage' => $gapPercentage,
                'period' => $kpi->period,
                'thresholds' => [
                    'warning' => $kpi->warning_threshold,
                    'critical' => $kpi->critical_threshold
                ],
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to evaluate KPI', [
                'kpi_id' => $kpiId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Determine KPI status based on thresholds
     *
     * @param float $currentValue
     * @param float $targetValue
     * @param float $warningThreshold
     * @param float $criticalThreshold
     * @param bool $isHigherBetter
     * @return string
     */
    protected function determineKPIStatus(
        float $currentValue,
        float $targetValue,
        float $warningThreshold,
        float $criticalThreshold,
        bool $isHigherBetter
    ): string {
        if ($isHigherBetter) {
            // For metrics where higher is better
            if ($currentValue >= $targetValue) {
                return self::STATUS_EXCEEDED;
            } elseif ($currentValue >= $warningThreshold) {
                return self::STATUS_ON_TRACK;
            } elseif ($currentValue >= $criticalThreshold) {
                return self::STATUS_AT_RISK;
            } else {
                return self::STATUS_OFF_TRACK;
            }
        } else {
            // For metrics where lower is better
            if ($currentValue <= $targetValue) {
                return self::STATUS_EXCEEDED;
            } elseif ($currentValue <= $warningThreshold) {
                return self::STATUS_ON_TRACK;
            } elseif ($currentValue <= $criticalThreshold) {
                return self::STATUS_AT_RISK;
            } else {
                return self::STATUS_OFF_TRACK;
            }
        }
    }

    /**
     * Get date range for period
     *
     * @param string $period
     * @return array
     */
    protected function getPeriodDateRange(string $period): array
    {
        $end = Carbon::now();

        $start = match($period) {
            'daily' => Carbon::now()->startOfDay(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'quarterly' => Carbon::now()->startOfQuarter(),
            'yearly' => Carbon::now()->startOfYear(),
            default => Carbon::now()->subDays(30)
        };

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    /**
     * Get all KPIs for organization
     *
     * @param string $orgId
     * @param string|null $period
     * @return array
     */
    public function getOrganizationKPIs(string $orgId, ?string $period = null): array
    {
        try {
            $query = DB::table('cmis_analytics.kpis')
                ->where('org_id', $orgId)
                ->where('is_active', true);

            if ($period) {
                $query->where('period', $period);
            }

            $kpis = $query->get();

            return [
                'success' => true,
                'org_id' => $orgId,
                'kpis' => $kpis,
                'count' => count($kpis)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get organization KPIs', [
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
     * Get KPI dashboard for organization
     *
     * @param string $orgId
     * @param string $entityType
     * @param string $entityId
     * @return array
     */
    public function getKPIDashboard(
        string $orgId,
        string $entityType,
        string $entityId
    ): array {
        try {
            $kpis = DB::table('cmis_analytics.kpis')
                ->where('org_id', $orgId)
                ->where('is_active', true)
                ->get();

            $dashboard = [];
            $statusCounts = [
                self::STATUS_EXCEEDED => 0,
                self::STATUS_ON_TRACK => 0,
                self::STATUS_AT_RISK => 0,
                self::STATUS_OFF_TRACK => 0
            ];

            foreach ($kpis as $kpi) {
                $evaluation = $this->evaluateKPI($kpi->kpi_id, $entityType, $entityId);

                if ($evaluation['success']) {
                    $dashboard[] = $evaluation;
                    $statusCounts[$evaluation['status']]++;
                }
            }

            // Calculate overall health score (0-100)
            $totalKPIs = count($dashboard);
            $healthScore = 0;

            if ($totalKPIs > 0) {
                $healthScore = round((
                    ($statusCounts[self::STATUS_EXCEEDED] * 100) +
                    ($statusCounts[self::STATUS_ON_TRACK] * 75) +
                    ($statusCounts[self::STATUS_AT_RISK] * 50) +
                    ($statusCounts[self::STATUS_OFF_TRACK] * 25)
                ) / $totalKPIs, 2);
            }

            return [
                'success' => true,
                'org_id' => $orgId,
                'kpis' => $dashboard,
                'summary' => [
                    'total_kpis' => $totalKPIs,
                    'status_counts' => $statusCounts,
                    'health_score' => $healthScore
                ],
                'timestamp' => Carbon::now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get KPI dashboard', [
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
     * Delete custom metric
     *
     * @param string $metricId
     * @return array
     */
    public function deleteMetric(string $metricId): array
    {
        try {
            DB::table('cmis_analytics.custom_metrics')
                ->where('metric_id', $metricId)
                ->update([
                    'is_active' => false,
                    'updated_at' => Carbon::now()
                ]);

            return [
                'success' => true,
                'message' => 'Custom metric deleted successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete KPI
     *
     * @param string $kpiId
     * @return array
     */
    public function deleteKPI(string $kpiId): array
    {
        try {
            DB::table('cmis_analytics.kpis')
                ->where('kpi_id', $kpiId)
                ->update([
                    'is_active' => false,
                    'updated_at' => Carbon::now()
                ]);

            return [
                'success' => true,
                'message' => 'KPI deleted successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
