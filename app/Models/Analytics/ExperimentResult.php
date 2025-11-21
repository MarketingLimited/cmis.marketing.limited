<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentResult extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.experiment_results';
    protected $primaryKey = 'result_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'experiment_id', 'variant_id', 'date', 'impressions', 'clicks',
        'conversions', 'spend', 'revenue', 'ctr', 'cpc',
        'conversion_rate', 'roi', 'additional_metrics'
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'ctr' => 'decimal:4',
        'cpc' => 'decimal:4',
        'conversion_rate' => 'decimal:4',
        'roi' => 'decimal:2',
        'additional_metrics' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id', 'experiment_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class, 'variant_id', 'variant_id');
    }

    /**
     * Calculate derived metrics
     */
    public function calculateMetrics(): void
    {
        $ctr = $this->impressions > 0 ? ($this->clicks / $this->impressions) * 100 : 0;
        $cpc = $this->clicks > 0 ? $this->spend / $this->clicks : 0;
        $conversionRate = $this->impressions > 0 ? ($this->conversions / $this->impressions) * 100 : 0;
        $roi = $this->spend > 0 ? (($this->revenue - $this->spend) / $this->spend) * 100 : 0;

        $this->update([
            'ctr' => $ctr,
            'cpc' => $cpc,
            'conversion_rate' => $conversionRate,
            'roi' => $roi
        ]);
    }

    /**
     * Scope: Recent results
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    /**
     * Scope: By date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
