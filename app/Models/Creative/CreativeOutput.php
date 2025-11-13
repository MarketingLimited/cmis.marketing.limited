<?php

namespace App\Models\Creative;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreativeOutput extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.creative_outputs';
    protected $primaryKey = 'output_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'output_id',
        'org_id',
        'campaign_id',
        'context_id',
        'type',
        'status',
        'data',
        'provider',
    ];

    protected $casts = ['output_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'asset_id' => 'string',
        'context_id' => 'string',
        'ai_model_id' => 'string',
        'created_by' => 'string',
        'content' => 'array',
        'metadata' => 'array',
        'performance_data' => 'array',
        'tags' => 'array',
        'quality_score' => 'float',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'data' => 'array',
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
     * Get the creative asset
     */
    public function asset()
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');
    }

    /**
     * Get the AI model used
     */
    public function aiModel()
    {
        return $this->belongsTo(\App\Models\AiModel::class, 'ai_model_id', 'model_id');
    }

    /**
     * Get performance metrics
     */
    public function performanceMetrics()
    {
        return $this->hasMany(\App\Models\PerformanceMetric::class, 'output_id', 'output_id');
    }

    /**
     * Scope published outputs
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * Scope by output type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('output_type', $type);
    }
}
