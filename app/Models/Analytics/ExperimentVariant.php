<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperimentVariant extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.experiment_variants';
    protected $primaryKey = 'variant_id';
    protected $fillable = [
        'experiment_id', 'name', 'description', 'is_control',
        'traffic_percentage', 'config', 'impressions', 'clicks',
        'conversions', 'spend', 'revenue', 'metrics',
        'conversion_rate', 'improvement_over_control',
        'confidence_interval_lower', 'confidence_interval_upper', 'status'
    ];

    protected $casts = [
        'is_control' => 'boolean',
        'config' => 'array',
        'metrics' => 'array',
        'traffic_percentage' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'conversion_rate' => 'decimal:4',
        'improvement_over_control' => 'decimal:2',
        'confidence_interval_lower' => 'decimal:4',
        'confidence_interval_upper' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id', 'experiment_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExperimentResult::class, 'variant_id', 'variant_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExperimentEvent::class, 'variant_id', 'variant_id');
    }

    /**
     * Calculate and update conversion rate
     */
    public function calculateConversionRate(): void
    {
        if ($this->impressions > 0) {
            $rate = ($this->conversions / $this->impressions) * 100;
            $this->update(['conversion_rate' => $rate]);
        }
    }

    /**
     * Calculate CTR (Click-Through Rate)
     */
    public function getCTR(): float
    {
        if ($this->impressions > 0) {
            return ($this->clicks / $this->impressions) * 100;
        }
        return 0;
    }

    /**
     * Calculate CPC (Cost Per Click)
     */
    public function getCPC(): float
    {
        if ($this->clicks > 0) {
            return $this->spend / $this->clicks;
        }
        return 0;
    }

    /**
     * Calculate CPA (Cost Per Acquisition)
     */
    public function getCPA(): float
    {
        if ($this->conversions > 0) {
            return $this->spend / $this->conversions;
        }
        return 0;
    }

    /**
     * Calculate ROI
     */
    public function getROI(): float
    {
        if ($this->spend > 0) {
            return (($this->revenue - $this->spend) / $this->spend) * 100;
        }
        return 0;
    }

    /**
     * Update metrics from aggregated data
     */
    public function updateMetrics(array $data): void
    {
        $this->update([
            'impressions' => $data['impressions'] ?? $this->impressions,
            'clicks' => $data['clicks'] ?? $this->clicks,
            'conversions' => $data['conversions'] ?? $this->conversions,
            'spend' => $data['spend'] ?? $this->spend,
            'revenue' => $data['revenue'] ?? $this->revenue
        ]);

        $this->calculateConversionRate();
    }

    /**
     * Check if variant is winning
     */
    public function isWinning(): bool
    {
        $experiment = $this->experiment;

        if (!$experiment || $this->is_control) {
            return false;
        }

        $control = $experiment->controlVariant();

        if (!$control) {
            return false;
        }

        // Check if improvement is positive and significant
        return $this->improvement_over_control > 0
            && $this->improvement_over_control >= $experiment->minimum_detectable_effect;
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(): array
    {
        return [
            'impressions' => $this->impressions,
            'clicks' => $this->clicks,
            'conversions' => $this->conversions,
            'spend' => (float) $this->spend,
            'revenue' => (float) $this->revenue,
            'ctr' => round($this->getCTR(), 2),
            'cpc' => round($this->getCPC(), 2),
            'cpa' => round($this->getCPA(), 2),
            'conversion_rate' => (float) $this->conversion_rate,
            'roi' => round($this->getROI(), 2),
            'improvement_over_control' => (float) $this->improvement_over_control,
            'is_winning' => $this->isWinning()
        ];
    }

    /**
     * Scope: Active variants
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Control variants
     */
    public function scopeControl($query): Builder
    {
        return $query->where('is_control', true);
    }

    /**
     * Scope: Test variants (non-control)
     */
    public function scopeTestVariants($query): Builder
    {
        return $query->where('is_control', false);
    }
}
