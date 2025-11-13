<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis_ads.ad_campaigns';
    protected $primaryKey = 'ad_campaign_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ad_account_id',
        'campaign_id',
        'platform',
        'campaign_name',
        'campaign_external_id',
        'campaign_status',
        'objective',
        'budget_type',
        'daily_budget',
        'lifetime_budget',
        'bid_strategy',
        'start_time',
        'end_time',
        'targeting',
        'placements',
        'optimization_goal',
        'metadata',
        'last_synced_at',
        'provider',
    ];

    protected $casts = [
        'ad_campaign_id' => 'string',
        'ad_account_id' => 'string',
        'campaign_id' => 'string',
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'targeting' => 'array',
        'placements' => 'array',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the ad account
     */
    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class, 'ad_account_id', 'ad_account_id');
    }

    /**
     * Get the CMIS campaign
     */
    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the integration (platform connection)
     */
    public function integration()
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'ad_account_id', 'account_id')
            ->where('platform', $this->platform);
    }

    /**
     * Get ad sets
     */
    public function adSets()
    {
        return $this->hasMany(AdSet::class, 'ad_campaign_id', 'ad_campaign_id');
    }

    /**
     * Get metrics
     */
    public function metrics()
    {
        return $this->hasMany(AdMetric::class, 'entity_id', 'ad_campaign_id')
            ->where('entity_type', 'campaign');
    }

    /**
     * Scope active campaigns
     */
    public function scopeActive($query)
    {
        return $query->where('campaign_status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_time')
                    ->orWhere('end_time', '>=', now());
            });
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
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
        return $query->where('campaign_status', $status);
    }

    /**
     * Check if campaign is running
     */
    public function isRunning(): bool
    {
        if ($this->campaign_status !== 'active') {
            return false;
        }

        if ($this->start_time && $this->start_time->isFuture()) {
            return false;
        }

        if ($this->end_time && $this->end_time->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get total spend
     */
    public function getTotalSpend(): float
    {
        return $this->metrics()
            ->sum('spend');
    }
}
