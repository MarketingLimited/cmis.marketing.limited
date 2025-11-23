<?php

namespace App\Models\Twitter;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Twitter Pixel Model
 *
 * Represents a Twitter/X conversion tracking pixel
 *
 * @property string $id
 * @property string $org_id
 * @property string $platform_pixel_id
 * @property string $platform_account_id
 * @property string $name
 * @property string $pixel_code
 * @property string $category
 * @property string $status
 * @property bool $is_verified
 * @property \Carbon\Carbon $verified_at
 * @property array $config_metadata
 */
class TwitterPixel extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis_twitter.pixels';

    protected $primaryKey = 'id';

    protected $fillable = [
        'org_id',
        'platform_pixel_id',
        'platform_account_id',
        'name',
        'pixel_code',
        'category',
        'status',
        'is_verified',
        'verified_at',
        'config_metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'config_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Pixel categories (event types)
     */
    public const CATEGORY_PURCHASE = 'PURCHASE';
    public const CATEGORY_SIGNUP = 'SIGNUP';
    public const CATEGORY_ADD_TO_CART = 'ADD_TO_CART';
    public const CATEGORY_PAGE_VIEW = 'PAGE_VIEW';
    public const CATEGORY_LEAD = 'LEAD';

    /**
     * Pixel statuses
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Get all events tracked by this pixel
     */
    public function events(): HasMany
    {
        return $this->hasMany(TwitterPixelEvent::class, 'pixel_id', 'id');
    }

    /**
     * Check if pixel is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    /**
     * Mark pixel as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Scope to get verified pixels
     */
    public function scopeVerified($query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get active pixels
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
