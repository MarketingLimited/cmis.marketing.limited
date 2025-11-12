<?php

namespace App\Models\Experiment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExperimentVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.experiment_variants';
    protected $primaryKey = 'variant_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'exp_id',
        'asset_id',
        'variant_name',
        'variant_label',
        'traffic_allocation',
        'impressions',
        'clicks',
        'conversions',
        'cost',
        'performance_metrics',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'variant_id' => 'string',
        'exp_id' => 'string',
        'asset_id' => 'string',
        'traffic_allocation' => 'float',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'cost' => 'decimal:2',
        'performance_metrics' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the experiment
     */
    public function experiment()
    {
        return $this->belongsTo(Experiment::class, 'exp_id', 'exp_id');
    }

    /**
     * Get the creative asset
     */
    public function asset()
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');
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
     * Calculate CPC
     */
    public function getCpcAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0.0;
        }

        return $this->cost / $this->clicks;
    }
}
