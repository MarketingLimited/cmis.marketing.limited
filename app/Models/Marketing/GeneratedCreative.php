<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneratedCreative extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis_marketing.generated_creatives';
    protected $primaryKey = 'creative_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'campaign_id',
        'creative_type',
        'prompt',
        'generated_content',
        'model_used',
        'tokens_used',
        'generation_time_ms',
        'quality_score',
        'is_approved',
        'approved_by',
        'approved_at',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'generated_content' => 'array',
        'tokens_used' => 'integer',
        'generation_time_ms' => 'integer',
        'quality_score' => 'float',
        'is_approved' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');
    }

    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by', 'user_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('creative_type', $type);
    }

    public function scopeHighQuality($query, $threshold = 0.8)
    {
        return $query->where('quality_score', '>=', $threshold);
    }

    public function approve($userId)
    {
        $this->update([
            'is_approved' => true,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }
}
