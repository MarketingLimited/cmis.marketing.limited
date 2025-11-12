<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiAction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis.ai_actions';
    protected $primaryKey = 'action_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'user_id',
        'action_type',
        'action_name',
        'input_data',
        'output_data',
        'model_used',
        'tokens_used',
        'cost',
        'execution_time_ms',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'metadata' => 'array',
        'tokens_used' => 'integer',
        'cost' => 'decimal:4',
        'execution_time_ms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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
        return $query->where('action_type', $type);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByOrg($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    // Helpers
    public static function log($userId, $orgId, $type, $name, $inputData, $outputData, $modelUsed, $tokensUsed = 0, $executionTime = 0)
    {
        return static::create([
            'user_id' => $userId,
            'org_id' => $orgId,
            'action_type' => $type,
            'action_name' => $name,
            'input_data' => $inputData,
            'output_data' => $outputData,
            'model_used' => $modelUsed,
            'tokens_used' => $tokensUsed,
            'execution_time_ms' => $executionTime,
            'status' => 'success',
        ]);
    }

    public static function logError($userId, $orgId, $type, $name, $inputData, $errorMessage)
    {
        return static::create([
            'user_id' => $userId,
            'org_id' => $orgId,
            'action_type' => $type,
            'action_name' => $name,
            'input_data' => $inputData,
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public static function totalCostForOrg($orgId, $days = 30)
    {
        return static::where('org_id', $orgId)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('cost');
    }

    public static function totalTokensForOrg($orgId, $days = 30)
    {
        return static::where('org_id', $orgId)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('tokens_used');
    }
}
