<?php

namespace App\Services\Intelligence;

use App\Models\Intelligence\Forecast;
use App\Models\Intelligence\PredictionModel;
use App\Models\Campaign\Campaign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ForecastService
{
    /**
     * Create a new forecast
     */
    public function createForecast(array $data): Forecast
    {
        $orgId = session('current_org_id');

        return DB::transaction(function () use ($data, $orgId) {
            $forecast = Forecast::create(array_merge($data, [
                'org_id' => $orgId,
                'created_by' => auth()->id(),
            ]));

            return $forecast->load(['predictionModel', 'campaign', 'creator']);
        });
    }

    /**
     * Generate forecasts for a campaign
     */
    public function generateForecasts(
        string $campaignId,
        array $metrics,
        int $forecastHorizon,
        ?string $modelId = null
    ): Collection {
        $orgId = session('current_org_id');
        $campaign = Campaign::findOrFail($campaignId);

        // Get or select prediction model
        if ($modelId) {
            $model = PredictionModel::findOrFail($modelId);
        } else {
            // Find best model for the first metric
            $model = PredictionModel::where('org_id', $orgId)
                ->where('target_metric', $metrics[0])
                ->active()
                ->orderBy('mape', 'asc')
                ->first();

            if (!$model) {
                throw new \Exception('No active prediction model found for the specified metric');
            }
        }

        $forecasts = collect();
        $startDate = now();

        foreach ($metrics as $metric) {
            // Generate historical data for the metric
            $historicalData = $this->getHistoricalData($campaignId, $metric);

            // Generate forecasts for each day in the horizon
            for ($day = 1; $day <= $forecastHorizon; $day++) {
                $forecastDate = $startDate->copy()->addDays($day);
                $prediction = $this->predict($historicalData, $day, $model);

                $forecast = Forecast::create([
                    'org_id' => $orgId,
                    'model_id' => $model->model_id,
                    'campaign_id' => $campaignId,
                    'metric_name' => $metric,
                    'forecast_date' => $forecastDate,
                    'predicted_value' => $prediction['value'],
                    'confidence_lower' => $prediction['confidence_lower'],
                    'confidence_upper' => $prediction['confidence_upper'],
                    'confidence_level' => 0.95, // 95% confidence interval
                    'forecast_horizon' => $day,
                    'metadata' => [
                        'algorithm' => $model->algorithm,
                        'model_version' => $model->version,
                        'historical_data_points' => count($historicalData),
                    ],
                    'created_by' => auth()->id(),
                ]);

                $forecasts->push($forecast);
            }
        }

        return $forecasts;
    }

    /**
     * Get forecast accuracy report
     */
    public function getAccuracyReport(
        string $orgId,
        ?string $campaignId = null,
        ?string $metric = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $query = Forecast::where('org_id', $orgId)
            ->whereNotNull('actuals');

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($metric) {
            $query->where('metric_name', $metric);
        }

        if ($dateFrom) {
            $query->where('forecast_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('forecast_date', '<=', $dateTo);
        }

        $forecasts = $query->get();

        if ($forecasts->isEmpty()) {
            return [
                'total_forecasts' => 0,
                'forecasts_with_actuals' => 0,
                'average_accuracy' => null,
                'mae' => null,
                'rmse' => null,
                'mape' => null,
            ];
        }

        $totalError = 0;
        $squaredError = 0;
        $percentageError = 0;
        $accuracySum = 0;

        foreach ($forecasts as $forecast) {
            $error = abs($forecast->predicted_value - $forecast->actuals);
            $totalError += $error;
            $squaredError += pow($error, 2);

            if ($forecast->actuals != 0) {
                $percentageError += abs(($forecast->predicted_value - $forecast->actuals) / $forecast->actuals);
            }

            if ($forecast->accuracy !== null) {
                $accuracySum += $forecast->accuracy;
            }
        }

        $count = $forecasts->count();

        return [
            'total_forecasts' => Forecast::where('org_id', $orgId)->count(),
            'forecasts_with_actuals' => $count,
            'average_accuracy' => round($accuracySum / $count, 4),
            'mae' => round($totalError / $count, 2), // Mean Absolute Error
            'rmse' => round(sqrt($squaredError / $count), 2), // Root Mean Squared Error
            'mape' => round(($percentageError / $count) * 100, 2), // Mean Absolute Percentage Error
            'by_metric' => $this->getAccuracyByMetric($forecasts),
            'by_horizon' => $this->getAccuracyByHorizon($forecasts),
            'trend' => $this->getAccuracyTrend($forecasts),
        ];
    }

    /**
     * Get analytics dashboard data
     */
    public function getAnalytics(string $orgId): array
    {
        $totalForecasts = Forecast::where('org_id', $orgId)->count();
        $activeForecasts = Forecast::where('org_id', $orgId)
            ->where('forecast_date', '>=', now())
            ->count();

        $recentForecasts = Forecast::where('org_id', $orgId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $accurateForecasts = Forecast::where('org_id', $orgId)
            ->whereNotNull('accuracy')
            ->where('accuracy', '>=', 0.8)
            ->count();

        $avgAccuracy = Forecast::where('org_id', $orgId)
            ->whereNotNull('accuracy')
            ->avg('accuracy');

        $forecastsByMetric = Forecast::where('org_id', $orgId)
            ->select('metric_name', DB::raw('count(*) as count'))
            ->groupBy('metric_name')
            ->get()
            ->pluck('count', 'metric_name');

        $forecastsByModel = Forecast::where('org_id', $orgId)
            ->join('cmis_intelligence.prediction_models', 'forecasts.model_id', '=', 'prediction_models.model_id')
            ->select('prediction_models.name', DB::raw('count(*) as count'))
            ->groupBy('prediction_models.name')
            ->get()
            ->pluck('count', 'name');

        return [
            'summary' => [
                'total_forecasts' => $totalForecasts,
                'active_forecasts' => $activeForecasts,
                'recent_forecasts' => $recentForecasts,
                'accurate_forecasts' => $accurateForecasts,
                'average_accuracy' => round($avgAccuracy ?? 0, 4),
            ],
            'by_metric' => $forecastsByMetric,
            'by_model' => $forecastsByModel,
            'recent_activity' => $this->getRecentActivity($orgId),
            'accuracy_trends' => $this->getAccuracyTrends($orgId),
        ];
    }

    /**
     * Get historical data for a campaign metric
     */
    protected function getHistoricalData(string $campaignId, string $metric): array
    {
        // This would typically query the metrics table or time-series data
        // For now, returning sample structure
        return [
            // ['date' => '2025-01-01', 'value' => 1000],
            // ['date' => '2025-01-02', 'value' => 1050],
            // etc.
        ];
    }

    /**
     * Generate prediction using model
     */
    protected function predict(array $historicalData, int $daysAhead, PredictionModel $model): array
    {
        // This would implement actual prediction logic based on the model's algorithm
        // For now, returning sample structure with confidence intervals

        // Simple moving average as placeholder
        if (empty($historicalData)) {
            return [
                'value' => 0,
                'confidence_lower' => 0,
                'confidence_upper' => 0,
            ];
        }

        $recentValues = array_slice($historicalData, -7); // Last 7 days
        $avg = array_sum(array_column($recentValues, 'value')) / count($recentValues);

        // Apply growth trend
        $growthRate = 0.02; // 2% daily growth placeholder
        $predictedValue = $avg * pow(1 + $growthRate, $daysAhead);

        // Calculate confidence interval (±10% placeholder)
        $margin = $predictedValue * 0.1;

        return [
            'value' => round($predictedValue, 2),
            'confidence_lower' => round($predictedValue - $margin, 2),
            'confidence_upper' => round($predictedValue + $margin, 2),
        ];
    }

    /**
     * Get accuracy breakdown by metric
     */
    protected function getAccuracyByMetric(Collection $forecasts): array
    {
        return $forecasts->groupBy('metric_name')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'average_accuracy' => round($group->avg('accuracy'), 4),
                    'mae' => round($group->avg(function ($f) {
                        return abs($f->predicted_value - $f->actuals);
                    }), 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get accuracy breakdown by forecast horizon
     */
    protected function getAccuracyByHorizon(Collection $forecasts): array
    {
        return $forecasts->groupBy('forecast_horizon')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'average_accuracy' => round($group->avg('accuracy'), 4),
                ];
            })
            ->sortKeys()
            ->toArray();
    }

    /**
     * Get accuracy trend over time
     */
    protected function getAccuracyTrend(Collection $forecasts): array
    {
        return $forecasts->groupBy(function ($forecast) {
            return $forecast->created_at->format('Y-m-d');
        })
            ->map(function ($group) {
                return round($group->avg('accuracy'), 4);
            })
            ->sortKeys()
            ->toArray();
    }

    /**
     * Get recent forecast activity
     */
    protected function getRecentActivity(string $orgId): Collection
    {
        return Forecast::where('org_id', $orgId)
            ->with(['campaign', 'predictionModel', 'creator'])
            ->latest('created_at')
            ->limit(10)
            ->get();
    }

    /**
     * Get accuracy trends over time
     */
    protected function getAccuracyTrends(string $orgId): array
    {
        $trends = Forecast::where('org_id', $orgId)
            ->whereNotNull('accuracy')
            ->where('created_at', '>=', now()->subDays(90))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(accuracy) as avg_accuracy'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trends->map(function ($trend) {
            return [
                'date' => $trend->date,
                'accuracy' => round($trend->avg_accuracy, 4),
                'count' => $trend->count,
            ];
        })->toArray();
    }
}
