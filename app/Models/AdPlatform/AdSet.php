<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.ad_sets';
    protected $primaryKey = 'ad_set_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ad_campaign_id',
        'platform',
        'ad_set_name',
        'ad_set_external_id',
        'ad_set_status',
        'targeting',
        'daily_budget',
        'lifetime_budget',
        'bid_amount',
        'bid_strategy',
        'optimization_goal',
        'start_time',
        'end_time',
        'placements',
        'schedule',
        'metadata',
        'last_synced_at',
        'provider',
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
     * Get the ad campaign
     */
    public function adCampaign()
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id', 'ad_campaign_id');
    }

    /**
     * Get ad entities (ads)
     */
    public function ads()
    {
        return $this->hasMany(AdEntity::class, 'ad_set_id', 'ad_set_id');
    }

    /**
     * Get metrics
     */
    public function metrics()
    {
        return $this->hasMany(AdMetric::class, 'entity_id', 'ad_set_id')
            ->where('entity_type', 'ad_set');
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
