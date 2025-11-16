<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AdCampaign extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.ad_campaigns';
    protected $primaryKey = 'id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

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
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the integration (platform connection)
     */
    public function integration()
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Scope active campaigns
     */
    public function scopeActive($query)
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
    public function scopeByObjective($query, string $objective)
    {
        return $query->where('objective', $objective);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get ad sets for this campaign
     */
    public function adSets()
    {
        return $this->hasMany(AdSet::class, 'campaign_external_id', 'campaign_external_id');
    }

    /**
     * Get ad account
     */
    public function adAccount()
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
    public function adMetrics()
    {
        return $this->hasMany(AdMetric::class, 'entity_external_id', 'campaign_external_id')
            ->where('entity_level', 'campaign');
    }

    /**
     * Scope by organization
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope by integration
     */
    public function scopeForIntegration($query, string $integrationId)
    {
        return $query->where('integration_id', $integrationId);
    }

    /**
     * Scope by provider/platform
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope running campaigns
     */
    public function scopeRunning($query)
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
