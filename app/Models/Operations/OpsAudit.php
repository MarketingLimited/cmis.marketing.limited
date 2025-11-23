<?php

namespace App\Models\Operations;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class OpsAudit extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.ops_audit';
    protected $primaryKey = 'audit_id';
            const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'audit_id',
        'org_id',
        'operation_name',
        'status',
        'executed_at',
        'details',
        'provider',
    ];

    protected $casts = ['old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'details' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');

        }
    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');

        }
    public function scopeByType($query, $type): Builder
    {
        return $query->where('operation_type', $type);

        }
    public function scopeByEntity($query, $entityType, $entityId = null)
    : \Illuminate\Database\Eloquent\Builder {
        $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where('entity_id', $entityId);

        return $query;

        }
    public function scopeByUser($query, $userId): Builder
    {
        return $query->where('user_id', $userId);

        }
    public function scopeSuccessful($query): Builder
    {
        return $query->where('status', 'success');

        }
    public function scopeFailed($query): Builder
    {
        return $query->where('status', 'failed');

        }
    public static function log($operationType, $operationName, $userId, $orgId, $entityType, $entityId, $oldValues = null, $newValues = null)
    : \Illuminate\Database\Eloquent\Relations\Relation {
        $changes = [];

        if ($oldValues && $newValues) {
            foreach ($newValues as $key => $value) {
                if (!isset($oldValues[$key]) || $oldValues[$key] !== $value) {
                    $changes[$key] = [
                        'old' => $oldValues[$key] ?? null,
                        'new' => $value,
                    ];

        return static::create([
            'operation_type' => $operationType,
            'operation_name' => $operationName,
            'user_id' => $userId,
            'org_id' => $orgId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
        ]);
}
