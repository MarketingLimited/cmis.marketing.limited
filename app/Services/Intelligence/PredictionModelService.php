<?php

namespace App\Services\Intelligence;

use App\Models\Intelligence\PredictionModel;
use App\Models\Intelligence\Forecast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PredictionModelService
{
    /**
     * Create a new prediction model
     */
    public function createModel(array $data): PredictionModel
    {
        $orgId = session('current_org_id');

        return DB::transaction(function () use ($data, $orgId) {
            $model = PredictionModel::create(array_merge($data, [
                'org_id' => $orgId,
                'status' => PredictionModel::STATUS_DRAFT,
                'version' => 1,
                'created_by' => auth()->id(),
            ]));

            return $model->load('creator');
        });
    }

    /**
     * Train a prediction model
     */
    public function trainModel(
        PredictionModel $model,
        ?array $trainingData = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        float $validationSplit = 0.2
    ): array {
        DB::beginTransaction();

        try {
            // Get training data if not provided
            if (!$trainingData) {
                $trainingData = $this->prepareTrainingData(
                    $model,
                    $dateFrom ?? now()->subMonths(6)->toDateString(),
                    $dateTo ?? now()->toDateString()
                );
            }

            if (empty($trainingData)) {
                throw new \Exception('No training data available');
            }

            // Split data into training and validation sets
            $splitIndex = (int)(count($trainingData) * (1 - $validationSplit));
            $trainSet = array_slice($trainingData, 0, $splitIndex);
            $validationSet = array_slice($trainingData, $splitIndex);

            // Train the model
            $modelParams = $this->trainAlgorithm($model->algorithm, $trainSet, $model->hyperparameters);

            // Validate the model
            $validationMetrics = $this->validateModel($modelParams, $validationSet);

            // Calculate accuracy metrics
            $accuracyMetrics = [
                'mae' => $validationMetrics['mae'],
                'rmse' => $validationMetrics['rmse'],
                'mape' => $validationMetrics['mape'],
                'r_squared' => $validationMetrics['r_squared'],
            ];

            // Update model with training results
            $model->recordTraining($accuracyMetrics, count($trainingData));

            // Store model parameters
            $model->update([
                'model_parameters' => $modelParams,
                'training_metadata' => [
                    'training_samples' => count($trainSet),
                    'validation_samples' => count($validationSet),
                    'validation_split' => $validationSplit,
                    'trained_at' => now()->toDateTimeString(),
                ],
            ]);

            DB::commit();

            return [
                'success' => true,
                'model' => $model,
                'accuracy_metrics' => $accuracyMetrics,
                'training_data_count' => count($trainSet),
                'validation_data_count' => count($validationSet),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Evaluate model performance
     */
    public function evaluateModel(
        PredictionModel $model,
        ?array $testData = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        // Get test data if not provided
        if (!$testData) {
            $testData = $this->prepareTrainingData(
                $model,
                $dateFrom ?? now()->subMonths(1)->toDateString(),
                $dateTo ?? now()->toDateString()
            );
        }

        if (empty($testData)) {
            return [
                'error' => 'No test data available',
            ];
        }

        $validationMetrics = $this->validateModel($model->model_parameters, $testData);

        return [
            'test_data_count' => count($testData),
            'mae' => $validationMetrics['mae'],
            'rmse' => $validationMetrics['rmse'],
            'mape' => $validationMetrics['mape'],
            'r_squared' => $validationMetrics['r_squared'],
            'predictions' => $validationMetrics['predictions'],
        ];
    }

    /**
     * Retrain model with new data
     */
    public function retrainModel(
        PredictionModel $model,
        bool $incremental = false,
        ?array $hyperparameters = null
    ): array {
        // Update hyperparameters if provided
        if ($hyperparameters) {
            $model->update(['hyperparameters' => $hyperparameters]);
        }

        // Determine date range for retraining
        if ($incremental && $model->last_trained_at) {
            // Incremental: only use data since last training
            $dateFrom = $model->last_trained_at->toDateString();
            $dateTo = now()->toDateString();
        } else {
            // Full retrain: use all available data
            $dateFrom = now()->subMonths(12)->toDateString();
            $dateTo = now()->toDateString();
        }

        return $this->trainModel($model, null, $dateFrom, $dateTo);
    }

    /**
     * Compare model accuracy
     */
    public function compareModelAccuracy(
        string $orgId,
        ?string $metric = null,
        ?string $algorithm = null
    ): array {
        $query = PredictionModel::where('org_id', $orgId);

        if ($metric) {
            $query->where('target_metric', $metric);
        }

        if ($algorithm) {
            $query->where('algorithm', $algorithm);
        }

        $models = $query->whereNotNull('mae')->get();

        $comparison = $models->map(function ($model) {
            return [
                'model_id' => $model->model_id,
                'name' => $model->name,
                'algorithm' => $model->algorithm,
                'target_metric' => $model->target_metric,
                'mae' => $model->mae,
                'rmse' => $model->rmse,
                'mape' => $model->mape,
                'r_squared' => $model->r_squared,
                'training_data_count' => $model->training_data_count,
                'last_trained_at' => $model->last_trained_at,
                'version' => $model->version,
            ];
        })->sortBy('mape')->values();

        return [
            'total_models' => $comparison->count(),
            'models' => $comparison,
            'best_model' => $comparison->first(),
            'average_mape' => round($comparison->avg('mape'), 2),
        ];
    }

    /**
     * Get analytics dashboard data
     */
    public function getAnalytics(string $orgId): array
    {
        $totalModels = PredictionModel::where('org_id', $orgId)->count();

        $activeModels = PredictionModel::where('org_id', $orgId)
            ->active()
            ->count();

        $dueForRetraining = PredictionModel::where('org_id', $orgId)
            ->needsRetraining()
            ->count();

        $avgAccuracy = PredictionModel::where('org_id', $orgId)
            ->whereNotNull('mape')
            ->avg('mape');

        $modelsByAlgorithm = PredictionModel::where('org_id', $orgId)
            ->select('algorithm', DB::raw('count(*) as count'))
            ->groupBy('algorithm')
            ->get()
            ->pluck('count', 'algorithm');

        $modelsByStatus = PredictionModel::where('org_id', $orgId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $recentlyTrained = PredictionModel::where('org_id', $orgId)
            ->whereNotNull('last_trained_at')
            ->orderBy('last_trained_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'summary' => [
                'total_models' => $totalModels,
                'active_models' => $activeModels,
                'due_for_retraining' => $dueForRetraining,
                'average_mape' => round($avgAccuracy ?? 0, 2),
            ],
            'by_algorithm' => $modelsByAlgorithm,
            'by_status' => $modelsByStatus,
            'recently_trained' => $recentlyTrained,
            'accuracy_trends' => $this->getAccuracyTrends($orgId),
            'top_performers' => $this->getTopPerformingModels($orgId),
        ];
    }

    /**
     * Bulk retrain models
     */
    public function bulkRetrainModels(array $modelIds): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($modelIds as $modelId) {
            try {
                $model = PredictionModel::findOrFail($modelId);
                $result = $this->retrainModel($model);

                $results[] = [
                    'model_id' => $modelId,
                    'success' => true,
                    'metrics' => $result['accuracy_metrics'],
                ];
                $successful++;
            } catch (\Exception $e) {
                $results[] = [
                    'model_id' => $modelId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                $failed++;
            }
        }

        return [
            'total' => count($modelIds),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Export model configuration
     */
    public function exportModel(PredictionModel $model): array
    {
        return [
            'name' => $model->name,
            'algorithm' => $model->algorithm,
            'target_metric' => $model->target_metric,
            'hyperparameters' => $model->hyperparameters,
            'model_parameters' => $model->model_parameters,
            'features' => $model->features,
            'version' => $model->version,
            'exported_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Import model configuration
     */
    public function importModel(array $config): PredictionModel
    {
        $orgId = session('current_org_id');

        return PredictionModel::create([
            'org_id' => $orgId,
            'name' => $config['name'],
            'algorithm' => $config['algorithm'],
            'target_metric' => $config['target_metric'],
            'hyperparameters' => $config['hyperparameters'],
            'model_parameters' => $config['model_parameters'],
            'features' => $config['features'],
            'status' => PredictionModel::STATUS_DRAFT,
            'version' => 1,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Prepare training data
     */
    protected function prepareTrainingData(
        PredictionModel $model,
        string $dateFrom,
        string $dateTo
    ): array {
        // This would fetch and prepare actual training data
        // For now, returning sample structure
        return [
            // ['features' => [...], 'target' => value],
        ];
    }

    /**
     * Train algorithm
     */
    protected function trainAlgorithm(string $algorithm, array $trainData, ?array $hyperparameters = null): array
    {
        // This would implement actual ML training logic
        // For now, returning sample model parameters
        return [
            'algorithm' => $algorithm,
            'coefficients' => [],
            'intercept' => 0,
            'trained_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Validate model
     */
    protected function validateModel(array $modelParams, array $validationData): array
    {
        // This would implement actual prediction and validation
        // For now, returning sample validation metrics

        $predictions = [];
        $errors = [];
        $percentageErrors = [];

        foreach ($validationData as $dataPoint) {
            // Simulate prediction
            $predicted = $dataPoint['target'] * (1 + (rand(-10, 10) / 100));
            $actual = $dataPoint['target'];

            $predictions[] = [
                'predicted' => $predicted,
                'actual' => $actual,
            ];

            $error = abs($predicted - $actual);
            $errors[] = $error;

            if ($actual != 0) {
                $percentageErrors[] = abs(($predicted - $actual) / $actual);
            }
        }

        $mae = array_sum($errors) / count($errors);
        $rmse = sqrt(array_sum(array_map(function ($e) {
            return $e * $e;
        }, $errors)) / count($errors));
        $mape = (array_sum($percentageErrors) / count($percentageErrors)) * 100;

        // Calculate R-squared
        $actualMean = array_sum(array_column($validationData, 'target')) / count($validationData);
        $ssTotal = array_sum(array_map(function ($d) use ($actualMean) {
            return pow($d['target'] - $actualMean, 2);
        }, $validationData));

        $ssResidual = array_sum($errors);
        $rSquared = $ssTotal != 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        return [
            'mae' => round($mae, 2),
            'rmse' => round($rmse, 2),
            'mape' => round($mape, 2),
            'r_squared' => max(0, min(1, round($rSquared, 4))),
            'predictions' => $predictions,
        ];
    }

    /**
     * Get accuracy trends
     */
    protected function getAccuracyTrends(string $orgId): array
    {
        $models = PredictionModel::where('org_id', $orgId)
            ->whereNotNull('last_trained_at')
            ->where('last_trained_at', '>=', now()->subDays(90))
            ->orderBy('last_trained_at')
            ->get();

        return $models->groupBy(function ($model) {
            return $model->last_trained_at->format('Y-m-d');
        })->map(function ($group) {
            return [
                'average_mape' => round($group->avg('mape'), 2),
                'models_trained' => $group->count(),
            ];
        })->toArray();
    }

    /**
     * Get top performing models
     */
    protected function getTopPerformingModels(string $orgId, int $limit = 10): Collection
    {
        return PredictionModel::where('org_id', $orgId)
            ->whereNotNull('mape')
            ->orderBy('mape', 'asc')
            ->limit($limit)
            ->get();
    }
}
