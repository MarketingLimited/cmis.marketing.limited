<?php

namespace App\Models\Automation;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.workflow_instances';
    protected $primaryKey = 'instance_id';

    protected $fillable = [
        'org_id',
        'template_id',
        'triggered_by',
        'instance_name',
        'workflow_definition',
        'context_data',
        'trigger_type',
        'trigger_data',
        'triggered_at',
        'status',
        'current_step_id',
        'steps_completed',
        'steps_total',
        'steps_failed',
        'started_at',
        'completed_at',
        'execution_time_seconds',
        'execution_results',
        'error_message',
        'error_details',
    ];

    protected $casts = [
        'workflow_definition' => 'array',
        'context_data' => 'array',
        'trigger_data' => 'array',
        'triggered_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'steps_completed' => 'integer',
        'steps_total' => 'integer',
        'steps_failed' => 'integer',
        'execution_time_seconds' => 'integer',
        'execution_results' => 'array',
        'error_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'template_id', 'template_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by', 'user_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'instance_id', 'instance_id')
            ->orderBy('step_order');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id', 'step_id');
    }

    // ===== Status Management =====

    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'running']);
    }

    public function complete(): void
    {
        $endTime = now();
        $executionTime = $this->started_at
            ? $endTime->diffInSeconds($this->started_at)
            : null;

        $this->update([
            'status' => 'completed',
            'completed_at' => $endTime,
            'execution_time_seconds' => $executionTime,
        ]);

        // Decrement active instances counter on template
        if ($this->template) {
            $this->template->decrementActiveInstances();
        }
    }

    public function fail(string $errorMessage, ?array $errorDetails = null): void
    {
        $endTime = now();
        $executionTime = $this->started_at
            ? $endTime->diffInSeconds($this->started_at)
            : null;

        $this->update([
            'status' => 'failed',
            'completed_at' => $endTime,
            'execution_time_seconds' => $executionTime,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
        ]);

        // Decrement active instances counter on template
        if ($this->template) {
            $this->template->decrementActiveInstances();
        }
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);

        // Decrement active instances counter on template
        if ($this->template) {
            $this->template->decrementActiveInstances();
        }
    }

    // ===== Progress Tracking =====

    public function incrementStepsCompleted(): void
    {
        $this->increment('steps_completed');
    }

    public function incrementStepsFailed(): void
    {
        $this->increment('steps_failed');
    }

    public function updateCurrentStep(string $stepId): void
    {
        $this->update(['current_step_id' => $stepId]);
    }

    public function getProgressPercentage(): float
    {
        if ($this->steps_total === 0) {
            return 0.0;
        }

        return round(($this->steps_completed / $this->steps_total) * 100, 2);
    }

    // ===== Query Helpers =====

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

    public function isPaused(): bool
    {
        return $this->status === 'paused';
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

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForTemplate($query, string $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));
    }
}
