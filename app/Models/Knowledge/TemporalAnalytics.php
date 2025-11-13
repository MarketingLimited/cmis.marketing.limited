<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Model;

class TemporalAnalytics extends Model
{
    protected $table = 'cmis.temporal_analytics';
    protected $primaryKey = 'analytics_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'entity_type',
        'entity_id',
        'time_period',
        'period_start',
        'period_end',
        'metrics',
        'trends',
        'anomalies',
        'predictions',
        'confidence_scores',
        'data_quality',
        'computed_at',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'analytics_id' => 'string',
        'org_id' => 'string',
        'entity_id' => 'string',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'metrics' => 'array',
        'trends' => 'array',
        'anomalies' => 'array',
        'predictions' => 'array',
        'confidence_scores' => 'array',
        'data_quality' => 'float',
        'computed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Scope by entity type
     */
    public function scopeByEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope by time period
     */
    public function scopeByPeriod($query, string $period)
    {
        return $query->where('time_period', $period);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->where('period_start', '>=', $start)
            ->where('period_end', '<=', $end);
    }

    /**
     * Scope with anomalies
     */
    public function scopeWithAnomalies($query)
    {
        return $query->whereNotNull('anomalies')
            ->whereRaw("jsonb_array_length(anomalies) > 0");
    }

    /**
     * Scope high confidence
     */
    public function scopeHighConfidence($query, float $threshold = 0.8)
    {
        return $query->whereRaw("(confidence_scores->>'overall')::float >= ?", [$threshold]);
    }

    /**
     * Scope good data quality
     */
    public function scopeGoodQuality($query, float $threshold = 0.7)
    {
        return $query->where('data_quality', '>=', $threshold);
    }

    /**
     * Scope recent analytics
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('computed_at', '>=', now()->subDays($days));
    }

    /**
     * Get metric value
     */
    public function getMetric(string $metricName)
    {
        return $this->metrics[$metricName] ?? null;
    }

    /**
     * Get trend for metric
     */
    public function getTrend(string $metricName): ?array
    {
        return $this->trends[$metricName] ?? null;
    }

    /**
     * Has anomalies
     */
    public function hasAnomalies(): bool
    {
        return !empty($this->anomalies);
    }
}
