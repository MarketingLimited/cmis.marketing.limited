<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ValueContext extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.value_contexts';
    protected $primaryKey = 'context_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'offering_id',
        'segment_id',
        'campaign_id',
        'channel_id',
        'format_id',
        'framework',
        'tone',
        'locale',
        'awareness_stage',
        'value_proposition',
        'pain_points',
        'benefits',
        'proof_points',
        'objection_handling',
        'unique_selling_points',
        'target_audience',
        'competitive_advantages',
        'metadata',
        'tags',
        'is_active',
        'created_by',
        'provider',
    ];

    protected $casts = [
        'context_id' => 'string',
        'org_id' => 'string',
        'offering_id' => 'string',
        'segment_id' => 'string',
        'campaign_id' => 'string',
        'created_by' => 'string',
        'channel_id' => 'integer',
        'format_id' => 'integer',
        'pain_points' => 'array',
        'benefits' => 'array',
        'proof_points' => 'array',
        'objection_handling' => 'array',
        'unique_selling_points' => 'array',
        'target_audience' => 'array',
        'competitive_advantages' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the base context
     */
    public function contextBase()
    {
        return $this->belongsTo(ContextBase::class, 'context_id', 'id');
    }

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the offering
     */
    public function offering()
    {
        return $this->belongsTo(\App\Models\Offering::class, 'offering_id', 'offering_id');
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
     * Scope active contexts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    /**
     * Scope by framework
     */
    public function scopeByFramework($query, string $framework)
    {
        return $query->where('framework', $framework);
    }

    /**
     * Scope by awareness stage
     */
    public function scopeByAwarenessStage($query, string $stage)
    {
        return $query->where('awareness_stage', $stage);
    }
}
