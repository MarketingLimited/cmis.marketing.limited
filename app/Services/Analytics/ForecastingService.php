<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Forecast;
use App\Models\Analytics\Anomaly;
use App\Models\Analytics\Recommendation;
use App\Models\Analytics\TrendAnalysis;
use Illuminate\Support\Facades\DB;

/**
 * Forecasting Service (Phase 16)
 *
 * Provides predictive analytics, anomaly detection, trend analysis,
 * and intelligent recommendations
 */
class ForecastingService
{
    /**
     * Generate forecasts for a metric using simple moving average
     */
    public function generateForecast(
        string $orgId,
        string $entityType,
        string $entityId,
        string $metric,
        int $days = 30,
        string $forecastType = 'moving_average'
    ): array {
        // Fetch historical data (last 90 days)
        $historicalData = $this->fetchHistoricalData($entityType, $entityId, $metric, 90);

        if (count($historicalData) < 7) {
            throw new \RuntimeException('Insufficient historical data for forecasting (minimum 7 days required)');
        }

        $forecasts = [];

        switch ($forecastType) {
            case 'moving_average':
                $forecasts = $this->movingAverageForecast($historicalData, $days);
                break;
            case 'linear_regression':
                $forecasts = $this->linearRegressionForecast($historicalData, $days);
                break;
            case 'weighted_average':
                $forecasts = $this->weightedAverageForecast($historicalData, $days);
                break;
            default:
                $forecasts = $this->movingAverageForecast($historicalData, $days);
        }

        // Store forecasts in database
        $storedForecasts = [];

        foreach ($forecasts as $forecastData) {
            $forecast = Forecast::create([
                'org_id' => $orgId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metric' => $metric,
                'forecast_type' => $forecastType,
                'forecast_date' => $forecastData['date'],
                'predicted_value' => $forecastData['predicted_value'],
                'confidence_lower' => $forecastData['confidence_lower'],
                'confidence_upper' => $forecastData['confidence_upper'],
                'confidence_level' => 95.00,
                'generated_at' => now()
            ]);

            $storedForecasts[] = $forecast;
        }

        return $storedForecasts;
    }

    /**
     * Moving average forecast (7-day window)
     */
    protected function movingAverageForecast(array $data, int $days): array
    {
        $window = min(7, count($data));
        $values = array_column($data, 'value');
        $lastValues = array_slice($values, -$window);
        $avgValue = array_sum($lastValues) / count($lastValues);

        // Calculate standard deviation for confidence interval
        $variance = 0;
        foreach ($lastValues as $value) {
            $variance += pow($value - $avgValue, 2);
        }
        $stdDev = sqrt($variance / count($lastValues));
        $margin = 1.96 * $stdDev; // 95% confidence

        $forecasts = [];
        $lastDate = end($data)['date'];

        for ($i = 1; $i <= $days; $i++) {
            $forecastDate = date('Y-m-d', strtotime($lastDate . " +{$i} days"));

            $forecasts[] = [
                'date' => $forecastDate,
                'predicted_value' => round($avgValue, 2),
                'confidence_lower' => round(max(0, $avgValue - $margin), 2),
                'confidence_upper' => round($avgValue + $margin, 2)
            ];
        }

        return $forecasts;
    }

    /**
     * Linear regression forecast
     */
    protected function linearRegressionForecast(array $data, int $days): array
    {
        $n = count($data);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $i => $point) {
            $x = $i;
            $y = $point['value'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        // Calculate slope (m) and intercept (b)
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Calculate residual standard error
        $residuals = [];
        foreach ($data as $i => $point) {
            $predicted = $slope * $i + $intercept;
            $residuals[] = $point['value'] - $predicted;
        }
        $rse = sqrt(array_sum(array_map(fn($r) => $r * $r, $residuals)) / ($n - 2));
        $margin = 1.96 * $rse;

        $forecasts = [];
        $lastDate = end($data)['date'];

        for ($i = 1; $i <= $days; $i++) {
            $forecastDate = date('Y-m-d', strtotime($lastDate . " +{$i} days"));
            $x = $n + $i - 1;
            $predictedValue = $slope * $x + $intercept;

            $forecasts[] = [
                'date' => $forecastDate,
                'predicted_value' => round(max(0, $predictedValue), 2),
                'confidence_lower' => round(max(0, $predictedValue - $margin), 2),
                'confidence_upper' => round($predictedValue + $margin, 2)
            ];
        }

        return $forecasts;
    }

    /**
     * Weighted average forecast (recent data weighted more)
     */
    protected function weightedAverageForecast(array $data, int $days): array
    {
        $window = min(14, count($data));
        $values = array_slice(array_column($data, 'value'), -$window);

        $weightedSum = 0;
        $weightSum = 0;

        foreach ($values as $i => $value) {
            $weight = $i + 1; // Linear weights (1, 2, 3, ...)
            $weightedSum += $value * $weight;
            $weightSum += $weight;
        }

        $weightedAvg = $weightedSum / $weightSum;

        // Standard deviation for confidence
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $weightedAvg, 2);
        }
        $stdDev = sqrt($variance / count($values));
        $margin = 1.96 * $stdDev;

        $forecasts = [];
        $lastDate = end($data)['date'];

        for ($i = 1; $i <= $days; $i++) {
            $forecastDate = date('Y-m-d', strtotime($lastDate . " +{$i} days"));

            $forecasts[] = [
                'date' => $forecastDate,
                'predicted_value' => round($weightedAvg, 2),
                'confidence_lower' => round(max(0, $weightedAvg - $margin), 2),
                'confidence_upper' => round($weightedAvg + $margin, 2)
            ];
        }

        return $forecasts;
    }

    /**
     * Detect anomalies in data
     */
    public function detectAnomalies(
        string $orgId,
        string $entityType,
        string $entityId,
        string $metric,
        int $days = 30
    ): array {
        $data = $this->fetchHistoricalData($entityType, $entityId, $metric, $days);

        if (count($data) < 7) {
            return [];
        }

        $values = array_column($data, 'value');
        $mean = array_sum($values) / count($values);

        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        $stdDev = sqrt($variance / count($values));

        $anomalies = [];

        foreach ($data as $point) {
            $zScore = $stdDev > 0 ? abs($point['value'] - $mean) / $stdDev : 0;

            // Detect anomaly if z-score > 2 (95% confidence) or > 3 (99% confidence)
            if ($zScore > 2) {
                $deviationPct = $mean > 0 ? (($point['value'] - $mean) / $mean) * 100 : 0;

                $anomaly = Anomaly::create([
                    'org_id' => $orgId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'metric' => $metric,
                    'anomaly_type' => $deviationPct > 0 ? 'spike' : 'drop',
                    'severity' => $zScore > 3 ? 'critical' : ($zScore > 2.5 ? 'high' : 'medium'),
                    'expected_value' => round($mean, 2),
                    'actual_value' => $point['value'],
                    'deviation_percentage' => round($deviationPct, 2),
                    'confidence_score' => round(min(100, $zScore * 30), 2),
                    'detected_date' => $point['date'],
                    'description' => $this->generateAnomalyDescription($metric, $deviationPct, $zScore),
                    'status' => 'new'
                ]);

                $anomalies[] = $anomaly;
            }
        }

        return $anomalies;
    }

    /**
     * Analyze trends in data
     */
    public function analyzeTrends(
        string $orgId,
        string $entityType,
        string $entityId,
        string $metric,
        int $days = 30
    ): TrendAnalysis {
        $data = $this->fetchHistoricalData($entityType, $entityId, $metric, $days);

        if (count($data) < 7) {
            throw new \RuntimeException('Insufficient data for trend analysis');
        }

        // Calculate linear regression slope
        $n = count($data);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $i => $point) {
            $x = $i;
            $y = $point['value'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        // Determine trend type
        $avgValue = $sumY / $n;
        $trendStrength = $avgValue > 0 ? ($slope / $avgValue) * 100 : 0;

        if (abs($trendStrength) < 1) {
            $trendType = 'stable';
        } elseif ($trendStrength > 5) {
            $trendType = 'upward';
        } elseif ($trendStrength < -5) {
            $trendType = 'downward';
        } else {
            $trendType = 'stable';
        }

        // Calculate R²
        $rSquared = $this->calculateRSquared($data, $slope, $avgValue);

        $trend = TrendAnalysis::create([
            'org_id' => $orgId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metric' => $metric,
            'trend_type' => $trendType,
            'trend_strength' => round($trendStrength, 2),
            'confidence' => round($rSquared * 100, 2),
            'period_start' => $data[0]['date'],
            'period_end' => end($data)['date'],
            'data_points' => $n,
            'slope' => round($slope, 4),
            'interpretation' => $this->generateTrendInterpretation($trendType, $trendStrength, $metric)
        ]);

        return $trend;
    }

    /**
     * Generate intelligent recommendations
     */
    public function generateRecommendations(
        string $orgId,
        string $entityType,
        string $entityId
    ): array {
        $recommendations = [];

        // Analyze multiple metrics
        $metrics = ['revenue', 'conversions', 'spend', 'roi'];

        foreach ($metrics as $metric) {
            try {
                $data = $this->fetchHistoricalData($entityType, $entityId, $metric, 30);

                if (count($data) < 7) {
                    continue;
                }

                // Check for declining performance
                $recentAvg = array_sum(array_slice(array_column($data, 'value'), -7)) / 7;
                $overallAvg = array_sum(array_column($data, 'value')) / count($data);

                if ($recentAvg < $overallAvg * 0.8) {
                    // Performance dropped by 20%+
                    $recommendations[] = $this->createRecommendation(
                        $orgId,
                        $entityType,
                        $entityId,
                        'performance_decline',
                        'performance',
                        'high',
                        75.0,
                        "{$metric} has declined significantly",
                        "Recent {$metric} is 20% below average. Consider reviewing campaign settings or creative.",
                        [
                            'action' => 'review_settings',
                            'metric' => $metric,
                            'decline_percentage' => round((($overallAvg - $recentAvg) / $overallAvg) * 100, 2)
                        ]
                    );
                }

                // Check for strong upward trend
                $trend = $this->analyzeTrends($orgId, $entityType, $entityId, $metric, 30);

                if ($trend->trend_type === 'upward' && $trend->trend_strength > 10) {
                    $recommendations[] = $this->createRecommendation(
                        $orgId,
                        $entityType,
                        $entityId,
                        'budget_increase',
                        'budget',
                        'medium',
                        80.0,
                        "Strong {$metric} growth detected",
                        "Consider increasing budget to capitalize on positive performance trend.",
                        [
                            'action' => 'increase_budget',
                            'metric' => $metric,
                            'trend_strength' => $trend->trend_strength,
                            'suggested_increase' => '20%'
                        ]
                    );
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $recommendations;
    }

    /**
     * Create recommendation
     */
    protected function createRecommendation(
        string $orgId,
        string $entityType,
        string $entityId,
        string $type,
        string $category,
        string $priority,
        float $confidence,
        string $title,
        string $description,
        array $actionDetails
    ): Recommendation {
        return Recommendation::create([
            'org_id' => $orgId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'recommendation_type' => $type,
            'category' => $category,
            'priority' => $priority,
            'confidence_score' => $confidence,
            'title' => $title,
            'description' => $description,
            'action_details' => $actionDetails,
            'status' => 'pending',
            'expires_at' => now()->addDays(7)
        ]);
    }

    /**
     * Fetch historical data from campaign metrics
     */
    protected function fetchHistoricalData(
        string $entityType,
        string $entityId,
        string $metric,
        int $days
    ): array {
        // Simplified - would query actual metrics tables based on entity type
        // For now, return mock structure
        return [];
    }

    /**
     * Calculate R² (coefficient of determination)
     */
    protected function calculateRSquared(array $data, float $slope, float $intercept): float
    {
        $values = array_column($data, 'value');
        $mean = array_sum($values) / count($values);

        $ssTotal = 0;
        $ssResidual = 0;

        foreach ($data as $i => $point) {
            $predicted = $slope * $i + $intercept;
            $ssTotal += pow($point['value'] - $mean, 2);
            $ssResidual += pow($point['value'] - $predicted, 2);
        }

        return $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;
    }

    /**
     * Generate anomaly description
     */
    protected function generateAnomalyDescription(string $metric, float $deviationPct, float $zScore): string
    {
        $direction = $deviationPct > 0 ? 'increased' : 'decreased';
        $severity = $zScore > 3 ? 'significantly' : 'noticeably';

        return ucfirst($metric) . " {$direction} {$severity} (" . abs(round($deviationPct, 1)) . "%) compared to expected baseline.";
    }

    /**
     * Generate trend interpretation
     */
    protected function generateTrendInterpretation(string $trendType, float $strength, string $metric): string
    {
        $interpretations = [
            'upward' => ucfirst($metric) . " is showing positive growth (" . round($strength, 1) . "% trend strength).",
            'downward' => ucfirst($metric) . " is declining (" . abs(round($strength, 1)) . "% trend strength). Review may be needed.",
            'stable' => ucfirst($metric) . " is stable with minimal variation.",
            'seasonal' => ucfirst($metric) . " shows seasonal patterns.",
            'volatile' => ucfirst($metric) . " is highly volatile with unpredictable changes."
        ];

        return $interpretations[$trendType] ?? "Trend analysis for {$metric}.";
    }
}
