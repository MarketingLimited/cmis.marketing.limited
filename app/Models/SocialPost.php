<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class SocialPost extends Model
{
    use SoftDeletes, HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.social_posts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'post_external_id',
        'caption',
        'media_url',
        'permalink',
        'media_type',
        'posted_at',
        'metrics',
        'fetched_at',
        'video_url',
        'thumbnail_url',
        'children_media',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'metrics' => 'array',
        'children_media' => 'array',
        'posted_at' => 'datetime',
        'fetched_at' => 'datetime',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this post.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the integration (social account) that this post belongs to.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get the social account for this post.
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'integration_id', 'integration_id');
    }

    /**
     * Get all metrics for this post.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(SocialPostMetric::class, 'post_id', 'id');
    }
}
