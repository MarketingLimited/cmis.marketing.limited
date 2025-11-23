<?php

namespace App\Models\Automation;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationExecution extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.automation_executions';
    protected $primaryKey = 'execution_id';
    protected $fillable = [
        'org_id', 'rule_id', 'entity_id', 'status', 'executed_at',
        'duration_ms', 'conditions_evaluated', 'actions_executed',
        'results', 'error_message', 'context'
    ];

    protected $casts = [
        'conditions_evaluated' => 'array',
        'actions_executed' => 'array',
        'results' => 'array',
        'context' => 'array',
        'duration_ms' => 'integer',
        'executed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ===== Relationships =====

    

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id', 'rule_id');
    }

    // ===== Helper Methods =====

    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failure';
    }

    public function wasPartial(): bool
    {
        return $this->status === 'partial';
    }

    public function wasSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function getSuccessfulActions(): array
    {
        if (!$this->results) {
            return [];
        }

        return array_filter($this->results, fn($result) => $result['status'] === 'success');
    }

    public function getFailedActions(): array
    {
        if (!$this->results) {
            return [];
        }

        return array_filter($this->results, fn($result) => $result['status'] === 'failure');
    }

    public function getExecutionSummary(): array
    {
        return [
            'execution_id' => $this->execution_id,
            'rule_name' => $this->rule->name ?? 'Unknown',
            'status' => $this->status,
            'executed_at' => $this->executed_at->toIso8601String(),
            'duration_ms' => $this->duration_ms,
            'conditions_count' => count($this->conditions_evaluated ?? []),
            'actions_count' => count($this->actions_executed ?? []),
            'successful_actions' => count($this->getSuccessfulActions()),
            'failed_actions' => count($this->getFailedActions()),
            'error' => $this->error_message
        ];
    }

    // ===== Scopes =====

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failure');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('executed_at', '>=', now()->subDays($days));
    }

    public function scopeForRule($query, string $ruleId)
    {
        return $query->where('rule_id', $ruleId);
    }
}
