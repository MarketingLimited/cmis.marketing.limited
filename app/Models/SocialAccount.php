<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.social_accounts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'account_external_id',
        'username',
        'display_name',
        'profile_picture_url',
        'biography',
        'followers_count',
        'follows_count',
        'media_count',
        'website',
        'category',
        'fetched_at',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'followers_count' => 'integer',
        'follows_count' => 'integer',
        'media_count' => 'integer',
        'fetched_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this social account.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the integration that this account belongs to.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get all posts for this social account.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'integration_id', 'integration_id');
    }

    /**
     * Get all metrics for this social account.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(SocialAccountMetric::class, 'account_id', 'id');
    }
}
