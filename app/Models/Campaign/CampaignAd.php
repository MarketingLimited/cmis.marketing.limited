<?php

namespace App\Models\Campaign;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignAd extends BaseModel
{
    use SoftDeletes, HasOrganization;

    protected $table = 'cmis.campaign_ads';
    protected $primaryKey = 'ad_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ad_id',
        'org_id',
        'campaign_id',
        'ad_set_id',
        'name',
        'description',
        'status',
        'ad_format',
        'primary_text',
        'headline',
        'description_text',
        'call_to_action',
        'media',
        'image_url',
        'video_url',
        'thumbnail_url',
        'carousel_cards',
        'destination_url',
        'display_url',
        'url_parameters',
        'tracking_pixel_id',
        'tracking_specs',
        'is_dynamic_creative',
        'dynamic_creative_assets',
        'external_ad_id',
        'external_creative_id',
        'last_synced_at',
        'sync_status',
        'review_status',
        'review_feedback',
        'platform_settings',
        'preview_urls',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'ad_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'ad_set_id' => 'string',
        'media' => 'array',
        'carousel_cards' => 'array',
        'url_parameters' => 'array',
        'tracking_specs' => 'array',
        'is_dynamic_creative' => 'boolean',
        'dynamic_creative_assets' => 'array',
        'platform_settings' => 'array',
        'preview_urls' => 'array',
        'last_synced_at' => 'datetime',
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
     * Ad format constants
     */
    public const FORMAT_IMAGE = 'image';
    public const FORMAT_VIDEO = 'video';
    public const FORMAT_CAROUSEL = 'carousel';
    public const FORMAT_COLLECTION = 'collection';
    public const FORMAT_STORIES = 'stories';
    public const FORMAT_REELS = 'reels';
    public const FORMAT_INSTANT_EXPERIENCE = 'instant_experience';

    /**
     * Review status constants
     */
    public const REVIEW_PENDING = 'pending';
    public const REVIEW_APPROVED = 'approved';
    public const REVIEW_REJECTED = 'rejected';
    public const REVIEW_IN_REVIEW = 'in_review';

    /**
     * Call to action constants
     */
    public const CTA_LEARN_MORE = 'LEARN_MORE';
    public const CTA_SHOP_NOW = 'SHOP_NOW';
    public const CTA_SIGN_UP = 'SIGN_UP';
    public const CTA_DOWNLOAD = 'DOWNLOAD';
    public const CTA_CONTACT_US = 'CONTACT_US';
    public const CTA_BOOK_NOW = 'BOOK_NOW';
    public const CTA_GET_QUOTE = 'GET_QUOTE';
    public const CTA_APPLY_NOW = 'APPLY_NOW';
    public const CTA_SUBSCRIBE = 'SUBSCRIBE';
    public const CTA_WATCH_MORE = 'WATCH_MORE';
    public const CTA_GET_OFFER = 'GET_OFFER';

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the ad set
     */
    public function adSet()
    {
        return $this->belongsTo(CampaignAdSet::class, 'ad_set_id', 'ad_set_id');
    }

    /**
     * Get the creator user
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\Core\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope active ads
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
     * Scope by format
     */
    public function scopeByFormat($query, string $format)
    {
        return $query->where('ad_format', $format);
    }

    /**
     * Scope approved ads
     */
    public function scopeApproved($query)
    {
        return $query->where('review_status', self::REVIEW_APPROVED);
    }

    /**
     * Scope pending review ads
     */
    public function scopePendingReview($query)
    {
        return $query->where('review_status', self::REVIEW_PENDING);
    }

    /**
     * Scope synced ads
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Check if ad is synced to external platform
     */
    public function isSynced(): bool
    {
        return !empty($this->external_ad_id) && $this->sync_status === 'synced';
    }

    /**
     * Check if ad is approved
     */
    public function isApproved(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED;
    }

    /**
     * Check if ad is rejected
     */
    public function isRejected(): bool
    {
        return $this->review_status === self::REVIEW_REJECTED;
    }

    /**
     * Get the primary media URL
     */
    public function getPrimaryMediaUrl(): ?string
    {
        if ($this->image_url) {
            return $this->image_url;
        }
        if ($this->video_url) {
            return $this->video_url;
        }
        if (!empty($this->media) && isset($this->media[0]['url'])) {
            return $this->media[0]['url'];
        }
        return null;
    }

    /**
     * Get UTM parameters as query string
     */
    public function getUtmQueryString(): string
    {
        if (empty($this->url_parameters)) {
            return '';
        }

        return http_build_query($this->url_parameters);
    }

    /**
     * Get destination URL with UTM parameters
     */
    public function getFullDestinationUrl(): ?string
    {
        if (empty($this->destination_url)) {
            return null;
        }

        $utmString = $this->getUtmQueryString();
        if (empty($utmString)) {
            return $this->destination_url;
        }

        $separator = str_contains($this->destination_url, '?') ? '&' : '?';
        return $this->destination_url . $separator . $utmString;
    }

    /**
     * Check if ad is carousel format
     */
    public function isCarousel(): bool
    {
        return $this->ad_format === self::FORMAT_CAROUSEL;
    }

    /**
     * Check if ad is video format
     */
    public function isVideo(): bool
    {
        return $this->ad_format === self::FORMAT_VIDEO;
    }

    /**
     * Check if ad uses dynamic creative
     */
    public function isDynamicCreative(): bool
    {
        return $this->is_dynamic_creative && !empty($this->dynamic_creative_assets);
    }

    /**
     * Get available call to action options
     */
    public static function getCallToActionOptions(): array
    {
        return [
            self::CTA_LEARN_MORE => 'Learn More',
            self::CTA_SHOP_NOW => 'Shop Now',
            self::CTA_SIGN_UP => 'Sign Up',
            self::CTA_DOWNLOAD => 'Download',
            self::CTA_CONTACT_US => 'Contact Us',
            self::CTA_BOOK_NOW => 'Book Now',
            self::CTA_GET_QUOTE => 'Get Quote',
            self::CTA_APPLY_NOW => 'Apply Now',
            self::CTA_SUBSCRIBE => 'Subscribe',
            self::CTA_WATCH_MORE => 'Watch More',
            self::CTA_GET_OFFER => 'Get Offer',
        ];
    }

    /**
     * Get available ad format options
     */
    public static function getFormatOptions(): array
    {
        return [
            self::FORMAT_IMAGE => 'Single Image',
            self::FORMAT_VIDEO => 'Video',
            self::FORMAT_CAROUSEL => 'Carousel',
            self::FORMAT_COLLECTION => 'Collection',
            self::FORMAT_STORIES => 'Stories',
            self::FORMAT_REELS => 'Reels',
            self::FORMAT_INSTANT_EXPERIENCE => 'Instant Experience',
        ];
    }
}
