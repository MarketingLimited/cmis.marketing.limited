<?php

namespace App\Models\Orchestration;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrchestrationRule extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.orchestration_rules';
    protected $primaryKey = 'rule_id';

    protected $fillable = [
        'rule_id',
        'org_id',
        'orchestration_id',
        'name',
        'description',
        'rule_type',
        'trigger',
        'trigger_conditions',
        'actions',
        'enabled',
        'priority',
        'last_executed_at',
        'execution_count',
        'success_count',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'actions' => 'array',
        'enabled' => 'boolean',
        'execution_count' => 'integer',
        'success_count' => 'integer',
        'last_executed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function orchestration(): BelongsTo
    {
        return $this->belongsTo(CampaignOrchestration::class, 'orchestration_id', 'orchestration_id');


        }
    public function enable(): void
    {
        $this->update(['enabled' => true]);

    public function disable(): void
    {
        $this->update(['enabled' => false]);

    public function recordExecution(bool $success): void
    {
        $this->increment('execution_count');
        if ($success) {
            $this->increment('success_count');
        $this->update(['last_executed_at' => now()]);

    public function getSuccessRate(): float
    {
        if ($this->execution_count === 0) {
            return 0.0;


            }
    public function isEnabled(): bool
    {
        return $this->enabled;

        }
    public function isGlobal(): bool
    {
        return $this->orchestration_id === null;

        }
    public function getRuleTypeLabel(): string
    {
        return match($this->rule_type) {
            'budget_reallocation' => 'Budget Reallocation',
            'pause_underperforming' => 'Pause Underperforming',
            'scale_winners' => 'Scale Winners',
            'creative_rotation' => 'Creative Rotation',
            default => ucfirst(str_replace('_', ' ', $this->rule_type))
        };

    public function getPriorityLevel(): int
    {
        return match($this->priority) {
            'critical' => 1,
            'high' => 2,
            'medium' => 3,
            'low' => 4,
            default => 5
        };

    // ===== Scopes =====

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);

        }
    public function scopeGlobal($query)
    {
        return $query->whereNull('orchestration_id');

        }
    public function scopeForOrchestration($query, string $orchestrationId)
    {
        return $query->where('orchestration_id', $orchestrationId);

        }
    public function scopeForType($query, string $type)
    {
        return $query->where('rule_type', $type);

        }
    public function scopeByPriority($query)
    {
        return $query->orderByRaw("
            CASE priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END
        ");
}
}
}
}
}
}
}
}
}
