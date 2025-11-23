<?php

namespace App\Models\Marketing;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class GeneratedCreative extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis_marketing.generated_creatives';
    protected $primaryKey = 'creative_id';
    protected $fillable = [
        'creative_id',
        'topic',
        'tone',
        'variant_index',
        'hook',
        'concept',
        'narrative',
        'slogan',
        'emotion_profile',
        'tags',
        'generated_at',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');

        }
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');

        }
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by', 'user_id');

        }
    public function scopeApproved($query): Builder
    {
        return $query->where('is_approved', true);

        }
    public function scopeByType($query, $type): Builder
    {
        return $query->where('creative_type', $type);

        }
    public function scopeHighQuality($query, $threshold = 0.8): Builder
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
}
