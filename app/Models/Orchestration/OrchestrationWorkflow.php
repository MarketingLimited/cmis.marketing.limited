<?php

namespace App\Models\Orchestration;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrchestrationWorkflow extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.orchestration_workflows';
    protected $primaryKey = 'workflow_id';
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
        'workflow_id',
        'org_id',
        'orchestration_id',
        'workflow_type',
        'status',
        'steps',
        'current_step',
        'total_steps',
        'started_at',
        'completed_at',
        'duration_seconds',
        'execution_log',
        'error_message',
        'rollback_data',
    ];

    protected $casts = [
        'steps' => 'array',
        'execution_log' => 'array',
        'rollback_data' => 'array',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function orchestration(): BelongsTo
    {
        return $this->belongsTo(CampaignOrchestration::class, 'orchestration_id', 'orchestration_id');
    }

    // ===== Workflow Execution =====

    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
            'current_step' => 0,
        ]);
    }

    public function advanceStep(): void
    {
        $this->increment('current_step');
    }

    public function logStep(string $stepName, string $status, ?array $details = null): void
    {
        $log = $this->execution_log ?? [];
        $log[] = [
            'step' => $this->current_step,
            'name' => $stepName,
            'status' => $status,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ];

        $this->update(['execution_log' => $log]);
    }

    public function complete(): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'current_step' => $this->total_steps,
        ]);
    }

    public function fail(string $errorMessage): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'error_message' => $errorMessage,
        ]);
    }

    public function getProgress(): float
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        return ($this->current_step / $this->total_steps) * 100;
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // ===== Scopes =====

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('workflow_type', $type);
    }
}
