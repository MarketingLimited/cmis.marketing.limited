<?php

namespace App\Models\Experiment;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExperimentVariant extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.experiment_variants';
    protected $primaryKey = 'variant_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'experiment_id',
        'name',
        'description',
        'is_control',
        'traffic_percentage',
        'config',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'metrics',
        'conversion_rate',
        'improvement_over_control',
        'confidence_interval_lower',
        'confidence_interval_upper',
        'status',
    ];

    protected $casts = [
        'variant_id' => 'string',
        'experiment_id' => 'string',
        'is_control' => 'boolean',
        'traffic_percentage' => 'decimal:2',
        'config' => 'array',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'metrics' => 'array',
        'conversion_rate' => 'decimal:4',
        'improvement_over_control' => 'decimal:2',
        'confidence_interval_lower' => 'decimal:4',
        'confidence_interval_upper' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the experiment
     */
    public function experiment()
    {
        return $this->belongsTo(Experiment::class, 'experiment_id', 'experiment_id');
    }

    /**
     * Get the variant results
     */
    public function results()
    {
        return $this->hasMany(ExperimentResult::class, 'variant_id', 'variant_id');
    }

    /**
     * Get the variant events
     */
    public function events()
    {
        return $this->hasMany(ExperimentEvent::class, 'variant_id', 'variant_id');
    }

    /**
     * Calculate CTR
     */
    public function getCtrAttribute(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }
        return ($this->clicks / $this->impressions) * 100;
    }

    /**
     * Calculate conversion rate from clicks
     */
    public function getClickConversionRateAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0.0;
        }
        return ($this->conversions / $this->clicks) * 100;
    }

    /**
     * Calculate CPC (Cost Per Click)
     */
    public function getCpcAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0.0;
        }
        return $this->spend / $this->clicks;
    }

    /**
     * Calculate CPA (Cost Per Acquisition)
     */
    public function getCpaAttribute(): float
    {
        if ($this->conversions === 0) {
            return 0.0;
        }
        return $this->spend / $this->conversions;
    }

    /**
     * Calculate ROAS (Return on Ad Spend)
     */
    public function getRoasAttribute(): float
    {
        if ($this->spend === 0) {
            return 0.0;
        }
        return $this->revenue / $this->spend;
    }

    /**
     * Calculate CPM (Cost Per Mille)
     */
    public function getCpmAttribute(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }
        return ($this->spend / $this->impressions) * 1000;
    }

    /**
     * Scope active variants
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope control variants
     */
    public function scopeControl($query)
    {
        return $query->where('is_control', true);
    }

    /**
     * Scope test variants (non-control)
     */
    public function scopeTestVariants($query)
    {
        return $query->where('is_control', false);
    }
}
