<?php

namespace App\Models\Campaign;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignAdSet extends BaseModel
{
    use SoftDeletes, HasOrganization;

    protected $table = 'cmis.campaign_ad_sets';
    protected $primaryKey = 'ad_set_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ad_set_id',
        'org_id',
        'campaign_id',
        'name',
        'description',
        'status',
        'budget_type',
        'daily_budget',
        'lifetime_budget',
        'bid_strategy',
        'bid_amount',
        'billing_event',
        'start_time',
        'end_time',
        'schedule',
        'optimization_goal',
        'conversion_event',
        'pixel_id',
        'app_id',
        'targeting',
        'locations',
        'age_range',
        'genders',
        'interests',
        'behaviors',
        'custom_audiences',
        'lookalike_audiences',
        'excluded_audiences',
        'placements',
        'automatic_placements',
        'device_platforms',
        'publisher_platforms',
        'external_ad_set_id',
        'last_synced_at',
        'sync_status',
        'platform_settings',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'ad_set_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'bid_amount' => 'decimal:4',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'last_synced_at' => 'datetime',
        'schedule' => 'array',
        'targeting' => 'array',
        'locations' => 'array',
        'age_range' => 'array',
        'genders' => 'array',
        'interests' => 'array',
        'behaviors' => 'array',
        'custom_audiences' => 'array',
        'lookalike_audiences' => 'array',
        'excluded_audiences' => 'array',
        'placements' => 'array',
        'automatic_placements' => 'boolean',
        'device_platforms' => 'array',
        'publisher_platforms' => 'array',
        'platform_settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Budget type constants
     */
    public const BUDGET_TYPE_DAILY = 'daily';
    public const BUDGET_TYPE_LIFETIME = 'lifetime';

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the ads for this ad set
     */
    public function ads()
    {
        return $this->hasMany(CampaignAd::class, 'ad_set_id', 'ad_set_id');
    }

    /**
     * Get the creator user
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\Core\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope active ad sets
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope synced ad sets
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope pending sync
     */
    public function scopePendingSync($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Check if ad set is running
     */
    public function isRunning(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
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
     * Get effective budget
     */
    public function getEffectiveBudget(): ?float
    {
        return $this->budget_type === self::BUDGET_TYPE_DAILY
            ? $this->daily_budget
            : $this->lifetime_budget;
    }

    /**
     * Check if ad set is synced to external platform
     */
    public function isSynced(): bool
    {
        return !empty($this->external_ad_set_id) && $this->sync_status === 'synced';
    }

    /**
     * Get total spend from ads
     */
    public function getTotalSpend(): float
    {
        return $this->ads()->sum('spend') ?? 0;
    }

    /**
     * Get active ads count
     */
    public function getActiveAdsCount(): int
    {
        return $this->ads()->where('status', CampaignAd::STATUS_ACTIVE)->count();
    }
}
