<?php

namespace App\Models\Twitter;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\Integration;
use App\Models\Analytics\UnifiedMetric;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Twitter Campaign Model
 *
 * Represents a Twitter/X Ads campaign with full support for:
 * - Promoted Tweets
 * - Promoted Accounts
 * - Promoted Trends
 *
 * @property string $id
 * @property string $org_id
 * @property string $integration_id
 * @property string $platform_campaign_id
 * @property string $platform_account_id
 * @property string $funding_instrument_id
 * @property string $name
 * @property string $objective
 * @property string $campaign_type
 * @property int $daily_budget_amount_local_micro
 * @property int $total_budget_amount_local_micro
 * @property string $currency
 * @property \Carbon\Carbon $start_time
 * @property \Carbon\Carbon $end_time
 * @property string $status
 * @property bool $standard_delivery
 * @property int $frequency_cap
 * @property array $targeting_metadata
 * @property array $platform_metadata
 */
class TwitterCampaign extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis_twitter.campaigns';

    protected $primaryKey = 'id';

    protected $fillable = [
        'org_id',
        'integration_id',
        'platform_campaign_id',
        'platform_account_id',
        'funding_instrument_id',
        'name',
        'objective',
        'campaign_type',
        'daily_budget_amount_local_micro',
        'total_budget_amount_local_micro',
        'currency',
        'start_time',
        'end_time',
        'status',
        'standard_delivery',
        'frequency_cap',
        'targeting_metadata',
        'platform_metadata',
    ];

    protected $casts = [
        'daily_budget_amount_local_micro' => 'integer',
        'total_budget_amount_local_micro' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'standard_delivery' => 'boolean',
        'frequency_cap' => 'integer',
        'targeting_metadata' => 'array',
        'platform_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Campaign types
     */
    public const TYPE_PROMOTED_TWEETS = 'PROMOTED_TWEETS';
    public const TYPE_PROMOTED_ACCOUNTS = 'PROMOTED_ACCOUNTS';
    public const TYPE_PROMOTED_TRENDS = 'PROMOTED_TRENDS';

    /**
     * Campaign objectives
     */
    public const OBJECTIVE_AWARENESS = 'AWARENESS';
    public const OBJECTIVE_TWEET_ENGAGEMENTS = 'TWEET_ENGAGEMENTS';
    public const OBJECTIVE_VIDEO_VIEWS = 'VIDEO_VIEWS';
    public const OBJECTIVE_FOLLOWERS = 'FOLLOWERS';
    public const OBJECTIVE_APP_INSTALLS = 'APP_INSTALLS';
    public const OBJECTIVE_WEBSITE_CLICKS = 'WEBSITE_CLICKS';
    public const OBJECTIVE_REACH = 'REACH';
    public const OBJECTIVE_APP_ENGAGEMENTS = 'APP_ENGAGEMENTS';

    /**
     * Campaign statuses
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_DELETED = 'DELETED';

    /**
     * Get the integration this campaign belongs to
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get all metrics for this campaign
     */
    public function metrics(): MorphMany
    {
        return $this->morphMany(UnifiedMetric::class, 'entity');
    }

    /**
     * Convert daily budget from micros to dollars
     */
    public function getDailyBudgetAttribute(): ?float
    {
        return $this->daily_budget_amount_local_micro
            ? round($this->daily_budget_amount_local_micro / 1000000, 2)
            : null;
    }

    /**
     * Convert total budget from micros to dollars
     */
    public function getTotalBudgetAttribute(): ?float
    {
        return $this->total_budget_amount_local_micro
            ? round($this->total_budget_amount_local_micro / 1000000, 2)
            : null;
    }

    /**
     * Set daily budget from dollars to micros
     */
    public function setDailyBudgetAttribute(?float $value): void
    {
        $this->attributes['daily_budget_amount_local_micro'] = $value
            ? (int) ($value * 1000000)
            : null;
    }

    /**
     * Set total budget from dollars to micros
     */
    public function setTotalBudgetAttribute(?float $value): void
    {
        $this->attributes['total_budget_amount_local_micro'] = $value
            ? (int) ($value * 1000000)
            : null;
    }

    /**
     * Check if campaign is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if campaign is paused
     */
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Scope to get active campaigns
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get campaigns by type
     */
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('campaign_type', $type);
    }

    /**
     * Scope to get campaigns by objective
     */
    public function scopeWithObjective($query, string $objective): Builder
    {
        return $query->where('objective', $objective);
    }
}
