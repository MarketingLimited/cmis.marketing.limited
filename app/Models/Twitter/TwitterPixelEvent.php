<?php

namespace App\Models\Twitter;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Twitter Pixel Event Model
 *
 * Represents a conversion event tracked by a Twitter Pixel
 *
 * @property string $id
 * @property string $org_id
 * @property string $pixel_id
 * @property string $event_type
 * @property array $event_data
 * @property string $user_identifier
 * @property \Carbon\Carbon $event_timestamp
 */
class TwitterPixelEvent extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis_twitter.pixel_events';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'org_id',
        'pixel_id',
        'event_type',
        'event_data',
        'user_identifier',
        'event_timestamp',
    ];

    protected $casts = [
        'event_data' => 'array',
        'event_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Event types
     */
    public const EVENT_PURCHASE = 'PURCHASE';
    public const EVENT_SIGNUP = 'SIGNUP';
    public const EVENT_ADD_TO_CART = 'ADD_TO_CART';
    public const EVENT_PAGE_VIEW = 'PAGE_VIEW';
    public const EVENT_LEAD = 'LEAD';
    public const EVENT_SEARCH = 'SEARCH';

    /**
     * Get the pixel this event belongs to
     */
    public function pixel(): BelongsTo
    {
        return $this->belongsTo(TwitterPixel::class, 'pixel_id', 'id');
    }

    /**
     * Scope to get events by type
     */
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope to get recent events
     */
    public function scopeRecent($query, int $days = 7): Builder
    {
        return $query->where('event_timestamp', '>=', now()->subDays($days));
    }
}
