<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpsAudit extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.ops_audit';
    protected $primaryKey = 'audit_id';
    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'operation_type',
        'operation_name',
        'user_id',
        'org_id',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'changes',
        'ip_address',
        'user_agent',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('operation_type', $type);
    }

    public function scopeByEntity($query, $entityType, $entityId = null)
    {
        $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helpers
    public static function log($operationType, $operationName, $userId, $orgId, $entityType, $entityId, $oldValues = null, $newValues = null)
    {
        $changes = [];

        if ($oldValues && $newValues) {
            foreach ($newValues as $key => $value) {
                if (!isset($oldValues[$key]) || $oldValues[$key] !== $value) {
                    $changes[$key] = [
                        'old' => $oldValues[$key] ?? null,
                        'new' => $value,
                    ];
                }
            }
        }

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
}
