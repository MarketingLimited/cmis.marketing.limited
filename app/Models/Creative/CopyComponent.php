<?php

namespace App\Models\Creative;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CopyComponent extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.copy_components';
    protected $primaryKey = 'component_id';
    protected $fillable = [
        'component_id',
        'type_code',
        'content',
        'industry_id',
        'market_id',
        'awareness_stage',
        'channel_id',
        'usage_notes',
        'quality_score',
        'context_id',
        'example_id',
        'campaign_id',
        'plan_id',
        'visual_prompt',
        'provider',
    ];

    protected $casts = ['component_id' => 'string',
        'org_id' => 'string',
        'context_id' => 'string',
        'campaign_id' => 'string',
        'example_id' => 'string',
        'created_by' => 'string',
        'channel_id' => 'integer',
        'market_id' => 'integer',
        'industry_id' => 'integer',
        'length' => 'integer',
        'usage_count' => 'integer',
        'performance_score' => 'float',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'visual_prompt' => 'array',
    ];

    

    /**
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');

    }
    /**
     * Get the channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id', 'channel_id');

    }
    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    }
    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('type_code', $type);

    }
    /**
     * Scope by tone
     */
    public function scopeWithTone($query, string $tone): Builder
    {
        return $query->where('tone', $tone);

    }
    /**
     * Scope high performing
     */
    public function scopeHighPerforming($query, float $threshold = 0.7): Builder
    {
        return $query->where('performance_score', '>=', $threshold)
            ->orderBy('performance_score', 'desc');

    }
    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
}
}
