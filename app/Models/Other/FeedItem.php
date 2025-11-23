<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class FeedItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.feed_items';

    protected $primaryKey = 'item_id';

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'feed_id',
        'sku',
        'payload',
        'valid_from',
        'valid_to',
        'provider',
    ];

    protected $casts = [
        'item_id' => 'string',
        'feed_id' => 'string',
        'payload' => 'array',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the data feed
     */
    public function dataFeed(): BelongsTo
    {
        return $this->belongsTo(DataFeed::class, 'feed_id', 'feed_id');

    }
    /**
     * Scope to get items for a specific feed
     */
    public function scopeForFeed($query, string $feedId): Builder
    {
        return $query->where('feed_id', $feedId);

    }
    /**
     * Scope to get valid items
     */
    public function scopeValid($query): Builder
    {
        return $query->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
}
}
}
