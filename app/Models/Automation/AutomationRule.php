<?php

namespace App\Models\Automation;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.automation_rules';
    protected $primaryKey = 'rule_id';
    protected $fillable = [
        'org_id', 'created_by', 'name', 'description', 'rule_type',
        'entity_type', 'entity_id', 'conditions', 'condition_logic',
        'actions', 'priority', 'status', 'enabled', 'max_executions_per_day',
        'cooldown_minutes', 'last_executed_at', 'execution_count',
        'success_count', 'failure_count'
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'enabled' => 'boolean',
        'max_executions_per_day' => 'integer',
        'cooldown_minutes' => 'integer',
        'execution_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'last_executed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ===== Relationships =====

    

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AutomationExecution::class, 'rule_id', 'rule_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(AutomationSchedule::class, 'rule_id', 'rule_id');
    }

    // ===== Status Management =====

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'enabled' => true
        ]);
    }

    public function pause(): void
    {
        $this->update([
            'status' => 'paused',
            'enabled' => false
        ]);
    }

    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
            'enabled' => false
        ]);
    }

    // ===== Execution Tracking =====

    public function recordExecution(string $status): void
    {
        $this->increment('execution_count');

        if ($status === 'success') {
            $this->increment('success_count');
        } elseif ($status === 'failure') {
            $this->increment('failure_count');
        }

        $this->update(['last_executed_at' => now()]);
    }

    public function canExecute(): bool
    {
        if (!$this->enabled || $this->status !== 'active') {
            return false;
        }

        // Check cooldown period
        if ($this->last_executed_at && $this->cooldown_minutes > 0) {
            $nextAllowedExecution = $this->last_executed_at->addMinutes($this->cooldown_minutes);
            if (now()->isBefore($nextAllowedExecution)) {
                return false;
            }
        }

        // Check daily execution limit
        if ($this->max_executions_per_day) {
            $todayExecutions = $this->executions()
                ->whereDate('executed_at', today())
                ->count();

            if ($todayExecutions >= $this->max_executions_per_day) {
                return false;
            }
        }

        return true;
    }

    public function getSuccessRate(): float
    {
        if ($this->execution_count === 0) {
            return 0.0;
        }

        return round(($this->success_count / $this->execution_count) * 100, 2);
    }

    // ===== Condition Helpers =====

    public function addCondition(array $condition): void
    {
        $conditions = $this->conditions ?? [];
        $conditions[] = $condition;
        $this->update(['conditions' => $conditions]);
    }

    public function removeCondition(int $index): void
    {
        $conditions = $this->conditions ?? [];
        if (isset($conditions[$index])) {
            unset($conditions[$index]);
            $this->update(['conditions' => array_values($conditions)]);
        }
    }

    // ===== Action Helpers =====

    public function addAction(array $action): void
    {
        $actions = $this->actions ?? [];
        $actions[] = $action;
        $this->update(['actions' => $actions]);
    }

    public function removeAction(int $index): void
    {
        $actions = $this->actions ?? [];
        if (isset($actions[$index])) {
            unset($actions[$index]);
            $this->update(['actions' => array_values($actions)]);
        }
    }

    // ===== Scopes =====

    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active')->where('enabled', true);
    }

    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('rule_type', $type);
    }

    public function scopeForEntity($query, string $entityType, string $entityId): Builder
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);
    }

    public function scopeDueForExecution($query): Builder
    {
        return $query->active()
            ->where(function ($q) {
                // No cooldown or cooldown expired
                $q->whereNull('last_executed_at')
                  ->orWhere('last_executed_at', '<=', now()->subMinutes(60));
            });
    }
}
