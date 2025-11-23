<?php

namespace App\Models\AdPlatform;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AdEntity extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.ad_entities';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'adset_external_id',
        'ad_external_id',
        'name',
        'status',
        'creative_id',
        'provider',
        'deleted_by',
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
     * Get the integration
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get the ad set
     */
    public function adSet(): BelongsTo
    {
        return $this->belongsTo(AdSet::class, 'adset_external_id', 'adset_external_id');
    }

    /**
     * Get the creative asset
     */
    public function creative(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Creative::class, 'creative_id', 'creative_id');
    }

    /**
     * Get metrics
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class, 'entity_external_id', 'ad_external_id')
            ->where('entity_level', 'ad');
    }

    /**
     * Scope active ads
     */
    public function scopeActive($query): Builder
    {
        return $query->where('ad_status', 'active');
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope by ad type
     */
    public function scopeByType($query, string $type): Builder
    {
        return $this->where('ad_type', $type);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status): Builder
    {
        return $query->where('ad_status', $status);
    }

    /**
     * Get latest metrics
     */
    public function getLatestMetrics()
    : \Illuminate\Database\Eloquent\Relations\Relation {
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
