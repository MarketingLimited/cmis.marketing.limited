<?php

namespace App\Models\Automation;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ScheduledJob extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.scheduled_jobs';
    protected $primaryKey = 'job_id';

    protected $fillable = [
        'org_id',
        'workflow_template_id',
        'automation_rule_id',
        'schedule_name',
        'schedule_type',
        'cron_expression',
        'recurrence_config',
        'next_run_at',
        'last_run_at',
        'start_date',
        'end_date',
        'max_executions',
        'execution_count',
        'timeout_seconds',
        'execution_context',
        'status',
        'last_error',
    ];

    protected $casts = [
        'recurrence_config' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'max_executions' => 'integer',
        'execution_count' => 'integer',
        'timeout_seconds' => 'integer',
        'execution_context' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id', 'template_id');
    }

    public function automationRule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id', 'rule_id');
    }

    // ===== Status Management =====

    public function activate(): void
    {
        $this->update(['status' => 'active']);
        $this->calculateNextRun();
    }

    public function pause(): void
    {
        $this->update([
            'status' => 'paused',
            'next_run_at' => null,
        ]);
    }

    public function markAsRun(?string $error = null): void
    {
        $this->increment('execution_count');

        $updates = ['last_run_at' => now()];

        if ($error) {
            $updates['last_error'] = $error;
            $updates['status'] = 'failed';
        }

        $this->update($updates);

        // Check if max executions reached
        if ($this->max_executions && $this->execution_count >= $this->max_executions) {
            $this->update(['status' => 'completed']);
        } else {
            $this->calculateNextRun();
        }
    }

    // ===== Schedule Calculation =====

    public function calculateNextRun(): void
    {
        if ($this->status !== 'active') {
            return;
        }

        $now = now();
        $nextRun = null;

        switch ($this->schedule_type) {
            case 'once':
                // Run only at start_date
                $nextRun = $this->start_date;
                if ($this->last_run_at) {
                    $nextRun = null; // Already ran
                }
                break;

            case 'recurring':
                $nextRun = $this->calculateRecurringNextRun($now);
                break;

            case 'cron':
                // Would use a cron parser library in production
                $nextRun = $this->parseCronExpression();
                break;
        }

        // Check if next run is within start/end dates
        if ($nextRun) {
            if ($this->start_date && $nextRun->isBefore($this->start_date)) {
                $nextRun = $this->start_date;
            }

            if ($this->end_date && $nextRun->isAfter($this->end_date)) {
                $nextRun = null; // Schedule has ended
                $this->update(['status' => 'completed']);
            }
        }

        $this->update(['next_run_at' => $nextRun]);
    }

    protected function calculateRecurringNextRun($from): ?\Carbon\Carbon
    {
        if (!$this->recurrence_config) {
            return null;
        }

        $frequency = $this->recurrence_config['frequency'] ?? 'daily';
        $interval = $this->recurrence_config['interval'] ?? 1;

        $next = $from->copy();

        switch ($frequency) {
            case 'hourly':
                $next->addHours($interval);
                break;

            case 'daily':
                $next->addDays($interval);
                break;

            case 'weekly':
                $next->addWeeks($interval);
                break;

            case 'monthly':
                $next->addMonths($interval);
                break;

            default:
                return null;
        }

        return $next;
    }

    protected function parseCronExpression(): ?\Carbon\Carbon
    {
        // Simplified - in production use a cron parsing library
        return null;
    }

    // ===== Query Helpers =====

    public function isDue(): bool
    {
        if ($this->status !== 'active' || !$this->next_run_at) {
            return false;
        }

        return now()->isAfter($this->next_run_at);
    }

    public function hasEnded(): bool
    {
        return $this->status === 'completed' ||
               ($this->end_date && now()->isAfter($this->end_date));
    }

    public function isForWorkflow(): bool
    {
        return !is_null($this->workflow_template_id);
    }

    public function isForAutomationRule(): bool
    {
        return !is_null($this->automation_rule_id);
    }

    // ===== Scopes =====

    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDue($query): Builder
    {
        return $query->active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }

    public function scopeForWorkflowTemplate($query, string $templateId): Builder
    {
        return $query->where('workflow_template_id', $templateId);
    }

    public function scopeForAutomationRule($query, string $ruleId): Builder
    {
        return $query->where('automation_rule_id', $ruleId);
    }

    public function scopeWithinDateRange($query, $start = null, $end = null): Builder
    {
        if ($start) {
            $query->where('start_date', '>=', $start);
        }

        if ($end) {
            $query->where('end_date', '<=', $end);
        }

        return $query;
    }
}
