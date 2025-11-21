<?php

namespace App\Models\Optimization;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OptimizationModel extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.optimization_models';
    protected $primaryKey = 'model_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'model_id',
        'org_id',
        'name',
        'description',
        'model_type',
        'algorithm',
        'optimization_goal',
        'hyperparameters',
        'training_data_query',
        'feature_columns',
        'target_column',
        'status',
        'version',
        'accuracy_score',
        'precision_score',
        'recall_score',
        'f1_score',
        'mae',
        'rmse',
        'r_squared',
        'training_samples',
        'validation_samples',
        'training_duration_seconds',
        'model_artifact_path',
        'trained_at',
        'deployed_at',
        'last_used_at',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'hyperparameters' => 'array',
        'feature_columns' => 'array',
        'accuracy_score' => 'float',
        'precision_score' => 'float',
        'recall_score' => 'float',
        'f1_score' => 'float',
        'mae' => 'float',
        'rmse' => 'float',
        'r_squared' => 'float',
        'training_samples' => 'integer',
        'validation_samples' => 'integer',
        'training_duration_seconds' => 'integer',
        'usage_count' => 'integer',
        'trained_at' => 'datetime',
        'deployed_at' => 'datetime',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(OptimizationRun::class, 'model_id', 'model_id');
    }

    // ===== Model Status Management =====

    public function markAsTrained(array $metrics): void
    {
        $this->update([
            'status' => 'trained',
            'accuracy_score' => $metrics['accuracy'] ?? null,
            'precision_score' => $metrics['precision'] ?? null,
            'recall_score' => $metrics['recall'] ?? null,
            'f1_score' => $metrics['f1'] ?? null,
            'mae' => $metrics['mae'] ?? null,
            'rmse' => $metrics['rmse'] ?? null,
            'r_squared' => $metrics['r_squared'] ?? null,
            'trained_at' => now(),
        ]);
    }

    public function deploy(): void
    {
        $this->update([
            'status' => 'deployed',
            'deployed_at' => now(),
        ]);
    }

    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function isDeployed(): bool
    {
        return $this->status === 'deployed';
    }

    public function isTrained(): bool
    {
        return in_array($this->status, ['trained', 'deployed']);
    }

    // ===== Model Performance Helpers =====

    public function getPerformanceScore(): float
    {
        // Calculate composite score based on available metrics
        $scores = [];

        if ($this->accuracy_score !== null) {
            $scores[] = $this->accuracy_score;
        }
        if ($this->f1_score !== null) {
            $scores[] = $this->f1_score;
        }
        if ($this->r_squared !== null) {
            $scores[] = $this->r_squared;
        }

        if (empty($scores)) {
            return 0.0;
        }

        return round(array_sum($scores) / count($scores), 4);
    }

    public function getModelTypeLabel(): string
    {
        return match($this->model_type) {
            'budget_allocation' => 'Budget Allocation',
            'bid_optimization' => 'Bid Optimization',
            'audience_targeting' => 'Audience Targeting',
            'creative_optimization' => 'Creative Optimization',
            'performance_prediction' => 'Performance Prediction',
            default => ucfirst(str_replace('_', ' ', $this->model_type))
        };
    }

    public function getAlgorithmLabel(): string
    {
        return match($this->algorithm) {
            'gradient_descent' => 'Gradient Descent',
            'genetic_algorithm' => 'Genetic Algorithm',
            'bayesian_optimization' => 'Bayesian Optimization',
            'reinforcement_learning' => 'Reinforcement Learning',
            'random_forest' => 'Random Forest',
            'neural_network' => 'Neural Network',
            default => ucfirst(str_replace('_', ' ', $this->algorithm))
        };
    }

    // ===== Scopes =====

    public function scopeDeployed($query)
    {
        return $query->where('status', 'deployed');
    }

    public function scopeForModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    public function scopeByAlgorithm($query, string $algorithm)
    {
        return $query->where('algorithm', $algorithm);
    }
}
