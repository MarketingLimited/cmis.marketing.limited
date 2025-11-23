<?php

namespace App\Models\AdPlatform;

use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdCampaign extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.ad_campaigns';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'campaign_external_id',
        'name',
        'objective',
        'start_date',
        'end_date',
        'status',
        'budget',
        'metrics',
        'fetched_at',
        'provider',
        'deleted_by',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'deleted_by' => 'string',
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'metrics' => 'array',
        'fetched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the integration (platform connection)
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Scope active campaigns
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope by objective
     */
    public function scopeByObjective($query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Get ad sets for this campaign
     */
    public function adSets(): HasMany
    {
        return $this->hasMany(AdSet::class, 'campaign_external_id', 'campaign_external_id');
    }

    /**
     * Get ad account
     */
    public function adAccount(): HasOneThrough
    {
        return $this->hasOneThrough(
            AdAccount::class,
            \App\Models\Core\Integration::class,
            'integration_id',
            'integration_id',
            'integration_id',
            'integration_id'
        );
    }

    /**
     * Get metrics for this campaign
     */
    public function adMetrics(): HasMany
    {
        return $this->hasMany(AdMetric::class, 'entity_external_id', 'campaign_external_id')
            ->where('entity_level', 'campaign');
    }

    /**
     * Alias for adMetrics() for consistency
     */
    public function metrics()
    : \Illuminate\Database\Eloquent\Relations\Relation {
        return $this->adMetrics();
    }

    /**
     * Scope by integration
     */
    public function scopeForIntegration(Builder $query, string $integrationId): Builder
    {
        return $query->where('integration_id', $integrationId);
    }

    /**
     * Scope by provider/platform
     */
    public function scopeByProvider($query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope running campaigns
     */
    public function scopeRunning($query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Check if campaign is running
     */
    public function isRunning(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(): array
    {
        $metrics = $this->adMetrics()
            ->selectRaw('
                SUM(spend) as total_spend,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks
            ')
            ->first();

        if (!$metrics) {
            return [
                'spend' => 0,
                'impressions' => 0,
                'clicks' => 0,
                'ctr' => 0,
                'cpc' => 0,
            ];
        }

        $ctr = $metrics->total_impressions > 0
            ? ($metrics->total_clicks / $metrics->total_impressions) * 100
            : 0;

        $cpc = $metrics->total_clicks > 0
            ? $metrics->total_spend / $metrics->total_clicks
            : 0;

        return [
            'spend' => round($metrics->total_spend, 2),
            'impressions' => $metrics->total_impressions,
            'clicks' => $metrics->total_clicks,
            'ctr' => round($ctr, 2),
            'cpc' => round($cpc, 2),
        ];
    }
}
