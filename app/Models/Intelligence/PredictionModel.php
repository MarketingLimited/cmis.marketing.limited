<?php

namespace App\Models\Intelligence;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionModel extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_intelligence.prediction_models';
    protected $primaryKey = 'model_id';

    protected $fillable = [
        'model_id',
        'org_id',
        'name',
        'description',
        'model_type',
        'algorithm',
        'target_metric',
        'features',
        'hyperparameters',
        'training_data_period_start',
        'training_data_period_end',
        'training_data_count',
        'accuracy_metrics',
        'mae',
        'rmse',
        'mape',
        'r_squared',
        'version',
        'status',
        'last_trained_at',
        'next_training_at',
        'training_frequency',
        'auto_retrain',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'features' => 'array',
        'hyperparameters' => 'array',
        'training_data_period_start' => 'date',
        'training_data_period_end' => 'date',
        'training_data_count' => 'integer',
        'accuracy_metrics' => 'array',
        'mae' => 'decimal:4',
        'rmse' => 'decimal:4',
        'mape' => 'decimal:4',
        'r_squared' => 'decimal:4',
        'last_trained_at' => 'datetime',
        'next_training_at' => 'datetime',
        'auto_retrain' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Model type constants
    public const TYPE_REGRESSION = 'regression';
    public const TYPE_CLASSIFICATION = 'classification';
    public const TYPE_TIME_SERIES = 'time_series';
    public const TYPE_CLUSTERING = 'clustering';
    public const TYPE_ENSEMBLE = 'ensemble';

    // Algorithm constants
    public const ALGO_LINEAR_REGRESSION = 'linear_regression';
    public const ALGO_RANDOM_FOREST = 'random_forest';
    public const ALGO_GRADIENT_BOOSTING = 'gradient_boosting';
    public const ALGO_NEURAL_NETWORK = 'neural_network';
    public const ALGO_ARIMA = 'arima';
    public const ALGO_PROPHET = 'prophet';
    public const ALGO_LSTM = 'lstm';
    public const ALGO_XGBOOST = 'xgboost';

    // Status constants
    public const STATUS_TRAINING = 'training';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ARCHIVED = 'archived';

    // Training frequency constants
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_MONTHLY = 'monthly';
    public const FREQ_QUARTERLY = 'quarterly';
    public const FREQ_MANUAL = 'manual';

    // Relationships
    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class, 'model_id', 'model_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('model_type', $type);
    }

    public function scopeByAlgorithm($query, string $algorithm)
    {
        return $query->where('algorithm', $algorithm);
    }

    public function scopeNeedsRetraining($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('auto_retrain', true)
                     ->where('next_training_at', '<=', now());
    }

    public function scopeHighAccuracy($query, float $minRSquared = 0.8)
    {
        return $query->where('r_squared', '>=', $minRSquared);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isTraining(): bool
    {
        return $this->status === self::STATUS_TRAINING;
    }

    public function needsRetraining(): bool
    {
        return $this->auto_retrain
            && $this->next_training_at !== null
            && $this->next_training_at->isPast();
    }

    public function isHighAccuracy(float $minRSquared = 0.8): bool
    {
        return $this->r_squared !== null && $this->r_squared >= $minRSquared;
    }

    public function hasGoodPerformance(float $maxMAPE = 10.0): bool
    {
        return $this->mape !== null && $this->mape <= $maxMAPE;
    }

    public function activate(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function deactivate(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    public function markAsTraining(): bool
    {
        return $this->update(['status' => self::STATUS_TRAINING]);
    }

    public function markAsFailed(): bool
    {
        return $this->update(['status' => self::STATUS_FAILED]);
    }

    public function recordTraining(array $accuracyMetrics, int $dataCount): bool
    {
        $nextTraining = $this->calculateNextTrainingDate();

        return $this->update([
            'accuracy_metrics' => $accuracyMetrics,
            'mae' => $accuracyMetrics['mae'] ?? null,
            'rmse' => $accuracyMetrics['rmse'] ?? null,
            'mape' => $accuracyMetrics['mape'] ?? null,
            'r_squared' => $accuracyMetrics['r_squared'] ?? null,
            'training_data_count' => $dataCount,
            'last_trained_at' => now(),
            'next_training_at' => $nextTraining,
            'version' => $this->version + 1,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public function calculateNextTrainingDate(): ?\DateTime
    {
        if (!$this->auto_retrain || $this->training_frequency === self::FREQ_MANUAL) {
            return null;
        }

        $interval = match($this->training_frequency) {
            self::FREQ_DAILY => 'P1D',
            self::FREQ_WEEKLY => 'P7D',
            self::FREQ_MONTHLY => 'P1M',
            self::FREQ_QUARTERLY => 'P3M',
            default => null,
        };

        if (!$interval) {
            return null;
        }

        $date = now();
        return $date->add(new \DateInterval($interval));
    }

    public function getPerformanceSummary(): array
    {
        return [
            'accuracy' => $this->r_squared,
            'error_metrics' => [
                'mae' => $this->mae,
                'rmse' => $this->rmse,
                'mape' => $this->mape,
            ],
            'training_data_count' => $this->training_data_count,
            'last_trained' => $this->last_trained_at?->diffForHumans(),
            'version' => $this->version,
        ];
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_TRAINING => 'blue',
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_FAILED => 'red',
            self::STATUS_ARCHIVED => 'yellow',
            default => 'gray',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_REGRESSION => 'Regression',
            self::TYPE_CLASSIFICATION => 'Classification',
            self::TYPE_TIME_SERIES => 'Time Series',
            self::TYPE_CLUSTERING => 'Clustering',
            self::TYPE_ENSEMBLE => 'Ensemble',
        ];
    }

    public static function getAlgorithmOptions(): array
    {
        return [
            self::ALGO_LINEAR_REGRESSION => 'Linear Regression',
            self::ALGO_RANDOM_FOREST => 'Random Forest',
            self::ALGO_GRADIENT_BOOSTING => 'Gradient Boosting',
            self::ALGO_NEURAL_NETWORK => 'Neural Network',
            self::ALGO_ARIMA => 'ARIMA',
            self::ALGO_PROPHET => 'Prophet',
            self::ALGO_LSTM => 'LSTM',
            self::ALGO_XGBOOST => 'XGBoost',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_TRAINING => 'Training',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public static function getFrequencyOptions(): array
    {
        return [
            self::FREQ_DAILY => 'Daily',
            self::FREQ_WEEKLY => 'Weekly',
            self::FREQ_MONTHLY => 'Monthly',
            self::FREQ_QUARTERLY => 'Quarterly',
            self::FREQ_MANUAL => 'Manual',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'model_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'algorithm' => 'required|in:' . implode(',', array_keys(self::getAlgorithmOptions())),
            'target_metric' => 'required|string|max:255',
            'features' => 'nullable|array',
            'hyperparameters' => 'nullable|array',
            'training_frequency' => 'required|in:' . implode(',', array_keys(self::getFrequencyOptions())),
            'auto_retrain' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'hyperparameters' => 'sometimes|array',
            'training_frequency' => 'sometimes|in:' . implode(',', array_keys(self::getFrequencyOptions())),
            'auto_retrain' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization is required',
            'name.required' => 'Model name is required',
            'model_type.required' => 'Model type is required',
            'algorithm.required' => 'Algorithm is required',
            'target_metric.required' => 'Target metric is required',
            'training_frequency.required' => 'Training frequency is required',
        ];
    }
}
