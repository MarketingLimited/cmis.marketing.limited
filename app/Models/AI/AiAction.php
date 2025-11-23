<?php

namespace App\Models\AI;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiAction extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.ai_actions';
    protected $primaryKey = 'action_id';
    protected $fillable = [
        'action_id',
        'org_id',
        'campaign_id',
        'prompt_used',
        'sql_executed',
        'result_summary',
        'confidence_score',
        'audit_id',
        'provider',
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
