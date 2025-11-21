<?php

namespace App\Models\Analytics;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experiment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.experiments';
    protected $primaryKey = 'experiment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id', 'created_by', 'name', 'description', 'experiment_type',
        'entity_type', 'entity_id', 'metric', 'metrics', 'hypothesis',
        'status', 'start_date', 'end_date', 'duration_days',
        'sample_size_per_variant', 'confidence_level', 'minimum_detectable_effect',
        'traffic_allocation', 'config', 'started_at', 'completed_at',
        'winner_variant_id', 'statistical_significance', 'results'
    ];

    protected $casts = [
        'metrics' => 'array',
        'config' => 'array',
        'results' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'sample_size_per_variant' => 'integer',
        'duration_days' => 'integer',
        'confidence_level' => 'decimal:2',
        'minimum_detectable_effect' => 'decimal:2',
        'statistical_significance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ExperimentVariant::class, 'experiment_id', 'experiment_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExperimentResult::class, 'experiment_id', 'experiment_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExperimentEvent::class, 'experiment_id', 'experiment_id');
    }

    public function controlVariant()
    {
        return $this->variants()->where('is_control', true)->first();
    }

    public function winnerVariant(): ?ExperimentVariant
    {
        if ($this->winner_variant_id) {
            return $this->variants()->find($this->winner_variant_id);
        }
        return null;
    }

    /**
     * Check if experiment is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running'
            && $this->started_at
            && (!$this->completed_at || $this->completed_at->isFuture());
    }

    /**
     * Check if experiment can be started
     */
    public function canStart(): bool
    {
        return $this->status === 'draft'
            && $this->variants()->count() >= 2
            && $this->variants()->where('is_control', true)->exists();
    }

    /**
     * Start the experiment
     */
    public function start(): void
    {
        if (!$this->canStart()) {
            throw new \RuntimeException('Experiment cannot be started');
        }

        $this->update([
            'status' => 'running',
            'started_at' => now()
        ]);
    }

    /**
     * Pause the experiment
     */
    public function pause(): void
    {
        if ($this->status !== 'running') {
            throw new \RuntimeException('Only running experiments can be paused');
        }

        $this->update(['status' => 'paused']);
    }

    /**
     * Resume the experiment
     */
    public function resume(): void
    {
        if ($this->status !== 'paused') {
            throw new \RuntimeException('Only paused experiments can be resumed');
        }

        $this->update(['status' => 'running']);
    }

    /**
     * Complete the experiment
     */
    public function complete(string $winnerVariantId = null, float $significance = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'winner_variant_id' => $winnerVariantId,
            'statistical_significance' => $significance
        ]);
    }

    /**
     * Cancel the experiment
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now()
        ]);
    }

    /**
     * Get experiment progress percentage
     */
    public function getProgressPercentage(): float
    {
        if (!$this->started_at || !$this->duration_days) {
            return 0;
        }

        $daysPassed = now()->diffInDays($this->started_at);
        return min(100, ($daysPassed / $this->duration_days) * 100);
    }

    /**
     * Get remaining days
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->started_at || !$this->duration_days) {
            return null;
        }

        $daysPassed = now()->diffInDays($this->started_at);
        $remaining = $this->duration_days - $daysPassed;

        return max(0, $remaining);
    }

    /**
     * Scope: Active experiments
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'running', 'paused']);
    }

    /**
     * Scope: Running experiments
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Completed experiments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: By experiment type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('experiment_type', $type);
    }
}
