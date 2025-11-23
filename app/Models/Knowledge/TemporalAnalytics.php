<?php

namespace App\Models\Knowledge;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;

class TemporalAnalytics extends BaseModel
{
    use HasOrganization;
protected $table = 'cmis.temporal_analytics';
    protected $primaryKey = 'analytics_id';
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
     * Scope by entity type
     */
    public function scopeByEntityType($query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);

    }
    /**
     * Scope by time period
     */
    public function scopeByPeriod($query, string $period): Builder
    {
        return $query->where('time_period', $period);

    }
    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $start, $end): Builder
    {
        return $query->where('period_start', '>=', $start)
            ->where('period_end', '<=', $end);

    }
    /**
     * Scope with anomalies
     */
    public function scopeWithAnomalies($query): Builder
    {
        return $query->whereNotNull('anomalies')
            ->whereRaw("jsonb_array_length(anomalies) > 0");

    }
    /**
     * Scope high confidence
     */
    public function scopeHighConfidence($query, float $threshold = 0.8): Builder
    {
        return $query->whereRaw("(confidence_scores->>'overall')::float >= ?", [$threshold]);

    }
    /**
     * Scope good data quality
     */
    public function scopeGoodQuality($query, float $threshold = 0.7): Builder
    {
        return $query->where('data_quality', '>=', $threshold);

    }
    /**
     * Scope recent analytics
     */
    public function scopeRecent($query, int $days = 30): Builder
    {
        return $query->where('computed_at', '>=', now()->subDays($days));

    }
    /**
     * Get metric value
     */
    public function getMetric(string $metricName)
    : mixed {
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
