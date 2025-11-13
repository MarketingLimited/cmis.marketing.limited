<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.feed_items';

    protected $primaryKey = 'item_id';

    public $incrementing = false;

    protected $keyType = 'string';

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
    public function dataFeed()
    {
        return $this->belongsTo(DataFeed::class, 'feed_id', 'feed_id');
    }

    /**
     * Scope to get items for a specific feed
     */
    public function scopeForFeed($query, string $feedId)
    {
        return $query->where('feed_id', $feedId);
    }

    /**
     * Scope to get valid items
     */
    public function scopeValid($query)
    {
        return $query->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            });
    }
}
