<?php

namespace App\Models\Influencer;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Influencer extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_influencer.influencers';
    protected $primaryKey = 'influencer_id';

    protected $fillable = [
        'influencer_id',
        'org_id',
        'name',
        'email',
        'phone',
        'bio',
        'profile_image_url',
        'category',
        'tier',
        'status',
        'primary_platform',
        'platform_handles',
        'follower_count',
        'engagement_rate',
        'average_reach',
        'location',
        'language',
        'age_range',
        'gender',
        'audience_demographics',
        'interests',
        'brand_affinity',
        'previous_collaborations',
        'rate_card',
        'preferred_content_types',
        'availability_status',
        'rating',
        'total_campaigns',
        'successful_campaigns',
        'last_collaboration_date',
        'contract_status',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'platform_handles' => 'array',
        'follower_count' => 'integer',
        'engagement_rate' => 'decimal:4',
        'average_reach' => 'integer',
        'audience_demographics' => 'array',
        'interests' => 'array',
        'brand_affinity' => 'array',
        'previous_collaborations' => 'array',
        'rate_card' => 'array',
        'preferred_content_types' => 'array',
        'rating' => 'decimal:2',
        'total_campaigns' => 'integer',
        'successful_campaigns' => 'integer',
        'last_collaboration_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Category constants
    public const CATEGORY_FASHION = 'fashion';
    public const CATEGORY_BEAUTY = 'beauty';
    public const CATEGORY_FITNESS = 'fitness';
    public const CATEGORY_FOOD = 'food';
    public const CATEGORY_TRAVEL = 'travel';
    public const CATEGORY_TECH = 'tech';
    public const CATEGORY_LIFESTYLE = 'lifestyle';
    public const CATEGORY_GAMING = 'gaming';
    public const CATEGORY_BUSINESS = 'business';
    public const CATEGORY_ENTERTAINMENT = 'entertainment';
    public const CATEGORY_OTHER = 'other';

    // Tier constants
    public const TIER_NANO = 'nano';           // 1K-10K followers
    public const TIER_MICRO = 'micro';         // 10K-100K followers
    public const TIER_MID = 'mid';             // 100K-500K followers
    public const TIER_MACRO = 'macro';         // 500K-1M followers
    public const TIER_MEGA = 'mega';           // 1M+ followers

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_BLACKLISTED = 'blacklisted';
    public const STATUS_ARCHIVED = 'archived';

    // Platform constants
    public const PLATFORM_INSTAGRAM = 'instagram';
    public const PLATFORM_TIKTOK = 'tiktok';
    public const PLATFORM_YOUTUBE = 'youtube';
    public const PLATFORM_TWITTER = 'twitter';
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_LINKEDIN = 'linkedin';
    public const PLATFORM_SNAPCHAT = 'snapchat';

    // Availability constants
    public const AVAILABILITY_AVAILABLE = 'available';
    public const AVAILABILITY_BUSY = 'busy';
    public const AVAILABILITY_ON_HOLD = 'on_hold';
    public const AVAILABILITY_EXCLUSIVE = 'exclusive';

    // Contract status constants
    public const CONTRACT_NONE = 'none';
    public const CONTRACT_PENDING = 'pending';
    public const CONTRACT_ACTIVE = 'active';
    public const CONTRACT_EXPIRED = 'expired';

    // Relationships
    public function campaigns(): HasMany
    {
        return $this->hasMany(InfluencerCampaign::class, 'influencer_id', 'influencer_id');
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(InfluencerPartnership::class, 'influencer_id', 'influencer_id');
    }

    public function content(): HasMany
    {
        return $this->hasMany(InfluencerContent::class, 'influencer_id', 'influencer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InfluencerPayment::class, 'influencer_id', 'influencer_id');
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(InfluencerPerformance::class, 'influencer_id', 'influencer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('primary_platform', $platform);
    }

    public function scopeAvailable($query)
    {
        return $query->where('availability_status', self::AVAILABILITY_AVAILABLE);
    }

    public function scopeHighEngagement($query, float $minRate = 0.05)
    {
        return $query->where('engagement_rate', '>=', $minRate);
    }

    public function scopeHighRated($query, float $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeByFollowerRange($query, int $min, ?int $max = null)
    {
        $query->where('follower_count', '>=', $min);

        if ($max !== null) {
            $query->where('follower_count', '<=', $max);
        }

        return $query;
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isAvailable(): bool
    {
        return $this->availability_status === self::AVAILABILITY_AVAILABLE;
    }

    public function hasActiveContract(): bool
    {
        return $this->contract_status === self::CONTRACT_ACTIVE;
    }

    public function activate(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function deactivate(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    public function blacklist(): bool
    {
        return $this->update(['status' => self::STATUS_BLACKLISTED]);
    }

    public function markAvailable(): bool
    {
        return $this->update(['availability_status' => self::AVAILABILITY_AVAILABLE]);
    }

    public function markBusy(): bool
    {
        return $this->update(['availability_status' => self::AVAILABILITY_BUSY]);
    }

    public function updateRating(float $rating): bool
    {
        return $this->update(['rating' => max(1, min(5, $rating))]);
    }

    public function incrementCampaigns(bool $successful = false): bool
    {
        $updates = ['total_campaigns' => $this->total_campaigns + 1];

        if ($successful) {
            $updates['successful_campaigns'] = $this->successful_campaigns + 1;
        }

        return $this->update($updates);
    }

    public function updateFollowerCount(int $count): bool
    {
        $tier = $this->calculateTier($count);

        return $this->update([
            'follower_count' => $count,
            'tier' => $tier,
        ]);
    }

    public function updateEngagementRate(float $rate): bool
    {
        return $this->update(['engagement_rate' => max(0, min(1, $rate))]);
    }

    protected function calculateTier(int $followerCount): string
    {
        return match(true) {
            $followerCount >= 1000000 => self::TIER_MEGA,
            $followerCount >= 500000 => self::TIER_MACRO,
            $followerCount >= 100000 => self::TIER_MID,
            $followerCount >= 10000 => self::TIER_MICRO,
            default => self::TIER_NANO,
        };
    }

    public function getSuccessRate(): float
    {
        if ($this->total_campaigns === 0) {
            return 0;
        }

        return round(($this->successful_campaigns / $this->total_campaigns) * 100, 2);
    }

    public function getEngagementPercentage(): float
    {
        return $this->engagement_rate * 100;
    }

    public function getTierColor(): string
    {
        return match($this->tier) {
            self::TIER_NANO => 'gray',
            self::TIER_MICRO => 'blue',
            self::TIER_MID => 'green',
            self::TIER_MACRO => 'purple',
            self::TIER_MEGA => 'orange',
            default => 'gray',
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_PENDING_VERIFICATION => 'yellow',
            self::STATUS_BLACKLISTED => 'red',
            self::STATUS_ARCHIVED => 'orange',
            default => 'gray',
        };
    }

    public function getAvailabilityColor(): string
    {
        return match($this->availability_status) {
            self::AVAILABILITY_AVAILABLE => 'green',
            self::AVAILABILITY_BUSY => 'red',
            self::AVAILABILITY_ON_HOLD => 'yellow',
            self::AVAILABILITY_EXCLUSIVE => 'purple',
            default => 'gray',
        };
    }

    // Static Methods
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_FASHION => 'Fashion',
            self::CATEGORY_BEAUTY => 'Beauty',
            self::CATEGORY_FITNESS => 'Fitness',
            self::CATEGORY_FOOD => 'Food',
            self::CATEGORY_TRAVEL => 'Travel',
            self::CATEGORY_TECH => 'Tech',
            self::CATEGORY_LIFESTYLE => 'Lifestyle',
            self::CATEGORY_GAMING => 'Gaming',
            self::CATEGORY_BUSINESS => 'Business',
            self::CATEGORY_ENTERTAINMENT => 'Entertainment',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    public static function getTierOptions(): array
    {
        return [
            self::TIER_NANO => 'Nano (1K-10K)',
            self::TIER_MICRO => 'Micro (10K-100K)',
            self::TIER_MID => 'Mid (100K-500K)',
            self::TIER_MACRO => 'Macro (500K-1M)',
            self::TIER_MEGA => 'Mega (1M+)',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_PENDING_VERIFICATION => 'Pending Verification',
            self::STATUS_BLACKLISTED => 'Blacklisted',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public static function getPlatformOptions(): array
    {
        return [
            self::PLATFORM_INSTAGRAM => 'Instagram',
            self::PLATFORM_TIKTOK => 'TikTok',
            self::PLATFORM_YOUTUBE => 'YouTube',
            self::PLATFORM_TWITTER => 'Twitter',
            self::PLATFORM_FACEBOOK => 'Facebook',
            self::PLATFORM_LINKEDIN => 'LinkedIn',
            self::PLATFORM_SNAPCHAT => 'Snapchat',
        ];
    }

    public static function getAvailabilityOptions(): array
    {
        return [
            self::AVAILABILITY_AVAILABLE => 'Available',
            self::AVAILABILITY_BUSY => 'Busy',
            self::AVAILABILITY_ON_HOLD => 'On Hold',
            self::AVAILABILITY_EXCLUSIVE => 'Exclusive',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'bio' => 'nullable|string',
            'profile_image_url' => 'nullable|url',
            'category' => 'required|in:' . implode(',', array_keys(self::getCategoryOptions())),
            'tier' => 'required|in:' . implode(',', array_keys(self::getTierOptions())),
            'status' => 'nullable|in:' . implode(',', array_keys(self::getStatusOptions())),
            'primary_platform' => 'required|in:' . implode(',', array_keys(self::getPlatformOptions())),
            'platform_handles' => 'nullable|array',
            'follower_count' => 'required|integer|min:0',
            'engagement_rate' => 'nullable|numeric|min:0|max:1',
            'average_reach' => 'nullable|integer|min:0',
            'location' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:50',
            'age_range' => 'nullable|string|max:50',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'audience_demographics' => 'nullable|array',
            'interests' => 'nullable|array',
            'brand_affinity' => 'nullable|array',
            'rate_card' => 'nullable|array',
            'preferred_content_types' => 'nullable|array',
            'availability_status' => 'nullable|in:' . implode(',', array_keys(self::getAvailabilityOptions())),
            'rating' => 'nullable|numeric|min:1|max:5',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'bio' => 'sometimes|string',
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'follower_count' => 'sometimes|integer|min:0',
            'engagement_rate' => 'sometimes|numeric|min:0|max:1',
            'availability_status' => 'sometimes|in:' . implode(',', array_keys(self::getAvailabilityOptions())),
            'rating' => 'sometimes|numeric|min:1|max:5',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization is required',
            'name.required' => 'Influencer name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'category.required' => 'Category is required',
            'tier.required' => 'Tier is required',
            'primary_platform.required' => 'Primary platform is required',
            'follower_count.required' => 'Follower count is required',
            'follower_count.min' => 'Follower count must be at least 0',
            'engagement_rate.min' => 'Engagement rate must be between 0 and 1',
            'engagement_rate.max' => 'Engagement rate must be between 0 and 1',
            'rating.min' => 'Rating must be between 1 and 5',
            'rating.max' => 'Rating must be between 1 and 5',
        ];
    }
}
