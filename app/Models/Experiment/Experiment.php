<?php

namespace App\Models\Experiment;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Experiment extends BaseModel
{
    use HasOrganization, HasFactory, SoftDeletes;

    protected $table = 'cmis.experiments';
    protected $primaryKey = 'experiment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'created_by',
        'name',
        'description',
        'experiment_type',
        'entity_type',
        'entity_id',
        'metric',
        'metrics',
        'hypothesis',
        'status',
        'start_date',
        'end_date',
        'duration_days',
        'sample_size_per_variant',
        'confidence_level',
        'minimum_detectable_effect',
        'traffic_allocation',
        'config',
        'started_at',
        'completed_at',
        'winner_variant_id',
        'statistical_significance',
        'results',
    ];

    protected $casts = [
        'experiment_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'entity_id' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_days' => 'integer',
        'sample_size_per_variant' => 'integer',
        'confidence_level' => 'decimal:2',
        'minimum_detectable_effect' => 'decimal:2',
        'statistical_significance' => 'decimal:2',
        'metrics' => 'array',
        'config' => 'array',
        'results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the experiment variants
     */
    public function variants()
    {
        return $this->hasMany(ExperimentVariant::class, 'experiment_id', 'experiment_id');
    }

    /**
     * Get the winner variant
     */
    public function winnerVariant()
    {
        return $this->belongsTo(ExperimentVariant::class, 'winner_variant_id', 'variant_id');
    }

    /**
     * Get the experiment results
     */
    public function experimentResults()
    {
        return $this->hasMany(ExperimentResult::class, 'experiment_id', 'experiment_id');
    }

    /**
     * Get the experiment events
     */
    public function events()
    {
        return $this->hasMany(ExperimentEvent::class, 'experiment_id', 'experiment_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\Core\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope active experiments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'running')
            ->whereNotNull('started_at')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope running experiments
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope completed experiments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope draft experiments
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Check if experiment has reached statistical significance
     */
    public function hasStatisticalSignificance(): bool
    {
        return $this->statistical_significance !== null && $this->statistical_significance >= $this->confidence_level;
    }

    /**
     * Check if experiment is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed' && $this->completed_at !== null;
    }

    /**
     * Check if experiment is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running' && $this->started_at !== null;
    }
}
