<?php

namespace App\Models\Influencer;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerContent extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_influencer.influencer_content';
    protected $primaryKey = 'content_id';

    protected $fillable = [
        'content_id',
        'influencer_campaign_id',
        'influencer_id',
        'org_id',
        'content_type',
        'platform',
        'post_url',
        'media_urls',
        'caption',
        'hashtags',
        'mentions',
        'post_date',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'revision_notes',
        'impressions',
        'reach',
        'engagement',
        'likes',
        'comments',
        'shares',
        'saves',
        'clicks',
        'conversions',
        'engagement_rate',
        'sentiment_score',
        'top_comments',
        'is_sponsored',
        'disclosure_compliant',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'hashtags' => 'array',
        'mentions' => 'array',
        'post_date' => 'datetime',
        'approved_at' => 'datetime',
        'impressions' => 'integer',
        'reach' => 'integer',
        'engagement' => 'integer',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'saves' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'engagement_rate' => 'decimal:4',
        'sentiment_score' => 'decimal:2',
        'top_comments' => 'array',
        'is_sponsored' => 'boolean',
        'disclosure_compliant' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Content type constants
    public const TYPE_POST = 'post';
    public const TYPE_STORY = 'story';
    public const TYPE_REEL = 'reel';
    public const TYPE_VIDEO = 'video';
    public const TYPE_IGTV = 'igtv';
    public const TYPE_CAROUSEL = 'carousel';
    public const TYPE_LIVE = 'live';
    public const TYPE_BLOG = 'blog';
    public const TYPE_YOUTUBE_VIDEO = 'youtube_video';
    public const TYPE_TIKTOK_VIDEO = 'tiktok_video';
    public const TYPE_TWEET = 'tweet';

    // Platform constants (same as Influencer)
    public const PLATFORM_INSTAGRAM = 'instagram';
    public const PLATFORM_TIKTOK = 'tiktok';
    public const PLATFORM_YOUTUBE = 'youtube';
    public const PLATFORM_TWITTER = 'twitter';
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_LINKEDIN = 'linkedin';
    public const PLATFORM_BLOG = 'blog';

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REVISION_REQUIRED = 'revision_required';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    // Approval status constants (same as InfluencerCampaign)
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const APPROVAL_REVISION_REQUIRED = 'revision_required';

    // Relationships
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(InfluencerCampaign::class, 'influencer_campaign_id', 'influencer_campaign_id');
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class, 'influencer_id', 'influencer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    public function scopeSponsored($query)
    {
        return $query->where('is_sponsored', true);
    }

    public function scopeCompliant($query)
    {
        return $query->where('disclosure_compliant', true);
    }

    public function scopeHighEngagement($query, float $minRate = 0.05)
    {
        return $query->where('engagement_rate', '>=', $minRate);
    }

    // Helper Methods
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isCompliant(): bool
    {
        return $this->disclosure_compliant === true;
    }

    public function submitForReview(): bool
    {
        return $this->update([
            'status' => self::STATUS_PENDING_REVIEW,
            'approval_status' => self::APPROVAL_PENDING,
        ]);
    }

    public function approve(?string $userId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function reject(?string $userId = null, ?string $notes = null): bool
    {
        $updates = [
            'status' => self::STATUS_REJECTED,
            'approval_status' => self::APPROVAL_REJECTED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ];

        if ($notes) {
            $updates['revision_notes'] = $notes;
        }

        return $this->update($updates);
    }

    public function requestRevision(?string $userId = null, ?string $notes = null): bool
    {
        $updates = [
            'status' => self::STATUS_REVISION_REQUIRED,
            'approval_status' => self::APPROVAL_REVISION_REQUIRED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ];

        if ($notes) {
            $updates['revision_notes'] = $notes;
        }

        return $this->update($updates);
    }

    public function publish(): bool
    {
        return $this->update([
            'status' => self::STATUS_PUBLISHED,
            'post_date' => now(),
        ]);
    }

    public function archive(): bool
    {
        return $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    public function updateMetrics(array $metrics): bool
    {
        $updates = [];

        if (isset($metrics['impressions'])) {
            $updates['impressions'] = $metrics['impressions'];
        }

        if (isset($metrics['reach'])) {
            $updates['reach'] = $metrics['reach'];
        }

        if (isset($metrics['engagement'])) {
            $updates['engagement'] = $metrics['engagement'];
        }

        if (isset($metrics['likes'])) {
            $updates['likes'] = $metrics['likes'];
        }

        if (isset($metrics['comments'])) {
            $updates['comments'] = $metrics['comments'];
        }

        if (isset($metrics['shares'])) {
            $updates['shares'] = $metrics['shares'];
        }

        if (isset($metrics['saves'])) {
            $updates['saves'] = $metrics['saves'];
        }

        if (isset($metrics['clicks'])) {
            $updates['clicks'] = $metrics['clicks'];
        }

        if (isset($metrics['conversions'])) {
            $updates['conversions'] = $metrics['conversions'];
        }

        // Calculate engagement rate
        if ($this->impressions > 0) {
            $totalEngagement = ($this->engagement ?? 0) + ($this->likes ?? 0) + ($this->comments ?? 0) + ($this->shares ?? 0) + ($this->saves ?? 0);
            $updates['engagement_rate'] = $totalEngagement / $this->impressions;
        }

        return $this->update($updates);
    }

    public function calculateEngagementRate(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        $totalEngagement = ($this->engagement ?? 0) + ($this->likes ?? 0) + ($this->comments ?? 0) + ($this->shares ?? 0) + ($this->saves ?? 0);
        return round(($totalEngagement / $this->impressions) * 100, 2);
    }

    public function getClickThroughRate(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    public function getConversionRate(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return round(($this->conversions / $this->clicks) * 100, 2);
    }

    public function getTotalEngagement(): int
    {
        return ($this->engagement ?? 0) + ($this->likes ?? 0) + ($this->comments ?? 0) + ($this->shares ?? 0) + ($this->saves ?? 0);
    }

    public function hasHighEngagement(float $threshold = 0.05): bool
    {
        return $this->engagement_rate >= $threshold;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING_REVIEW => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REVISION_REQUIRED => 'orange',
            self::STATUS_SCHEDULED => 'blue',
            self::STATUS_PUBLISHED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_ARCHIVED => 'gray',
            default => 'gray',
        };
    }

    public function getApprovalColor(): string
    {
        return match($this->approval_status) {
            self::APPROVAL_PENDING => 'yellow',
            self::APPROVAL_APPROVED => 'green',
            self::APPROVAL_REJECTED => 'red',
            self::APPROVAL_REVISION_REQUIRED => 'orange',
            default => 'gray',
        };
    }

    public function getPlatformIcon(): string
    {
        return match($this->platform) {
            self::PLATFORM_INSTAGRAM => 'instagram',
            self::PLATFORM_TIKTOK => 'tiktok',
            self::PLATFORM_YOUTUBE => 'youtube',
            self::PLATFORM_TWITTER => 'twitter',
            self::PLATFORM_FACEBOOK => 'facebook',
            self::PLATFORM_LINKEDIN => 'linkedin',
            self::PLATFORM_BLOG => 'rss',
            default => 'globe',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_POST => 'Post',
            self::TYPE_STORY => 'Story',
            self::TYPE_REEL => 'Reel',
            self::TYPE_VIDEO => 'Video',
            self::TYPE_IGTV => 'IGTV',
            self::TYPE_CAROUSEL => 'Carousel',
            self::TYPE_LIVE => 'Live',
            self::TYPE_BLOG => 'Blog Post',
            self::TYPE_YOUTUBE_VIDEO => 'YouTube Video',
            self::TYPE_TIKTOK_VIDEO => 'TikTok Video',
            self::TYPE_TWEET => 'Tweet',
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
            self::PLATFORM_BLOG => 'Blog',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REVISION_REQUIRED => 'Revision Required',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public static function getApprovalStatusOptions(): array
    {
        return [
            self::APPROVAL_PENDING => 'Pending',
            self::APPROVAL_APPROVED => 'Approved',
            self::APPROVAL_REJECTED => 'Rejected',
            self::APPROVAL_REVISION_REQUIRED => 'Revision Required',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'influencer_campaign_id' => 'required|uuid|exists:cmis_influencer.influencer_campaigns,influencer_campaign_id',
            'influencer_id' => 'required|uuid|exists:cmis_influencer.influencers,influencer_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'content_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'platform' => 'required|in:' . implode(',', array_keys(self::getPlatformOptions())),
            'post_url' => 'nullable|url',
            'media_urls' => 'nullable|array',
            'media_urls.*' => 'url',
            'caption' => 'nullable|string',
            'hashtags' => 'nullable|array',
            'mentions' => 'nullable|array',
            'post_date' => 'nullable|date',
            'status' => 'nullable|in:' . implode(',', array_keys(self::getStatusOptions())),
            'is_sponsored' => 'nullable|boolean',
            'disclosure_compliant' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'caption' => 'sometimes|string',
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'approval_status' => 'sometimes|in:' . implode(',', array_keys(self::getApprovalStatusOptions())),
            'impressions' => 'sometimes|integer|min:0',
            'engagement' => 'sometimes|integer|min:0',
            'disclosure_compliant' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'influencer_campaign_id.required' => 'Campaign is required',
            'influencer_id.required' => 'Influencer is required',
            'org_id.required' => 'Organization is required',
            'content_type.required' => 'Content type is required',
            'platform.required' => 'Platform is required',
            'post_url.url' => 'Post URL must be a valid URL',
            'media_urls.*.url' => 'All media URLs must be valid URLs',
        ];
    }
}
