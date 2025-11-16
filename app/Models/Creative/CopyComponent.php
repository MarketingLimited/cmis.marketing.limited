<?php

namespace App\Models\Creative;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CopyComponent extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.copy_components';
    protected $primaryKey = 'component_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

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
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the channel
     */
    public function channel()
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id', 'channel_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type_code', $type);
    }

    /**
     * Scope by tone
     */
    public function scopeWithTone($query, string $tone)
    {
        return $query->where('tone', $tone);
    }

    /**
     * Scope high performing
     */
    public function scopeHighPerforming($query, float $threshold = 0.7)
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
