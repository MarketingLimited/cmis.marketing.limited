<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CampaignAnalytics extends Model
{
    use HasUuids;
    protected $table = 'cmis.campaign_analytics';
    protected $primaryKey = 'analytics_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'date',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'ctr',
        'cpc',
        'cpa',
        'roas',
        'engagement_rate',
        'reach',
        'frequency',
        'video_views',
        'shares',
        'comments',
        'likes',
        'custom_metrics',
        'recorded_at',
    ];

    protected $casts = [
        'analytics_id' => 'string',
        'campaign_id' => 'string',
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'ctr' => 'float',
        'cpc' => 'decimal:4',
        'cpa' => 'decimal:4',
        'roas' => 'float',
        'engagement_rate' => 'float',
        'reach' => 'integer',
        'frequency' => 'float',
        'video_views' => 'integer',
        'shares' => 'integer',
        'comments' => 'integer',
        'likes' => 'integer',
        'custom_metrics' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope recent analytics
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    /**
     * Calculate conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0.0;
        }

        return ($this->conversions / $this->clicks) * 100;
    }

    /**
     * Calculate ROI
     */
    public function getRoiAttribute(): float
    {
        if ($this->spend == 0) {
            return 0.0;
        }

        return (($this->revenue - $this->spend) / $this->spend) * 100;
    }
}
