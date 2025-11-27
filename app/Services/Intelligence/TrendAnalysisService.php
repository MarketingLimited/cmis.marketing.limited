<?php

namespace App\Services\Intelligence;

use App\Models\Intelligence\TrendAnalysis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrendAnalysisService
{
    /**
     * Analyze trends for an entity
     */
    public function analyzeTrends(
        string $entityType,
        string $entityId,
        array $metrics,
        string $dateFrom,
        string $dateTo,
        int $analysisWindow = 30
    ): Collection {
        $orgId = session('current_org_id');
        $analyses = collect();

        foreach ($metrics as $metric) {
            // Get historical data for the metric
            $historicalData = $this->getHistoricalMetricData(
                $entityType,
                $entityId,
                $metric,
                $dateFrom,
                $dateTo
            );

            if ($historicalData->count() < 10) {
                // Not enough data for meaningful trend analysis
                continue;
            }

            // Perform statistical analysis
            $trendStats = $this->calculateTrendStatistics($historicalData);

            // Detect pattern type
            $patternType = $this->detectPatternType($historicalData, $trendStats);

            // Check for seasonality
            $seasonality = $this->detectSeasonality($historicalData);

            $analysis = TrendAnalysis::create([
                'org_id' => $orgId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric_name' => $metric,
                'analysis_date' => now(),
                'start_date' => $dateFrom,
                'end_date' => $dateTo,
                'data_points' => $historicalData->count(),
                'trend_direction' => $trendStats['direction'],
                'growth_rate' => $trendStats['growth_rate'],
                'pattern_type' => $patternType,
                'volatility' => $trendStats['volatility'],
                'r_squared' => $trendStats['r_squared'],
                'statistical_significance' => $trendStats['p_value'],
                'seasonal_pattern' => $seasonality['has_seasonality'] ? $seasonality['pattern'] : null,
                'forecast_next_value' => $this->forecastNextValue($historicalData, $trendStats),
                'metadata' => [
                    'analysis_window' => $analysisWindow,
                    'mean' => $trendStats['mean'],
                    'std_dev' => $trendStats['std_dev'],
                    'min' => $trendStats['min'],
                    'max' => $trendStats['max'],
                    'seasonality' => $seasonality,
                ],
                'created_by' => auth()->id(),
            ]);

            $analyses->push($analysis);
        }

        return $analyses;
    }

    /**
     * Compare trends across multiple entities
     */
    public function compareEntities(
        array $entities,
        string $metric,
        string $dateFrom,
        string $dateTo
    ): array {
        $comparison = [];

        foreach ($entities as $entity) {
            $historicalData = $this->getHistoricalMetricData(
                $entity['type'],
                $entity['id'],
                $metric,
                $dateFrom,
                $dateTo
            );

            if ($historicalData->isEmpty()) {
                continue;
            }

            $trendStats = $this->calculateTrendStatistics($historicalData);

            $comparison[] = [
                'entity_type' => $entity['type'],
                'entity_id' => $entity['id'],
                'trend_direction' => $trendStats['direction'],
                'growth_rate' => $trendStats['growth_rate'],
                'volatility' => $trendStats['volatility'],
                'current_value' => $historicalData->last()['value'],
                'average_value' => $trendStats['mean'],
                'data_points' => $historicalData->count(),
            ];
        }

        // Rank entities by performance
        usort($comparison, function ($a, $b) {
            return $b['growth_rate'] <=> $a['growth_rate'];
        });

        return [
            'metric' => $metric,
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            'entities' => $comparison,
            'best_performer' => $comparison[0] ?? null,
            'worst_performer' => end($comparison) ?: null,
        ];
    }

    /**
     * Detect patterns in trend data
     */
    public function detectPatterns(
        string $entityType,
        string $entityId,
        string $metric,
        string $dateFrom,
        string $dateTo
    ): array {
        $historicalData = $this->getHistoricalMetricData(
            $entityType,
            $entityId,
            $metric,
            $dateFrom,
            $dateTo
        );

        if ($historicalData->count() < 10) {
            return ['error' => 'Insufficient data for pattern detection'];
        }

        $patterns = [];

        // Linear trend
        $linearFit = $this->fitLinearTrend($historicalData);
        $patterns['linear'] = [
            'r_squared' => $linearFit['r_squared'],
            'slope' => $linearFit['slope'],
            'intercept' => $linearFit['intercept'],
        ];

        // Cyclical patterns
        $cyclical = $this->detectCyclicalPattern($historicalData);
        if ($cyclical['detected']) {
            $patterns['cyclical'] = $cyclical;
        }

        // Seasonality
        $seasonality = $this->detectSeasonality($historicalData);
        if ($seasonality['has_seasonality']) {
            $patterns['seasonal'] = $seasonality;
        }

        // Outliers
        $outliers = $this->detectOutliers($historicalData);
        if (!empty($outliers)) {
            $patterns['outliers'] = $outliers;
        }

        return [
            'metric' => $metric,
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            'patterns' => $patterns,
            'dominant_pattern' => $this->identifyDominantPattern($patterns),
        ];
    }

    /**
     * Analyze seasonality
     */
    public function analyzeSeasonality(
        string $entityType,
        string $entityId,
        string $metric,
        int $periods = 12
    ): array {
        // Get at least 2 years of data for meaningful seasonality analysis
        $dateFrom = now()->subMonths($periods * 2)->toDateString();
        $dateTo = now()->toDateString();

        $historicalData = $this->getHistoricalMetricData(
            $entityType,
            $entityId,
            $metric,
            $dateFrom,
            $dateTo
        );

        if ($historicalData->count() < $periods * 2) {
            return [
                'has_seasonality' => false,
                'message' => 'Insufficient data for seasonality analysis',
            ];
        }

        $seasonality = $this->detectSeasonality($historicalData, $periods);

        return $seasonality;
    }

    /**
     * Get analytics dashboard data
     */
    public function getAnalytics(string $orgId): array
    {
        $totalAnalyses = TrendAnalysis::where('org_id', $orgId)->count();

        $recentAnalyses = TrendAnalysis::where('org_id', $orgId)
            ->where('analysis_date', '>=', now()->subDays(7))
            ->count();

        $significantTrends = TrendAnalysis::where('org_id', $orgId)
            ->significant()
            ->count();

        $upwardTrends = TrendAnalysis::where('org_id', $orgId)
            ->where('trend_direction', TrendAnalysis::DIRECTION_UPWARD)
            ->count();

        $downwardTrends = TrendAnalysis::where('org_id', $orgId)
            ->where('trend_direction', TrendAnalysis::DIRECTION_DOWNWARD)
            ->count();

        $analysesByMetric = TrendAnalysis::where('org_id', $orgId)
            ->select('metric_name', DB::raw('count(*) as count'))
            ->groupBy('metric_name')
            ->get()
            ->pluck('count', 'metric_name');

        $analysesByDirection = TrendAnalysis::where('org_id', $orgId)
            ->select('trend_direction', DB::raw('count(*) as count'))
            ->groupBy('trend_direction')
            ->get()
            ->pluck('count', 'trend_direction');

        return [
            'summary' => [
                'total_analyses' => $totalAnalyses,
                'recent_analyses' => $recentAnalyses,
                'significant_trends' => $significantTrends,
                'upward_trends' => $upwardTrends,
                'downward_trends' => $downwardTrends,
            ],
            'by_metric' => $analysesByMetric,
            'by_direction' => $analysesByDirection,
            'recent_significant' => $this->getRecentSignificantTrends($orgId),
            'volatility_alerts' => $this->getHighVolatilityTrends($orgId),
        ];
    }

    /**
     * Get trend insights
     */
    public function getInsights(string $orgId, int $days = 30, ?string $entityType = null): array
    {
        $query = TrendAnalysis::where('org_id', $orgId)
            ->where('analysis_date', '>=', now()->subDays($days));

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        $trends = $query->get();

        $insights = [
            'period_days' => $days,
            'total_trends_analyzed' => $trends->count(),
            'significant_trends' => $trends->where('statistical_significance', '<=', 0.05)->count(),
            'top_growing_metrics' => $this->getTopGrowingMetrics($trends),
            'declining_metrics' => $this->getDecliningMetrics($trends),
            'stable_metrics' => $this->getStableMetrics($trends),
            'volatile_metrics' => $this->getVolatileMetrics($trends),
            'seasonal_patterns_detected' => $trends->whereNotNull('seasonal_pattern')->count(),
        ];

        return $insights;
    }

    /**
     * Export trend data
     */
    public function exportTrends(?array $trendIds, string $format): array
    {
        $query = TrendAnalysis::query();

        if ($trendIds) {
            $query->whereIn('trend_id', $trendIds);
        }

        $trends = $query->with(['entity'])->get();

        $filename = 'trends_export_' . now()->format('Y-m-d_His') . '.' . $format;
        $path = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // Generate export based on format
        switch ($format) {
            case 'csv':
                $this->exportToCSV($trends, $path);
                break;
            case 'json':
                $this->exportToJSON($trends, $path);
                break;
            case 'xlsx':
                $this->exportToExcel($trends, $path);
                break;
        }

        return [
            'path' => $path,
            'filename' => $filename,
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
     * Calculate trend statistics
     */
    protected function calculateTrendStatistics(Collection $data): array
    {
        $values = $data->pluck('value')->toArray();
        $n = count($values);

        $mean = array_sum($values) / $n;
        $min = min($values);
        $max = max($values);

        // Calculate standard deviation
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $n;
        $stdDev = sqrt($variance);

        // Calculate volatility (coefficient of variation)
        $volatility = $mean != 0 ? ($stdDev / $mean) * 100 : 0;

        // Linear regression for growth rate
        $linearFit = $this->fitLinearTrend($data);
        $growthRate = $linearFit['slope'];

        // Determine direction
        $direction = match (true) {
            abs($growthRate) < 0.01 => TrendAnalysis::DIRECTION_STABLE,
            $volatility > 50 => TrendAnalysis::DIRECTION_VOLATILE,
            $growthRate > 0 => TrendAnalysis::DIRECTION_UPWARD,
            default => TrendAnalysis::DIRECTION_DOWNWARD,
        };

        return [
            'mean' => $mean,
            'std_dev' => $stdDev,
            'min' => $min,
            'max' => $max,
            'volatility' => round($volatility, 2),
            'growth_rate' => round($growthRate, 6),
            'direction' => $direction,
            'r_squared' => $linearFit['r_squared'],
            'p_value' => $this->calculatePValue($linearFit['r_squared'], $n),
        ];
    }

    /**
     * Fit linear trend
     */
    protected function fitLinearTrend(Collection $data): array
    {
        $n = $data->count();
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data->values() as $i => $point) {
            $x = $i;
            $y = $point['value'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Calculate R-squared
        $yMean = $sumY / $n;
        $ssTotal = 0;
        $ssResidual = 0;

        foreach ($data->values() as $i => $point) {
            $y = $point['value'];
            $yPred = $slope * $i + $intercept;
            $ssTotal += pow($y - $yMean, 2);
            $ssResidual += pow($y - $yPred, 2);
        }

        $rSquared = $ssTotal != 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => max(0, min(1, $rSquared)),
        ];
    }

    /**
     * Detect pattern type
     */
    protected function detectPatternType(Collection $data, array $trendStats): string
    {
        // Simple heuristic based on R-squared and volatility
        $rSquared = $trendStats['r_squared'];
        $volatility = $trendStats['volatility'];

        if ($rSquared > 0.8) {
            return TrendAnalysis::PATTERN_LINEAR;
        } elseif ($volatility > 50) {
            return TrendAnalysis::PATTERN_CYCLICAL;
        } else {
            return TrendAnalysis::PATTERN_EXPONENTIAL;
        }
    }

    /**
     * Detect seasonality
     */
    protected function detectSeasonality(Collection $data, int $periods = 7): array
    {
        // Simplified seasonality detection
        // In production, use autocorrelation or spectral analysis
        return [
            'has_seasonality' => false,
            'pattern' => null,
            'strength' => 0,
        ];
    }

    /**
     * Detect cyclical pattern
     */
    protected function detectCyclicalPattern(Collection $data): array
    {
        // Simplified cyclical pattern detection
        return [
            'detected' => false,
            'period' => null,
        ];
    }

    /**
     * Detect outliers
     */
    protected function detectOutliers(Collection $data): array
    {
        $values = $data->pluck('value')->toArray();
        $mean = array_sum($values) / count($values);
        $stdDev = sqrt(array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values));

        $outliers = [];
        foreach ($data as $point) {
            $zScore = abs(($point['value'] - $mean) / $stdDev);
            if ($zScore > 3) {
                $outliers[] = [
                    'date' => $point['date'],
                    'value' => $point['value'],
                    'z_score' => round($zScore, 2),
                ];
            }
        }

        return $outliers;
    }

    /**
     * Identify dominant pattern
     */
    protected function identifyDominantPattern(array $patterns): ?string
    {
        if (isset($patterns['linear']) && $patterns['linear']['r_squared'] > 0.8) {
            return 'linear';
        }

        if (isset($patterns['seasonal']) && $patterns['seasonal']['has_seasonality']) {
            return 'seasonal';
        }

        if (isset($patterns['cyclical']) && $patterns['cyclical']['detected']) {
            return 'cyclical';
        }

        return null;
    }

    /**
     * Forecast next value
     */
    protected function forecastNextValue(Collection $data, array $trendStats): ?float
    {
        if (empty($data)) {
            return null;
        }

        $lastValue = $data->last()['value'];
        $growthRate = $trendStats['growth_rate'];

        return round($lastValue * (1 + $growthRate), 2);
    }

    /**
     * Calculate p-value (simplified)
     */
    protected function calculatePValue(float $rSquared, int $n): float
    {
        // Simplified p-value calculation
        // In production, use proper statistical library
        return 1 - $rSquared;
    }

    /**
     * Get recent significant trends
     */
    protected function getRecentSignificantTrends(string $orgId, int $limit = 10): Collection
    {
        return TrendAnalysis::where('org_id', $orgId)
            ->significant()
            ->with(['entity'])
            ->latest('analysis_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get high volatility trends
     */
    protected function getHighVolatilityTrends(string $orgId, int $limit = 10): Collection
    {
        return TrendAnalysis::where('org_id', $orgId)
            ->where('volatility', '>', 50)
            ->with(['entity'])
            ->orderBy('volatility', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top growing metrics
     */
    protected function getTopGrowingMetrics(Collection $trends, int $limit = 5): Collection
    {
        return $trends->where('trend_direction', TrendAnalysis::DIRECTION_UPWARD)
            ->sortByDesc('growth_rate')
            ->take($limit)
            ->values();
    }

    /**
     * Get declining metrics
     */
    protected function getDecliningMetrics(Collection $trends, int $limit = 5): Collection
    {
        return $trends->where('trend_direction', TrendAnalysis::DIRECTION_DOWNWARD)
            ->sortBy('growth_rate')
            ->take($limit)
            ->values();
    }

    /**
     * Get stable metrics
     */
    protected function getStableMetrics(Collection $trends): Collection
    {
        return $trends->where('trend_direction', TrendAnalysis::DIRECTION_STABLE);
    }

    /**
     * Get volatile metrics
     */
    protected function getVolatileMetrics(Collection $trends, int $limit = 5): Collection
    {
        return $trends->where('trend_direction', TrendAnalysis::DIRECTION_VOLATILE)
            ->sortByDesc('volatility')
            ->take($limit)
            ->values();
    }

    /**
     * Export to CSV
     */
    protected function exportToCSV(Collection $trends, string $path): void
    {
        $file = fopen($path, 'w');
        fputcsv($file, ['Date', 'Metric', 'Direction', 'Growth Rate', 'Volatility', 'R-Squared']);

        foreach ($trends as $trend) {
            fputcsv($file, [
                $trend->analysis_date,
                $trend->metric_name,
                $trend->trend_direction,
                $trend->growth_rate,
                $trend->volatility,
                $trend->r_squared,
            ]);
        }

        fclose($file);
    }

    /**
     * Export to JSON
     */
    protected function exportToJSON(Collection $trends, string $path): void
    {
        file_put_contents($path, $trends->toJson(JSON_PRETTY_PRINT));
    }

    /**
     * Export to Excel
     */
    protected function exportToExcel(Collection $trends, string $path): void
    {
        // Would use a library like PhpSpreadsheet
        // For now, fallback to CSV
        $this->exportToCSV($trends, $path);
    }
}
