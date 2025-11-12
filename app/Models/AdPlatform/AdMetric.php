<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Model;

class AdMetric extends Model
{
    protected $table = 'cmis.ad_metrics';
    protected $primaryKey = 'metric_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'platform',
        'metric_date',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'ctr',
        'cpc',
        'cpm',
        'cpa',
        'roas',
        'video_views',
        'video_completions',
        'engagement_rate',
        'reach',
        'frequency',
        'custom_metrics',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'metric_id' => 'string',
        'entity_id' => 'string',
        'metric_date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'ctr' => 'float',
        'cpc' => 'decimal:4',
        'cpm' => 'decimal:4',
        'cpa' => 'decimal:4',
        'roas' => 'float',
        'video_views' => 'integer',
        'video_completions' => 'integer',
        'engagement_rate' => 'float',
        'reach' => 'integer',
        'frequency' => 'float',
        'custom_metrics' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the entity (polymorphic)
     */
    public function entity()
    {
        $models = [
            'campaign' => AdCampaign::class,
            'ad_set' => AdSet::class,
            'ad' => AdEntity::class,
        ];

        $modelClass = $models[$this->entity_type] ?? null;

        if (!$modelClass) {
            return null;
        }

        return $modelClass::find($this->entity_id);
    }

    /**
     * Scope by entity type
     */
    public function scopeByEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    /**
     * Scope recent metrics
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('metric_date', '>=', now()->subDays($days));
    }

    /**
     * Scope by metric date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('metric_date', $date);
    }

    /**
     * Calculate CTR if not set
     */
    public function calculateCtr(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }

        return ($this->clicks / $this->impressions) * 100;
    }

    /**
     * Calculate CPC if not set
     */
    public function calculateCpc(): float
    {
        if ($this->clicks === 0) {
            return 0.0;
        }

        return $this->spend / $this->clicks;
    }

    /**
     * Calculate CPA if not set
     */
    public function calculateCpa(): float
    {
        if ($this->conversions === 0) {
            return 0.0;
        }

        return $this->spend / $this->conversions;
    }

    /**
     * Calculate ROAS if not set
     */
    public function calculateRoas(): float
    {
        if ($this->spend == 0) {
            return 0.0;
        }

        return $this->revenue / $this->spend;
    }

    /**
     * Get conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0.0;
        }

        return ($this->conversions / $this->clicks) * 100;
    }
}
