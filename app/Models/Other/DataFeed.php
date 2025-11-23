<?php

namespace App\Models\Other;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class DataFeed extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.data_feeds';

    protected $primaryKey = 'feed_id';

    public $timestamps = false;

    protected $fillable = [
        'feed_id',
        'org_id',
        'kind',
        'source_meta',
        'last_ingested',
        'provider',
    ];

    protected $casts = [
        'feed_id' => 'string',
        'org_id' => 'string',
        'source_meta' => 'array',
        'last_ingested' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the feed items
     */
    public function feedItems(): HasMany
    {
        return $this->hasMany(FeedItem::class, 'feed_id', 'feed_id');

    }
    /**
     * Scope to filter by kind
     */
    public function scopeOfKind($query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }
}
