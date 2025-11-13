<?php

namespace App\Models\Other;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataFeed extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.data_feeds';

    protected $primaryKey = 'feed_id';

    public $incrementing = false;

    protected $keyType = 'string';

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
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the feed items
     */
    public function feedItems()
    {
        return $this->hasMany(FeedItem::class, 'feed_id', 'feed_id');
    }

    /**
     * Scope to filter by kind
     */
    public function scopeOfKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    /**
     * Scope to get feeds for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }
}
