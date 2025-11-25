<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Campaign extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.campaigns';

    protected $primaryKey = 'campaign_id';

    protected $fillable = [
        'campaign_id',
        'org_id',
        'name',
        'objective',
        'status',
        'start_date',
        'end_date',
        'budget',
        'currency',
        'context_id',
        'creative_id',
        'value_id',
        'created_by',
        'provider',
        'deleted_by',
        'description',
        // New platform-specific fields
        'platform',
        'campaign_type',
        'buying_type',
        'budget_type',
        'daily_budget',
        'lifetime_budget',
        'bid_strategy',
        'bid_amount',
        'optimization_goal',
        'is_advantage_plus',
        'is_smart_campaign',
        'is_performance_max',
        'attribution_spec',
        'external_campaign_id',
        'last_synced_at',
        'sync_status',
        'platform_settings',
        'targeting_summary',
    ];

    protected $casts = [
        'campaign_id' => 'string',
        'org_id' => 'string',
        'context_id' => 'string',
        'creative_id' => 'string',
        'value_id' => 'string',
        'created_by' => 'string',
        'deleted_by' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        // New platform-specific casts
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'bid_amount' => 'decimal:4',
        'is_advantage_plus' => 'boolean',
        'is_smart_campaign' => 'boolean',
        'is_performance_max' => 'boolean',
        'last_synced_at' => 'datetime',
        'platform_settings' => 'array',
        'targeting_summary' => 'array',
    ];

    /**
     * Platform constants
     */
    public const PLATFORM_META = 'meta';
    public const PLATFORM_GOOGLE = 'google';
    public const PLATFORM_TIKTOK = 'tiktok';
    public const PLATFORM_SNAPCHAT = 'snapchat';
    public const PLATFORM_TWITTER = 'twitter';
    public const PLATFORM_LINKEDIN = 'linkedin';

    /**
     * Campaign type/objective constants
     */
    public const TYPE_AWARENESS = 'awareness';
    public const TYPE_TRAFFIC = 'traffic';
    public const TYPE_ENGAGEMENT = 'engagement';
    public const TYPE_LEADS = 'leads';
    public const TYPE_CONVERSIONS = 'conversions';
    public const TYPE_APP_INSTALLS = 'app_installs';
    public const TYPE_VIDEO_VIEWS = 'video_views';
    public const TYPE_REACH = 'reach';
    public const TYPE_SALES = 'sales';

    /**
     * Bid strategy constants
     */
    public const BID_LOWEST_COST = 'lowest_cost';
    public const BID_COST_CAP = 'cost_cap';
    public const BID_BID_CAP = 'bid_cap';
    public const BID_TARGET_COST = 'target_cost';
    public const BID_MANUAL = 'manual';

    /**
     * Get validation rules for campaign creation
     *
     * @return array
     */
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'objective' => 'required|in:awareness,traffic,engagement,leads,sales,app_installs,video_views',
            'status' => 'nullable|in:draft,active,paused,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'context_id' => 'nullable|uuid',
            'creative_id' => 'nullable|uuid',
            'value_id' => 'nullable|uuid',
            'created_by' => 'nullable|uuid|exists:cmis.users,user_id',
            'provider' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get validation rules for campaign updates
     *
     * @return array
     */
    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'objective' => 'sometimes|required|in:awareness,traffic,engagement,leads,sales,app_installs,video_views',
            'status' => 'sometimes|required|in:draft,active,paused,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'context_id' => 'nullable|uuid',
            'creative_id' => 'nullable|uuid',
            'value_id' => 'nullable|uuid',
            'provider' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get validation messages
     *
     * @return array
     */
    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization ID is required',
            'org_id.uuid' => 'Organization ID must be a valid UUID',
            'org_id.exists' => 'Organization does not exist',
            'name.required' => 'Campaign name is required',
            'name.max' => 'Campaign name cannot exceed 255 characters',
            'objective.required' => 'Campaign objective is required',
            'objective.in' => 'Invalid campaign objective',
            'status.in' => 'Invalid campaign status',
            'end_date.after' => 'End date must be after start date',
            'budget.numeric' => 'Budget must be a number',
            'budget.min' => 'Budget cannot be negative',
            'currency.size' => 'Currency code must be exactly 3 characters',
            'created_by.exists' => 'Creator user does not exist',
        ];
    }



    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function offerings(): BelongsToMany
    {
        return $this->belongsToMany(
            Offering::class,
            'cmis.campaign_offerings',
            'campaign_id',
            'offering_id'
        );
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(CampaignPerformanceMetric::class, 'campaign_id', 'campaign_id');
    }

    public function adCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class, 'campaign_id', 'campaign_id');
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(\App\Models\Social\SocialPost::class, 'campaign_id', 'campaign_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(CampaignPerformanceMetric::class, 'campaign_id', 'campaign_id');
    }

    public function creativeAssets(): HasMany
    {
        return $this->hasMany(\App\Models\CreativeAsset::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the ad sets for this campaign
     */
    public function adSets(): HasMany
    {
        return $this->hasMany(Campaign\CampaignAdSet::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the ads for this campaign (through ad sets)
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Campaign\CampaignAd::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get available platforms
     */
    public static function getAvailablePlatforms(): array
    {
        return [
            self::PLATFORM_META => 'Meta (Facebook/Instagram)',
            self::PLATFORM_GOOGLE => 'Google Ads',
            self::PLATFORM_TIKTOK => 'TikTok Ads',
            self::PLATFORM_SNAPCHAT => 'Snapchat Ads',
            self::PLATFORM_TWITTER => 'X (Twitter) Ads',
            self::PLATFORM_LINKEDIN => 'LinkedIn Ads',
        ];
    }

    /**
     * Get available objectives
     */
    public static function getAvailableObjectives(): array
    {
        return [
            self::TYPE_AWARENESS => 'Brand Awareness',
            self::TYPE_REACH => 'Reach',
            self::TYPE_TRAFFIC => 'Traffic',
            self::TYPE_ENGAGEMENT => 'Engagement',
            self::TYPE_VIDEO_VIEWS => 'Video Views',
            self::TYPE_LEADS => 'Lead Generation',
            self::TYPE_CONVERSIONS => 'Conversions',
            self::TYPE_APP_INSTALLS => 'App Installs',
            self::TYPE_SALES => 'Sales',
        ];
    }

    /**
     * Get available bid strategies
     */
    public static function getAvailableBidStrategies(): array
    {
        return [
            self::BID_LOWEST_COST => 'Lowest Cost (Auto)',
            self::BID_COST_CAP => 'Cost Cap',
            self::BID_BID_CAP => 'Bid Cap',
            self::BID_TARGET_COST => 'Target Cost',
            self::BID_MANUAL => 'Manual Bidding',
        ];
    }

    /**
     * Check if campaign is synced to external platform
     */
    public function isSynced(): bool
    {
        return !empty($this->external_campaign_id) && $this->sync_status === 'synced';
    }

    /**
     * Check if campaign uses Advantage+ (Meta)
     */
    public function isAdvantagePlus(): bool
    {
        return $this->is_advantage_plus === true;
    }

    /**
     * Check if campaign uses Smart+ (TikTok) or Smart (Google)
     */
    public function isSmartCampaign(): bool
    {
        return $this->is_smart_campaign === true;
    }

    /**
     * Check if campaign is Performance Max (Google)
     */
    public function isPerformanceMax(): bool
    {
        return $this->is_performance_max === true;
    }

    /**
     * Get effective budget based on budget type
     */
    public function getEffectiveBudget(): ?float
    {
        if ($this->budget_type === 'daily') {
            return $this->daily_budget;
        }
        return $this->lifetime_budget;
    }

    /**
     * Get total active ad sets count
     */
    public function getActiveAdSetsCount(): int
    {
        return $this->adSets()->where('status', 'active')->count();
    }

    /**
     * Get total active ads count
     */
    public function getActiveAdsCount(): int
    {
        return $this->ads()->where('status', 'active')->count();
    }
}
