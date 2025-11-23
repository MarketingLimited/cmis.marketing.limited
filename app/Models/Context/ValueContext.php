<?php

namespace App\Models\Context;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ValueContext extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.value_contexts';
    protected $primaryKey = 'context_id';
    protected $fillable = [
        'context_id',
        'org_id',
        'offering_id',
        'segment_id',
        'campaign_id',
        'channel_id',
        'format_id',
        'locale',
        'awareness_stage',
        'funnel_stage',
        'framework',
        'tone',
        'dataset_ref',
        'variant_tag',
        'tags',
        'market_id',
        'industry_id',
        'context_fingerprint',
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
     * Get the offering
     */
    public function offering(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Offering::class, 'offering_id', 'offering_id');

    }
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
     * Scope active contexts
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true)->whereNull('deleted_at');

    }
    /**
     * Scope by framework
     */
    public function scopeByFramework($query, string $framework): Builder
    {
        return $query->where('framework', $framework);

    }
    /**
     * Scope by awareness stage
     */
    public function scopeByAwarenessStage($query, string $stage): Builder
    {
        return $query->where('awareness_stage', $stage);
}
}
