<?php

namespace App\Models\Twitter;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Twitter Audience Model
 *
 * Represents a Twitter/X Tailored Audience (custom audience)
 *
 * @property string $id
 * @property string $org_id
 * @property string $platform_audience_id
 * @property string $platform_account_id
 * @property string $name
 * @property string $audience_type
 * @property string $list_type
 * @property int $size_estimate
 * @property int $targetable_size
 * @property string $status
 * @property string $source_audience_id
 * @property string $source_country
 * @property array $config_metadata
 * @property array $platform_metadata
 */
class TwitterAudience extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis_twitter.audiences';

    protected $primaryKey = 'id';

    protected $fillable = [
        'org_id',
        'platform_audience_id',
        'platform_account_id',
        'name',
        'audience_type',
        'list_type',
        'size_estimate',
        'targetable_size',
        'status',
        'source_audience_id',
        'source_country',
        'config_metadata',
        'platform_metadata',
    ];

    protected $casts = [
        'size_estimate' => 'integer',
        'targetable_size' => 'integer',
        'config_metadata' => 'array',
        'platform_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Audience types
     */
    public const TYPE_TAILORED = 'TAILORED';
    public const TYPE_LOOKALIKE = 'LOOKALIKE';
    public const TYPE_FOLLOWER_LOOKALIKE = 'FOLLOWER_LOOKALIKE';

    /**
     * List types (for tailored audiences)
     */
    public const LIST_TYPE_EMAIL = 'EMAIL';
    public const LIST_TYPE_TWITTER_ID = 'TWITTER_ID';
    public const LIST_TYPE_MOBILE_AD_ID = 'MOBILE_ADVERTISING_ID';
    public const LIST_TYPE_WEB = 'WEB';

    /**
     * Audience statuses
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_BUILDING = 'BUILDING';
    public const STATUS_READY = 'READY';
    public const STATUS_TOO_SMALL = 'TOO_SMALL';
    public const STATUS_DELETED = 'DELETED';

    /**
     * Get the source audience (for lookalike audiences)
     */
    public function sourceAudience(): BelongsTo
    {
        return $this->belongsTo(TwitterAudience::class, 'source_audience_id', 'id');
    }

    /**
     * Check if audience is ready for targeting
     */
    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    /**
     * Check if audience is a lookalike
     */
    public function isLookalike(): bool
    {
        return in_array($this->audience_type, [
            self::TYPE_LOOKALIKE,
            self::TYPE_FOLLOWER_LOOKALIKE,
        ]);
    }

    /**
     * Scope to get ready audiences
     */
    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }

    /**
     * Scope to get audiences by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('audience_type', $type);
    }
}
