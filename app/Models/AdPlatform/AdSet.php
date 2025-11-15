<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.ad_sets';
    protected $primaryKey = 'id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'campaign_external_id',
        'adset_external_id',
        'name',
        'status',
        'daily_budget',
        'start_date',
        'end_date',
        'billing_event',
        'optimization_goal',
        'provider',
        'deleted_by',
    ];

    protected $casts = [
        'ad_set_id' => 'string',
        'ad_campaign_id' => 'string',
        'targeting' => 'array',
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'bid_amount' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'placements' => 'array',
        'schedule' => 'array',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
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
     * Get the integration
     */
    public function integration()
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get the ad campaign
     */
    public function adCampaign()
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_external_id', 'campaign_external_id');
    }

    /**
     * Get ad entities (ads)
     */
    public function ads()
    {
        return $this->hasMany(AdEntity::class, 'adset_external_id', 'adset_external_id');
    }

    /**
     * Get metrics
     */
    public function metrics()
    {
        return $this->hasMany(AdMetric::class, 'entity_external_id', 'adset_external_id')
            ->where('entity_level', 'adset');
    }

    /**
     * Scope active ad sets
     */
    public function scopeActive($query)
    {
        return $query->where('ad_set_status', 'active')
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
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('ad_set_status', $status);
    }

    /**
     * Check if ad set is running
     */
    public function isRunning(): bool
    {
        if ($this->ad_set_status !== 'active') {
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
        return $this->metrics()->sum('spend');
    }
}
