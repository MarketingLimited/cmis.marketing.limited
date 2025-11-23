<?php

namespace App\Models\Automation;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AutomationAuditLog extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.automation_audit_log';
    protected $primaryKey = 'audit_id';

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'org_id',
        'rule_id',
        'execution_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'changes',
        'metadata',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id', 'rule_id');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AutomationExecution::class, 'execution_id', 'execution_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // ===== Static Helper Methods =====

    public static function logRuleCreated(
        string $orgId,
        string $ruleId,
        ?string $userId = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'org_id' => $orgId,
            'rule_id' => $ruleId,
            'user_id' => $userId,
            'action' => 'rule_created',
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    public static function logRuleUpdated(
        string $orgId,
        string $ruleId,
        array $changes,
        ?string $userId = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'org_id' => $orgId,
            'rule_id' => $ruleId,
            'user_id' => $userId,
            'action' => 'rule_updated',
            'changes' => $changes,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    public static function logRuleDeleted(
        string $orgId,
        string $ruleId,
        ?string $userId = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'org_id' => $orgId,
            'rule_id' => $ruleId,
            'user_id' => $userId,
            'action' => 'rule_deleted',
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    public static function logRuleExecuted(
        string $orgId,
        string $ruleId,
        string $executionId,
        ?array $metadata = null
    ): self {
        return self::create([
            'org_id' => $orgId,
            'rule_id' => $ruleId,
            'execution_id' => $executionId,
            'action' => 'rule_executed',
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public static function logActionTaken(
        string $orgId,
        string $ruleId,
        string $executionId,
        string $entityType,
        string $entityId,
        array $metadata = []
    ): self {
        return self::create([
            'org_id' => $orgId,
            'rule_id' => $ruleId,
            'execution_id' => $executionId,
            'action' => 'action_taken',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    // ===== Query Helpers =====

    public function isRuleCreation(): bool
    {
        return $this->action === 'rule_created';
    }

    public function isRuleUpdate(): bool
    {
        return $this->action === 'rule_updated';
    }

    public function isRuleExecution(): bool
    {
        return $this->action === 'rule_executed';
    }

    public function isActionTaken(): bool
    {
        return $this->action === 'action_taken';
    }

    // ===== Scopes =====

    public function scopeForRule($query, string $ruleId): Builder
    {
        return $query->where('rule_id', $ruleId);
    }

    public function scopeForExecution($query, string $executionId): Builder
    {
        return $query->where('execution_id', $executionId);
    }

    public function scopeByUser($query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfAction($query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeForEntity($query, string $entityType, ?string $entityId = null): Builder
    {
        $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    public function scopeRecent($query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    public function scopeBetweenDates($query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
