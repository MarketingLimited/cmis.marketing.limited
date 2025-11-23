<?php

namespace App\Models\Automation;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class WorkflowStep extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.workflow_steps';
    protected $primaryKey = 'step_id';

    protected $fillable = [
        'org_id',
        'instance_id',
        'step_name',
        'step_type',
        'step_order',
        'step_config',
        'status',
        'started_at',
        'completed_at',
        'execution_time_ms',
        'input_data',
        'output_data',
        'error_message',
        'retry_count',
        'max_retries',
        'next_step_id',
        'branch_taken',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'step_config' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'execution_time_ms' => 'integer',
        'input_data' => 'array',
        'output_data' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'branch_taken' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id', 'instance_id');
    }

    public function nextStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'next_step_id', 'step_id');
    }

    // ===== Execution Management =====

    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function complete(array $outputData = []): void
    {
        $endTime = now();
        $executionTime = $this->started_at
            ? (int)($endTime->diffInMilliseconds($this->started_at))
            : null;

        $this->update([
            'status' => 'completed',
            'completed_at' => $endTime,
            'execution_time_ms' => $executionTime,
            'output_data' => $outputData,
        ]);

        // Update instance progress
        if ($this->instance) {
            $this->instance->incrementStepsCompleted();
        }
    }

    public function fail(string $errorMessage): void
    {
        $endTime = now();
        $executionTime = $this->started_at
            ? (int)($endTime->diffInMilliseconds($this->started_at))
            : null;

        $this->update([
            'status' => 'failed',
            'completed_at' => $endTime,
            'execution_time_ms' => $executionTime,
            'error_message' => $errorMessage,
        ]);

        // Update instance progress
        if ($this->instance) {
            $this->instance->incrementStepsFailed();
        }
    }

    public function skip(string $reason = 'Skipped'): void
    {
        $this->update([
            'status' => 'skipped',
            'error_message' => $reason,
        ]);
    }

    public function retry(): bool
    {
        if ($this->retry_count >= $this->max_retries) {
            return false;
        }

        $this->increment('retry_count');
        $this->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        return true;
    }

    // ===== Query Helpers =====

    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries;
    }

    public function isAction(): bool
    {
        return $this->step_type === 'action';
    }

    public function isCondition(): bool
    {
        return $this->step_type === 'condition';
    }

    public function isDelay(): bool
    {
        return $this->step_type === 'delay';
    }

    public function isSplit(): bool
    {
        return $this->step_type === 'split';
    }

    public function isMerge(): bool
    {
        return $this->step_type === 'merge';
    }

    // ===== Scopes =====

    public function scopePending($query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRunning($query): Builder
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeForInstance($query, string $instanceId): Builder
    {
        return $query->where('instance_id', $instanceId)
            ->orderBy('step_order');
    }

    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('step_type', $type);
    }
}
