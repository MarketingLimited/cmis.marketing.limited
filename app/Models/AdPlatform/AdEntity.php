<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.ad_entities';
    protected $primaryKey = 'ad_entity_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ad_set_id',
        'asset_id',
        'platform',
        'ad_name',
        'ad_external_id',
        'ad_status',
        'ad_type',
        'creative_data',
        'headline',
        'description',
        'call_to_action',
        'destination_url',
        'display_url',
        'tracking_params',
        'metadata',
        'last_synced_at',
        'provider',
    ];

    protected $casts = [
        'ad_entity_id' => 'string',
        'ad_set_id' => 'string',
        'asset_id' => 'string',
        'creative_data' => 'array',
        'tracking_params' => 'array',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the ad set
     */
    public function adSet()
    {
        return $this->belongsTo(AdSet::class, 'ad_set_id', 'ad_set_id');
    }

    /**
     * Get the creative asset
     */
    public function asset()
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');
    }

    /**
     * Get metrics
     */
    public function metrics()
    {
        return $this->hasMany(AdMetric::class, 'entity_id', 'ad_entity_id')
            ->where('entity_type', 'ad');
    }

    /**
     * Scope active ads
     */
    public function scopeActive($query)
    {
        return $query->where('ad_status', 'active');
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope by ad type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('ad_type', $type);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('ad_status', $status);
    }

    /**
     * Get latest metrics
     */
    public function getLatestMetrics()
    {
        return $this->metrics()
            ->orderBy('metric_date', 'desc')
            ->first();
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(): array
    {
        $metrics = $this->metrics()->get();

        return [
            'total_impressions' => $metrics->sum('impressions'),
            'total_clicks' => $metrics->sum('clicks'),
            'total_conversions' => $metrics->sum('conversions'),
            'total_spend' => $metrics->sum('spend'),
            'avg_ctr' => $metrics->avg('ctr'),
            'avg_cpc' => $metrics->avg('cpc'),
            'avg_cpa' => $metrics->avg('cpa'),
        ];
    }
}
