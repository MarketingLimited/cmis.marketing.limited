<?php

namespace App\Models\Experiment;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExperimentResult extends BaseModel
{
    use HasFactory;

    protected $table = 'cmis.experiment_results';
    protected $primaryKey = 'result_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'experiment_id',
        'variant_id',
        'date',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'ctr',
        'cpc',
        'conversion_rate',
        'roi',
        'additional_metrics',
    ];

    protected $casts = [
        'result_id' => 'string',
        'experiment_id' => 'string',
        'variant_id' => 'string',
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
        'updated_at' => 'datetime',
    ];

    /**
     * Get the experiment
     */
    public function experiment()
    {
        return $this->belongsTo(Experiment::class, 'experiment_id', 'experiment_id');
    }

    /**
     * Get the variant
     */
    public function variant()
    {
        return $this->belongsTo(ExperimentVariant::class, 'variant_id', 'variant_id');
    }

    /**
     * Scope for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for recent results
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }
}
